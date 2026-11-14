<?php
$pageTitle = "Home";
require_once 'includes/header.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to appropriate dashboard
    if (isAdmin()) {
        redirect(BASE_URL . '/admin/dashboard.php');
    } elseif (isTeacher()) {
        redirect(BASE_URL . '/teacher/dashboard.php');
    } elseif (isStudent()) {
        redirect(BASE_URL . '/student/dashboard.php');
    }
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2 text-center mt-5">
        <div class="jumbotron py-5">
            <h1 class="display-4">Welcome to <?php echo SITE_NAME; ?></h1>
            <p class="lead">An automated attendance management system using facial recognition technology.</p>
            <hr class="my-4">
            <p>This system uses AWS Rekognition to accurately identify students and mark their attendance, eliminating manual roll calls and reducing errors.</p>
            
            <div class="mt-4">
                <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-camera-video text-primary mb-3" style="font-size: 3rem;"></i>
                <h4 class="card-title">Face Recognition</h4>
                <p class="card-text">Utilize cutting-edge facial recognition technology powered by AWS Rekognition to accurately identify students.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-clipboard-check text-primary mb-3" style="font-size: 3rem;"></i>
                <h4 class="card-title">Effortless Attendance</h4>
                <p class="card-text">Mark attendance quickly with high accuracy, eliminating the need for time-consuming manual roll calls.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-file-earmark-text text-primary mb-3" style="font-size: 3rem;"></i>
                <h4 class="card-title">Detailed Reports</h4>
                <p class="card-text">Access comprehensive attendance reports and analytics to track student attendance patterns.</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5 mb-5">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4>System Features</h4>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-success me-3" style="font-size: 1.2rem;"></i>
                        <span>Secure authentication with role-based access control</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-success me-3" style="font-size: 1.2rem;"></i>
                        <span>Real-time facial recognition for immediate attendance marking</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-success me-3" style="font-size: 1.2rem;"></i>
                        <span>Comprehensive management of branches, semesters, and classes</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-success me-3" style="font-size: 1.2rem;"></i>
                        <span>Student registration with facial data enrollment</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-success me-3" style="font-size: 1.2rem;"></i>
                        <span>Detailed attendance reports and analytics</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-success me-3" style="font-size: 1.2rem;"></i>
                        <span>Responsive design for access on various devices</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 