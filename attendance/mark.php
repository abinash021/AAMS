<?php
$pageTitle = "Mark Attendance";
require_once '../includes/header.php';

// Check if user is logged in and is a teacher or admin
if (!isLoggedIn() || (!isTeacher() && !isAdmin())) {
    setFlashMessage('You do not have permission to access that page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize classes
$academic = new Academic();
$attendance = new Attendance();

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

// Process the captured image
$success = '';
$error = '';
$result = null;
$debugInfo = null;

if (isPostRequest()) {
    $csrfToken = getPostField('csrf_token');
    $imageData = getPostField('image_data');
    $selectedClassId = getPostField('class_id');
    
    // Validate CSRF token
    if (!validateCsrfToken($csrfToken)) {
        $error = 'Invalid form submission. Please try again.';
    } elseif (empty($imageData)) {
        $error = 'No image data received. Please capture an image and try again.';
    } elseif (empty($selectedClassId)) {
        $error = 'No class selected. Please select a class and try again.';
    } else {
        // Save the image temporarily
        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $imageData = base64_decode($imageData);
        
        // Create directory if not exists
        if (!is_dir(UPLOAD_DIR . 'temp')) {
            mkdir(UPLOAD_DIR . 'temp', 0755, true);
        }
        
        $imagePath = UPLOAD_DIR . 'temp/capture_' . time() . '.jpg';
        file_put_contents($imagePath, $imageData);
        
        // Process the image with AWS Rekognition
        $result = $attendance->markByFace($imagePath, $selectedClassId, getCurrentUserId());
        
        if ($result) {
            $debugInfo = $result['debug'];
            if (!empty($result['attendance'])) {
                $success = 'Attendance marked successfully. Faces detected: ' . $debugInfo['total_faces_detected'] . 
                           ', Students recognized: ' . count($result['attendance']) . '.';
            } else {
                $error = 'No matching students found in the database. Please try again or use manual entry.';
            }
        } else {
            $error = 'Error processing the image. Please try again.';
        }
        
        // Delete the temporary image
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
}

// Get today's attendance for the selected class
$todayAttendance = [];
if ($selectedClass) {
    $todayAttendance = $attendance->getForClass($selectedClass['id']);
}

// Sort by time, most recent first
usort($todayAttendance, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Mark Attendance</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="attendanceForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        
                        <div class="form-group">
                            <label for="class_id">Select Class</label>
                            <select class="form-control" id="class_id" name="class_id" required>
                                <?php foreach ($teacherClasses as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo ($class['id'] == $classId) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Capture Image</label>
                            <div class="video-container">
                                <video id="video" autoplay></video>
                                <canvas id="canvas" style="display: none;"></canvas>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-primary" id="captureBtn">Capture</button>
                                <button type="button" class="btn btn-secondary" id="retakeBtn" style="display: none;">Retake</button>
                            </div>
                        </div>
                        
                        <input type="hidden" name="image_data" id="imageData">
                        <button type="submit" class="btn btn-success" id="submitBtn" disabled>Mark Attendance</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Today's Attendance</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($todayAttendance)): ?>
                        <p>No attendance marked yet for today.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Roll No.</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todayAttendance as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['roll_number']); ?></td>
                                            <td><?php echo date('h:i A', strtotime($record['time'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $record['status'] == 'present' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Debug Information Modal -->
<?php if ($debugInfo): ?>
<div class="modal fade" id="debugModal" tabindex="-1" role="dialog" aria-labelledby="debugModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="debugModalLabel">Face Detection Results</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Total Faces Detected:</strong> <?php echo $debugInfo['total_faces_detected']; ?><br>
                    <strong>Faces Recognized:</strong> <?php echo $debugInfo['faces_recognized']; ?>
                </div>
                
                <?php if (!empty($debugInfo['face_details'])): ?>
                    <h6>Recognized Students:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Roll No.</th>
                                    <th>Similarity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($debugInfo['face_details'] as $face): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($face['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($face['roll_number']); ?></td>
                                        <td><?php echo number_format($face['similarity'], 2); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($debugInfo['message'])): ?>
                    <div class="alert alert-warning">
                        <?php echo $debugInfo['message']; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#debugModal').modal('show');
    });
</script>
<?php endif; ?>

<script>
    // Webcam and capture functionality
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const captureBtn = document.getElementById('captureBtn');
    const retakeBtn = document.getElementById('retakeBtn');
    const submitBtn = document.getElementById('submitBtn');
    const imageData = document.getElementById('imageData');
    
    // Access webcam
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => {
            video.srcObject = stream;
        })
        .catch(err => {
            console.error('Error accessing webcam:', err);
            alert('Error accessing webcam. Please make sure you have granted camera permissions.');
        });
    
    // Capture image
    captureBtn.addEventListener('click', () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        // Convert to base64
        imageData.value = canvas.toDataURL('image/jpeg');
        
        // Show retake button and enable submit
        captureBtn.style.display = 'none';
        retakeBtn.style.display = 'inline-block';
        submitBtn.disabled = false;
    });
    
    // Retake image
    retakeBtn.addEventListener('click', () => {
        imageData.value = '';
        captureBtn.style.display = 'inline-block';
        retakeBtn.style.display = 'none';
        submitBtn.disabled = true;
    });
</script>

<?php require_once '../includes/footer.php'; ?> 