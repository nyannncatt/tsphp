<?php
session_start();
require_once '../config/database.php';

// Function to log errors
function logError($message) {
    $logFile = '../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    
    // Create logs directory if it doesn't exist
    if (!file_exists('../logs')) {
        mkdir('../logs', 0777, true);
    }
    
    error_log($logMessage, 3, $logFile);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    // Validate input
    if (empty($_POST['username'])) {
        $errors[] = "Username is required";
    }
    if (empty($_POST['password'])) {
        $errors[] = "Password is required";
    }
    
    if (empty($errors)) {
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];

        try {
            $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                // Check if user is currently blocked
                $block_check = "SELECT * FROM user_blocks 
                              WHERE user_id = ? 
                              AND block_until > CURRENT_TIMESTAMP 
                              ORDER BY block_until DESC 
                              LIMIT 1";
                $block_stmt = $conn->prepare($block_check);
                $block_stmt->bind_param("i", $user['id']);
                $block_stmt->execute();
                $block_result = $block_stmt->get_result();
                
                if ($block_result->num_rows > 0) {
                    $block = $block_result->fetch_assoc();
                    $errors[] = "Your account is blocked until " . date('M d, Y h:i A', strtotime($block['block_until']));
                    logError("Blocked login attempt: User {$user['username']} ({$user['role']})");
                    
                    // Log blocked attempt
                    $log_query = "INSERT INTO login_history (user_id, login_time, ip_address, user_agent, status) 
                                VALUES (?, CURRENT_TIMESTAMP, ?, ?, 'blocked')";
                    $log_stmt = $conn->prepare($log_query);
                    $log_stmt->bind_param("iss", $user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                    $log_stmt->execute();
                } else if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Log successful login
                    logError("Successful login: User {$user['username']} ({$user['role']})");
                    
                    // Log successful attempt
                    $log_query = "INSERT INTO login_history (user_id, login_time, ip_address, user_agent, status) 
                                VALUES (?, CURRENT_TIMESTAMP, ?, ?, 'success')";
                    $log_stmt = $conn->prepare($log_query);
                    $log_stmt->bind_param("iss", $user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                    $log_stmt->execute();
                    
                    // Redirect based on role
                    switch($user['role']) {
                        case 'admin':
                            header("Location: ../admin/dashboard.php");
                            break;
                        case 'student':
                            header("Location: ../student/dashboard.php");
                            break;
                        case 'parent':
                            header("Location: ../parent/dashboard.php");
                            break;
                    }
                    exit();
                } else {
                    $errors[] = "Invalid password";
                    logError("Failed login attempt: Invalid password for user $username");
                    
                    // Log failed attempt
                    $log_query = "INSERT INTO login_history (user_id, login_time, ip_address, user_agent, status) 
                                VALUES (?, CURRENT_TIMESTAMP, ?, ?, 'failed')";
                    $log_stmt = $conn->prepare($log_query);
                    $log_stmt->bind_param("iss", $user['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                    $log_stmt->execute();
                }
            } else {
                $errors[] = "Username not found";
                logError("Failed login attempt: Username not found - $username");
            }
        } catch (Exception $e) {
            $errors[] = "An error occurred during login";
            logError("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SchoolComSphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --dark-bg: #1a1b2e;
            --card-bg: #242639;
            --accent-purple: #8b5cf6;
            --text-primary: #ffffff;
            --text-secondary: #9ca3af;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }

        .form-control {
            background: var(--dark-bg);
            border: 1px solid #2f3245;
            color: var(--text-primary);
        }

        .form-control:focus {
            background: var(--dark-bg);
            border-color: var(--accent-purple);
            color: var(--text-primary);
        }

        .btn-primary {
            background: var(--accent-purple);
            border: none;
        }

        .btn-primary:hover {
            background: #7c4cf6;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h2><i class="bi bi-building me-2"></i>SchoolComSphere</h2>
            <p class="text-secondary">Please login to continue</p>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'blocked'): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Your account is blocked until <?php echo date('M d, Y h:i A', strtotime($_GET['until'])); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 