<?php
// Database initialization script
require_once '../config/config.php';

try {
    // Create/open database
    $db = new SQLite3(DB_PATH);
    
    echo "Database connected successfully.\n";
    
    // Read schema SQL
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Execute schema SQL
    $result = $db->exec($schemaSql);
    
    if ($result !== false) {
        echo "Database schema created successfully.\n";
        
        // Create default admin user if not exists
        $checkAdmin = $db->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
        $row = $checkAdmin->fetchArray(SQLITE3_ASSOC);
        
        if ($row['count'] == 0) {
            // Hash the default password
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            
            // Get admin role ID
            $roleQuery = $db->query("SELECT id FROM roles WHERE name = 'admin'");
            $roleRow = $roleQuery->fetchArray(SQLITE3_ASSOC);
            $adminRoleId = $roleRow['id'];
            
            // Insert default admin
            $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role_id) VALUES (:username, :email, :password, :full_name, :role_id)");
            $stmt->bindValue(':username', 'admin', SQLITE3_TEXT);
            $stmt->bindValue(':email', 'admin@example.com', SQLITE3_TEXT);
            $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
            $stmt->bindValue(':full_name', 'System Administrator', SQLITE3_TEXT);
            $stmt->bindValue(':role_id', $adminRoleId, SQLITE3_INTEGER);
            
            $result = $stmt->execute();
            
            if ($result) {
                echo "Default admin user created successfully.\n";
            } else {
                echo "Failed to create default admin user.\n";
            }
        } else {
            echo "Admin user already exists.\n";
        }
    } else {
        echo "Error creating database schema.\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    die("Database initialization error: " . $e->getMessage());
}
?> 