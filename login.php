<?php
$pageTitle = "Login";
require_once 'includes/functions.php';
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/User.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL);
}

$error = '';

// Process login form
if (isPostRequest()) {
    $username = getPostField('username');
    $password = getPostField('password');
    $csrfToken = getPostField('csrf_token');
    
    // Validate CSRF token
    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid form submission. Please try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        // Attempt to authenticate
        $user = new User();
        $userData = $user->authenticate($username, $password);
        
        if ($userData) {
            // Set session variables
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['full_name'] = $userData['full_name'];
            $_SESSION['email'] = $userData['email'];
            $_SESSION['role_id'] = $userData['role_id'];
            $_SESSION['role_name'] = $userData['role_name'];
            
            // Redirect based on role
            switch ($userData['role_name']) {
                case 'admin':
                    redirect(BASE_URL . '/admin/dashboard.php');
                    break;
                case 'teacher':
                    redirect(BASE_URL . '/teacher/dashboard.php');
                    break;
                case 'student':
                    redirect(BASE_URL . '/student/dashboard.php');
                    break;
                default:
                    redirect(BASE_URL);
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(to right, #3a7bd5, #00d2ff);
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo i {
            font-size: 48px;
            color: #3a7bd5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <i class="bi bi-camera-video"></i>
                <h4 class="mt-2"><?php echo SITE_NAME; ?></h4>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="loading-on-submit">
                <?php echo csrfField(); ?>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg" data-loading-text="Logging in...">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html> 