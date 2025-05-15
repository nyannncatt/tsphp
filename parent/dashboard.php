<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['parent']);
require_once '../config/database.php';

$user_id = getUserId();

// Get parent information
$parent_query = "SELECT p.* FROM parents p WHERE p.user_id = ?";
$stmt = $conn->prepare($parent_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();

// Get children information
$children_query = "
    SELECT s.*, u.username, u.email,
           (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id AND status = 'present') as attendance_present,
           (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id) as attendance_total
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN student_parent sp ON s.id = sp.student_id
    WHERE sp.parent_id = ?
";
$stmt = $conn->prepare($children_query);
$stmt->bind_param("i", $parent['id']);
$stmt->execute();
$children = $stmt->get_result();

// Get recent announcements
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/parent_navbar.php'; ?>

    <div class="container mt-4">
        <h2>Welcome, <?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?>!</h2>
        
        <div class="row mt-4">
            <!-- Children Overview -->
            <div class="col-md-8">
                <?php while ($child = $children->fetch_assoc()): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                <span class="badge bg-secondary">Grade <?php echo $child['grade_level']; ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Overall Attendance -->
                            <div class="mb-3">
                                <h6>Overall Attendance</h6>
                                <div class="progress">
                                    <?php 
                                    $attendance_percentage = $child['attendance_total'] > 0 
                                        ? ($child['attendance_present'] / $child['attendance_total']) * 100 
                                        : 0;
                                    ?>
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $attendance_percentage; ?>%"
                                         aria-valuenow="<?php echo $attendance_percentage; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo number_format($attendance_percentage, 1); ?>%
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Grades -->
                            <?php
                            $grades_query = "
                                SELECT c.course_name, g.grade, g.grading_period
                                FROM grades g
                                JOIN courses c ON g.course_id = c.id
                                WHERE g.student_id = ?
                                ORDER BY g.created_at DESC
                                LIMIT 5
                            ";
                            $stmt = $conn->prepare($grades_query);
                            $stmt->bind_param("i", $child['id']);
                            $stmt->execute();
                            $grades = $stmt->get_result();
                            ?>
                            <h6>Recent Grades</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Grade</th>
                                            <th>Period</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($grade = $grades->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                                <td><?php echo number_format($grade['grade'], 2); ?>%</td>
                                                <td><?php echo htmlspecialchars($grade['grading_period']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="student_details.php?id=<?php echo $child['id']; ?>" class="btn btn-primary btn-sm">
                                View Full Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Recent Announcements -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Announcements</h5>
                    </div>
                    <div class="card-body">
                        <?php while ($announcement = $announcements->fetch_assoc()): ?>
                            <div class="mb-3">
                                <h6><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                <p class="small text-muted mb-1">
                                    <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                </p>
                                <p class="mb-0">
                                    <?php 
                                        $content = htmlspecialchars($announcement['content']);
                                        echo strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                                    ?>
                                </p>
                            </div>
                        <?php endwhile; ?>
                        <a href="announcements.php" class="btn btn-primary btn-sm">View All Announcements</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 