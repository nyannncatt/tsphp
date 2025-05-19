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
    <title>Manage Parents - SchoolComSphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --dark-bg: #1a1b2e;
            --card-bg: #242639;
            --accent-purple: #8b5cf6;
            --text-primary: #ffffff;
            --text-secondary: #9ca3af;
            --success-color: #10b981;
            --card-border: #2f3245;
            --hover-bg: #2f3245;
            --stat-text: #10b981;
            --header-text: #8b5cf6;
            --primary-color: #8b5cf6;
            --secondary-color: #a78bfa;
            --navbar-bg-start: #e9d5ff;
            --navbar-bg-end: #d8b4fe;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
        }

        .breadcrumb-item a {
            color: var(--accent-purple);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--text-secondary);
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--card-border);
            padding: 1.25rem;
            border-radius: 0.75rem 0.75rem 0 0 !important;
        }

        .card-header h5 {
            color: var(--header-text);
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--accent-purple);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .alert {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
        }

        .alert-success {
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            border-left: 4px solid #ef4444;
        }

        .form-label {
            color: var(--text-secondary);
        }

        .form-control {
            background: var(--hover-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
        }

        .form-control:focus {
            background: var(--hover-bg);
            border-color: var(--accent-purple);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.25);
        }

        .table {
            color: var(--text-primary);
        }

        .table thead th {
            background: var(--card-bg);
            color: var(--header-text);
            border-bottom: 1px solid var(--card-border);
        }

        .table tbody td {
            border-color: var(--card-border);
        }

        .table tbody tr:hover {
            background: var(--hover-bg);
        }

        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
        }

        .modal-header {
            border-bottom: 1px solid var(--card-border);
        }

        .modal-footer {
            border-top: 1px solid var(--card-border);
        }

        .breadcrumb {
            background: var(--card-bg);
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--card-border);
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door me-1"></i>Dashboard</a></li>
                        <li class="breadcrumb-item active">Manage Parents</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Add New Parent Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-person-plus me-2"></i>Add New Parent</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_parent">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person me-1"></i>Username
                                </label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope me-1"></i>Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-key me-1"></i>Password
                                </label>
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
                                <label for="phone" class="form-label">
                                    <i class="bi bi-telephone me-1"></i>Phone
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="address" class="form-label">
                                    <i class="bi bi-geo-alt me-1"></i>Address
                                </label>
                                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus me-2"></i>Add Parent
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Parents List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-people me-2"></i>Parents List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
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
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-circle text-primary me-2 fs-5"></i>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?></div>
                                            <small class="text-muted">@<?php echo htmlspecialchars($parent['username']); ?></small>
                                        </div>
                                    </div>
                                    </td>
                                    <td>
                                    <div><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($parent['email']); ?></div>
                                    <div><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($parent['phone']); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($parent['linked_students']): ?>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-mortarboard me-2"></i>
                                            <?php echo htmlspecialchars($parent['linked_students']); ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted"><i class="bi bi-exclamation-circle me-1"></i>No students linked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#linkStudentModal<?php echo $parent['id']; ?>">
                                            <i class="bi bi-link me-1"></i>Link Student
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $parent['id']; ?>)">
                                            <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                    </td>
                                </tr>

                                <!-- Link Student Modal -->
                                <div class="modal fade" id="linkStudentModal<?php echo $parent['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="bi bi-link me-2"></i>Link Student to <?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?>
                                            </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="action" value="link_student">
                                                    <input type="hidden" name="parent_id" value="<?php echo $parent['id']; ?>">
                                            <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="student_id" class="form-label">Select Student</label>
                                                        <select class="form-select" id="student_id" name="student_id" required>
                                                            <option value="">Choose a student...</option>
                                                            <?php foreach ($students_array as $student): ?>
                                                                <option value="<?php echo $student['id']; ?>">
                                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (Grade ' . $student['grade_level'] . ')'); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-link me-1"></i>Link Student
                                                                        </button>
                                            </div>
                                        </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmDelete(parentId) {
        if (confirm('Are you sure you want to delete this parent? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_parent">
                <input type="hidden" name="parent_id" value="${parentId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html> 