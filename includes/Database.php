<?php
class Database {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        try {
            // Using PDO instead of SQLite3
            $this->db = new PDO('sqlite:' . DB_PATH);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    // Singleton pattern to ensure only one database connection
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Execute a query without returning results (INSERT, UPDATE, DELETE)
    public function exec($sql, $params = []) {
        try {
            if (empty($params)) {
                return $this->db->exec($sql);
            } else {
                $stmt = $this->db->prepare($sql);
                foreach ($params as $param => $value) {
                    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                    $stmt->bindValue($param, $value, $type);
                }
                return $stmt->execute();
            }
        } catch (PDOException $e) {
            error_log("Database exec error: " . $e->getMessage());
            return false;
        }
    }
    
    // Execute a query and return all results
    public function query($sql, $params = []) {
        try {
            if (empty($params)) {
                return $this->db->query($sql);
            } else {
                $stmt = $this->db->prepare($sql);
                foreach ($params as $param => $value) {
                    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                    $stmt->bindValue($param, $value, $type);
                }
                $stmt->execute();
                return $stmt;
            }
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }
    
    // Prepare a statement
    public function prepare($sql) {
        return $this->db->prepare($sql);
    }
    
    // Get a single row
    public function getRow($sql, $params = []) {
        $result = $this->query($sql, $params);
        if ($result) {
            return $result->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }
    
    // Get all rows as an array
    public function getRows($sql, $params = []) {
        $result = $this->query($sql, $params);
        if ($result) {
            return $result->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }
    
    // Get the last inserted row ID
    public function lastInsertId() {
        return $this->db->lastInsertId();
    }
    
    // Count rows
    public function count($sql, $params = []) {
        $result = $this->query($sql, $params);
        if ($result) {
            $row = $result->fetch(PDO::FETCH_NUM);
            return (int)$row[0];
        }
        return 0;
    }
    
    // Close the database connection
    public function close() {
        $this->db = null;
    }
    
    // Destructor to ensure the database is closed
    public function __destruct() {
        $this->close();
    }
}
?> 