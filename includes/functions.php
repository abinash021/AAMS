<?php
/**
 * Function to check if a user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Function to get the current user ID
 * 
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Function to get the current user role
 * 
 * @return string|null User role if logged in, null otherwise
 */
function getCurrentUserRole() {
    return $_SESSION['role_name'] ?? null;
}

/**
 * Function to check if the current user is an admin
 * 
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return getCurrentUserRole() === 'admin';
}

/**
 * Function to check if the current user is a teacher
 * 
 * @return bool True if teacher, false otherwise
 */
function isTeacher() {
    return getCurrentUserRole() === 'teacher';
}

/**
 * Function to check if the current user is a student
 * 
 * @return bool True if student, false otherwise
 */
function isStudent() {
    return getCurrentUserRole() === 'student';
}

/**
 * Redirect to another page
 * 
 * @param string $url The URL to redirect to
 * @return void
 */
function redirect($url) {
    global $preventOutput;
    $preventOutput = true;
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        echo "<script>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit;
    }
}

/**
 * Function to display a flash message
 * 
 * @param string $message Message to display
 * @param string $type Message type (success, danger, warning, info)
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Function to get and clear the flash message
 * 
 * @return array|null Flash message data if exists, null otherwise
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type']
        ];
        
        // Clear the flash message
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return $message;
    }
    
    return null;
}

/**
 * Function to sanitize input data
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Function to validate an email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Function to check if a file is a valid image
 * 
 * @param array $file File data from $_FILES
 * @return bool True if valid image, false otherwise
 */
function isValidImage($file) {
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check if extension is allowed
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return false;
    }
    
    // Check if it's actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return false;
    }
    
    return true;
}

/**
 * Function to upload an image file
 * 
 * @param array $file File data from $_FILES
 * @param string $directory Directory to upload to
 * @param string $filename Custom filename (optional)
 * @return string|bool Path to uploaded file if successful, false otherwise
 */
function uploadImage($file, $directory, $filename = null) {
    // Validate image
    if (!isValidImage($file)) {
        return false;
    }
    
    // Ensure directory exists
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Generate a unique filename if not provided
    if ($filename === null) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '.' . $extension;
    }
    
    $targetPath = $directory . '/' . $filename;
    
    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $targetPath;
    }
    
    return false;
}

/**
 * Function to format a date
 * 
 * @param string $date Date in Y-m-d format
 * @param string $format Output format
 * @return string Formatted date
 */
function formatDate($date, $format = 'd M, Y') {
    return date($format, strtotime($date));
}

/**
 * Function to format time
 * 
 * @param string $time Time in H:i:s format
 * @param string $format Output format
 * @return string Formatted time
 */
function formatTime($time, $format = 'h:i A') {
    return date($format, strtotime($time));
}

/**
 * Function to check if user has permission for an action
 * 
 * @param string $action Action to check (admin, teacher, student)
 * @return bool True if permitted, false otherwise
 */
function hasPermission($action) {
    if (!isLoggedIn()) {
        return false;
    }
    
    switch ($action) {
        case 'admin':
            return isAdmin();
        case 'teacher':
            return isAdmin() || isTeacher();
        case 'student':
            return isAdmin() || isTeacher() || isStudent();
        default:
            return false;
    }
}

/**
 * Function to generate a secure random password
 * 
 * @param int $length Password length
 * @return string Random password
 */
function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $index = rand(0, strlen($chars) - 1);
        $password .= $chars[$index];
    }
    
    return $password;
}

/**
 * Function to create a directory if it doesn't exist
 * 
 * @param string $path Directory path
 * @param int $permissions Directory permissions
 * @return bool True if directory exists or was created, false otherwise
 */
function createDirectoryIfNotExists($path, $permissions = 0755) {
    if (!is_dir($path)) {
        return mkdir($path, $permissions, true);
    }
    
    return true;
}

/**
 * Function to check if a string starts with a specific substring
 * 
 * @param string $haystack String to check
 * @param string $needle Substring to find
 * @return bool True if starts with needle, false otherwise
 */
function startsWith($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Function to check if a string ends with a specific substring
 * 
 * @param string $haystack String to check
 * @param string $needle Substring to find
 * @return bool True if ends with needle, false otherwise
 */
function endsWith($haystack, $needle) {
    return substr($haystack, -strlen($needle)) === $needle;
}

/**
 * Function to limit a string to a specific length
 * 
 * @param string $string String to limit
 * @param int $length Maximum length
 * @param string $append String to append if truncated
 * @return string Truncated string
 */
function truncate($string, $length = 100, $append = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    
    return substr($string, 0, $length) . $append;
}

/**
 * Function to generate a CSRF token
 * 
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Function to validate a CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Function to include a CSRF token in a form
 * 
 * @return string HTML input field with CSRF token
 */
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Function to check if the current request is a POST request
 * 
 * @return bool True if POST request, false otherwise
 */
function isPostRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Function to get a specific URL query parameter
 * 
 * @param string $name Parameter name
 * @param mixed $default Default value if parameter doesn't exist
 * @return mixed Parameter value or default
 */
function getQueryParam($name, $default = null) {
    return $_GET[$name] ?? $default;
}

/**
 * Function to get a POST form field
 * 
 * @param string $name Field name
 * @param mixed $default Default value if field doesn't exist
 * @return mixed Field value or default
 */
function getPostField($name, $default = null) {
    return $_POST[$name] ?? $default;
}
?> 