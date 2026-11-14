<?php
$pageTitle = "Manage Classes";
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize classes
$academic = new Academic();
$user = new User();

// Get action and ID parameters
$action = getQueryParam('action', 'list');
$classId = getQueryParam('id', null);

// Process actions
$error = '';
$success = '';
$classData = [];

// Get semesters and teachers for dropdowns
$semesters = $academic->getAllSemesters();
$teachers = $user->getAllByRole(User::ROLE_TEACHER);

// Handle form submission
if (isPostRequest()) {
    $csrfToken = getPostField('csrf_token');
    
    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Extract fields
        $name = sanitize(getPostField('name'));
        $semesterId = getPostField('semester_id');
        $teacherId = getPostField('teacher_id');
        
        // Validate fields
        if (empty($name)) {
            $error = 'Class name is required.';
        } elseif (empty($semesterId)) {
            $error = 'Semester is required.';
        } elseif (empty($teacherId)) {
            $error = 'Teacher is required.';
        } else {
            if ($action === 'add') {
                // Create new class
                $result = $academic->createClass($name, $semesterId, $teacherId);
                
                if ($result) {
                    setFlashMessage('Class added successfully.', 'success');
                    redirect(BASE_URL . '/admin/classes.php');
                } else {
                    $error = 'Failed to create class. Class name may already exist in this semester.';
                }
            } elseif ($action === 'edit' && $classId) {
                // Update class
                $result = $academic->updateClass($classId, $name, $semesterId, $teacherId);
                
                if ($result) {
                    setFlashMessage('Class updated successfully.', 'success');
                    redirect(BASE_URL . '/admin/classes.php');
                } else {
                    $error = 'Failed to update class.';
                }
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && $classId) {
    $result = $academic->deleteClass($classId);
    
    if ($result) {
        setFlashMessage('Class deleted successfully.', 'success');
    } else {
        setFlashMessage('Failed to delete class. It may have attendance records.', 'danger');
    }
    
    redirect(BASE_URL . '/admin/classes.php');
}

// Get data for edit action
if ($action === 'edit' && $classId) {
    $classData = $academic->getClassById($classId);
    
    if (!$classData) {
        setFlashMessage('Class not found.', 'danger');
        redirect(BASE_URL . '/admin/classes.php');
    }
}

// Get all classes for list action
$classes = ($action === 'list') ? $academic->getAllClasses() : [];
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="bi bi-book"></i> Classes</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if ($action === 'list'): ?>
            <a href="<?php echo BASE_URL; ?>/admin/classes.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Class
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php
// Show flash message
$flashMessage = getFlashMessage();
if ($flashMessage): 
?>
    <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show">
        <?php echo $flashMessage['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Classes</h5>
        </div>
        <div class="card-body">
            <?php if (count($classes) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Semester</th>
                                <th>Branch</th>
                                <th>Teacher</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td><?php echo $class['name']; ?></td>
                                    <td><?php echo $class['semester_name']; ?></td>
                                    <td><?php echo $class['branch_name']; ?></td>
                                    <td><?php echo $class['teacher_name']; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/admin/classes.php?action=edit&id=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/classes.php?action=delete&id=<?php echo $class['id']; ?>" class="btn btn-sm btn-outline-danger confirm-action" data-confirm-message="Are you sure you want to delete this class? This will also delete all associated attendance records.">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted my-4">No classes found.</p>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Add/Edit Class Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><?php echo ($action === 'add') ? 'Add New Class' : 'Edit Class'; ?></h5>
        </div>
        <div class="card-body">
            <?php if (count($semesters) === 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> You need to create at least one semester before adding a class.
                    <a href="<?php echo BASE_URL; ?>/admin/semesters.php?action=add" class="btn btn-sm btn-warning ms-2">Add Semester</a>
                </div>
            <?php elseif (count($teachers) === 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> You need to add at least one teacher before creating a class.
                    <a href="<?php echo BASE_URL; ?>/admin/users.php?action=add&role=teacher" class="btn btn-sm btn-warning ms-2">Add Teacher</a>
                </div>
            <?php else: ?>
                <form method="post">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $classData['name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="semester_id" class="form-label">Semester</label>
                        <select class="form-select" id="semester_id" name="semester_id" required>
                            <option value="">Select Semester</option>
                            <?php foreach ($semesters as $semester): ?>
                                <option value="<?php echo $semester['id']; ?>" <?php echo (isset($classData['semester_id']) && $classData['semester_id'] == $semester['id']) ? 'selected' : ''; ?>>
                                    <?php echo $semester['name']; ?> - <?php echo $semester['branch_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="teacher_id" class="form-label">Teacher</label>
                        <select class="form-select" id="teacher_id" name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>" <?php echo (isset($classData['teacher_id']) && $classData['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                    <?php echo $teacher['full_name']; ?> (<?php echo $teacher['email']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>/admin/classes.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> <?php echo ($action === 'add') ? 'Add Class' : 'Save Changes'; ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?> 