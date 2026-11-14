<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/Database.php';
require_once 'includes/User.php';

// Log logout time if logged in
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    $user = new User();
    $user->logLogout($userId);
}

// Destroy the session
session_destroy();

// Redirect to login page
redirect(BASE_URL . '/login.php');
?> 