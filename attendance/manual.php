<?php
$pageTitle = "Manual Attendance";
require_once '../includes/header.php';

// Check if user is logged in and is a teacher or admin
if (!isLoggedIn() || (!isTeacher() && !isAdmin())) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize classes
$academic = new Academic();
$attendance = new Attendance();
$student = new Student();

// Get classes - all classes for admin, or classes taught by the teacher
$teacherClasses = isAdmin() ? $academic->getAllClasses() : $academic->getClassesByTeacher(getCurrentUserId());

// Check if any classes exist
if (empty($teacherClasses)) {
    setFlashMessage('No classes available in the system.', 'warning');
    redirect(BASE_URL . '/'.  (isAdmin() ? 'admin' : 'teacher') .'/dashboard.php');
}

// Get selected class
$classId = getQueryParam('class_id', $teacherClasses[0]['id'] ?? null);

// Check if a valid class is selected
$selectedClass = null;
foreach ($teacherClasses as $class) {
    if ($class['id'] == $classId) {
        $selectedClass = $class;
        break;
    }
}

// If no valid class selected, use the first one
if (!$selectedClass && !empty($teacherClasses)) {
    $selectedClass = $teacherClasses[0];
    $classId = $selectedClass['id'];
}

// Get students in the selected class's semester
$studentsInSemester = [];
if ($selectedClass) {
    $studentsInSemester = $student->getStudentsBySemesterId($selectedClass['semester_id']);
}

// Get today's attendance for the selected class
$todayAttendance = [];
if ($selectedClass) {
    $todayAttendance = $attendance->getForClass($selectedClass['id']);
}

// Organize attendance by student ID for easy checking
$attendanceByStudent = [];
foreach ($todayAttendance as $record) {
    $attendanceByStudent[$record['student_id']] = $record;
}

// Process attendance submission
$success = '';
$error = '';

