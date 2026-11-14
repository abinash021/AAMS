<?php
$pageTitle = "Admin Dashboard";
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize classes
$user = new User();
$student = new Student();
$academic = new Academic();

// Get counts for dashboard
$teacherCount = count($user->getAllByRole(User::ROLE_TEACHER));
$studentCount = $student->getTotalCount();
$branchCount = count($academic->getAllBranches());
$semesterCount = count($academic->getAllSemesters());

// Get recent users (last 5)
$recentTeachers = array_slice($user->getAllByRole(User::ROLE_TEACHER), 0, 5);
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
        <p class="text-muted">Welcome, <?php echo $_SESSION['full_name']; ?>! Manage your attendance system here.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card bg-primary text-white">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-person-workspace"></i>
                </div>
                <h5 class="card-title">Teachers</h5>
                <div class="count"><?php echo $teacherCount; ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/users.php?role=teacher" class="card-footer text-white text-center">
                View All <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card dashboard-card bg-success text-white">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-mortarboard"></i>
                </div>
                <h5 class="card-title">Students</h5>
                <div class="count"><?php echo $studentCount; ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/users.php?role=student" class="card-footer text-white text-center">
                View All <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card dashboard-card bg-info text-white">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <h5 class="card-title">Branches</h5>
                <div class="count"><?php echo $branchCount; ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/branches.php" class="card-footer text-white text-center">
                Manage <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card dashboard-card bg-warning text-white">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-calendar3"></i>
                </div>
                <h5 class="card-title">Semesters</h5>
                <div class="count"><?php echo $semesterCount; ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/semesters.php" class="card-footer text-white text-center">
                Manage <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-people"></i> Recent Teachers</h5>
                <a href="<?php echo BASE_URL; ?>/admin/users.php?role=teacher" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($recentTeachers) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTeachers as $teacher): ?>
                                    <tr>
                                        <td><?php echo $teacher['full_name']; ?></td>
                                        <td><?php echo $teacher['email']; ?></td>
                                        <td><?php echo formatDate($teacher['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted my-4">No teachers registered yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-gear"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <a href="<?php echo BASE_URL; ?>/admin/users.php?action=add&role=teacher" class="btn btn-outline-primary btn-lg d-block mb-3">
                            <i class="bi bi-person-plus"></i> Add Teacher
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo BASE_URL; ?>/admin/branches.php?action=add" class="btn btn-outline-primary btn-lg d-block mb-3">
                            <i class="bi bi-plus-square"></i> Add Branch
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo BASE_URL; ?>/admin/semesters.php?action=add" class="btn btn-outline-primary btn-lg d-block mb-3">
                            <i class="bi bi-plus-square"></i> Add Semester
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo BASE_URL; ?>/admin/classes.php?action=add" class="btn btn-outline-primary btn-lg d-block mb-3">
                            <i class="bi bi-plus-square"></i> Add Class
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo BASE_URL; ?>/admin/mass-students.php" class="btn btn-outline-success btn-lg d-block mb-3">
                            <i class="bi bi-people-fill"></i> Mass Student Registration
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo BASE_URL; ?>/students/register.php" class="btn btn-outline-success btn-lg d-block mb-3">
                            <i class="bi bi-person-plus"></i> Register Student
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo BASE_URL; ?>/attendance/report.php" class="btn btn-outline-info btn-lg d-block mb-3">
                            <i class="bi bi-file-earmark-text"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> System Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>System Name</th>
                                <td><?php echo SITE_NAME; ?></td>
                            </tr>
                            <tr>
                                <th>PHP Version</th>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <th>Database</th>
                                <td>SQLite 3</td>
                            </tr>
                            <tr>
                                <th>Server</th>
                                <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h5><i class="bi bi-exclamation-triangle"></i> Important Note</h5>
                            <p>Make sure to configure your AWS Rekognition credentials in the config file to use the face recognition features.</p>
                            <p class="mb-0">Current AWS Region: <strong><?php echo AWS_REGION; ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 