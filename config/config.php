<?php
// General site configuration
define('SITE_NAME', 'AAMS');
define('BASE_URL', 'http://localhost/AAMS');

// Database configuration
define('DB_PATH', __DIR__ . '/../db/attendance.db');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
session_start();

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('STUDENT_PHOTOS_DIR', UPLOAD_DIR . 'students/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// AWS Configuration
define('AWS_KEY', 'XX.............');
define('AWS_SECRET', 'XX.............');
define('AWS_REGION', 'ap-south-1');
define('AWS_COLLECTION_ID', 'db_aams');

// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Time zone setting
date_default_timezone_set('Asia/Kolkata');

// Function to handle fatal errors gracefully
function fatalErrorHandler() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        // Log the error
        error_log("Fatal error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        
        // Display an error page
        include(__DIR__ . '/../includes/error.php');
        exit();
    }
}

register_shutdown_function('fatalErrorHandler');
?> 
