<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_parent':
                $parent_id = (int)$_POST['parent_id'];
                
                // Start transaction
                $conn->begin_transaction();
                try {
                    // Get user_id first
                    $user_query = "SELECT user_id FROM parents WHERE id = ?";
                    $stmt = $conn->prepare($user_query);
                    $stmt->bind_param("i", $parent_id);
                    $stmt->execute();
                    $user_result = $stmt->get_result();
                    $user = $user_result->fetch_assoc();
                    
                    if ($user) {
                        // Delete from student_parent table first (foreign key relationships)
                        $delete_links = "DELETE FROM student_parent WHERE parent_id = ?";
                        $stmt = $conn->prepare($delete_links);
                        $stmt->bind_param("i", $parent_id);
                        $stmt->execute();
                        
                        // Delete from parents table
                        $delete_parent = "DELETE FROM parents WHERE id = ?";
                        $stmt = $conn->prepare($delete_parent);
                        $stmt->bind_param("i", $parent_id);
                        $stmt->execute();
                        
                        // Delete from users table
                        $delete_user = "DELETE FROM users WHERE id = ?";
                        $stmt = $conn->prepare($delete_user);
                        $stmt->bind_param("i", $user['user_id']);
                        $stmt->execute();
                        
                        $conn->commit();
                        $success = "Parent deleted successfully!";
                    } else {
                        throw new Exception("Parent not found");
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error deleting parent: " . $e->getMessage();
                }
                break;

            case 'add_parent':
                // Create user account first
                $username = $conn->real_escape_string($_POST['username']);
                $email = $conn->real_escape_string($_POST['email']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $first_name = $conn->real_escape_string($_POST['first_name']);
                $last_name = $conn->real_escape_string($_POST['last_name']);
                $phone = $conn->real_escape_string($_POST['phone']);
                $address = $conn->real_escape_string($_POST['address']);

                // Start transaction
                $conn->begin_transaction();
                try {
                    // Insert user
                    $user_sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'parent')";
                    $stmt = $conn->prepare($user_sql);
                    $stmt->bind_param("sss", $username, $email, $password);
                    $stmt->execute();
                    $user_id = $conn->insert_id;

                    // Insert parent
                    $parent_sql = "INSERT INTO parents (user_id, first_name, last_name, phone, address) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($parent_sql);
                    $stmt->bind_param("issss", $user_id, $first_name, $last_name, $phone, $address);
                    $stmt->execute();

                    $conn->commit();
                    $success = "Parent added successfully!";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error adding parent: " . $e->getMessage();
                }
                break;

            case 'link_student':
                $parent_id = (int)$_POST['parent_id'];
                $student_id = (int)$_POST['student_id'];

                // Check if link already exists
                $check_sql = "SELECT * FROM student_parent WHERE student_id = ? AND parent_id = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("ii", $student_id, $parent_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows == 0) {
                    // Create new link
                    $link_sql = "INSERT INTO student_parent (student_id, parent_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($link_sql);
                    $stmt->bind_param("ii", $student_id, $parent_id);
                    if ($stmt->execute()) {
                        $success = "Student linked to parent successfully!";
                    } else {
                        $error = "Error linking student to parent";
                    }
                } else {
                    $error = "This student is already linked to this parent";
                }
                break;

            case 'unlink_student':
                $parent_id = (int)$_POST['parent_id'];
                $student_id = (int)$_POST['student_id'];
                
                $unlink_sql = "DELETE FROM student_parent WHERE student_id = ? AND parent_id = ?";
                $stmt = $conn->prepare($unlink_sql);
                $stmt->bind_param("ii", $student_id, $parent_id);
                if ($stmt->execute()) {
                    $success = "Student unlinked from parent successfully!";
                } else {
                    $error = "Error unlinking student from parent";
                }
                break;
        }
    }
}

// Get all parents with their linked students
$parents_query = "
    SELECT p.*, u.email, u.username,
           GROUP_CONCAT(
               CONCAT(s.first_name, ' ', s.last_name, ' (Grade ', s.grade_level, ')')
               SEPARATOR ', '
           ) as linked_students
    FROM parents p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN student_parent sp ON p.id = sp.parent_id
    LEFT JOIN students s ON sp.student_id = s.id
    GROUP BY p.id
    ORDER BY p.last_name, p.first_name
