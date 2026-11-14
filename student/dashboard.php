<?php
$pageTitle = "Student Dashboard";
require_once '../includes/header.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || (!isStudent() && !isAdmin())) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize classes
$student = new Student();
$academic = new Academic();
$attendance = new Attendance();

// Get student information
$studentData = $student->getByUserId(getCurrentUserId());

if (!$studentData) {
    setFlashMessage('Student profile not found. Please contact administrator.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Get classes for the student's semester
$classes = $academic->getClassesBySemester($studentData['semester_id']);

// Get the student's attendance records
$totalAttendance = 0;
$totalPresent = 0;
$totalAbsent = 0;
$attendanceByClass = [];

foreach ($classes as $class) {
    // Get attendance stats for this class
    $stats = $attendance->getStudentStats($studentData['id'], $class['id']);
    $attendanceByClass[$class['id']] = $stats;
    
    $totalAttendance += $stats['total'] ?? 0;
    $totalPresent += $stats['present_count'] ?? 0;
    $totalAbsent += $stats['absent_count'] ?? 0;
}

// Calculate overall attendance percentage
$attendancePercentage = ($totalAttendance > 0) ? round(($totalPresent / $totalAttendance) * 100, 2) : 0;

// Get recent attendance (last 5 entries)
$recentAttendance = $attendance->getForStudent($studentData['id']);
$recentAttendance = array_slice($recentAttendance, 0, 5);
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-speedometer2"></i> Student Dashboard</h2>
        <p class="text-muted">Welcome, <?php echo $_SESSION['full_name']; ?>! View your attendance records here.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card profile-card text-center mb-4">
            <div class="card-body">
                <?php if (!empty($studentData['face_image_path']) && file_exists($studentData['face_image_path'])): ?>
                    <img src="<?php echo BASE_URL . '/' . $studentData['face_image_path']; ?>" alt="Profile" class="student-photo">
                <?php else: ?>
                    <div class="student-photo d-flex align-items-center justify-content-center bg-light mx-auto">
                        <i class="bi bi-person" style="font-size: 3rem; color: #6c757d;"></i>
                    </div>
                <?php endif; ?>
                <h5 class="mt-3"><?php echo $studentData['full_name']; ?></h5>
                <p class="text-muted mb-1"><?php echo $studentData['roll_number']; ?></p>
                <div class="badge bg-primary mb-3"><?php echo $studentData['semester_name']; ?> - <?php echo $studentData['branch_name']; ?></div>
                
                <div class="attendance-percentage">
                    <?php if ($attendancePercentage >= 75): ?>
                        <span class="text-success"><?php echo $attendancePercentage; ?>%</span>
                    <?php elseif ($attendancePercentage >= 60): ?>
                        <span class="text-warning"><?php echo $attendancePercentage; ?>%</span>
                    <?php else: ?>
                        <span class="text-danger"><?php echo $attendancePercentage; ?>%</span>
                    <?php endif; ?>
                    <div class="text-muted small">Overall Attendance</div>
                </div>
            </div>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between">
                    <span>Total Classes:</span>
                    <strong><?php echo $totalAttendance; ?></strong>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span>Present:</span>
                    <strong class="text-success"><?php echo $totalPresent; ?></strong>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span>Absent:</span>
                    <strong class="text-danger"><?php echo $totalAbsent; ?></strong>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo BASE_URL; ?>/student/attendance.php" class="btn btn-primary w-100">
                    <i class="bi bi-file-earmark-text"></i> View Full Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Recent Attendance</h5>
            </div>
            <div class="card-body">
                <?php if (count($recentAttendance) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Class</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Marked By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAttendance as $record): ?>
                                    <tr>
                                        <td><?php echo formatDate($record['date']); ?></td>
                                        <td><?php echo $record['class_name']; ?></td>
                                        <td><?php echo formatTime($record['time']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($record['status'] == 'present') ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $record['teacher_name']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted my-4">No attendance records found.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Attendance by Class</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($classes) > 0): ?>
                            <div class="row">
                                <?php foreach ($classes as $class): ?>
                                    <?php 
                                        $classStats = $attendanceByClass[$class['id']] ?? null;
                                        $classPercentage = 0;
                                        if ($classStats && $classStats['total'] > 0) {
                                            $classPercentage = round(($classStats['present_count'] / $classStats['total']) * 100, 2);
                                        }
                                        
                                        $colorClass = 'bg-success';
                                        if ($classPercentage < 75 && $classPercentage >= 60) {
                                            $colorClass = 'bg-warning';
                                        } elseif ($classPercentage < 60) {
                                            $colorClass = 'bg-danger';
                                        }
                                    ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo $class['name']; ?></h6>
                                                <div class="progress mb-3">
                                                    <div class="progress-bar <?php echo $colorClass; ?>" role="progressbar" 
                                                         style="width: <?php echo $classPercentage; ?>%" 
                                                         aria-valuenow="<?php echo $classPercentage; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $classPercentage; ?>%
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between small">
                                                    <span>
                                                        <i class="bi bi-check-circle-fill text-success"></i> 
                                                        Present: <?php echo $classStats['present_count'] ?? 0; ?>
                                                    </span>
                                                    <span>
                                                        <i class="bi bi-x-circle-fill text-danger"></i> 
                                                        Absent: <?php echo $classStats['absent_count'] ?? 0; ?>
                                                    </span>
                                                    <span>
                                                        <i class="bi bi-calendar3"></i> 
                                                        Total: <?php echo $classStats['total'] ?? 0; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted my-4">No classes found for your semester.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 