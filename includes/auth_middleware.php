<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/activity_logger.php';

function recordUserActivity($user_id, $action) {
    global $conn;
    $query = "INSERT INTO user_activity (user_id, action, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $action);
    $stmt->execute();
}

function checkAuth() {
    global $conn;
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: /tsphp/login.php");
        exit();
    }

    // Check if user is blocked
    $block_query = "SELECT * FROM user_blocks WHERE user_id = ? AND block_until > CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($block_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $block_result = $stmt->get_result();

    if ($block_result->num_rows > 0) {
        $block = $block_result->fetch_assoc();
        $_SESSION['error'] = "Your account has been blocked until " . date('M d, Y h:i A', strtotime($block['block_until'])) . ". Reason: " . $block['reason'];
        header("Location: /tsphp/login.php");
        exit();
    }

    // Check for navigation restrictions
    $restriction_query = "SELECT restricted_pages FROM nav_restrictions WHERE user_id = ?";
    $stmt = $conn->prepare($restriction_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $restriction_result = $stmt->get_result();

    if ($restriction_result->num_rows > 0) {
        $restriction = $restriction_result->fetch_assoc();
        $restricted_pages = explode(',', $restriction['restricted_pages']);
        $current_page = basename($_SERVER['PHP_SELF'], '.php');

        if (in_array($current_page, $restricted_pages)) {
            // Log the restricted access attempt
            $log_query = "INSERT INTO system_logs (user_id, action, details) VALUES (?, 'restricted_access', ?)";
            $stmt = $conn->prepare($log_query);
            $details = "Attempted to access restricted page: " . $current_page;
            $stmt->bind_param("is", $_SESSION['user_id'], $details);
            $stmt->execute();

            header("Location: /tsphp/restricted.php?page=" . urlencode($current_page));
            exit();
        }
    }

    // Log login activity if this is a new session
    if (!isset($_SESSION['login_logged'])) {
        $stmt = $conn->prepare("INSERT INTO user_activity (user_id, action, created_at) VALUES (?, 'login', CURRENT_TIMESTAMP)");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $_SESSION['login_logged'] = true;
    }
}

function checkRole($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: /auth/unauthorized.php");
        exit();
    }
}

// Ensure user_activity table has the correct structure
$check_table = "SHOW TABLES LIKE 'user_activity'";
$table_exists = $conn->query($check_table);

if ($table_exists->num_rows == 0) {
    // Create the table if it doesn't exist
    $create_table = "
    CREATE TABLE user_activity (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        page VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->query($create_table);
} else {
    // Check if page column exists
    $check_column = "SHOW COLUMNS FROM user_activity LIKE 'page'";
    $column_exists = $conn->query($check_column);
    
    if ($column_exists->num_rows == 0) {
        // Add page column if it doesn't exist
        $add_column = "ALTER TABLE user_activity ADD COLUMN page VARCHAR(255) AFTER action";
        $conn->query($add_column);
    }
}

// Log page view after authentication checks
if (isset($_SESSION['user_id'])) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    $page_labels = [
        'dashboard' => 'Dashboard',
        'grades' => 'My Grades',
        'attendance' => 'My Attendance',
        'courses' => 'My Courses',
        'messages' => 'Messages',
        'announcements' => 'Announcements',
        'children' => 'My Student',
        'profile' => 'Profile',
        'settings' => 'Settings',
        'view_course' => 'Course Details',
        'view_grade' => 'Grade Details',
        'view_announcement' => 'Announcement Details',
        'view_message' => 'Message Details',
        'compose_message' => 'Compose Message',
        'edit_profile' => 'Edit Profile',
        'change_password' => 'Change Password'
    ];
    
    $page_label = $page_labels[$current_page] ?? ucfirst($current_page);
    
    // Only log if it's not a login/logout page
    if (!in_array($current_page, ['login', 'logout'])) {
        $query = "INSERT INTO user_activity (user_id, action, page) VALUES (?, 'page_view', ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $_SESSION['user_id'], $page_label);
        $stmt->execute();
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Add logout recording
function logout() {
    global $conn;
    
    // Record logout activity before destroying session
    if (isset($_SESSION['user_id'])) {
        $query = "INSERT INTO user_activity (user_id, action, created_at) VALUES (?, 'logout', CURRENT_TIMESTAMP)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
    }
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: /tsphp/login.php");
    exit();
}
?> 