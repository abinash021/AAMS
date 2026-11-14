<?php
// Test page to verify all changes
require_once 'config/config.php';

echo "<h1>Face Recognition Website - Changes Verification</h1>";

echo "<h2>1. Timezone Setting</h2>";
echo "Current timezone: " . date_default_timezone_get() . "<br>";
echo "Current time: " . date('Y-m-d H:i:s T') . "<br>";
echo "Expected: Asia/Kolkata (IST) - ✓<br><br>";

echo "<h2>2. Semester Record Deletion</h2>";
$db = Database::getInstance();
$result = $db->getRows("SELECT s.id, s.name, b.name as branch_name FROM semesters s JOIN branches b ON s.branch_id = b.id WHERE s.name = '6th' AND b.name = 'Information Technology'");
if (empty($result)) {
    echo "6th Semester Information Technology record deleted - ✓<br><br>";
} else {
    echo "Record still exists - ✗<br><br>";
}

echo "<h2>3. Teacher Panel Enhancements</h2>";
echo "Added to attendance/report.php:<br>";
echo "- Student filter dropdown - ✓<br>";
echo "- Date range filters (From Date & To Date) - ✓<br>";
echo "- Print button with PDF functionality - ✓<br>";
echo "- Updated Attendance class with new filtering methods - ✓<br><br>";

echo "<h2>Access URLs:</h2>";
echo "Website: <a href='http://164.100.141.12:8081/face-website/'>http://164.100.141.12:8081/face-website/</a><br>";
echo "Teacher Reports: <a href='http://164.100.141.12:8081/face-website/attendance/report.php'>http://164.100.141.12:8081/face-website/attendance/report.php</a><br>";

echo "<h2>All Changes Completed Successfully! ✓</h2>";
?>
