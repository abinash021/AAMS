<?php
$pageTitle = "Class Students";
require_once '../includes/header.php';

// Check if user is logged in and is a teacher or admin
if (!isLoggedIn() || (!isTeacher() && !isAdmin())) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize classes
$student = new Student();
$academic = new Academic();

// Validate semester_id parameter
if (!isset($_GET['semester_id']) || !is_numeric($_GET['semester_id'])) {
    setFlashMessage('Invalid semester specified.', 'danger');
    redirect(BASE_URL . '/teacher/dashboard.php');
}

$semesterId = (int)$_GET['semester_id'];
$semesterInfo = $academic->getSemesterById($semesterId);

if (!$semesterInfo) {
    setFlashMessage('Semester not found.', 'danger');
    redirect(BASE_URL . '/teacher/dashboard.php');
}

// Get students in this semester
$semesterStudents = $student->getBySemester($semesterId);

// Add error logging
error_log("Fetching students for semester ID: " . $semesterId);
error_log("Number of students found: " . count($semesterStudents));

// For teachers, verify they teach this semester
if (isTeacher() && !isAdmin()) {
    $teacherId = getCurrentUserId();
    $teacherClasses = $academic->getClassesByTeacher($teacherId);
    $hasAccess = false;
    
    foreach ($teacherClasses as $class) {
        if ($class['semester_id'] == $semesterId) {
            $hasAccess = true;
            break;
        }
    }
    
    if (!$hasAccess) {
        setFlashMessage('You do not have permission to view students in this semester.', 'danger');
        redirect(BASE_URL . '/teacher/dashboard.php');
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-people"></i> Students - <?php echo $semesterInfo['branch_name']; ?> (<?php echo $semesterInfo['name']; ?>)</h2>
        <p class="text-muted">Viewing all students enrolled in this semester.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Student List</h5>
                <div>
                    <a href="<?php echo BASE_URL; ?>/teacher/classes.php" class="btn btn-sm btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Classes
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="<?php echo BASE_URL; ?>/students/register.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-person-plus"></i> Add Student
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($semesterStudents) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="studentsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Roll Number</th>
                                    <th>Email</th>
                                    <th>Face Image</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($semesterStudents as $student): ?>
                                    <tr>
                                        <td><?php echo $student['id']; ?></td>
                                        <td><?php echo $student['full_name']; ?></td>
                                        <td><?php echo $student['roll_number']; ?></td>
                                        <td><?php echo $student['email']; ?></td>
                                        <td>
                                            <?php if (!empty($student['face_image_path']) && file_exists("../" . $student['face_image_path'])): ?>
                                                <img src="<?php echo BASE_URL . '/' . $student['face_image_path']; ?>" 
                                                     alt="Face Image" 
                                                     class="img-thumbnail" 
                                                     style="max-width: 50px; max-height: 50px;">
                                            <?php else: ?>
                                                <span class="badge bg-warning">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?php echo BASE_URL; ?>/attendance/report.php?student_id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="bi bi-file-text"></i> Attendance
                                                </a>
                                                <?php if (isAdmin()): ?>
                                                <a href="<?php echo BASE_URL; ?>/students/edit.php?id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No students found in this semester.
                        <?php if (isAdmin()): ?>
                            <a href="<?php echo BASE_URL; ?>/students/register.php" class="alert-link">Register students</a> to get started.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#studentsTable').DataTable({
            responsive: true
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?> 