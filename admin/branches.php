<?php
$pageTitle = "Manage Branches";
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
$branchId = getQueryParam('id', null);

// Process actions
$error = '';
$success = '';
$branchData = [];

// Handle form submission
if (isPostRequest()) {
    $csrfToken = getPostField('csrf_token');
    
    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Extract fields
        $name = sanitize(getPostField('name'));
        $description = sanitize(getPostField('description'));
        
        // Validate fields
        if (empty($name)) {
            $error = 'Branch name is required.';
        } else {
            if ($action === 'add') {
                // Create new branch
                $result = $academic->createBranch($name, $description);
                
                if ($result) {
                    setFlashMessage('Branch added successfully.', 'success');
                    redirect(BASE_URL . '/admin/branches.php');
                } else {
                    $error = 'Failed to create branch. Branch name may already exist.';
                }
            } elseif ($action === 'edit' && $branchId) {
                // Update branch
                $result = $academic->updateBranch($branchId, $name, $description);
                
                if ($result) {
                    setFlashMessage('Branch updated successfully.', 'success');
                    redirect(BASE_URL . '/admin/branches.php');
                } else {
                    $error = 'Failed to update branch.';
                }
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && $branchId) {
    $result = $academic->deleteBranch($branchId);
    
    if ($result) {
        setFlashMessage('Branch deleted successfully.', 'success');
    } else {
        setFlashMessage('Failed to delete branch. It may be in use by semesters.', 'danger');
    }
    
    redirect(BASE_URL . '/admin/branches.php');
}

// Get data for edit action
if ($action === 'edit' && $branchId) {
    $branchData = $academic->getBranchById($branchId);
    
    if (!$branchData) {
        setFlashMessage('Branch not found.', 'danger');
        redirect(BASE_URL . '/admin/branches.php');
    }
}

// Get all branches for list action
$branches = ($action === 'list') ? $academic->getAllBranches() : [];
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="bi bi-diagram-3"></i> Branches</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if ($action === 'list'): ?>
            <a href="<?php echo BASE_URL; ?>/admin/branches.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Branch
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
            <h5 class="mb-0">All Branches</h5>
        </div>
        <div class="card-body">
            <?php if (count($branches) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($branches as $branch): ?>
                                <tr>
                                    <td><?php echo $branch['name']; ?></td>
                                    <td><?php echo $branch['description'] ? $branch['description'] : 'No description'; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/admin/branches.php?action=edit&id=<?php echo $branch['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/branches.php?action=delete&id=<?php echo $branch['id']; ?>" class="btn btn-sm btn-outline-danger confirm-action" data-confirm-message="Are you sure you want to delete this branch? This will also delete related semesters and classes.">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted my-4">No branches found.</p>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Add/Edit Branch Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><?php echo ($action === 'add') ? 'Add New Branch' : 'Edit Branch'; ?></h5>
        </div>
        <div class="card-body">
            <form method="post">
                <?php echo csrfField(); ?>
                
                <div class="mb-3">
                    <label for="name" class="form-label">Branch Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $branchData['name'] ?? ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $branchData['description'] ?? ''; ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>/admin/branches.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> <?php echo ($action === 'add') ? 'Add Branch' : 'Save Changes'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?> 