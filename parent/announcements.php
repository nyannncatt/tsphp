<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['parent']);
require_once '../config/database.php';

// Fetch all announcements
$announcements = $conn->query("
    SELECT a.*, u.username 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Announcements - SchoolComSphere System</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">    <style>        body {            background-color: #1a1b2e;            color: #ffffff;        }        .breadcrumb-item a {            color: #8b5cf6;            text-decoration: none;        }        .breadcrumb-item a:hover {            color: #7c3aed;        }        .breadcrumb-item.active {            color: #ffffff;        }        .card {            background-color: #242639;            border: 1px solid #2f3245;        }        .card-header {            background-color: #2f3245;            border-bottom: 1px solid #2f3245;            color: #ffffff;        }        .text-muted {            color: #a0aec0 !important;        }        .alert-info {            background-color: #2f3245;            border-color: #3f4259;            color: #ffffff;        }        .list-group-item {            background-color: #242639;            border: 1px solid #2f3245;            color: #ffffff;            border-radius: 0.5rem !important;            transition: transform 0.2s ease-in-out;        }        .list-group-item:hover {            transform: translateY(-2px);            background-color: #2f3245;        }        .list-group-item h5 {            color: #8b5cf6;            font-weight: 600;        }        .list-group-item p {            color: #ffffff;            line-height: 1.6;        }        .text-purple {            color: #8b5cf6 !important;        }    </style></head>
<body>
    <?php include '../includes/parent_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Announcements</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                                        <div class="card-header">                        <h4 class="mb-0">                            <i class="bi bi-megaphone-fill me-2 text-purple"></i>                            School Announcements                        </h4>                    </div>                    <div class="card-body">                        <?php if ($announcements->num_rows == 0): ?>                            <div class="alert alert-info">                                <i class="bi bi-info-circle me-2"></i>                                No announcements available at this time.                            </div>                        <?php else: ?>                            <div class="list-group">                                <?php while ($announcement = $announcements->fetch_assoc()): ?>                                    <div class="list-group-item mb-3">                                        <div class="d-flex w-100 justify-content-between align-items-start">                                            <h5 class="mb-1">                                                <i class="bi bi-bell-fill me-2"></i>                                                <?php echo htmlspecialchars($announcement['title']); ?>                                            </h5>                                            <small class="text-muted">                                                <i class="bi bi-calendar3 me-1"></i>                                                <?php echo date('M d, Y H:i', strtotime($announcement['created_at'])); ?>                                            </small>                                        </div>                                        <p class="mb-2 mt-2"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>                                        <small class="text-muted">                                            <i class="bi bi-person-fill me-1"></i>                                            Posted by <?php echo htmlspecialchars($announcement['username']); ?>                                        </small>                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 