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
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Log successful login
                    logError("Successful login: User {$user['username']} ({$user['role']})");
                    
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
    <title>Login - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Login</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="invalid-feedback">
                                    Please enter your username
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">
                                    Please enter your password
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                            <div class="mt-3 text-center">
                                <a href="register.php" class="text-decoration-none">Don't have an account? Register here</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html> 