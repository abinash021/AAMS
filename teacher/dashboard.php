<?php
$pageTitle = "Teacher Dashboard";
require_once '../includes/header.php';

// Check if user is logged in and is a teacher
if (!isLoggedIn() || (!isTeacher() && !isAdmin())) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize classes
$user = new User();
$student = new Student();
$academic = new Academic();
$attendance = new Attendance();

// Get the teacher's classes
$teacherClasses = $academic->getClassesByTeacher(getCurrentUserId());

// Get total students count for teacher's classes
$totalStudents = 0;
$totalAttendanceToday = 0;
$totalAttendanceRecords = 0;
$classIds = [];

foreach ($teacherClasses as $class) {
    $classIds[] = $class['id'];
    
    // Get students in this semester
    $semesterStudents = $student->getBySemester($class['semester_id']);
    $totalStudents += count($semesterStudents);
    
    // Get today's attendance for this class
    $todayAttendance = $attendance->getForClass($class['id']);
    $totalAttendanceToday += count($todayAttendance);
    
    // Get attendance stats for the class
    $classStats = $attendance->getClassStats($class['id']);
    $totalAttendanceRecords += $classStats['total_records'] ?? 0;
}

// Get recent attendance records (combined from all classes)
$recentAttendanceRecords = [];
$date = date('Y-m-d');

foreach ($classIds as $classId) {
    $classAttendance = $attendance->getForClass($classId, $date);
    $recentAttendanceRecords = array_merge($recentAttendanceRecords, $classAttendance);
}

// Sort by time, most recent first
usort($recentAttendanceRecords, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

// Take only the 5 most recent
$recentAttendanceRecords = array_slice($recentAttendanceRecords, 0, 5);
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-speedometer2"></i> Teacher Dashboard</h2>
        <p class="text-muted">Welcome, <?php echo $_SESSION['full_name']; ?>! Manage your classes and attendance here.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card bg-primary text-white">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-book"></i>
                </div>
                <h5 class="card-title">My Classes</h5>
                <div class="count"><?php echo count($teacherClasses); ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>/teacher/classes.php" class="card-footer text-white text-center">
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
                <div class="count"><?php echo $totalStudents; ?></div>
            </div>
            <?php if (count($teacherClasses) > 0): ?>
                <a href="<?php echo BASE_URL; ?>/students/class_students.php?semester_id=<?php echo $teacherClasses[0]['semester_id']; ?>" class="card-footer text-white text-center">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/teacher/classes.php" class="card-footer text-white text-center">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card dashboard-card bg-info text-white">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <h5 class="card-title">Today's Attendance</h5>
                <div class="count"><?php echo $totalAttendanceToday; ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>/attendance/report.php?date=<?php echo date('Y-m-d'); ?>" class="card-footer text-white text-center">
                View Details <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card dashboard-card bg-warning text-white">
            <div class="card-body">
                <div class="icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <h5 class="card-title">Total Records</h5>
                <div class="count"><?php echo $totalAttendanceRecords; ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>/attendance/report.php" class="card-footer text-white text-center">
                View Reports <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-book"></i> My Classes</h5>
                <a href="<?php echo BASE_URL; ?>/teacher/classes.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($teacherClasses) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Semester</th>
                                    <th>Branch</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teacherClasses as $class): ?>
                                    <tr>
                                        <td><?php echo $class['name']; ?></td>
                                        <td><?php echo $class['semester_name']; ?></td>
                                        <td><?php echo $class['branch_name']; ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/attendance/mark.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-camera"></i> Mark Attendance
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted my-4">You don't have any classes assigned yet.</p>
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
                        <a href="<?php echo BASE_URL; ?>/attendance/mark.php" class="btn btn-outline-primary btn-lg d-block mb-3">
                            <i class="bi bi-camera"></i> Mark Attendance
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo BASE_URL; ?>/attendance/manual.php" class="btn btn-outline-primary btn-lg d-block mb-3">
                            <i class="bi bi-pencil"></i> Manual Entry
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Attendance</h5>
                <span class="badge bg-primary"><?php echo date('d M, Y'); ?></span>
            </div>
            <div class="card-body">
                <?php if (count($recentAttendanceRecords) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Roll Number</th>
                                    <th>Class</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Recognition Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAttendanceRecords as $record): ?>
                                    <tr>
                                        <td><?php echo $record['student_name']; ?></td>
                                        <td><?php echo $record['roll_number']; ?></td>
                                        <td>
                                            <?php 
                                                foreach ($teacherClasses as $class) {
                                                    if ($class['id'] == $record['class_id']) {
                                                        echo $class['name'];
                                                        break;
                                                    }
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo formatTime($record['time']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($record['status'] == 'present') ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($record['confidence_score'])): ?>
                                                <?php echo round($record['confidence_score'], 2); ?>%
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted my-4">No attendance records for today.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 