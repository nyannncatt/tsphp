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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            color: #333;
        }

        .login-header .icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            color: #333;
            line-height: 1;
            display: flex;
            justify-content: center;
        }

        .login-header .icon i {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-form {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-label {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            opacity: 1;
            pointer-events: none;
        }

        .input-group:focus-within .input-label,
        .input-group input:not(:placeholder-shown) + .input-label {
            opacity: 0;
            transform: translateY(-100%);
        }

        .form-control {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            color: #333;
            padding: 12px 20px;
            height: 50px;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: #2196f3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
            color: #333;
            outline: none;
        }

        .btn-login {
            background: #2196f3;
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(33, 150, 243, 0.2);
        }

        .btn-login:hover {
            background: #1976d2;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .register-link a {
            color: #2196f3;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .register-link a:hover {
            color: #1976d2;
        }

        .welcome-text {
            text-align: center;
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 2.5rem;
            line-height: 1.3;
            font-weight: 500;
        }

        .alert {
            background: #fff3f3;
            border: 1px solid #ffcdd2;
            color: #f44336;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            padding: 1rem;
        }

        .alert ul {
            margin-bottom: 0;
            padding-left: 1.5rem;
        }

        ::placeholder {
            color: transparent;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon">
                <i class="bi bi-display"></i>
            </div>
            <div class="welcome-text">Welcome to SchoolComSphere</div>
        </div>

        <div class="login-form">
            <?php if (!empty($errors)): ?>
                <div class="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="input-group">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder=" " required>
                    <span class="input-label">Username:</span>
                </div>

                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder=" " required>
                    <span class="input-label">Password:</span>
                </div>

                <button type="submit" class="btn btn-login">LOGIN</button>

                <div class="register-link">
                    No account? <a href="register.php">Register here</a>
                </div>
            </form>
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