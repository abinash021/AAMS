<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Face Recognition Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .error-container {
            max-width: 600px;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="error-container bg-white">
                    <div class="error-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h1 class="mb-4">Oops! Something went wrong</h1>
                    <p class="lead mb-4">We apologize for the inconvenience. An unexpected error has occurred.</p>
                    <p class="text-muted mb-4">Please try refreshing the page or contact the administrator if the problem persists.</p>
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                        <a href="<?php echo BASE_URL; ?>" class="btn btn-primary me-sm-3">Go to Homepage</a>
                        <button onclick="history.back()" class="btn btn-outline-secondary">Go Back</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 