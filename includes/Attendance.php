<?php
require_once 'functions.php';
require_once 'Database.php';

/**
 * Attendance Class
 */
class Attendance {
    private $db;
    private $student;
    
    /**
     * Initialize database connection
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->student = new Student();
    }
    
    /**
     * Mark attendance using face recognition
     * 
     * @param string $imagePath Path to captured image
     * @param int $classId Class ID
     * @param int $teacherId Teacher ID who is marking attendance
     * @return array|bool Array of attendance data if successful, false otherwise
     */
    public function markByFace($imagePath, $classId, $teacherId) {
        // Search for students by face - now returns array of students
        $students = $this->student->searchByFace($imagePath);
        
        // Get total faces detected in the image
        $detectedFaces = $this->student->getRekognition()->detectFaces($imagePath);
        $totalFaces = $detectedFaces && !empty($detectedFaces['FaceDetails']) ? count($detectedFaces['FaceDetails']) : 0;
        
        // Prepare response with debugging information
        $response = [
            'debug' => [
                'total_faces_detected' => $totalFaces,
                'faces_recognized' => is_array($students) ? count($students) : 0,
                'face_details' => []
            ],
            'attendance' => []
        ];
        
        if (!$students || !is_array($students)) {
            $response['debug']['message'] = 'No students recognized in the image';
            return $response;
        }
        
        // Format current date and time
        $date = date('Y-m-d');
        $time = date('H:i:s');
        
        // Save the original image with a unique name
        $imageDir = UPLOAD_DIR . 'attendance/';
        
        // Check if the attendance directory exists, if not create it
        if (!is_dir($imageDir)) {
            if (!mkdir($imageDir, 0755, true)) {
                error_log("Failed to create attendance directory: " . $imageDir);
                throw new Exception("Failed to create attendance directory");
            }
        }
        
        // Check if the directory is writable
        if (!is_writable($imageDir)) {
            error_log("Attendance directory not writable: " . $imageDir);
            throw new Exception("Attendance directory not writable");
        }
        
        // Process each detected student
        foreach ($students as $student) {
            if (!is_array($student) || !isset($student['id'])) {
                continue;
            }
            
            // Add student details to debug information
            $response['debug']['face_details'][] = [
                'student_id' => $student['id'],
                'student_name' => isset($student['full_name']) ? $student['full_name'] : '',
                'roll_number' => isset($student['roll_number']) ? $student['roll_number'] : '',
                'similarity' => isset($student['similarity']) ? $student['similarity'] : 0
            ];
            
            // Check if attendance already marked for today
            $existingAttendance = $this->db->getRow(
                "SELECT id, status FROM attendance 
                 WHERE student_id = :student_id 
                 AND class_id = :class_id 
                 AND date = :date",
                [
                    ':student_id' => $student['id'],
                    ':class_id' => $classId,
                    ':date' => $date
                ]
            );
            
            // Create unique image name for each student
            $imageName = 'attendance_' . $student['id'] . '_' . $classId . '_' . $date . '_' . time() . '.jpg';
            $savedImagePath = $imageDir . $imageName;
            copy($imagePath, $savedImagePath);
            
            if ($existingAttendance) {
                // Update existing attendance
                $result = $this->db->exec(
                    "UPDATE attendance 
                     SET status = 'present', 
                         time = :time, 
                         marked_by = :marked_by, 
                         confidence_score = :confidence_score, 
                         image_path = :image_path 
                     WHERE id = :id",
                    [
                        ':time' => $time,
                        ':marked_by' => $teacherId,
                        ':confidence_score' => isset($student['similarity']) ? $student['similarity'] : 100,
                        ':image_path' => $savedImagePath,
                        ':id' => $existingAttendance['id']
                    ]
                );
                
                if ($result) {
                    $response['attendance'][] = [
                        'id' => $existingAttendance['id'],
                        'student_id' => $student['id'],
                        'student_name' => isset($student['full_name']) ? $student['full_name'] : '',
                        'roll_number' => isset($student['roll_number']) ? $student['roll_number'] : '',
                        'class_id' => $classId,
                        'date' => $date,
                        'time' => $time,
                        'status' => 'present',
                        'confidence_score' => isset($student['similarity']) ? $student['similarity'] : 100,
                        'image_path' => $savedImagePath,
                        'updated' => true
                    ];
                }
            } else {
                // Insert new attendance record
                $result = $this->db->exec(
                    "INSERT INTO attendance 
                     (student_id, class_id, date, time, status, marked_by, confidence_score, image_path) 
                     VALUES 
                     (:student_id, :class_id, :date, :time, :status, :marked_by, :confidence_score, :image_path)",
                    [
                        ':student_id' => $student['id'],
                        ':class_id' => $classId,
                        ':date' => $date,
                        ':time' => $time,
                        ':status' => 'present',
                        ':marked_by' => $teacherId,
                        ':confidence_score' => isset($student['similarity']) ? $student['similarity'] : 100,
                        ':image_path' => $savedImagePath
                    ]
                );
                
                if ($result) {
                    $id = $this->db->lastInsertId();
                    
                    $response['attendance'][] = [
                        'id' => $id,
                        'student_id' => $student['id'],
                        'student_name' => isset($student['full_name']) ? $student['full_name'] : '',
                        'roll_number' => isset($student['roll_number']) ? $student['roll_number'] : '',
                        'class_id' => $classId,
                        'date' => $date,
                        'time' => $time,
                        'status' => 'present',
                        'confidence_score' => isset($student['similarity']) ? $student['similarity'] : 100,
                        'image_path' => $savedImagePath,
                        'updated' => false
                    ];
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Mark attendance manually
     * 
     * @param int $studentId Student ID
     * @param int $classId Class ID
     * @param string $status Status (present/absent)
     * @param int $teacherId Teacher ID who is marking attendance
     * @param string $date Date in Y-m-d format (defaults to current date)
     * @return bool Success or failure
     */
    public function markManually($studentId, $classId, $status, $teacherId, $date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $time = date('H:i:s');
        
        // Check if attendance already marked for this day
        $existingAttendance = $this->db->getRow(
            "SELECT id FROM attendance 
             WHERE student_id = :student_id 
             AND class_id = :class_id 
             AND date = :date",
            [
                ':student_id' => $studentId,
                ':class_id' => $classId,
                ':date' => $date
            ]
        );
        
        if ($existingAttendance) {
            // Update existing attendance
            return $this->db->exec(
                "UPDATE attendance 
                 SET status = :status, 
                     time = :time, 
                     marked_by = :marked_by 
                 WHERE id = :id",
                [
                    ':status' => $status,
                    ':time' => $time,
                    ':marked_by' => $teacherId,
                    ':id' => $existingAttendance['id']
                ]
            );
        } else {
            // Insert new attendance record
            return $this->db->exec(
                "INSERT INTO attendance 
                 (student_id, class_id, date, time, status, marked_by) 
                 VALUES 
                 (:student_id, :class_id, :date, :time, :status, :marked_by)",
                [
                    ':student_id' => $studentId,
                    ':class_id' => $classId,
                    ':date' => $date,
                    ':time' => $time,
                    ':status' => $status,
                    ':marked_by' => $teacherId
                ]
            );
        }
    }
    
    /**
     * Get attendance for a student
     * 
     * @param int $studentId Student ID
     * @param int $classId Class ID (optional)
     * @param string $startDate Start date in Y-m-d format (optional)
     * @param string $endDate End date in Y-m-d format (optional)
     * @return array List of attendance records
     */
    public function getForStudent($studentId, $classId = null, $startDate = null, $endDate = null) {
        $sql = "SELECT a.*, c.name as class_name, u.full_name as teacher_name 
                FROM attendance a
                JOIN classes c ON a.class_id = c.id
                JOIN users u ON a.marked_by = u.id
                WHERE a.student_id = :student_id";
        
        $params = [':student_id' => $studentId];
        
        if ($classId) {
            $sql .= " AND a.class_id = :class_id";
            $params[':class_id'] = $classId;
        }
        
        if ($startDate) {
            $sql .= " AND a.date >= :start_date";
            $params[':start_date'] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND a.date <= :end_date";
            $params[':end_date'] = $endDate;
        }
        
        $sql .= " ORDER BY a.date DESC, a.time DESC";
        
        return $this->db->getRows($sql, $params);
    }
    
    /**
     * Get attendance for a class
     * 
     * @param int $classId Class ID
     * @param string $date Date in Y-m-d format (defaults to current date)
     * @param int $studentId Optional student ID filter
     * @return array List of attendance records with student details
     */
    public function getForClass($classId, $date = null, $studentId = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $sql = "SELECT a.*, s.roll_number, u.full_name as student_name, 
                       t.full_name as teacher_name
                FROM attendance a
                JOIN students s ON a.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN users t ON a.marked_by = t.id
                WHERE a.class_id = :class_id
                AND a.date = :date";
        
        $params = [
            ':class_id' => $classId,
            ':date' => $date
        ];
        
        if ($studentId) {
            $sql .= " AND a.student_id = :student_id";
            $params[':student_id'] = $studentId;
        }
        
        $sql .= " ORDER BY u.full_name";
        
        return $this->db->getRows($sql, $params);
    }
    
    /**
     * Get attendance records for a class within a date range
     * 
     * @param int $classId Class ID
     * @param string $fromDate Start date in Y-m-d format
     * @param string $toDate End date in Y-m-d format
     * @param int $studentId Optional student ID filter
     * @return array List of attendance records with student details
     */
    public function getForClassDateRange($classId, $fromDate, $toDate, $studentId = null) {
        $sql = "SELECT a.*, s.roll_number, u.full_name as student_name, 
                       t.full_name as teacher_name
                FROM attendance a
                JOIN students s ON a.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN users t ON a.marked_by = t.id
                WHERE a.class_id = :class_id
                AND a.date BETWEEN :from_date AND :to_date";
        
        $params = [
            ':class_id' => $classId,
            ':from_date' => $fromDate,
            ':to_date' => $toDate
        ];
        
        if ($studentId) {
            $sql .= " AND a.student_id = :student_id";
            $params[':student_id'] = $studentId;
        }
        
        $sql .= " ORDER BY a.date DESC, u.full_name";
        
        return $this->db->getRows($sql, $params);
    }
    
    /**
     * Get attendance statistics for a student
     * 
     * @param int $studentId Student ID
     * @param int $classId Class ID (optional)
     * @return array Statistics with present and absent counts
     */
    public function getStudentStats($studentId, $classId = null) {
        $sql = "SELECT 
                  COUNT(*) as total,
                  SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                  SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
                FROM attendance
                WHERE student_id = :student_id";
        
        $params = [':student_id' => $studentId];
        
        if ($classId) {
            $sql .= " AND class_id = :class_id";
            $params[':class_id'] = $classId;
        }
        
        $stats = $this->db->getRow($sql, $params);
        
        if ($stats) {
            // Calculate attendance percentage
            $stats['present_percentage'] = ($stats['total'] > 0) 
                ? round(($stats['present_count'] / $stats['total']) * 100, 2) 
                : 0;
        }
        
        return $stats;
    }
    
    /**
     * Get attendance statistics for a class
     * 
     * @param int $classId Class ID
     * @param string $date Date in Y-m-d format (optional)
     * @return array Statistics with present and absent counts
     */
    public function getClassStats($classId, $date = null) {
        $sql = "SELECT 
                  COUNT(DISTINCT s.id) as total_students,
                  COUNT(a.id) as total_records,
                  SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                  SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
                FROM classes c
                JOIN semesters sem ON c.semester_id = sem.id
                JOIN students s ON s.semester_id = sem.id
                LEFT JOIN attendance a ON a.student_id = s.id AND a.class_id = c.id";
        
        $params = [':class_id' => $classId];
        
        if ($date) {
            $sql .= " AND a.date = :date";
            $params[':date'] = $date;
        }
        
        $sql .= " WHERE c.id = :class_id";
        
        $stats = $this->db->getRow($sql, $params);
        
        if ($stats) {
            // Calculate attendance percentage
            $stats['present_percentage'] = ($stats['total_records'] > 0) 
                ? round(($stats['present_count'] / $stats['total_records']) * 100, 2) 
                : 0;
            
            // Calculate absent students
            $stats['absent_students'] = $stats['total_students'] - ($stats['present_count'] / ($stats['total_records'] ?: 1));
        }
        
        return $stats;
    }
    
    /**
     * Delete an attendance record
     * 
     * @param int $attendanceId Attendance ID
     * @return bool Success or failure
     */
    public function delete($attendanceId) {
        // Get the attendance record to delete associated image
        $attendance = $this->db->getRow(
            "SELECT image_path FROM attendance WHERE id = :id",
            [':id' => $attendanceId]
        );
        
        // Delete the image if exists
        if ($attendance && !empty($attendance['image_path']) && file_exists($attendance['image_path'])) {
            unlink($attendance['image_path']);
        }
        
        // Delete the attendance record
        return $this->db->exec(
            "DELETE FROM attendance WHERE id = :id",
            [':id' => $attendanceId]
        );
    }

    /**
     * Mark attendance for a student
     * 
     * @param int $studentId Student ID
     * @param int $courseId Course ID
     * @param string $date Date of attendance
     * @param string $status Attendance status
     * @return bool True if successful, false otherwise
     */
    public function markAttendance($studentId, $courseId, $date, $status) {
        // Check if attendance already exists for this student, course and date
        $query = "SELECT * FROM attendance WHERE student_id = ? AND course_id = ? AND date = ?";
        $this->db->query($query);
        $this->db->bind(1, $studentId);
        $this->db->bind(2, $courseId);
        $this->db->bind(3, $date);
        $result = $this->db->single();

        if ($result) {
            // Update existing attendance
            $query = "UPDATE attendance SET status = ? WHERE student_id = ? AND course_id = ? AND date = ?";
            $this->db->query($query);
            $this->db->bind(1, $status);
            $this->db->bind(2, $studentId);
            $this->db->bind(3, $courseId);
            $this->db->bind(4, $date);
            if ($this->db->execute()) {
                return true;
            } else {
                return false;
            }
        } else {
            // Insert new attendance
            $query = "INSERT INTO attendance (student_id, course_id, date, status) VALUES (?, ?, ?, ?)";
            $this->db->query($query);
            $this->db->bind(1, $studentId);
            $this->db->bind(2, $courseId);
            $this->db->bind(3, $date);
            $this->db->bind(4, $status);
            if ($this->db->execute()) {
                return true;
            } else {
                return false;
            }
        }
    }
}
?> 