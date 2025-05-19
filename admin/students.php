<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

// Get all students with their user information
$query = "SELECT s.*, u.username, u.email 
          FROM students s
          JOIN users u ON s.user_id = u.id
          ORDER BY s.last_name, s.first_name";
$students = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - SchoolComSphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
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
            --info-text: #000000;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .dashboard-title {
            color: var(--header-text);
            font-weight: 600;
            margin-bottom: 0;
            font-size: 1.5rem;
        }

        .search-bar {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            color: var(--text-primary);
            width: 300px;
        }

        .search-bar input {
            background: transparent;
            border: none;
            color: var(--text-primary);
            width: 100%;
            outline: none;
        }

        .search-bar input::placeholder {
            color: var(--text-secondary);
        }

        .btn-custom-primary {
            background: var(--accent-purple);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .btn-custom-primary:hover {
            background: #7c4ef3;
            color: white;
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

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            margin-bottom: 1.5rem;
        }

        .table {
            color: var(--text-primary);
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--card-bg);
            color: var(--header-text);
            font-weight: 600;
            border-bottom: 1px solid var(--card-border);
            padding: 1rem;
        }

        .table tbody td {
            border-bottom: 1px solid var(--card-border);
            padding: 1rem;
            vertical-align: middle;
            color: var(--info-text);
        }

        .table tbody tr:hover {
            background: var(--hover-bg);
        }

        .btn-action {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            margin-right: 0.25rem;
        }

        .btn-view {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .btn-view:hover {
            background: #2563eb;
            color: white;
        }

        .btn-edit {
            background: var(--accent-purple);
            color: white;
            border: none;
        }

        .btn-edit:hover {
            background: #7c4ef3;
            color: white;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
        }

        .btn-delete:hover {
            background: #dc2626;
            color: white;
        }

        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            color: var(--text-primary);
        }

        .modal-header {
            border-bottom: 1px solid var(--card-border);
            padding: 1.25rem;
        }

        .modal-footer {
            border-top: 1px solid var(--card-border);
            padding: 1.25rem;
        }

        .btn-close {
            color: var(--text-primary);
            opacity: 0.75;
        }

        .table-responsive {
            border-radius: 1rem;
        }

        .empty-state {
            padding: 2rem;
            text-align: center;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="dashboard-title">Manage Students</h2>
            <div class="d-flex gap-3 align-items-center">
                <div class="search-bar d-flex align-items-center">
                    <i class="bi bi-search me-2 text-secondary"></i>
                    <input type="text" placeholder="Search students..." id="searchInput" />
                </div>
                <a href="../auth/register.php?role=student" class="btn btn-custom-primary">
                    <i class="bi bi-plus-lg me-2"></i>Add New Student
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success mb-4">
                <i class="bi bi-check-circle me-2"></i>
                Student deleted successfully.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Year Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students->num_rows > 0): ?>
                            <?php while ($student = $students->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-circle me-2 text-secondary"></i>
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td>
                                    <span class="badge bg-primary">Year level: <?php echo htmlspecialchars($student['grade_level']); ?></span>
                                </td>
                                <td>
                                    <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-action btn-view">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-action btn-edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-action btn-delete" onclick="deleteStudent(<?php echo $student['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="bi bi-people mb-2" style="font-size: 2rem;"></i>
                                    <p class="mb-0">No students found. Add your first student to get started.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                    Are you sure you want to delete this student? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="delete_student.php" style="display: inline;">
                        <input type="hidden" name="student_id" id="deleteStudentId">
                        <button type="submit" class="btn btn-delete">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteStudent(studentId) {
            document.getElementById('deleteStudentId').value = studentId;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Simple search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    </script>
</body>
</html> 