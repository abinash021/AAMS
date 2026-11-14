// Face Recognition Attendance System - Main JavaScript File

// Initialize tooltips and popovers
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Setup confirm dialog handlers
    setupConfirmHandlers();
    
    // Setup webcam if the element exists
    if (document.getElementById('webcam')) {
        setupWebcam();
    }
    
    // Setup branch-semester cascade select if elements exist
    if (document.getElementById('branch_id') && document.getElementById('semester_id')) {
        setupBranchSemesterCascade();
    }
});

// Handler for confirm action dialogs
function setupConfirmHandlers() {
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('confirm-action')) {
            e.preventDefault();
            
            const message = e.target.getAttribute('data-confirm-message') || 'Are you sure you want to perform this action?';
            
            if (confirm(message)) {
                if (e.target.tagName === 'A') {
                    window.location.href = e.target.href;
                } else if (e.target.closest('form')) {
                    e.target.closest('form').submit();
                }
            }
        }
    });
}

// Function to handle webcam access for face recognition
function setupWebcam() {
    const webcamElement = document.getElementById('webcam');
    const canvasElement = document.getElementById('canvas');
    const snapButton = document.getElementById('snap-button');
    const retakeButton = document.getElementById('retake-button');
    const submitButton = document.getElementById('submit-button');
    const statusElement = document.getElementById('status');
    
    let stream = null;
    
    // Access the webcam
    navigator.mediaDevices.getUserMedia({ video: true, audio: false })
        .then(function(mediaStream) {
            stream = mediaStream;
            webcamElement.srcObject = mediaStream;
            webcamElement.play();
            
            if (statusElement) {
                statusElement.textContent = 'Camera activated. Position your face and click "Capture".';
                statusElement.className = 'alert alert-info';
            }
            
            if (snapButton) {
                snapButton.disabled = false;
            }
        })
        .catch(function(err) {
            console.error('Error accessing the webcam:', err);
            if (statusElement) {
                statusElement.textContent = 'Error accessing the camera. Please ensure you have granted camera permissions.';
                statusElement.className = 'alert alert-danger';
            }
        });
    
    // Capture an image from the webcam
    if (snapButton) {
        snapButton.addEventListener('click', function() {
            if (canvasElement && webcamElement) {
                canvasElement.width = webcamElement.videoWidth;
                canvasElement.height = webcamElement.videoHeight;
                
                const context = canvasElement.getContext('2d');
                context.drawImage(webcamElement, 0, 0, canvasElement.width, canvasElement.height);
                
                // Convert to data URL for preview and form submission
                const imageDataURL = canvasElement.toDataURL('image/jpeg');
                
                // Add captured image to hidden input for form submission
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'image_data';
                hiddenInput.value = imageDataURL;
                
                // Replace any existing hidden input
                const existingInput = document.querySelector('input[name="image_data"]');
                if (existingInput) {
                    existingInput.value = imageDataURL;
                } else {
                    document.querySelector('form').appendChild(hiddenInput);
                }
                
                // Hide webcam, show canvas with captured image
                webcamElement.style.display = 'none';
                canvasElement.style.display = 'block';
                
                // Toggle buttons
                snapButton.style.display = 'none';
                if (retakeButton) retakeButton.style.display = 'inline-block';
                if (submitButton) submitButton.disabled = false;
                
                if (statusElement) {
                    statusElement.textContent = 'Image captured. Click "Submit" to process or "Retake" for a new photo.';
                    statusElement.className = 'alert alert-success';
                }
            }
        });
    }
    
    // Retake the photo
    if (retakeButton) {
        retakeButton.addEventListener('click', function() {
            if (canvasElement && webcamElement) {
                // Show webcam, hide canvas
                webcamElement.style.display = 'block';
                canvasElement.style.display = 'none';
                
                // Toggle buttons
                snapButton.style.display = 'inline-block';
                retakeButton.style.display = 'none';
                if (submitButton) submitButton.disabled = true;
                
                if (statusElement) {
                    statusElement.textContent = 'Position your face and click "Capture".';
                    statusElement.className = 'alert alert-info';
                }
            }
        });
    }
    
    // Clean up when the page is unloaded
    window.addEventListener('beforeunload', function() {
        if (stream) {
            stream.getTracks().forEach(function(track) {
                track.stop();
            });
        }
    });
}

// Function to handle branch-semester cascade selection
function setupBranchSemesterCascade() {
    const branchSelect = document.getElementById('branch_id');
    const semesterSelect = document.getElementById('semester_id');
    
    if (branchSelect && semesterSelect) {
        // Store the initial semesters
        const semesterOptions = {};
        
        // Populate initial options
        Array.from(semesterSelect.options).forEach(option => {
            if (option.dataset.branchId) {
                if (!semesterOptions[option.dataset.branchId]) {
                    semesterOptions[option.dataset.branchId] = [];
                }
                semesterOptions[option.dataset.branchId].push({
                    value: option.value,
                    text: option.text
                });
            }
        });
        
        // Update semesters when branch changes
        branchSelect.addEventListener('change', function() {
            const branchId = this.value;
            
            // Clear the semester select
            semesterSelect.innerHTML = '';
            
            // Add empty option
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.text = 'Select Semester';
            semesterSelect.appendChild(emptyOption);
            
            // Add relevant options
            if (branchId && semesterOptions[branchId]) {
                semesterOptions[branchId].forEach(option => {
                    const newOption = document.createElement('option');
                    newOption.value = option.value;
                    newOption.text = option.text;
                    semesterSelect.appendChild(newOption);
                });
            }
        });
    }
}

// Helper function to format dates
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Helper function to show loading state
function showLoading(buttonElement, loadingText = 'Loading...') {
    if (buttonElement) {
        buttonElement.disabled = true;
        buttonElement.dataset.originalText = buttonElement.innerHTML;
        buttonElement.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${loadingText}`;
    }
}

// Helper function to hide loading state
function hideLoading(buttonElement) {
    if (buttonElement && buttonElement.dataset.originalText) {
        buttonElement.innerHTML = buttonElement.dataset.originalText;
        buttonElement.disabled = false;
    }
}

// Function to handle form submissions and show loading state
document.addEventListener('submit', function(e) {
    if (e.target && e.target.classList.contains('loading-on-submit')) {
        const submitBtn = e.target.querySelector('button[type="submit"]');
        if (submitBtn) {
            showLoading(submitBtn, submitBtn.dataset.loadingText || 'Processing...');
        }
    }
}); 