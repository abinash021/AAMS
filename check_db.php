<?php
require_once 'config/config.php';

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection successful!\n\n";
    
    // Check if all required tables exist
    $requiredTables = ['roles', 'users', 'branches', 'semesters', 'classes', 'students', 'attendance', 'session_logs'];
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Checking required tables:\n";
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' is missing\n";
        }
    }
    
    // Check roles table data
    if (in_array('roles', $tables)) {
        echo "\nRoles table contents:\n";
        $roles = $db->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
        print_r($roles);
    }
    
    // Check users table data
    if (in_array('users', $tables)) {
        echo "\nUsers table contents (first 5):\n";
        $users = $db->query("SELECT * FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        print_r($users);
    }
    
    // Check branches table data
    if (in_array('branches', $tables)) {
        echo "\nBranches table contents:\n";
        $branches = $db->query("SELECT * FROM branches")->fetchAll(PDO::FETCH_ASSOC);
        print_r($branches);
    }
    
    // Check semesters table data
    if (in_array('semesters', $tables)) {
        echo "\nSemesters table contents:\n";
        $semesters = $db->query("SELECT s.*, b.name as branch_name 
                                FROM semesters s 
                                JOIN branches b ON s.branch_id = b.id")->fetchAll(PDO::FETCH_ASSOC);
        print_r($semesters);
    }
    
    // Check classes table data
    if (in_array('classes', $tables)) {
        echo "\nClasses table contents:\n";
        $classes = $db->query("SELECT c.*, s.name as semester_name, u.full_name as teacher_name 
                              FROM classes c 
                              JOIN semesters s ON c.semester_id = s.id 
                              JOIN users u ON c.teacher_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
        print_r($classes);
    }
    
    // Check students table data
    if (in_array('students', $tables)) {
        echo "\nStudents table contents (first 5):\n";
        $students = $db->query("SELECT s.*, u.full_name, u.email, sem.name as semester_name, b.name as branch_name 
                               FROM students s 
                               JOIN users u ON s.user_id = u.id 
                               JOIN semesters sem ON s.semester_id = sem.id 
                               JOIN branches b ON sem.branch_id = b.id 
                               LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        print_r($students);
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?> 