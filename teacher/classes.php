<?php
$pageTitle = "My Classes";
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
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-book"></i> My Classes</h2>
        <p class="text-muted">View and manage all classes assigned to you.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assigned Classes</h5>
                <a href="<?php echo BASE_URL; ?>/teacher/dashboard.php" class="btn btn-sm btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <?php if (count($teacherClasses) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="classesTable">
                            <thead>
                                <tr>
                                    <th>Class Name</th>
                                    <th>Semester</th>
                                    <th>Branch</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teacherClasses as $class): ?>
                                    <?php 
                                        // Get students in this semester
                                        $semesterStudents = $student->getBySemester($class['semester_id']);
                                        $studentCount = count($semesterStudents);
                                    ?>
                                    <tr>
                                        <td><?php echo $class['name']; ?></td>
                                        <td><?php echo $class['semester_name']; ?></td>
                                        <td><?php echo $class['branch_name']; ?></td>
                                        <td><?php echo $studentCount; ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?php echo BASE_URL; ?>/attendance/mark.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-camera"></i> Mark Attendance
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/students/class_students.php?semester_id=<?php echo $class['semester_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-people"></i> View Students
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/attendance/report.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-secondary">
                                                    <i class="bi bi-file-text"></i> Reports
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> You don't have any classes assigned yet. Please contact the administrator.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#classesTable').DataTable({
            responsive: true
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?> 