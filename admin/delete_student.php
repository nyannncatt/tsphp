<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get the user_id associated with the student
        $query = "SELECT user_id FROM students WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        
        if (!$student) {
            throw new Exception("Student not found");
        }
        
        // Delete enrollments
        $query = "DELETE FROM enrollments WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        
        // Delete grades
        $query = "DELETE FROM grades WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        
        // Delete attendance records
        $query = "DELETE FROM attendance WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        
        // Delete the student record
        $query = "DELETE FROM students WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        
        // Delete the associated user account
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student['user_id']);
        $stmt->execute();
        
        $conn->commit();
        header("Location: students.php?success=1");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: students.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: students.php");
    exit();
} 