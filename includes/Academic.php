<?php
class Academic {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new branch
     * 
     * @param string $name Branch name
     * @param string $description Branch description
     * @return int|bool New branch ID if successful, false otherwise
     */
    public function createBranch($name, $description = '') {
        // Check if branch already exists
        $existingBranch = $this->db->getRow(
            "SELECT id FROM branches WHERE name = :name",
            [':name' => $name]
        );
        
        if ($existingBranch) {
            return false;
        }
        
        // Insert the new branch
        $result = $this->db->exec(
            "INSERT INTO branches (name, description) VALUES (:name, :description)",
            [':name' => $name, ':description' => $description]
        );
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Get branch by ID
     * 
     * @param int $branchId Branch ID
     * @return array|bool Branch data if found, false otherwise
     */
    public function getBranchById($branchId) {
        return $this->db->getRow(
            "SELECT * FROM branches WHERE id = :id",
            [':id' => $branchId]
        );
    }
    
    /**
     * Get all branches
     * 
     * @return array List of branches
     */
    public function getAllBranches() {
        return $this->db->getRows("SELECT * FROM branches ORDER BY name");
    }
    
    /**
     * Update a branch
     * 
     * @param int $branchId Branch ID
     * @param string $name Branch name
     * @param string $description Branch description
     * @return bool Success or failure
     */
    public function updateBranch($branchId, $name, $description = '') {
        return $this->db->exec(
            "UPDATE branches SET name = :name, description = :description WHERE id = :id",
            [':name' => $name, ':description' => $description, ':id' => $branchId]
        );
    }
    
    /**
     * Delete a branch
     * 
     * @param int $branchId Branch ID
     * @return bool Success or failure
     */
    public function deleteBranch($branchId) {
        // Check if branch has associated semesters
        $semesterCount = $this->db->count(
            "SELECT COUNT(*) FROM semesters WHERE branch_id = :branch_id",
            [':branch_id' => $branchId]
        );
        
        if ($semesterCount > 0) {
            return false; // Cannot delete branch with semesters
        }
        
        return $this->db->exec(
            "DELETE FROM branches WHERE id = :id",
            [':id' => $branchId]
        );
    }
    
    /**
     * Create a new semester
     * 
     * @param string $name Semester name
     * @param int $branchId Branch ID
     * @return int|bool New semester ID if successful, false otherwise
     */
    public function createSemester($name, $branchId) {
        // Check if semester already exists in this branch
        $existingSemester = $this->db->getRow(
            "SELECT id FROM semesters WHERE name = :name AND branch_id = :branch_id",
            [':name' => $name, ':branch_id' => $branchId]
        );
        
        if ($existingSemester) {
            return false;
        }
        
        // Insert the new semester
        $result = $this->db->exec(
            "INSERT INTO semesters (name, branch_id) VALUES (:name, :branch_id)",
            [':name' => $name, ':branch_id' => $branchId]
        );
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Get semester by ID
     * 
     * @param int $semesterId Semester ID
     * @return array|bool Semester data if found, false otherwise
     */
    public function getSemesterById($semesterId) {
        return $this->db->getRow(
            "SELECT s.*, b.name as branch_name 
             FROM semesters s
             JOIN branches b ON s.branch_id = b.id
             WHERE s.id = :id",
            [':id' => $semesterId]
        );
    }
    
    /**
     * Get all semesters by branch
     * 
     * @param int $branchId Branch ID
     * @return array List of semesters
     */
    public function getSemestersByBranch($branchId) {
        return $this->db->getRows(
            "SELECT * FROM semesters WHERE branch_id = :branch_id ORDER BY name",
            [':branch_id' => $branchId]
        );
    }
    
    /**
     * Get all semesters
     * 
     * @return array List of semesters with branch information
     */
    public function getAllSemesters() {
        return $this->db->getRows(
            "SELECT s.*, b.name as branch_name 
             FROM semesters s
             JOIN branches b ON s.branch_id = b.id
             ORDER BY b.name, s.name"
        );
    }
    
    /**
     * Update a semester
     * 
     * @param int $semesterId Semester ID
     * @param string $name Semester name
     * @param int $branchId Branch ID
     * @return bool Success or failure
     */
    public function updateSemester($semesterId, $name, $branchId) {
        return $this->db->exec(
            "UPDATE semesters SET name = :name, branch_id = :branch_id WHERE id = :id",
            [':name' => $name, ':branch_id' => $branchId, ':id' => $semesterId]
        );
    }
    
    /**
     * Delete a semester
     * 
     * @param int $semesterId Semester ID
     * @return bool Success or failure
     */
    public function deleteSemester($semesterId) {
        // Check if semester has associated classes
        $classCount = $this->db->count(
            "SELECT COUNT(*) FROM classes WHERE semester_id = :semester_id",
            [':semester_id' => $semesterId]
        );
        
        if ($classCount > 0) {
            return false; // Cannot delete semester with classes
        }
        
        // Check if semester has associated students
        $studentCount = $this->db->count(
            "SELECT COUNT(*) FROM students WHERE semester_id = :semester_id",
            [':semester_id' => $semesterId]
        );
        
        if ($studentCount > 0) {
            return false; // Cannot delete semester with students
        }
        
        return $this->db->exec(
            "DELETE FROM semesters WHERE id = :id",
            [':id' => $semesterId]
        );
    }
    
    /**
     * Create a new class
     * 
     * @param string $name Class name
     * @param int $semesterId Semester ID
     * @param int $teacherId Teacher ID
     * @return int|bool New class ID if successful, false otherwise
     */
    public function createClass($name, $semesterId, $teacherId) {
        // Check if class already exists in this semester
        $existingClass = $this->db->getRow(
            "SELECT id FROM classes WHERE name = :name AND semester_id = :semester_id",
            [':name' => $name, ':semester_id' => $semesterId]
        );
        
        if ($existingClass) {
            return false;
        }
        
        // Insert the new class
        $result = $this->db->exec(
            "INSERT INTO classes (name, semester_id, teacher_id) 
             VALUES (:name, :semester_id, :teacher_id)",
            [
                ':name' => $name,
                ':semester_id' => $semesterId,
                ':teacher_id' => $teacherId
            ]
        );
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Get class by ID
     * 
     * @param int $classId Class ID
     * @return array|bool Class data if found, false otherwise
     */
    public function getClassById($classId) {
        return $this->db->getRow(
            "SELECT c.*, s.name as semester_name, b.name as branch_name,
                    u.full_name as teacher_name
             FROM classes c
             JOIN semesters s ON c.semester_id = s.id
             JOIN branches b ON s.branch_id = b.id
             JOIN users u ON c.teacher_id = u.id
             WHERE c.id = :id",
            [':id' => $classId]
        );
    }
    
    /**
     * Get all classes by semester
     * 
     * @param int $semesterId Semester ID
     * @return array List of classes
     */
    public function getClassesBySemester($semesterId) {
        return $this->db->getRows(
            "SELECT c.*, u.full_name as teacher_name
             FROM classes c
             JOIN users u ON c.teacher_id = u.id
             WHERE c.semester_id = :semester_id
             ORDER BY c.name",
            [':semester_id' => $semesterId]
        );
    }
    
    /**
     * Get all classes by teacher
     * 
     * @param int $teacherId Teacher ID
     * @return array List of classes
     */
    public function getClassesByTeacher($teacherId) {
        return $this->db->getRows(
            "SELECT c.*, s.name as semester_name, b.name as branch_name
             FROM classes c
             JOIN semesters s ON c.semester_id = s.id
             JOIN branches b ON s.branch_id = b.id
             WHERE c.teacher_id = :teacher_id
             ORDER BY b.name, s.name, c.name",
            [':teacher_id' => $teacherId]
        );
    }
    
    /**
     * Update a class
     * 
     * @param int $classId Class ID
     * @param string $name Class name
     * @param int $semesterId Semester ID
     * @param int $teacherId Teacher ID
     * @return bool Success or failure
     */
    public function updateClass($classId, $name, $semesterId, $teacherId) {
        return $this->db->exec(
            "UPDATE classes 
             SET name = :name, semester_id = :semester_id, teacher_id = :teacher_id 
             WHERE id = :id",
            [
                ':name' => $name,
                ':semester_id' => $semesterId,
                ':teacher_id' => $teacherId,
                ':id' => $classId
            ]
        );
    }
    
    /**
     * Delete a class
     * 
     * @param int $classId Class ID
     * @return bool Success or failure
     */
    public function deleteClass($classId) {
        // Check if class has attendance records
        $attendanceCount = $this->db->count(
            "SELECT COUNT(*) FROM attendance WHERE class_id = :class_id",
            [':class_id' => $classId]
        );
        
        if ($attendanceCount > 0) {
            return false; // Cannot delete class with attendance records
        }
        
        return $this->db->exec(
            "DELETE FROM classes WHERE id = :id",
            [':id' => $classId]
        );
    }
    
    /**
     * Get all classes with related information
     * 
     * @return array List of all classes
     */
    public function getAllClasses() {
        return $this->db->getRows(
            "SELECT c.*, s.name as semester_name, b.name as branch_name, u.full_name as teacher_name
             FROM classes c
             JOIN semesters s ON c.semester_id = s.id
             JOIN branches b ON s.branch_id = b.id
             JOIN users u ON c.teacher_id = u.id
             ORDER BY b.name, s.name, c.name"
        );
    }
}
?> 