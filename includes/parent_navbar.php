<?php
require_once '../config/database.php';
require_once '../includes/messages.php';
$unread_count = getUnreadMessageCount($conn, getUserId());
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/tsphp/parent/dashboard.php">SchoolComSphere</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="/tsphp/parent/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/tsphp/parent/children.php">My Student</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/tsphp/parent/announcements.php">Announcements</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/tsphp/parent/messages.php">
                        Messages
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/tsphp/auth/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav> 