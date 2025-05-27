<?php
require_once __DIR__ . '/../config/database.php';

function logUserActivity($user_id, $page, $action = 'view') {
    global $conn;
    
    $query = "INSERT INTO user_activity (user_id, page, action) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $user_id, $page, $action);
    $stmt->execute();
}

// Function to get current page label
function getCurrentPage() {
    $page = basename($_SERVER['PHP_SELF']);
    
    // Map of page filenames to their display labels
    $page_labels = [
        'dashboard.php' => 'Dashboard',
        'grades.php' => 'My Grades',
        'attendance.php' => 'My Attendance',
        'courses.php' => 'My Courses',
        'messages.php' => 'Messages',
        'announcements.php' => 'Announcements',
        'children.php' => 'My Student',
        'profile.php' => 'Profile',
        'settings.php' => 'Settings',
        'view_course.php' => 'Course Details',
        'view_grade.php' => 'Grade Details',
        'view_announcement.php' => 'Announcement Details',
        'view_message.php' => 'Message Details',
        'compose_message.php' => 'Compose Message',
        'edit_profile.php' => 'Edit Profile',
        'change_password.php' => 'Change Password'
    ];
    
    return $page_labels[$page] ?? $page;
}

// Function to log page view
function logPageView() {
    if (isset($_SESSION['user_id'])) {
        $page = getCurrentPage();
        logUserActivity($_SESSION['user_id'], $page, 'view');
    }
}

// Function to log specific action
function logAction($action) {
    if (isset($_SESSION['user_id'])) {
        $page = getCurrentPage();
        logUserActivity($_SESSION['user_id'], $page, $action);
    }
}
?> 