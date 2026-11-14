<?php
// Turn off error display but log errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

// Turn off any existing output buffering completely
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output buffer
ob_start();

// Set JSON content type and prevent caching
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Define this as an API endpoint
$preventOutput = true;

// Include minimal config and classes
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Student.php';
require_once __DIR__ . '/../includes/Attendance.php';
require_once __DIR__ . '/../includes/Rekognition.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to send clean JSON response
function sendJsonResponse($data) {
    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Start fresh buffer
    ob_start();
    
    // Set headers again to be sure
    header('Content-Type: application/json');
    
    // Output JSON
    echo json_encode($data);
    
    // Send output and end
    ob_end_flush();
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'success' => false,
        'error' => 'Authentication required'
    ]);
}

// Handle attendance marking via face recognition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
    $class_id = $_POST['class_id'] ?? '';
    
    try {
        if (empty($class_id)) {
            throw new Exception('Class ID is required');
        }
        
        // Check if image file is uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No image uploaded or image upload error: ' . ($_FILES['image']['error'] ?? 'Unknown error'));
        }
        
        // Save the uploaded image to a temporary file
        $tempDir = UPLOAD_DIR . 'temp/';
        $tempFile = $tempDir . uniqid() . '.jpg';
        
        // Make sure UPLOAD_DIR exists
        if (!is_dir(UPLOAD_DIR)) {
            if (!mkdir(UPLOAD_DIR, 0777, true)) {
                throw new Exception('Failed to create upload directory: ' . UPLOAD_DIR);
            }
        }
        
        // Make sure temp directory exists
        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0777, true)) {
                throw new Exception('Failed to create temporary directory: ' . $tempDir);
            }
        }
        
        // Check write permissions
        if (!is_writable($tempDir)) {
            throw new Exception('Temporary directory is not writable: ' . $tempDir);
        }
        
        // Move the uploaded file
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $tempFile)) {
            $errorCode = $_FILES['image']['error'];
            throw new Exception('Failed to move uploaded image. Error code: ' . $errorCode);
        }
        
        // Initialize attendance class
        $attendance = new Attendance();
        
        // Process attendance with the temporary file
        $result = $attendance->markByFace($tempFile, $class_id, $_SESSION['user_id']);
        
        // Delete the temporary file
        @unlink($tempFile);
        
        // Add success flag
        $result['success'] = true;
        
        // Send clean JSON response
        sendJsonResponse($result);
        
    } catch (Exception $e) {
        error_log('Live attendance error: ' . $e->getMessage());
        
        // Send clean error JSON response
        sendJsonResponse([
            'success' => false, 
            'error' => $e->getMessage(),
            'debug' => [
                'total_faces_detected' => 0,
                'faces_recognized' => 0,
                'face_details' => []
            ],
            'attendance' => []
        ]);
    }
}

// Default response for invalid requests
sendJsonResponse([
    'success' => false,
    'error' => 'Invalid request'
]);
?> 