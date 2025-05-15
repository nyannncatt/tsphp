<?php
session_start();

// If user is logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    switch($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'student':
            header("Location: student/dashboard.php");
            break;
        case 'parent':
            header("Location: parent/dashboard.php");
            break;
    }
    exit();
}

// If not logged in, redirect to login page
header("Location: auth/login.php");
exit();
?> 