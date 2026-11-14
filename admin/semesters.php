<?php
$pageTitle = "Manage Semesters";
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize Academic class
$academic = new Academic();

// Get action and ID parameters
$action = getQueryParam('action', 'list');
$semesterId = getQueryParam('id', null);

// Process actions
$error = '';
$success = '';
$semesterData = [];

// Get all branches for dropdown
$branches = $academic->getAllBranches();

// Handle form submission
if (isPostRequest()) {
    $csrfToken = getPostField('csrf_token');
    
    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Extract fields
        $name = sanitize(getPostField('name'));
        $branchId = getPostField('branch_id');
        
        // Validate fields
        if (empty($name)) {
            $error = 'Semester name is required.';
        } elseif (empty($branchId)) {
            $error = 'Branch is required.';
        } else {
            if ($action === 'add') {
                // Create new semester
                $result = $academic->createSemester($name, $branchId);
                
                if ($result) {
                    setFlashMessage('Semester added successfully.', 'success');
                    redirect(BASE_URL . '/admin/semesters.php');
                } else {
                    $error = 'Failed to create semester. Semester name may already exist in this branch.';
                }
            } elseif ($action === 'edit' && $semesterId) {
                // Update semester
                $result = $academic->updateSemester($semesterId, $name, $branchId);
                
                if ($result) {
                    setFlashMessage('Semester updated successfully.', 'success');
                    redirect(BASE_URL . '/admin/semesters.php');
                } else {
                    $error = 'Failed to update semester.';
                }
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && $semesterId) {
    $result = $academic->deleteSemester($semesterId);
    
    if ($result) {
        setFlashMessage('Semester deleted successfully.', 'success');
    } else {
        setFlashMessage('Failed to delete semester. It may be in use by classes or students.', 'danger');
    }
    
    redirect(BASE_URL . '/admin/semesters.php');
}

// Get data for edit action
if ($action === 'edit' && $semesterId) {
    $semesterData = $academic->getSemesterById($semesterId);
    
    if (!$semesterData) {
        setFlashMessage('Semester not found.', 'danger');
        redirect(BASE_URL . '/admin/semesters.php');
    }
}

// Get all semesters for list action
$semesters = ($action === 'list') ? $academic->getAllSemesters() : [];
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="bi bi-calendar3"></i> Semesters</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if ($action === 'list'): ?>
            <a href="<?php echo BASE_URL; ?>/admin/semesters.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Semester
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
            <h5 class="mb-0">All Semesters</h5>
        </div>
        <div class="card-body">
            <?php if (count($semesters) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Branch</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($semesters as $semester): ?>
                                <tr>
                                    <td><?php echo $semester['name']; ?></td>
                                    <td><?php echo $semester['branch_name']; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/admin/semesters.php?action=edit&id=<?php echo $semester['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/semesters.php?action=delete&id=<?php echo $semester['id']; ?>" class="btn btn-sm btn-outline-danger confirm-action" data-confirm-message="Are you sure you want to delete this semester? This will also delete related classes and student data.">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted my-4">No semesters found.</p>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Add/Edit Semester Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><?php echo ($action === 'add') ? 'Add New Semester' : 'Edit Semester'; ?></h5>
        </div>
        <div class="card-body">
            <?php if (count($branches) === 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> You need to create at least one branch before adding a semester.
                    <a href="<?php echo BASE_URL; ?>/admin/branches.php?action=add" class="btn btn-sm btn-warning ms-2">Add Branch</a>
                </div>
            <?php else: ?>
                <form method="post">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Semester Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $semesterData['name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="branch_id" class="form-label">Branch</label>
                        <select class="form-select" id="branch_id" name="branch_id" required>
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo (isset($semesterData['branch_id']) && $semesterData['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                                    <?php echo $branch['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>/admin/semesters.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> <?php echo ($action === 'add') ? 'Add Semester' : 'Save Changes'; ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>