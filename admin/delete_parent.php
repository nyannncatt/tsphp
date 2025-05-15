<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parent_id'])) {
    $parent_id = intval($_POST['parent_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get the user_id associated with the parent
        $query = "SELECT user_id FROM parents WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $parent = $result->fetch_assoc();
        
        if (!$parent) {
            throw new Exception("Parent not found");
        }
        
        // Delete the parent record
        $query = "DELETE FROM parents WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        
        // Delete the associated user account
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $parent['user_id']);
        $stmt->execute();
        
        $conn->commit();
        header("Location: parents.php?success=1");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: parents.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: parents.php");
    exit();
} 