<?php
// Required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Student.php';
require_once __DIR__ . '/Academic.php';
require_once __DIR__ . '/Attendance.php';

// Conditionally include Rekognition only if the autoload file exists
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    try {
        require_once __DIR__ . '/Rekognition.php';
    } catch (Exception $e) {
        // Log error but continue
        error_log("Could not load Rekognition class: " . $e->getMessage());
    }
}

// Check for active session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get flash messages if any
$flashMessage = getFlashMessage();

// Check if this is an API request (AJAX call)
$isApiRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                (isset($preventOutput) && $preventOutput === true);

// Don't output any content yet if there's a pending redirect or API request
if (!isset($preventOutput) || !$preventOutput):
    if (!$isApiRequest):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <?php if (isset($extraCss)): ?>
        <?php echo $extraCss; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <i class="bi bi-camera-video"></i> <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <!-- Admin Menu -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="usersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Users
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="usersDropdown">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/users.php?role=teacher">Teachers</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/users.php?role=student">Students</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/users.php?action=add&role=teacher">Add Teacher</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/students/register.php">Register Student</a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="academicDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Academic
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="academicDropdown">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/branches.php">Branches</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/semesters.php">Semesters</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/classes.php">Classes</a></li>
                                </ul>
                            </li>
                        <?php elseif (isTeacher()): ?>
                            <!-- Teacher Menu -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/teacher/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="attendanceDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Attendance
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="attendanceDropdown">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/attendance/mark.php">Mark Attendance</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/attendance/manual.php">Manual Entry</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/attendance/report.php">Reports</a></li>
                                </ul>
                            </li>
                            <!-- <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/students/list.php">Students</a>
                            </li> -->
                        <?php elseif (isStudent()): ?>
                            <!-- Student Menu -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/student/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/student/attendance.php">My Attendance</a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>">Home</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?php echo $_SESSION['full_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile.php">My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Message -->
    <?php if (!empty($flashMessage)): ?>
        <div class="container">
            <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flashMessage['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container">
<?php 
    endif; // end of if (!$isApiRequest)
endif; // end of if (!isset($preventOutput) || !$preventOutput)
?> 