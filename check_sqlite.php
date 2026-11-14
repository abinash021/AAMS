<?php
// Check if SQLite3 extension is loaded
if (extension_loaded('sqlite3')) {
    echo "SQLite3 extension is enabled!\n";
    
    // Try creating a test database
    try {
        $testDb = new SQLite3(':memory:');
        echo "Successfully created a test SQLite3 database in memory.\n";
        $testDb->close();
    } catch (Exception $e) {
        echo "Error when trying to use SQLite3: " . $e->getMessage() . "\n";
    }
} else {
    echo "SQLite3 extension is NOT enabled!\n";
    
    // List all loaded extensions
    echo "\nLoaded extensions:\n";
    foreach (get_loaded_extensions() as $ext) {
        echo "- $ext\n";
    }
    
    // Provide guidance
    echo "\n=== SOLUTION ===\n";
    echo "1. Locate your php.ini file (used by your web server)\n";
    echo "2. Uncomment or add the line: extension=sqlite3\n";
    echo "3. Restart your web server\n";
}
?> 