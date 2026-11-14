<?php
// Start output buffering to prevent "headers already sent" warnings
ob_start();

require_once __DIR__ . '/../includes/header.php';
// The header.php already includes Student.php, Attendance.php, and Rekognition.php

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    // Use the redirect function to handle redirects properly
    redirect(BASE_URL . '/login.php');
}

// Initialize classes
$student = new Student();
$attendance = new Attendance();
$rekognition = new Rekognition();
$academic = new Academic();

// Get all classes
$classes = $academic->getAllClasses();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #video {
            width: 100%;
            max-width: 640px;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        #canvas {
            display: none;
        }
        .detected-faces {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .face-box {
            position: absolute;
            border: 2px solid #00ff00;
            background: transparent;
        }
        #video-container {
            position: relative;
            display: inline-block;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-active {
            background-color: #28a745;
        }
        .status-inactive {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Live Attendance System</h2>
            <div>
                <span class="status-indicator status-inactive" id="cameraStatus"></span>
                <span id="statusText">Camera Off</span>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Camera</h5>
                            <div>
                                <button type="button" id="startCamera" class="btn btn-primary btn-sm">Start Camera</button>
                                <button type="button" id="stopCamera" class="btn btn-danger btn-sm" disabled>Stop Camera</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="attendanceForm">
                            <div class="mb-3">
                                <label for="class_id" class="form-label">Select Class</label>
                                <select class="form-select" id="class_id" name="class_id" required>
                                    <option value="">Select a class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>">
                                            <?php 
                                                $classDisplay = htmlspecialchars($class['name'] ?? 'Unknown class');
                                                if (isset($class['branch_name']) && isset($class['semester_name'])) {
                                                    $classDisplay .= ' - ' . htmlspecialchars($class['branch_name'] . ' / ' . $class['semester_name']);
                                                }
                                                echo $classDisplay;
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                        
                        <div id="video-container">
                            <video id="video" autoplay playsinline></video>
                            <canvas id="canvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Students Present</h5>
                    </div>
                    <div class="card-body">
                        <div id="attendanceResults">
                            <p class="text-muted">Start the camera and select a class to mark attendance.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug Modal -->
    <div class="modal fade" id="debugModal" tabindex="-1" aria-labelledby="debugModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="debugModalLabel">Face Detection Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="debugModalBody">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-header">Face Detection Summary</div>
                                <div class="card-body" id="faceDetectionSummary">
                                    <p>No data available</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">Recognized Faces</div>
                                <div class="card-body" id="recognizedFacesList">
                                    <p>No faces recognized</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // DOM elements
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const startCameraBtn = document.getElementById('startCamera');
        const stopCameraBtn = document.getElementById('stopCamera');
        const classSelect = document.getElementById('class_id');
        const attendanceResults = document.getElementById('attendanceResults');
        const cameraStatus = document.getElementById('cameraStatus');
        const statusText = document.getElementById('statusText');
        
        // State variables
        let stream = null;
        let detectionInterval = null;
        let presentStudents = new Map(); // Map student ID to student details
        
        // Start camera
        startCameraBtn.addEventListener('click', async () => {
            if (!classSelect.value) {
                alert('Please select a class first');
                return;
            }
            
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                
                // Update UI
                startCameraBtn.disabled = true;
                stopCameraBtn.disabled = false;
                cameraStatus.classList.remove('status-inactive');
                cameraStatus.classList.add('status-active');
                statusText.textContent = 'Camera Active';
                
                // Start face detection
                startDetection();
            } catch (err) {
                console.error('Error accessing camera:', err);
                alert('Error accessing camera. Please make sure you have granted camera permissions.');
            }
        });

        // Stop camera
        stopCameraBtn.addEventListener('click', () => {
            stopCamera();
        });
        
        // Function to stop the camera and reset state
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                video.srcObject = null;
                clearInterval(detectionInterval);
                
                // Update UI
                startCameraBtn.disabled = false;
                stopCameraBtn.disabled = true;
                cameraStatus.classList.remove('status-active');
                cameraStatus.classList.add('status-inactive');
                statusText.textContent = 'Camera Off';
            }
        }

        // Function to start face detection
        function startDetection() {
            detectionInterval = setInterval(() => {
                // Get selected class ID
                const classId = classSelect.value;
                if (!classId) {
                    stopCamera();
                    alert('Please select a class');
                    return;
                }

                // Capture frame from video
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Convert to blob and upload
                canvas.toBlob(async (blob) => {
                    const formData = new FormData();
                    formData.append('class_id', classId);
                    formData.append('image', blob, 'frame.jpg');
                    formData.append('action', 'mark_attendance');

                    try {
                        // Show processing indicator
                        statusText.textContent = 'Processing...';
                        
                        // Send image to server using our dedicated API endpoint
                        const response = await fetch('<?php echo BASE_URL; ?>/attendance/api.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        // Get response text for debugging
                        const responseText = await response.text();
                        
                        // Check if the response is empty
                        if (!responseText || responseText.trim() === '') {
                            throw new Error('Server returned empty response');
                        }
                        
                        // Debug log
                        console.log('Raw server response:', responseText);
                        
                        // Extract valid JSON using a regular expression
                        const jsonMatch = responseText.match(/(\{.*\})/s);
                        
                        if (!jsonMatch) {
                            throw new Error(`No JSON object found in response: ${responseText.substring(0, 100)}...`);
                        }
                        
                        // Parse the extracted JSON
                        const result = JSON.parse(jsonMatch[0]);
                        
                        console.log('Parsed server response:', result);
                        
                        // Reset status
                        statusText.textContent = 'Camera Active';
                        
                        // Check if the result contains an error message
                        if (result.error) {
                            console.error('Server error:', result.error);
                            showErrorMessage(`Server error: ${result.error}`);
                            return;
                        }
                        
                        // Show debug information in modal
                        showDebugInfo(result);
                        
                        // Update attendance list
                        updateAttendanceList(result);
                        
                    } catch (err) {
                        console.error('Error processing image:', err);
                        statusText.textContent = 'Error processing';
                        
                        // Show error message in a more user-friendly way
                        showErrorMessage(`Error processing image: ${err.message}`);
                        
                        // Reset status after delay
                        setTimeout(() => {
                            statusText.textContent = 'Camera Active';
                        }, 2000);
                    }
                }, 'image/jpeg');
            }, 5000); // Process every 5 seconds
        }
        
        // Function to show debug information
        function showDebugInfo(result) {
            // Always show the modal with whatever information we have
            const debugModal = new bootstrap.Modal(document.getElementById('debugModal'));
            
            // Update the summary section
            const summaryElement = document.getElementById('faceDetectionSummary');
            const recognizedListElement = document.getElementById('recognizedFacesList');
            
            if (!result.debug) {
                summaryElement.innerHTML = `
                    <div class="alert alert-warning">
                        <p><strong>No face detection data available</strong></p>
                        <p>The server did not return any face detection information.</p>
                    </div>
                `;
                recognizedListElement.innerHTML = '<p>No face detection data available</p>';
            } else {
                // Update face detection summary
                const facesRecognized = result.debug.face_details ? result.debug.face_details.length : 0;
                summaryElement.innerHTML = `
                    <p><strong>Total faces detected:</strong> ${result.debug.total_faces_detected}</p>
                    <p><strong>Faces recognized:</strong> ${facesRecognized}</p>
                `;
                
                // Update recognized faces list
                if (result.debug.face_details && result.debug.face_details.length > 0) {
                    let facesHtml = '<ul class="list-group">';
                    result.debug.face_details.forEach(face => {
                        facesHtml += `
                            <li class="list-group-item">
                                <div><strong>Student ID:</strong> ${face.student_id}</div>
                                <div><strong>Name:</strong> ${face.student_name || 'Unknown'}</div>
                                <div><strong>Roll Number:</strong> ${face.roll_number || 'N/A'}</div>
                                <div><strong>Similarity:</strong> ${face.similarity.toFixed(2)}%</div>
                            </li>
                        `;
                    });
                    facesHtml += '</ul>';
                    recognizedListElement.innerHTML = facesHtml;
                } else {
                    recognizedListElement.innerHTML = '<p>No faces recognized</p>';
                }
            }
            
            // Show the modal
            debugModal.show();
        }
        
        // Function to update attendance list
        function updateAttendanceList(result) {
            if (result.attendance && result.attendance.length > 0) {
                // Add new students to the map
                result.attendance.forEach(record => {
                    presentStudents.set(record.student_id, {
                        id: record.student_id,
                        name: record.student_name,
                        rollNumber: record.roll_number,
                        time: record.time
                    });
                });
            }
            
            // Update UI
            if (presentStudents.size === 0) {
                attendanceResults.innerHTML = '<p class="text-muted">No students marked present yet.</p>';
                return;
            }
            
            // Create table of present students
            let tableHtml = `
                <div class="alert alert-success mb-3">
                    <strong>${presentStudents.size}</strong> student${presentStudents.size !== 1 ? 's' : ''} marked present
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Roll Number</th>
                                <th>Name</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            // Convert Map to Array for sorting
            const studentsArray = Array.from(presentStudents.values());
            
            // Sort by roll number
            studentsArray.sort((a, b) => {
                return (a.rollNumber || '').localeCompare(b.rollNumber || '');
            });
            
            // Add each student to the table
            studentsArray.forEach(student => {
                tableHtml += `
                    <tr>
                        <td><strong>${student.rollNumber || 'N/A'}</strong></td>
                        <td>${student.name || 'Unknown'}</td>
                        <td>${student.time || 'N/A'}</td>
                        <td><span class="badge bg-success">Present</span></td>
                    </tr>
                `;
            });
            
            tableHtml += `
                    </tbody>
                </table>
            </div>
            `;
            
            attendanceResults.innerHTML = tableHtml;
        }

        // Function to show error message
        function showErrorMessage(message) {
            // Create alert in the results area
            const errorHtml = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error:</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                ${attendanceResults.innerHTML}
            `;
            attendanceResults.innerHTML = errorHtml;
            
            // Also log to console
            console.error(message);
        }

        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>
</html> 