";
$parents = $conn->query($parents_query);

// Get all students for linking
$students_query = "
    SELECT s.id, s.first_name, s.last_name, s.grade_level,
           GROUP_CONCAT(
               CONCAT(p.first_name, ' ', p.last_name)
               SEPARATOR ', '
           ) as current_parents
    FROM students s
    LEFT JOIN student_parent sp ON s.id = sp.student_id
    LEFT JOIN parents p ON sp.parent_id = p.id
    GROUP BY s.id
    ORDER BY s.grade_level, s.last_name, s.first_name
";
$students = $conn->query($students_query);
$students_array = [];
while ($student = $students->fetch_assoc()) {
    $students_array[] = $student;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parents - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">School Management</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="parents.php">Parents</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="courses.php">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="grades.php">Grades</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php">Attendance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="announcements.php">Announcements</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">Messages</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Manage Parents</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add New Parent Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Add New Parent</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_parent">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Parent</button>
                </form>
            </div>
        </div>

        <!-- Parents List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Parents List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact Info</th>
                                <th>Linked Students</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($parent = $parents->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?>
                                        <br>
                                        <small class="text-muted">Username: <?php echo htmlspecialchars($parent['username']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($parent['email']); ?>
                                        <?php if ($parent['phone']): ?>
                                            <br>
                                            <?php echo htmlspecialchars($parent['phone']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($parent['linked_students']): ?>
                                            <?php echo htmlspecialchars($parent['linked_students']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">No students linked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#linkStudentModal<?php echo $parent['id']; ?>">
                                            Link Student
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteParentModal<?php echo $parent['id']; ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>

                                <!-- Delete Parent Modal -->
                                <div class="modal fade" id="deleteParentModal<?php echo $parent['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Delete Parent</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete <?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?>?</p>
                                                <p class="text-danger">
                                                    <i class="bi bi-exclamation-triangle"></i> 
                                                    This action cannot be undone. This will:
                                                </p>
                                                <ul class="text-danger">
                                                    <li>Delete the parent's user account</li>
                                                    <li>Remove all student-parent relationships</li>
                                                    <li>Delete all associated messages</li>
                                                </ul>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_parent">
                                                    <input type="hidden" name="parent_id" value="<?php echo $parent['id']; ?>">
                                                    <button type="submit" class="btn btn-danger">Delete Parent</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Link Student Modal -->
                                <div class="modal fade" id="linkStudentModal<?php echo $parent['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Link Student to <?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="action" value="link_student">
                                                    <input type="hidden" name="parent_id" value="<?php echo $parent['id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="student_id" class="form-label">Select Student</label>
                                                        <select class="form-select" id="student_id" name="student_id" required>
                                                            <option value="">Choose a student...</option>
                                                            <?php foreach ($students_array as $student): ?>
                                                                <option value="<?php echo $student['id']; ?>">
                                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . 
                                                                        ' (Grade ' . $student['grade_level'] . ')'); ?>
                                                                    <?php if ($student['current_parents']): ?>
                                                                        - Current parents: <?php echo htmlspecialchars($student['current_parents']); ?>
                                                                    <?php endif; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Link Student</button>
                                                </form>

                                                <?php if ($parent['linked_students']): ?>
                                                    <hr>
                                                    <h6>Currently Linked Students:</h6>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="action" value="unlink_student">
                                                        <input type="hidden" name="parent_id" value="<?php echo $parent['id']; ?>">
                                                        <div class="list-group">
                                                            <?php foreach ($students_array as $student): ?>
                                                                <?php
                                                                $check_link = "SELECT 1 FROM student_parent WHERE student_id = {$student['id']} AND parent_id = {$parent['id']}";
                                                                if ($conn->query($check_link)->num_rows > 0):
                                                                ?>
                                                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . 
                                                                            ' (Grade ' . $student['grade_level'] . ')'); ?>
                                                                        <button type="submit" name="student_id" value="<?php echo $student['id']; ?>" 
                                                                                class="btn btn-danger btn-sm">
                                                                            Unlink
                                                                        </button>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 