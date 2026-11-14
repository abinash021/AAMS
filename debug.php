<?php
// Disable output buffering
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Clear any existing buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers to prevent caching
header('Content-Type: text/plain');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo "=== PHP DIAGNOSTIC INFO ===\n\n";

echo "PHP Version: " . phpversion() . "\n";
echo "Output buffering: " . (ini_get('output_buffering') ? 'ON' : 'OFF') . "\n";
echo "Output buffering level: " . ob_get_level() . "\n";
echo "Zlib output compression: " . (ini_get('zlib.output_compression') ? 'ON' : 'OFF') . "\n\n";

echo "=== OUTPUT BUFFER TEST ===\n\n";

// Test for any automatic output buffering
ob_start();
echo "This text should be in the buffer.\n";
$buffer = ob_get_contents();
ob_end_clean();

echo "Buffer contained: " . $buffer . "\n\n";

echo "=== INCLUDE/REQUIRE TEST ===\n\n";

// Test if any of the included files produce output
echo "Testing config include...\n";
ob_start();
include_once __DIR__ . '/config/config.php';
$output = ob_get_contents();
ob_end_clean();
echo "Output from config: " . ($output ? "YES - " . strlen($output) . " bytes" : "NONE") . "\n";

echo "Testing functions include...\n";
ob_start();
include_once __DIR__ . '/includes/functions.php';
$output = ob_get_contents();
ob_end_clean();
echo "Output from functions: " . ($output ? "YES - " . strlen($output) . " bytes" : "NONE") . "\n";

echo "Testing header include...\n";
ob_start();
$preventOutput = true; // Should prevent HTML output
include_once __DIR__ . '/includes/header.php';
$output = ob_get_contents();
ob_end_clean();
echo "Output from header: " . ($output ? "YES - " . strlen($output) . " bytes" : "NONE") . "\n";
if ($output) {
    echo "First 100 chars: " . substr($output, 0, 100) . "...\n";
}

echo "\n=== WHITESPACE CHECK ===\n\n";

// Check for whitespace before/after PHP tags in common files
$files_to_check = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/includes/functions.php',
    __DIR__ . '/includes/header.php',
    __DIR__ . '/includes/Database.php',
    __DIR__ . '/includes/Student.php',
    __DIR__ . '/includes/Attendance.php',
    __DIR__ . '/includes/Rekognition.php'
];

foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check for whitespace before opening PHP tag
    if (preg_match('/^\s+<\?php/', $content)) {
        echo "Whitespace before opening PHP tag in: $file\n";
    }
    
    // Check for whitespace after closing PHP tag
    if (preg_match('/\?>\s+$/', $content)) {
        echo "Whitespace after closing PHP tag in: $file\n";
    }
    
    // Check if file ends with ?> (best practice is to omit closing PHP tag)
    if (preg_match('/\?>$/', $content)) {
        echo "File ends with closing PHP tag (should be omitted): $file\n";
    }
}

echo "\n=== DONE ===\n";
?> 