if (isPostRequest()) {
    $csrfToken = getPostField('csrf_token');
    $studentStatuses = getPostField('student_status', []);
    $selectedClassId = getPostField('class_id');
    
    // Validate CSRF token
    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid form submission. Please try again.';
    } elseif (empty($selectedClassId)) {
        $error = 'No class selected. Please select a class and try again.';
    } elseif (empty($studentStatuses)) {
        $error = 'No attendance data received. Please mark attendance for at least one student.';
    } else {
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($studentStatuses as $studentId => $status) {
            // Skip if student already has attendance record for today
            if (isset($attendanceByStudent[$studentId])) {
                continue;
            }
            
            // Mark attendance
            $result = $attendance->markManually(
                $studentId,
                $selectedClassId,
                $status,
                getCurrentUserId()
            );
            
            if ($result) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        if ($successCount > 0) {
            setFlashMessage("Attendance marked successfully for $successCount students." . 
                ($errorCount > 0 ? " Failed for $errorCount students." : ""), 
                $errorCount > 0 ? 'warning' : 'success');
            redirect($_SERVER['REQUEST_URI']);
        } else {
            $error = 'No new attendance records were created. Students may already have attendance marked for today.';
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-pencil-square"></i> Manual Attendance</h2>
        <p class="text-muted">Mark attendance manually for students in the selected class.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Attendance Sheet</h5>
                <span class="badge bg-primary"><?php echo date('d M, Y'); ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($studentsInSemester)): ?>
                    <form method="post" id="attendance-form">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="class_id" value="<?php echo $selectedClass['id']; ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Roll Number</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $count = 1; ?>
                                    <?php foreach ($studentsInSemester as $student): ?>
                                        <tr>
                                            <td><?php echo $count++; ?></td>
                                            <td><?php echo $student['roll_number']; ?></td>
                                            <td><?php echo $student['full_name']; ?></td>
                                            <td>
                                                <?php if (isset($attendanceByStudent[$student['id']])): ?>
                                                    <span class="badge bg-<?php echo ($attendanceByStudent[$student['id']]['status'] == 'present') ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($attendanceByStudent[$student['id']]['status']); ?>
                                                    </span>
                                                    <small class="text-muted ms-2">
                                                        <?php echo formatTime($attendanceByStudent[$student['id']]['time']); ?>
                                                    </small>
                                                <?php else: ?>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" 
                                                            name="student_status[<?php echo $student['id']; ?>]" 
                                                            id="present_<?php echo $student['id']; ?>" 
                                                            value="present" checked>
                                                        <label class="form-check-label" for="present_<?php echo $student['id']; ?>">Present</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" 
                                                            name="student_status[<?php echo $student['id']; ?>]" 
                                                            id="absent_<?php echo $student['id']; ?>" 
                                                            value="absent">
                                                        <label class="form-check-label" for="absent_<?php echo $student['id']; ?>">Absent</label>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($attendanceByStudent[$student['id']])): ?>
                                                    <button type="button" class="btn btn-sm btn-warning" disabled>
                                                        <i class="bi bi-pencil"></i> Already Marked
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary mark-single" data-student-id="<?php echo $student['id']; ?>">
                                                        <i class="bi bi-check2"></i> Mark Only This
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Attendance
                            </button>
                            <a href="<?php echo BASE_URL; ?>/attendance/mark.php?class_id=<?php echo $selectedClass['id']; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-camera"></i> Switch to Face Recognition
                            </a>
                            <?php if (count($todayAttendance) > 0): ?>
                            <a href="<?php echo BASE_URL; ?>/attendance/report.php?class_id=<?php echo $selectedClass['id']; ?>&date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary">
                                <i class="bi bi-file-earmark-text"></i> View Full Attendance
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No students found in this semester. Please contact the administrator.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-book"></i> Select Class</h5>
            </div>
            <div class="card-body">
                <form method="get" class="mb-3">
                    <div class="input-group">
                        <select name="class_id" id="class-select" class="form-select">
                            <?php foreach ($teacherClasses as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo ($classId == $class['id']) ? 'selected' : ''; ?>>
                                    <?php echo $class['name']; ?> (<?php echo $class['semester_name']; ?> - <?php echo $class['branch_name']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Select
                        </button>
                    </div>
                </form>
                
                <?php if ($selectedClass): ?>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title"><?php echo $selectedClass['name']; ?></h6>
                            <p class="mb-0">
                                <span class="text-muted">Semester:</span> <?php echo $selectedClass['semester_name']; ?><br>
                                <span class="text-muted">Branch:</span> <?php echo $selectedClass['branch_name']; ?><br>
                                <span class="text-muted">Students:</span> <?php echo count($studentsInSemester); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Attendance Summary</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0">
                                    <span class="text-muted">Total Students:</span> <?php echo count($studentsInSemester); ?><br>
                                    <span class="text-muted">Marked Today:</span> <?php echo count($todayAttendance); ?><br>
                                    <span class="text-muted">Remaining:</span> <?php echo count($studentsInSemester) - count($todayAttendance); ?>
                                </p>
                            </div>
                            <div class="text-center">
                                <div class="progress-circle" data-value="<?php echo count($studentsInSemester) > 0 ? round((count($todayAttendance) / count($studentsInSemester)) * 100) : 0; ?>">
                                    <span class="progress-text"><?php echo count($studentsInSemester) > 0 ? round((count($todayAttendance) / count($studentsInSemester)) * 100) : 0; ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Instructions</h5>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li>Select a class from the dropdown menu.</li>
                    <li>For each student, select whether they are present or absent.</li>
                    <li>Click "Save Attendance" to save all marked attendance.</li>
                    <li>Use "Mark Only This" to mark a single student's attendance immediately.</li>
                    <li>Students who already have attendance marked for today will be shown as "Already Marked".</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php
// Add extra JavaScript
$extraJs = <<<EOT
<script>
    // Initialize when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Class selection change handler
        document.getElementById('class-select').addEventListener('change', function() {
            this.form.submit();
        });
        
        // Mark single student handlers
        document.querySelectorAll('.mark-single').forEach(function(button) {
            button.addEventListener('click', function() {
                const studentId = this.getAttribute('data-student-id');
                const status = document.querySelector('input[name="student_status[' + studentId + ']"]:checked').value;
                
                // Create a new form
                const form = document.createElement('form');
                form.method = 'post';
                
                // Add the CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('input[name="csrf_token"]').value;
                form.appendChild(csrfInput);
                
                // Add the class ID
                const classInput = document.createElement('input');
                classInput.type = 'hidden';
                classInput.name = 'class_id';
                classInput.value = document.querySelector('input[name="class_id"]').value;
                form.appendChild(classInput);
                
                // Add the student status
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'student_status[' + studentId + ']';
                statusInput.value = status;
                form.appendChild(statusInput);
                
                // Add the form to the body and submit it
                document.body.appendChild(form);
                form.submit();
            });
        });
        
        // Simple CSS for the progress circle
        const style = document.createElement('style');
        style.textContent = `
            .progress-circle {
                position: relative;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: #f0f0f0;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            
            .progress-circle::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                border-radius: 50%;
                background: conic-gradient(
                    var(--bs-success) var(--progress-percent),
                    transparent var(--progress-percent)
                );
            }
            
            .progress-circle::after {
                content: '';
                position: absolute;
                top: 5px;
                left: 5px;
                width: calc(100% - 10px);
                height: calc(100% - 10px);
                background: white;
                border-radius: 50%;
            }
            
            .progress-text {
                position: relative;
                z-index: 1;
                font-weight: bold;
                font-size: 14px;
            }
        `;
        
        document.head.appendChild(style);
        
        // Set the progress circle values
        document.querySelectorAll('.progress-circle').forEach(function(circle) {
            const value = circle.getAttribute('data-value');
            circle.style.setProperty('--progress-percent', value + '%');
        });
    });
</script>
EOT;
?>

<?php require_once '../includes/footer.php'; ?> 