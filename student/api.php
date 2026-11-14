<?php
require_once __DIR__ . '/../includes/header.php';

// Prevent the rest of the header from being displayed
$preventOutput = true;

// Set the content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Get the action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Initialize the Student class
$student = new Student();

switch ($action) {
    case 'get_student':
        // Get student ID from request
        $student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($student_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
            exit();
        }
        
        // Get student details
        $studentDetails = $student->getById($student_id);
        
        if ($studentDetails) {
            echo json_encode([
                'success' => true, 
                'student' => [
                    'id' => $studentDetails['id'],
                    'student_name' => $studentDetails['full_name'],
                    'roll_number' => $studentDetails['roll_number'],
                    'class_name' => $studentDetails['class_name'] ?? '',
                    'email' => $studentDetails['email']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?> 