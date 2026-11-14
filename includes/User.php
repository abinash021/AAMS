<?php
class User {
    private $db;
    
    // User roles as constants
    const ROLE_ADMIN = 1;
    const ROLE_TEACHER = 2;
    const ROLE_STUDENT = 3;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authenticate a user
     * 
     * @param string $username
     * @param string $password
     * @return array|bool User data if authenticated, false otherwise
     */
    public function authenticate($username, $password) {
        $user = $this->db->getRow(
            "SELECT u.*, r.name as role_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.username = :username",
            [':username' => $username]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            // Log successful login
            $this->logSession($user['id']);
            
            // Remove password from return data
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Create a new user
     * 
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $fullName
     * @param int $roleId
     * @return int|bool New user ID if successful, false otherwise
     */
    public function create($username, $email, $password, $fullName, $roleId) {
        // Check if username or email already exists
        $existingUser = $this->db->getRow(
            "SELECT id FROM users WHERE username = :username OR email = :email",
            [':username' => $username, ':email' => $email]
        );
        
        if ($existingUser) {
            return false;
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert the new user
        $result = $this->db->exec(
            "INSERT INTO users (username, email, password, full_name, role_id) 
             VALUES (:username, :email, :password, :full_name, :role_id)",
            [
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':full_name' => $fullName,
                ':role_id' => $roleId
            ]
        );
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId
     * @return array|bool User data if found, false otherwise
     */
    public function getById($userId) {
        $user = $this->db->getRow(
            "SELECT u.*, r.name as role_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.id = :id",
            [':id' => $userId]
        );
        
        if ($user) {
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Update user profile
     * 
     * @param int $userId
     * @param array $data
     * @return bool Success or failure
     */
    public function update($userId, $data) {
        $updateFields = [];
        $params = [':id' => $userId];
        
        // Build update fields
        foreach ($data as $field => $value) {
            if (in_array($field, ['email', 'full_name'])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }
        
        // Update password if provided
        if (!empty($data['password'])) {
            $updateFields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
        return $this->db->exec($sql, $params);
    }
    
    /**
     * Get all users by role
     * 
     * @param int $roleId
     * @return array List of users
     */
    public function getAllByRole($roleId) {
        return $this->db->getRows(
            "SELECT u.id, u.username, u.email, u.full_name, u.created_at, r.name as role_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             WHERE u.role_id = :role_id
             ORDER BY u.full_name",
            [':role_id' => $roleId]
        );
    }
    
    /**
     * Delete a user
     * 
     * @param int $userId
     * @return bool Success or failure
     */
    public function delete($userId) {
        // Check if user exists
        $user = $this->getById($userId);
        
        if (!$user) {
            return false;
        }
        
        // Delete the user
        return $this->db->exec(
            "DELETE FROM users WHERE id = :id",
            [':id' => $userId]
        );
    }
    
    /**
     * Log user session
     * 
     * @param int $userId
     */
    private function logSession($userId) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $this->db->exec(
            "INSERT INTO session_logs (user_id, ip_address, user_agent) 
             VALUES (:user_id, :ip_address, :user_agent)",
            [
                ':user_id' => $userId,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent
            ]
        );
    }
    
    /**
     * Update logout time for the session
     * 
     * @param int $userId
     */
    public function logLogout($userId) {
        $this->db->exec(
            "UPDATE session_logs 
             SET logout_time = CURRENT_TIMESTAMP 
             WHERE user_id = :user_id 
             AND logout_time IS NULL",
            [':user_id' => $userId]
        );
    }
    
    /**
     * Check if a user has a specific role
     * 
     * @param int $userId
     * @param int $roleId
     * @return bool True if the user has the role, false otherwise
     */
    public function hasRole($userId, $roleId) {
        $user = $this->db->getRow(
            "SELECT role_id FROM users WHERE id = :id",
            [':id' => $userId]
        );
        
        return $user && $user['role_id'] == $roleId;
    }
    
    /**
     * Check if a user is an admin
     * 
     * @param int $userId
     * @return bool True if the user is an admin, false otherwise
     */
    public function isAdmin($userId) {
        return $this->hasRole($userId, self::ROLE_ADMIN);
    }
    
    /**
     * Check if a user is a teacher
     * 
     * @param int $userId
     * @return bool True if the user is a teacher, false otherwise
     */
    public function isTeacher($userId) {
        return $this->hasRole($userId, self::ROLE_TEACHER);
    }
    
    /**
     * Check if a user is a student
     * 
     * @param int $userId
     * @return bool True if the user is a student, false otherwise
     */
    public function isStudent($userId) {
        return $this->hasRole($userId, self::ROLE_STUDENT);
    }
}
?> 