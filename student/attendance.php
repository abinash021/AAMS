<?php
$pageTitle = "My Attendance";
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

// Process filters
$classId = getQueryParam('class_id', 'all');
$startDate = getQueryParam('start_date', date('Y-m-d', strtotime('-30 days')));
$endDate = getQueryParam('end_date', date('Y-m-d'));

// Get attendance records
$attendanceRecords = [];
if ($classId != 'all') {
    $attendanceRecords = $attendance->getForStudent($studentData['id'], $classId, $startDate, $endDate);
} else {
    $attendanceRecords = $attendance->getForStudent($studentData['id'], null, $startDate, $endDate);
}

// Get attendance stats
$stats = null;
if ($classId != 'all') {
    $stats = $attendance->getStudentStats($studentData['id'], $classId);
} else {
    $stats = $attendance->getStudentStats($studentData['id']);
}

// Calculate attendance percentage
$attendancePercentage = 0;
if ($stats && $stats['total'] > 0) {
    $attendancePercentage = round(($stats['present_count'] / $stats['total']) * 100, 2);
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-calendar-check"></i> My Attendance</h2>
        <p class="text-muted">View your attendance records and filter by class and date range.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-funnel"></i> Filter Attendance</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="class_id" class="form-label">Class</label>
                        <select name="class_id" id="class_id" class="form-select">
                            <option value="all" <?php echo ($classId == 'all') ? 'selected' : ''; ?>>All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo ($classId == $class['id']) ? 'selected' : ''; ?>>
                                    <?php echo $class['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> Attendance Summary</h5>
                <span class="text-muted">
                    <?php echo formatDate($startDate); ?> to <?php echo formatDate($endDate); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-3 mb-md-0">
                        <div class="py-3">
                            <h3><?php echo $stats['total'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Total Classes</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mb-3 mb-md-0">
                        <div class="py-3">
                            <h3 class="text-success"><?php echo $stats['present_count'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Present</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mb-3 mb-md-0">
                        <div class="py-3">
                            <h3 class="text-danger"><?php echo $stats['absent_count'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Absent</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="py-3">
                            <h3 class="<?php echo ($attendancePercentage >= 75) ? 'text-success' : (($attendancePercentage >= 60) ? 'text-warning' : 'text-danger'); ?>">
                                <?php echo $attendancePercentage; ?>%
                            </h3>
                            <p class="text-muted mb-0">Attendance Percentage</p>
                        </div>
                    </div>
                </div>
                
                <div class="progress mt-3">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: <?php echo $attendancePercentage; ?>%" 
                         aria-valuenow="<?php echo $attendancePercentage; ?>" 
                         aria-valuemin="0" aria-valuemax="100">
                        <?php echo $attendancePercentage; ?>%
                    </div>
                </div>
                
                <?php if ($attendancePercentage < 75): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle-fill"></i> 
                        Your attendance is below 75%. Please ensure regular attendance to meet the minimum requirements.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-table"></i> Attendance Records</h5>
            </div>
            <div class="card-body">
                <?php if (count($attendanceRecords) > 0): ?>
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
                                <?php foreach ($attendanceRecords as $record): ?>
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
                    <p class="text-center text-muted my-4">No attendance records found for the selected filter criteria.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 