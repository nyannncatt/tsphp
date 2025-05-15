<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

$success_message = '';
$error_message = '';

// Get parent ID from URL
$parent_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get parent details
$query = "SELECT p.*, u.email, u.username 
          FROM parents p
          JOIN users u ON p.user_id = u.id
          WHERE p.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();

if (!$parent) {
    header("Location: parents.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($first_name) || empty($last_name)) {
        $error_message = "First name and last name are required.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Update parent information
            $query = "UPDATE parents 
                     SET first_name = ?, last_name = ?, phone = ?, address = ?
                     WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssi", $first_name, $last_name, $phone, $address, $parent_id);
            $stmt->execute();

            // Update email in users table
            $query = "UPDATE users SET email = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $email, $parent['user_id']);
            $stmt->execute();

            $conn->commit();
            $success_message = "Parent information updated successfully.";

            // Refresh parent data
            $query = "SELECT p.*, u.email, u.username 
                     FROM parents p
                     JOIN users u ON p.user_id = u.id
                     WHERE p.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $parent_id);
            $stmt->execute();
            $parent = $stmt->get_result()->fetch_assoc();

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating parent information: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Parent - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="parents.php">Parents</a></li>
                        <li class="breadcrumb-item active">Edit Parent</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Edit Parent Information</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($parent['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($parent['last_name']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($parent['username']); ?>" readonly>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($parent['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($parent['phone']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($parent['address']); ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Parent Information</button>
                                <a href="parents.php" class="btn btn-secondary">Back to Parents List</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 