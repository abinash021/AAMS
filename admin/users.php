<?php
$pageTitle = "Manage Users";
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize User class
$user = new User();
$student = new Student();
$academic = new Academic();

// Get role parameter
$role = getQueryParam('role', 'teacher');
$action = getQueryParam('action', 'list');
$userId = getQueryParam('id', null);

// Validate role
if (!in_array($role, ['teacher', 'student'])) {
    $role = 'teacher';
}

// Set role ID based on role string
$roleId = ($role === 'teacher') ? User::ROLE_TEACHER : User::ROLE_STUDENT;

// Process actions
$error = '';
$success = '';
$userData = [];
$semesters = [];
$faceImagePath = '';

if ($role === 'student' && $action !== 'delete') {
    // Get semesters for student registration/edit
    $semesters = $academic->getAllSemesters();
}

// Handle form submission
if (isPostRequest()) {
    $csrfToken = getPostField('csrf_token');
    
    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Extract common fields
        $username = sanitize(getPostField('username'));
        $email = sanitize(getPostField('email'));
        $password = getPostField('password');
        $confirmPassword = getPostField('confirm_password');
        $fullName = sanitize(getPostField('full_name'));
        
        // Role-specific fields
        $semesterId = ($role === 'student') ? getPostField('semester_id') : null;
        $rollNumber = ($role === 'student') ? sanitize(getPostField('roll_number')) : null;
        
        // Handle face image for student
        $faceImageData = ($role === 'student') ? getPostField('image_data') : null;
        
        // Validate common fields
        if (empty($username) || empty($email) || empty($fullName)) {
            $error = 'All fields are required.';
        } elseif (!isValidEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif ($action === 'add' && empty($password)) {
            $error = 'Password is required for new users.';
        } elseif (!empty($password) && $password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } 
        // Role-specific validation
        elseif ($role === 'student' && empty($rollNumber)) {
            $error = 'Roll number is required.';
        } elseif ($role === 'student' && empty($semesterId)) {
            $error = 'Semester is required.';
        } elseif ($role === 'student' && $action === 'add' && empty($faceImageData) && !isset($_FILES['face_image'])) {
            $error = 'Face image is required for student registration.';
        }
        
        // Process face image for student
        if ($role === 'student' && $action === 'add' && empty($error)) {
            // Create upload directory if not exists
            if (!is_dir(STUDENT_PHOTOS_DIR)) {
                mkdir(STUDENT_PHOTOS_DIR, 0755, true);
            }
            
            if (!empty($faceImageData)) {
                // Image captured from webcam
                $imageData = base64_decode(str_replace('data:image/jpeg;base64,', '', $faceImageData));
                $imageName = 'student_' . time() . '_' . uniqid() . '.jpg';
                $faceImagePath = STUDENT_PHOTOS_DIR . $imageName;
                
                file_put_contents($faceImagePath, $imageData);
                
            } elseif (isset($_FILES['face_image']) && $_FILES['face_image']['error'] === UPLOAD_ERR_OK) {
                // Image uploaded from file
                $uploadedPath = uploadImage($_FILES['face_image'], STUDENT_PHOTOS_DIR);
                
                if ($uploadedPath !== false) {
                    $faceImagePath = $uploadedPath;
                } else {
                    $error = 'Failed to upload image. Please try again.';
                }
            }
        }
        
        // If no errors, process the form
        if (empty($error)) {
            if ($action === 'add') {
                // Create new user
                $userId = $user->create($username, $email, $password, $fullName, $roleId);
                
                if ($userId) {
                    if ($role === 'student') {
                        // Register student with the user
                        $studentResult = $student->register($userId, $rollNumber, $semesterId, $faceImagePath);
                        
                        if ($studentResult) {
                            $success = 'Student added successfully.';
                            redirect(BASE_URL . '/admin/users.php?role=student');
                        } else {
                            // Delete user if student registration failed
                            $user->delete($userId);
                            $error = 'Failed to create student profile. Face recognition registration may have failed.';
                        }
                    } else {
                        $success = 'Teacher added successfully.';
                        redirect(BASE_URL . '/admin/users.php?role=teacher');
                    }
                } else {
                    $error = 'Failed to create user. Username or email may already exist.';
                }
            } elseif ($action === 'edit' && $userId) {
                // Update user
                $updateData = [
                    'full_name' => $fullName,
                    'email' => $email
                ];
                
                if (!empty($password)) {
                    $updateData['password'] = $password;
                }
                
                $result = $user->update($userId, $updateData);
                
                if ($result) {
                    if ($role === 'student') {
                        // Get student by user ID
                        $studentData = $student->getByUserId($userId);
                        
                        if ($studentData) {
                            // Update student
                            $studentUpdateData = [
                                'roll_number' => $rollNumber,
                                'semester_id' => $semesterId
                            ];
                            
                            // If new face image provided for edit
                            if (!empty($faceImagePath)) {
                                $studentUpdateData['face_image_path'] = $faceImagePath;
                            }
                            
                            $studentResult = $student->update($studentData['id'], $studentUpdateData);
                            
                            if ($studentResult) {
                                $success = 'Student updated successfully.';
                                redirect(BASE_URL . '/admin/users.php?role=student');
                            } else {
                                $error = 'Failed to update student profile.';
                            }
                        } else {
                            $error = 'Student profile not found.';
                        }
                    } else {
                        $success = 'Teacher updated successfully.';
                        redirect(BASE_URL . '/admin/users.php?role=teacher');
                    }
                } else {
                    $error = 'Failed to update user.';
                }
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && $userId) {
    if ($role === 'student') {
        // Get student by user ID
        $studentData = $student->getByUserId($userId);
        
        if ($studentData) {
            // Delete student
            $student->delete($studentData['id']);
        }
    }
    
    // Delete user
    $result = $user->delete($userId);
    
    if ($result) {
        setFlashMessage(ucfirst($role) . ' deleted successfully.', 'success');
    } else {
        setFlashMessage('Failed to delete ' . $role . '.', 'danger');
    }
    
    redirect(BASE_URL . '/admin/users.php?role=' . $role);
}

// Get data for edit action
if ($action === 'edit' && $userId) {
    $userData = $user->getById($userId);
    
    if (!$userData || $userData['role_id'] != $roleId) {
        setFlashMessage('User not found.', 'danger');
        redirect(BASE_URL . '/admin/users.php?role=' . $role);
    }
    
    if ($role === 'student') {
        $studentData = $student->getByUserId($userId);
        
        if (!$studentData) {
            setFlashMessage('Student profile not found.', 'danger');
            redirect(BASE_URL . '/admin/users.php?role=student');
        }
    }
}

// Get all users for list action
$users = ($action === 'list') ? $user->getAllByRole($roleId) : [];
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>
            <i class="bi bi-people"></i> 
            <?php echo ($role === 'teacher') ? 'Teachers' : 'Students'; ?>
        </h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if ($action === 'list'): ?>
            <?php if ($role === 'student'): ?>
                <a href="<?php echo BASE_URL; ?>/admin/mass-students.php" class="btn btn-primary me-2">
                    <i class="bi bi-people-fill"></i> Mass Registration
                </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/admin/users.php?action=add&role=<?php echo $role; ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add <?php echo ucfirst($role); ?>
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/admin/users.php?role=<?php echo $role; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
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

<?php if ($action === 'list'): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <?php echo ($role === 'teacher') ? 'All Teachers' : 'All Students'; ?>
            </h5>
            <a href="<?php echo BASE_URL; ?>/admin/users.php?action=add&role=<?php echo $role; ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> 
                Add <?php echo ucfirst($role); ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (count($users) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <?php if ($role === 'student'): ?>
                                    <th>Roll Number</th>
                                    <th>Semester</th>
                                <?php endif; ?>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): 
                                $studentDetails = ($role === 'student') ? $student->getByUserId($u['id']) : null;
                            ?>
                                <tr>
                                    <td><?php echo $u['full_name']; ?></td>
                                    <td><?php echo $u['username']; ?></td>
                                    <td><?php echo $u['email']; ?></td>
                                    <?php if ($role === 'student'): ?>
                                        <td><?php echo $studentDetails ? $studentDetails['roll_number'] : 'N/A'; ?></td>
                                        <td><?php echo $studentDetails ? $studentDetails['semester_name'] : 'N/A'; ?></td>
                                    <?php endif; ?>
                                    <td><?php echo formatDate($u['created_at']); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/admin/users.php?action=edit&role=<?php echo $role; ?>&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/users.php?action=delete&role=<?php echo $role; ?>&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger confirm-action" data-confirm-message="Are you sure you want to delete this <?php echo $role; ?>?">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted my-4">
                    No <?php echo ($role === 'teacher') ? 'teachers' : 'students'; ?> found.
                </p>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Add/Edit User Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <?php echo ($action === 'add') ? 'Add New ' : 'Edit '; ?>
                <?php echo ucfirst($role); ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                
                <div class="row">
                    <div class="col-md-<?php echo ($role === 'student' && $action === 'add') ? '6' : '12'; ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo $userData['username'] ?? ''; ?>" <?php echo ($action === 'edit') ? 'readonly' : 'required'; ?>>
                                <?php if ($action === 'edit'): ?>
                                    <div class="form-text">Username cannot be changed.</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $userData['email'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $userData['full_name'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <?php if ($role === 'student'): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="roll_number" class="form-label">Roll Number</label>
                                    <input type="text" class="form-control" id="roll_number" name="roll_number" value="<?php echo $studentData['roll_number'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="semester_id" class="form-label">Semester</label>
                                    <select class="form-select" id="semester_id" name="semester_id" required>
                                        <option value="">Select Semester</option>
                                        <?php foreach ($semesters as $sem): ?>
                                            <option value="<?php echo $sem['id']; ?>" <?php echo (isset($studentData['semester_id']) && $studentData['semester_id'] == $sem['id']) ? 'selected' : ''; ?>>
                                                <?php echo $sem['name']; ?> - <?php echo $sem['branch_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">
                                    <?php echo ($action === 'add') ? 'Password' : 'New Password'; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" <?php echo ($action === 'add') ? 'required' : ''; ?>>
                                <?php if ($action === 'edit'): ?>
                                    <div class="form-text">Leave blank to keep current password.</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" <?php echo ($action === 'add') ? 'required' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($role === 'student' && $action === 'add'): ?>
                        <div class="col-md-6">
                            <h5 class="mb-3">Capture Face Image</h5>
                            <p class="text-muted small">Capture a clear facial image of the student for attendance recognition.</p>
                            
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
                            
                            <input type="hidden" name="image_data" id="image_data">
                            
                            <div class="mt-3 text-center">
                                <p class="text-muted">OR</p>
                                <label for="face_image" class="form-label">Upload face image</label>
                                <input type="file" class="form-control" id="face_image" name="face_image" accept="image/*">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="<?php echo BASE_URL; ?>/admin/users.php?role=<?php echo $role; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button type="submit" id="submit-button" class="btn btn-primary" <?php echo ($role === 'student' && $action === 'add') ? 'disabled' : ''; ?>>
                        <i class="bi bi-save"></i> <?php echo ($action === 'add') ? 'Add ' . ucfirst($role) : 'Save Changes'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($role === 'student' && $action === 'add'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('webcam');
            const canvas = document.getElementById('canvas');
            const snapButton = document.getElementById('snap-button');
            const retakeButton = document.getElementById('retake-button');
            const statusElement = document.getElementById('status');
            const imageDataInput = document.getElementById('image_data');
            const submitButton = document.getElementById('submit-button');
            const fileUpload = document.getElementById('face_image');
            
            let stream = null;
            
            // Start webcam
            function startWebcam() {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(function(s) {
                        stream = s;
                        video.srcObject = stream;
                        statusElement.textContent = 'Camera is ready. Click "Capture" to take a photo.';
                        statusElement.className = 'alert alert-success';
                        snapButton.disabled = false;
                    })
                    .catch(function(error) {
                        console.error('Error accessing webcam:', error);
                        statusElement.textContent = 'Error accessing webcam. Please upload an image file instead.';
                        statusElement.className = 'alert alert-danger';
                    });
            }
            
            // Stop webcam
            function stopWebcam() {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
            }
            
            // Capture image
            snapButton.addEventListener('click', function() {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                
                // Hide video, show canvas
                video.style.display = 'none';
                canvas.style.display = 'block';
                
                // Hide capture button, show retake button
                snapButton.style.display = 'none';
                retakeButton.style.display = 'inline-block';
                
                // Set image data and enable submit
                imageDataInput.value = canvas.toDataURL('image/jpeg');
                submitButton.disabled = false;
                
                // Disable file upload
                fileUpload.disabled = true;
                
                statusElement.textContent = 'Image captured! Click "Register Student" to proceed or "Retake" for a new photo.';
            });
            
            // Retake image
            retakeButton.addEventListener('click', function() {
                // Show video, hide canvas
                video.style.display = 'block';
                canvas.style.display = 'none';
                
                // Show capture button, hide retake button
                snapButton.style.display = 'inline-block';
                retakeButton.style.display = 'none';
                
                // Clear image data
                imageDataInput.value = '';
                submitButton.disabled = true;
                
                // Enable file upload
                fileUpload.disabled = false;
                
                statusElement.textContent = 'Camera is ready. Click "Capture" to take a photo.';
            });
            
            // Handle file upload
            fileUpload.addEventListener('change', function() {
                if (fileUpload.files && fileUpload.files[0]) {
                    stopWebcam();
                    
                    // Hide webcam elements
                    video.style.display = 'none';
                    canvas.style.display = 'none';
                    snapButton.style.display = 'none';
                    retakeButton.style.display = 'none';
                    
                    // Enable submit button
                    submitButton.disabled = false;
                    
                    statusElement.textContent = 'Image file selected. Click "Register Student" to proceed.';
                    statusElement.className = 'alert alert-success';
                }
            });
            
            // Start webcam on page load
            startWebcam();
        });
    </script>
    <?php endif; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?> 