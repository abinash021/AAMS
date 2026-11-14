<?php
$pageTitle = "Register Student";
require_once '../includes/header.php';

// Check if user is logged in and is an admin or teacher
if (!isLoggedIn() || (!isAdmin() && !isTeacher())) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize classes
$user = new User();
$student = new Student();
$academic = new Academic();

// Get all semesters for dropdown
$semesters = $academic->getAllSemesters();

// Set default variables
$error = '';
$success = '';
$faceImagePath = '';
$faceImagePreview = '';

// Handle form submission
if (isPostRequest()) {
    $csrfToken = getPostField('csrf_token');
    
    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Extract fields
        $username = sanitize(getPostField('username'));
        $email = sanitize(getPostField('email'));
        $password = getPostField('password');
        $confirmPassword = getPostField('confirm_password');
        $fullName = sanitize(getPostField('full_name'));
        $rollNumber = sanitize(getPostField('roll_number'));
        $semesterId = getPostField('semester_id');
        
        // Handle face image
        $faceImageData = getPostField('image_data');
        
        // Validate common fields
        if (empty($username) || empty($email) || empty($password) || empty($fullName) || empty($rollNumber) || empty($semesterId)) {
            $error = 'All fields are required.';
        } elseif (!isValidEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (empty($faceImageData) && !isset($_FILES['face_image'])) {
            $error = 'Face image is required.';
        } else {
            // Process face image (from webcam or file upload)
            $faceImagePath = '';
            
            // Create upload directory if not exists
            if (!is_dir(STUDENT_PHOTOS_DIR)) {
                mkdir(STUDENT_PHOTOS_DIR, 0755, true);
            }
            
            if (!empty($faceImageData)) {
                // Image captured from webcam
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $faceImageData));
                $imageName = 'student_' . time() . '_' . uniqid() . '.jpg';
                $faceImagePath = STUDENT_PHOTOS_DIR . $imageName;
                
                file_put_contents($faceImagePath, $imageData);
                
            } elseif (isset($_FILES['face_image']) && $_FILES['face_image']['error'] === UPLOAD_ERR_OK) {
                // Image uploaded from file
                $uploadResult = uploadImage($_FILES['face_image'], STUDENT_PHOTOS_DIR);
                
                if ($uploadResult['success']) {
                    $faceImagePath = $uploadResult['path'];
                } else {
                    $error = $uploadResult['error'];
                }
            }
            
            // If no errors and image processed, register the student
            if (empty($error) && !empty($faceImagePath)) {
                // Create user account
                $userId = $user->create($username, $email, $password, $fullName, User::ROLE_STUDENT);
                
                if ($userId) {
                    // Register student with user ID
                    $studentResult = $student->register($userId, $rollNumber, $semesterId, $faceImagePath);
                    
                    if ($studentResult) {
                        setFlashMessage('Student registered successfully.', 'success');
                        redirect(BASE_URL . '/admin/users.php?role=student');
                    } else {
                        // Delete user if student registration failed
                        $user->delete($userId);
                        $error = 'Failed to register student. Please try again.';
                    }
                } else {
                    $error = 'Failed to create user account. Username or email may already exist.';
                }
            }
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-person-plus"></i> Register New Student</h2>
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

<?php if (count($semesters) === 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> You need to create at least one semester before registering students.
        <a href="<?php echo BASE_URL; ?>/admin/semesters.php?action=add" class="btn btn-sm btn-warning ms-2">Add Semester</a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Student Information</h5>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="loading-on-submit">
                <?php echo csrfField(); ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3">Account Details</h5>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <h5 class="mb-3 mt-4">Student Details</h5>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="roll_number" class="form-label">Roll Number</label>
                            <input type="text" class="form-control" id="roll_number" name="roll_number" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="semester_id" class="form-label">Semester</label>
                            <select class="form-select" id="semester_id" name="semester_id" required>
                                <option value="">Select Semester</option>
                                <?php foreach ($semesters as $semester): ?>
                                    <option value="<?php echo $semester['id']; ?>">
                                        <?php echo $semester['name']; ?> - <?php echo $semester['branch_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 class="mb-3">Capture Face Image</h5>
                        <p class="text-muted small">Capture a clear facial image of the student for attendance recognition.</p>
                        
                        <div class="tab-content" id="imageCaptureTabs">
                            <div class="tab-pane fade show active" id="webcam-tab" role="tabpanel">
                                <div class="webcam-container">
                                    <video id="webcam" class="w-100 rounded" autoplay playsinline></video>
                                    <canvas id="canvas" class="w-100 rounded" style="display: none;"></canvas>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <div id="status" class="alert alert-info">
                                        Allow camera access to capture student's face.
                                    </div>
                                    
                                    <div>
                                        <button type="button" id="snap-button" class="btn btn-primary" disabled>
                                            <i class="bi bi-camera"></i> Capture
                                        </button>
                                        <button type="button" id="retake-button" class="btn btn-secondary" style="display: none;">
                                            <i class="bi bi-arrow-counterclockwise"></i> Retake
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="<?php echo BASE_URL; ?>/admin/users.php?role=student" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button type="submit" id="submit-button" class="btn btn-primary" disabled>
                        <i class="bi bi-save"></i> Register Student
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php 
// Add extra JavaScript for webcam handling
$extraJs = '<script>
    document.addEventListener("DOMContentLoaded", function() {
        setupWebcam();
    });
</script>';
?>

<?php require_once '../includes/footer.php'; ?> 