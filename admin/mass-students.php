<?php
$pageTitle = "Mass Student Registration";
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize classes
$user = new User();
$student = new Student();
$academic = new Academic();

// Get all semesters for dropdown
$semesters = $academic->getAllSemesters();

// Process CSV upload
$success = '';
$error = '';
$results = [];

if (isPostRequest()) {
    $csrfToken = getPostField('csrf_token');
    $semesterId = getPostField('semester_id');
    
    // Validate CSRF token
    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid form submission. Please try again.';
    } elseif (empty($semesterId)) {
        $error = 'Please select a semester.';
    } elseif (!isset($_FILES['student_csv']) || $_FILES['student_csv']['error'] != UPLOAD_ERR_OK) {
        $error = 'Please upload a valid CSV file.';
    } else {
        // Process CSV file
        $csvFile = $_FILES['student_csv']['tmp_name'];
        $handle = fopen($csvFile, 'r');
        
        if (!$handle) {
            $error = 'Failed to open CSV file.';
        } else {
            // Check if student images were uploaded
            $hasImages = (isset($_FILES['student_images']) && $_FILES['student_images']['error'][0] != UPLOAD_ERR_NO_FILE);
            $uploadedImages = [];
            
            // If images were uploaded, organize them by filename (which should match roll numbers)
            if ($hasImages) {
                $imageCount = count($_FILES['student_images']['name']);
                
                for ($i = 0; $i < $imageCount; $i++) {
                    if ($_FILES['student_images']['error'][$i] == UPLOAD_ERR_OK) {
                        $fileName = $_FILES['student_images']['name'][$i];
                        $tmpName = $_FILES['student_images']['tmp_name'][$i];
                        
                        // Extract roll number from filename (assuming format: rollnumber.jpg)
                        $rollNumber = pathinfo($fileName, PATHINFO_FILENAME);
                        $uploadedImages[$rollNumber] = $tmpName;
                    }
                }
            }
            
            // Skip header row
            $headerRow = fgetcsv($handle);
            
            // Process each row in the CSV file
            $successCount = 0;
            $failCount = 0;
            $rowNum = 1; // Start counting after header row
            
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                
                // Check if the row has enough columns (name, email, roll number, password)
                if (count($row) < 4) {
                    $results[] = [
                        'row' => $rowNum,
                        'status' => 'error',
                        'message' => 'Invalid row format - not enough columns'
                    ];
                    $failCount++;
                    continue;
                }
                
                // Extract data from CSV row
                $fullName = trim($row[0]);
                $email = trim($row[1]);
                $rollNumber = trim($row[2]);
                $password = trim($row[3]);
                
                // Validate data
                if (empty($fullName) || empty($email) || empty($rollNumber) || empty($password)) {
                    $results[] = [
                        'row' => $rowNum,
                        'status' => 'error',
                        'message' => 'Missing required fields',
                        'data' => $row
                    ];
                    $failCount++;
                    continue;
                }
                
                if (!isValidEmail($email)) {
                    $results[] = [
                        'row' => $rowNum,
                        'status' => 'error',
                        'message' => 'Invalid email address',
                        'data' => $row
                    ];
                    $failCount++;
                    continue;
                }
                
                // Generate a username from roll number
                $username = $rollNumber;
                
                // Check if username already exists
                $db = Database::getInstance();
                $existingUser = $db->getRow(
                    "SELECT id FROM users WHERE username = :username",
                    [':username' => $username]
                );
                
                if ($existingUser) {
                    $results[] = [
                        'row' => $rowNum,
                        'status' => 'error',
                        'message' => 'Username already exists',
                        'data' => $row
                    ];
                    $failCount++;
                    continue;
                }
                
                // Create new user account
                $userId = $user->create($username, $email, $password, $fullName, User::ROLE_STUDENT);
                
                if (!$userId) {
                    $results[] = [
                        'row' => $rowNum,
                        'status' => 'error',
                        'message' => 'Failed to create user account',
                        'data' => $row
                    ];
                    $failCount++;
                    continue;
                }
                
                // Process face image if available
                $faceImagePath = null;
                
                if (isset($uploadedImages[$rollNumber])) {
                    // Create directory for student faces if it doesn't exist
                    $studentFaceDir = UPLOAD_DIR . 'faces/' . $rollNumber;
                    if (!is_dir($studentFaceDir)) {
                        mkdir($studentFaceDir, 0755, true);
                    }
                    
                    // Save the face image to the student's directory
                    $faceImagePath = $studentFaceDir . '/1.jpg';
                    move_uploaded_file($uploadedImages[$rollNumber], $faceImagePath);
                }
                
                // Register student with image if available
                $studentId = $student->register($userId, $rollNumber, $semesterId, $faceImagePath);
                
                if ($studentId) {
                    $results[] = [
                        'row' => $rowNum,
                        'status' => 'success',
                        'message' => 'Student registered successfully',
                        'data' => [
                            'name' => $fullName,
                            'roll_number' => $rollNumber,
                            'has_image' => !empty($faceImagePath)
                        ]
                    ];
                    $successCount++;
                } else {
                    // If student registration fails, delete the user account
                    $user->delete($userId);
                    
                    // If face image was saved, delete it
                    if ($faceImagePath && file_exists($faceImagePath)) {
                        unlink($faceImagePath);
                    }
                    
                    $results[] = [
                        'row' => $rowNum,
                        'status' => 'error',
                        'message' => 'Failed to register student (roll number may already exist)',
                        'data' => $row
                    ];
                    $failCount++;
                }
            }
            
            fclose($handle);
            
            if ($successCount > 0) {
                $success = "Successfully registered $successCount students. Failed: $failCount.";
            } else {
                $error = "Failed to register any students. Please check the error messages and try again.";
            }
        }
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-people-fill"></i> Mass Student Registration</h2>
        <p class="text-muted">Upload a CSV file to register multiple students at once. You can also upload student face images in bulk.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-upload"></i> Upload Student Data</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-3">
                        <label for="semester_id" class="form-label">Semester</label>
                        <select name="semester_id" id="semester_id" class="form-select" required>
                            <option value="">Select Semester</option>
                            <?php foreach ($semesters as $semester): ?>
                                <option value="<?php echo $semester['id']; ?>">
                                    <?php echo $semester['name']; ?> (<?php echo $semester['branch_name']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_csv" class="form-label">Student CSV File</label>
                        <input type="file" class="form-control" id="student_csv" name="student_csv" accept=".csv" required>
                        <div class="form-text">
                            CSV file should have the following columns: Full Name, Email, Roll Number, Password
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_images" class="form-label">Student Face Images (Optional)</label>
                        <input type="file" class="form-control" id="student_images" name="student_images[]" accept="image/jpeg,image/png" multiple>
                        <div class="form-text">
                            Upload student face images (JPG/PNG). The filename should match the student's roll number (e.g., "202101.jpg").
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-upload"></i> Upload and Register Students
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Instructions</h5>
            </div>
            <div class="card-body">
                <h6>CSV File Format</h6>
                <p>The CSV file should have the following format:</p>
                <pre>Full Name,Email,Roll Number,Password
John Doe,john@example.com,2021001,password123
Jane Smith,jane@example.com,2021002,password456</pre>
                
                <h6>Image Upload</h6>
                <p>For face images, name each file with the student's roll number (e.g., "2021001.jpg"). The system will automatically match images to students based on roll numbers.</p>
                
                <h6>Folder Structure</h6>
                <p>Each student's face image will be stored at: <code>uploads/faces/[roll_number]/1.jpg</code></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Upload Results</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($results)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    <th>Student</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <tr class="<?php echo ($result['status'] === 'success') ? 'table-success' : 'table-danger'; ?>">
                                        <td><?php echo $result['row']; ?></td>
                                        <td>
                                            <?php if ($result['status'] === 'success'): ?>
                                                <?php echo $result['data']['name']; ?> (<?php echo $result['data']['roll_number']; ?>)
                                                <?php if ($result['data']['has_image']): ?>
                                                    <i class="bi bi-camera-fill text-success ms-1" title="Face image uploaded"></i>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php echo isset($result['data'][0]) ? $result['data'][0] : 'N/A'; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo ($result['status'] === 'success') ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($result['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $result['message']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center my-4">
                        No upload results yet. Upload a CSV file to register students.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between">
            <a href="<?php echo BASE_URL; ?>/admin/users.php?role=student" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Student Management
            </a>
            
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-outline-primary">
                <i class="bi bi-speedometer2"></i> Admin Dashboard
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 