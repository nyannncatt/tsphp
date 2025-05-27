<?php
require_once 'includes/auth_middleware.php';
checkAuth();
require_once 'config/database.php';

// Get the restricted page name from the URL
$restricted_page = isset($_GET['page']) ? $_GET['page'] : 'Unknown Page';

// Get user's information
$user_query = "SELECT u.*, 
               COALESCE(s.first_name, p.first_name) as first_name,
               COALESCE(s.last_name, p.last_name) as last_name
               FROM users u
               LEFT JOIN students s ON u.id = s.user_id
               LEFT JOIN parents p ON u.id = p.user_id
               WHERE u.id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Log the restricted access attempt
$log_query = "INSERT INTO system_logs (user_id, action) VALUES (?, 'Attempted access to restricted page: $restricted_page')";
$log_stmt = $conn->prepare($log_query);
$log_stmt->bind_param("i", $_SESSION['user_id']);
$log_stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted - SchoolComSphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --dark-bg: #1a1b2e;
            --card-bg: #242639;
            --accent-purple: #8b5cf6;
            --text-primary: #ffffff;
            --text-secondary: #9ca3af;
            --danger-color: #ef4444;
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

        .restricted-card {
            background: var(--card-bg);
            border-radius: 1rem;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            text-align: center;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .warning-icon {
            font-size: 4rem;
            color: var(--danger-color);
            margin-bottom: 1.5rem;
        }

        .page-name {
            color: var(--accent-purple);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 1rem 0;
        }

        .user-info {
            background: rgba(139, 92, 246, 0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1.5rem 0;
        }

        .btn-back {
            background: var(--accent-purple);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-back:hover {
            background: #7c3aed;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="restricted-card">
        <i class="bi bi-shield-lock warning-icon"></i>
        <h1 class="mb-4">Access Restricted</h1>
        
        <p class="text-secondary mb-3">
            You do not have permission to access this page. This restriction has been set by an administrator.
        </p>

        <div class="page-name">
            Restricted Page: <?php echo htmlspecialchars(ucfirst($restricted_page)); ?>
        </div>

        <div class="user-info">
            <p class="mb-2">User Information:</p>
            <p class="mb-1">
                <strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
            </p>
            <p class="mb-1">
                <strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
            </p>
            <p class="mb-0">
                <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
            </p>
        </div>

        <p class="text-secondary mb-4">
            If you believe this is an error, please contact your administrator for assistance.
        </p>

        <a href="<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn-back">
            <i class="bi bi-arrow-left me-2"></i>Return to Dashboard
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 