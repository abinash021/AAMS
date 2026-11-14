<?php
$pageTitle = "My Profile";
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

$user = new User();
$userData = $user->getById(getCurrentUserId());

$success = '';
$error = '';

// Handle profile update
if (isPostRequest()) {
    $csrfToken = getPostField('csrf_token');
    
    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $fullName = sanitize(getPostField('full_name'));
        $email = sanitize(getPostField('email'));
        $currentPassword = getPostField('current_password');
        $newPassword = getPostField('new_password');
        $confirmPassword = getPostField('confirm_password');
        
        // Validate data
        if (empty($fullName)) {
            $error = 'Full name is required.';
        } elseif (empty($email) || !isValidEmail($email)) {
            $error = 'A valid email address is required.';
        } elseif (!empty($newPassword) && empty($currentPassword)) {
            $error = 'Current password is required to change password.';
        } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
            $error = 'New password and confirm password do not match.';
        } elseif (!empty($currentPassword)) {
            // Verify current password
            $checkUser = $user->authenticate($userData['username'], $currentPassword);
            
            if (!$checkUser) {
                $error = 'Current password is incorrect.';
            }
        }
        
        // Update profile if no errors
        if (empty($error)) {
            $updateData = [
                'full_name' => $fullName,
                'email' => $email
            ];
            
            // Add new password if provided
            if (!empty($newPassword)) {
                $updateData['password'] = $newPassword;
            }
            
            $result = $user->update(getCurrentUserId(), $updateData);
            
            if ($result) {
                // Update session data
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
                
                $success = 'Profile updated successfully.';
                
                // Refresh user data
                $userData = $user->getById(getCurrentUserId());
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// Get student data if user is a student
$studentData = null;
if (isStudent()) {
    $student = new Student();
    $studentData = $student->getByUserId(getCurrentUserId());
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4><i class="bi bi-person-circle"></i> My Profile</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-3 text-center">
                        <?php if ($studentData && !empty($studentData['face_image_path']) && file_exists($studentData['face_image_path'])): ?>
                            <img src="<?php echo BASE_URL . '/' . $studentData['face_image_path']; ?>" alt="Profile" class="profile-image">
                        <?php else: ?>
                            <div class="profile-image d-flex align-items-center justify-content-center bg-light">
                                <i class="bi bi-person" style="font-size: 5rem; color: #6c757d;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-9">
                        <h3><?php echo $userData['full_name']; ?></h3>
                        <p class="text-muted">
                            <span class="badge bg-primary"><?php echo ucfirst($userData['role_name']); ?></span>
                            <span class="ms-2"><?php echo $userData['email']; ?></span>
                        </p>
                        
                        <?php if ($studentData): ?>
                            <div class="mt-3">
                                <p><strong>Roll Number:</strong> <?php echo $studentData['roll_number']; ?></p>
                                <p><strong>Branch:</strong> <?php echo $studentData['branch_name']; ?></p>
                                <p><strong>Semester:</strong> <?php echo $studentData['semester_name']; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <form method="post">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo $userData['username']; ?>" readonly disabled>
                        <div class="form-text">Username cannot be changed.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $userData['full_name']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $userData['email']; ?>" required>
                    </div>
                    
                    <hr class="my-4">
                    <h5>Change Password</h5>
                    <p class="text-muted">Leave these fields blank if you don't want to change your password.</p>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 