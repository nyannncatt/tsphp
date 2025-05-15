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
    
    // Validate username
    $username = trim($conn->real_escape_string($_POST['username']));
    if (empty($username)) {
        $errors[] = "Username is required";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Username already exists";
        }
    }
    
    // Validate password
    if (empty($_POST['password'])) {
        $errors[] = "Password is required";
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = "Passwords do not match";
    } elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $_POST['password'])) {
        $errors[] = "Password must be at least 8 characters long and contain uppercase, lowercase, and numbers";
    }
    
    // Validate email
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    // Validate role
    $role = $_POST['role'];
    if (!in_array($role, ['student', 'parent'])) {
        $errors[] = "Invalid role selected";
    }
    
    // Role-specific validation
    $first_name = trim($conn->real_escape_string($_POST['first_name']));
    $last_name = trim($conn->real_escape_string($_POST['last_name']));
    
    if (empty($first_name) || empty($last_name)) {
        $errors[] = "First name and last name are required";
    }
    
    if ($role == 'student') {
        if (!isset($_POST['grade_level']) || !is_numeric($_POST['grade_level'])) {
            $errors[] = "Valid grade level is required for students";
        }
    } elseif ($role == 'parent') {
        if (empty($_POST['phone']) || empty($_POST['address'])) {
            $errors[] = "Phone number and address are required for parents";
        }
    }
    
    if (empty($errors)) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create user account
            $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $username, $password, $email, $role);
            $stmt->execute();
            
            $user_id = $conn->insert_id;
            
            if ($role == 'student') {
                // Create student profile
                $grade_level = (int)$_POST['grade_level'];
                $sql = "INSERT INTO students (user_id, first_name, last_name, grade_level) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issi", $user_id, $first_name, $last_name, $grade_level);
                $stmt->execute();
            } else {
                // Create parent profile
                $phone = $conn->real_escape_string($_POST['phone']);
                $address = $conn->real_escape_string($_POST['address']);
                $sql = "INSERT INTO parents (user_id, first_name, last_name, phone, address) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issss", $user_id, $first_name, $last_name, $phone, $address);
                $stmt->execute();
            }
            
            $conn->commit();
            $success = "Registration successful! You can now login.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error during registration: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Register</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
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

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                                           title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters"
                                           required>
                                    <div class="form-text">Password must be at least 8 characters long, contain uppercase and lowercase letters, and numbers.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div id="password-match-feedback" class="invalid-feedback">
                                        Passwords do not match
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" required onchange="toggleFields()">
                                        <option value="">Select role</option>
                                        <option value="student">Student</option>
                                        <option value="parent">Parent</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>

                            <!-- Student-specific fields -->
                            <div id="studentFields" style="display: none;">
                                <div class="mb-3">
                                    <label for="grade_level" class="form-label">Grade Level</label>
                                    <input type="number" class="form-control" id="grade_level" name="grade_level">
                                </div>
                            </div>

                            <!-- Parent-specific fields -->
                            <div id="parentFields" style="display: none;">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Register</button>
                                <a href="login.php" class="btn btn-link">Already have an account? Login</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleFields() {
        const role = document.getElementById('role').value;
        const studentFields = document.getElementById('studentFields');
        const parentFields = document.getElementById('parentFields');
        
        if (role === 'student') {
            studentFields.style.display = 'block';
            parentFields.style.display = 'none';
            document.getElementById('grade_level').required = true;
            document.getElementById('phone').required = false;
            document.getElementById('address').required = false;
        } else if (role === 'parent') {
            studentFields.style.display = 'none';
            parentFields.style.display = 'block';
            document.getElementById('grade_level').required = false;
            document.getElementById('phone').required = true;
            document.getElementById('address').required = true;
        } else {
            studentFields.style.display = 'none';
            parentFields.style.display = 'none';
        }
    }

    // Password validation
    document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
        const feedback = document.getElementById('password-match-feedback');
        
        if (password !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
            feedback.style.display = 'block';
        } else {
            this.setCustomValidity('');
            feedback.style.display = 'none';
        }
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        this.classList.add('was-validated');
    });
    </script>
</body>
</html> 