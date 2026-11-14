<?php
class Student {
    private $db;
    private $rekognition;
    private $lastError = '';
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->rekognition = new Rekognition();
    }
    
    /**
     * Register a new student
     * 
     * @param int $userId User ID from users table
     * @param string $rollNumber Roll/Registration number
     * @param int $semesterId Semester ID
     * @param string $faceImagePath Path to face image file
     * @return int|bool Student ID if registered successfully, false otherwise
     */
    public function register($userId, $rollNumber, $semesterId, $faceImagePath = null) {
        // Reset last error at the beginning of each registration attempt
        $this->lastError = '';

        // Check if student already exists with this user ID
        $existingStudent = $this->db->getRow(
            "SELECT id FROM students WHERE user_id = :user_id",
            [':user_id' => $userId]
        );
        
        if ($existingStudent) {
            $this->lastError = 'A student profile already exists for this user.';
            return false;
        }
        
        // Check if roll number already exists in this semester
        $existingRoll = $this->db->getRow(
            "SELECT id FROM students WHERE roll_number = :roll_number AND semester_id = :semester_id",
            [':roll_number' => $rollNumber, ':semester_id' => $semesterId]
        );
        
        if ($existingRoll) {
            $this->lastError = 'This roll number is already registered for the selected semester.';
            return false;
        }
        
        $awsFaceIds = null;
        
        // If face image provided, process with AWS Rekognition.
        // Registration should still succeed even if Rekognition fails, so we
        // log an error but do not abort the whole operation.
        if ($faceImagePath && file_exists($faceImagePath)) {
            $awsResult = $this->rekognition->indexFace($faceImagePath, $userId);
            
            if ($awsResult) {
                $awsFaceIds = json_encode($awsResult);
            } else {
                $this->lastError = 'Face recognition indexing failed. The student will be registered, but face-based attendance may not work until AWS Rekognition is configured correctly.';
                error_log('Student::register - Rekognition indexing failed for user_id ' . $userId . ' and image ' . $faceImagePath);
                // Continue without AWS face IDs
                $awsFaceIds = null;
            }
        }
        
        // Insert the student record
        $result = $this->db->exec(
            "INSERT INTO students (user_id, roll_number, semester_id, face_image_path, aws_face_id) 
             VALUES (:user_id, :roll_number, :semester_id, :face_image_path, :aws_face_id)",
            [
                ':user_id' => $userId,
                ':roll_number' => $rollNumber,
                ':semester_id' => $semesterId,
                ':face_image_path' => $faceImagePath,
                ':aws_face_id' => $awsFaceIds
            ]
        );
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        $this->lastError = 'Failed to save student record in the database. Please check the logs for more details.';
        error_log('Student::register - Database insert failed for user_id ' . $userId . ' and roll_number ' . $rollNumber);
        return false;
    }

    /**
     * Get the last error message, if any, from the previous operation.
     *
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Update student information
     * 
     * @param int $studentId Student ID
     * @param array $data Data to update
     * @return bool Success or failure
     */
    public function update($studentId, $data) {
        $updateFields = [];
        $params = [':id' => $studentId];
        
        // Build update fields
        foreach ($data as $field => $value) {
            if (in_array($field, ['roll_number', 'semester_id'])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }
        
        // Handle face image update
        if (!empty($data['face_image_path']) && file_exists($data['face_image_path'])) {
            $student = $this->getById($studentId);
            
            // Get user ID for AWS external ID
            $userId = $student['user_id'];
            
            // If student already has a face ID, delete it
            if (!empty($student['aws_face_id'])) {
                $this->rekognition->deleteFace($student['aws_face_id']);
            }
            
            // Index the new face
            $awsResult = $this->rekognition->indexFace($data['face_image_path'], $userId);
            
            if ($awsResult) {
                $updateFields[] = "face_image_path = :face_image_path";
                $updateFields[] = "aws_face_id = :aws_face_id";
                $params[':face_image_path'] = $data['face_image_path'];
                $params[':aws_face_id'] = $awsResult['faceId'];
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $sql = "UPDATE students SET " . implode(', ', $updateFields) . " WHERE id = :id";
        return $this->db->exec($sql, $params);
    }
    
    /**
     * Get student by ID
     * 
     * @param int $studentId
     * @return array|bool Student data if found, false otherwise
     */
    public function getById($studentId) {
        return $this->db->getRow(
            "SELECT s.*, u.full_name, u.username, u.email, 
                    sem.name as semester_name, b.name as branch_name
             FROM students s
             JOIN users u ON s.user_id = u.id
             JOIN semesters sem ON s.semester_id = sem.id
             JOIN branches b ON sem.branch_id = b.id
             WHERE s.id = :id",
            [':id' => $studentId]
        );
    }
    
    /**
     * Get student by user ID
     * 
     * @param int $userId
     * @return array|bool Student data if found, false otherwise
     */
    public function getByUserId($userId) {
        return $this->db->getRow(
            "SELECT s.*, u.full_name, u.username, u.email, 
                    sem.name as semester_name, b.name as branch_name
             FROM students s
             JOIN users u ON s.user_id = u.id
             JOIN semesters sem ON s.semester_id = sem.id
             JOIN branches b ON sem.branch_id = b.id
             WHERE s.user_id = :user_id",
            [':user_id' => $userId]
        );
    }
    
    /**
     * Get all students by semester
     * 
     * @param int $semesterId
     * @return array List of students
     */
    public function getBySemester($semesterId) {
        error_log("getBySemester called with semester ID: " . $semesterId);
        
        $result = $this->db->getRows(
            "SELECT s.*, u.full_name, u.username, u.email
             FROM students s
             JOIN users u ON s.user_id = u.id
             WHERE s.semester_id = :semester_id
             ORDER BY u.full_name",
            [':semester_id' => $semesterId]
        );
        
        error_log("Query result count: " . count($result));
        return $result;
    }
    
    /**
     * Get all students by semester ID (alias for getBySemester)
     * 
     * @param int $semesterId
     * @return array List of students
     */
    public function getStudentsBySemesterId($semesterId) {
        return $this->getBySemester($semesterId);
    }
    
    /**
     * Delete a student
     * 
     * @param int $studentId
     * @return bool Success or failure
     */
    public function delete($studentId) {
        $student = $this->getById($studentId);
        
        if (!$student) {
            return false;
        }
        
        // Delete the face from AWS Rekognition if exists
        if (!empty($student['aws_face_id'])) {
            $this->rekognition->deleteFace($student['aws_face_id']);
        }
        
        // Delete the student record
        return $this->db->exec(
            "DELETE FROM students WHERE id = :id",
            [':id' => $studentId]
        );
    }
    
    /**
     * Get total count of students
     * 
     * @return int Number of students
     */
    public function getTotalCount() {
        $result = $this->db->getRow("SELECT COUNT(*) as count FROM students");
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Search for a student by face
     * 
     * @param string $imagePath Path to the image file
     * @return array|bool Student data if found, false otherwise
     */
    public function searchByFace($imagePath) {
        // Search face in AWS Rekognition
        $matches = $this->rekognition->searchFace($imagePath);
        
        if (!$matches || empty($matches)) {
            return false;
        }
        
        $students = [];
        
        // Process all matches that meet a minimum similarity threshold
        foreach ($matches as $match) {
            $faceId = $match['Face']['FaceId'];
            $similarity = $match['Similarity'];
            
            // Skip matches with low similarity (you can adjust this threshold)
            if ($similarity < 80) {
                continue;
            }
            
            // Find student with this face ID
            $student = $this->db->getRow(
                "SELECT s.*, u.full_name, u.username, u.email, 
                        sem.name as semester_name, b.name as branch_name
                 FROM students s
                 JOIN users u ON s.user_id = u.id
                 JOIN semesters sem ON s.semester_id = sem.id
                 JOIN branches b ON sem.branch_id = b.id
                 WHERE s.aws_face_id LIKE :face_id",
                [':face_id' => '%' . $faceId . '%']
            );
            
            if ($student) {
                $student['similarity'] = $similarity;
                $students[] = $student;
            }
        }
        
        return !empty($students) ? $students : false;
    }
    
    /**
     * Get the Rekognition instance
     * 
     * @return Rekognition
     */
    public function getRekognition() {
        return $this->rekognition;
    }
}
?> 