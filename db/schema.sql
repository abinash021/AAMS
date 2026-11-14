-- ROLES TABLE
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

-- Insert roles
INSERT INTO roles (name) VALUES ('admin');
INSERT INTO roles (name) VALUES ('teacher');
INSERT INTO roles (name) VALUES ('student');

-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    full_name TEXT NOT NULL,
    role_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- BRANCHES TABLE (e.g., Electrical, Mechanical)
CREATE TABLE IF NOT EXISTS branches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT
);

-- SEMESTERS TABLE
CREATE TABLE IF NOT EXISTS semesters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    branch_id INTEGER NOT NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    UNIQUE(name, branch_id)
);

-- CLASSES TABLE
CREATE TABLE IF NOT EXISTS classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    semester_id INTEGER NOT NULL,
    teacher_id INTEGER NOT NULL,
    FOREIGN KEY (semester_id) REFERENCES semesters(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    UNIQUE(name, semester_id)
);

-- STUDENTS TABLE
CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    roll_number TEXT NOT NULL,
    semester_id INTEGER NOT NULL,
    face_image_path TEXT,
    aws_face_id TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id),
    UNIQUE(roll_number, semester_id)
);

-- ATTENDANCE TABLE
CREATE TABLE IF NOT EXISTS attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    class_id INTEGER NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    status TEXT NOT NULL, -- present, absent
    marked_by INTEGER NOT NULL, -- teacher who marked the attendance
    confidence_score REAL, -- AWS Rekognition confidence score
    image_path TEXT, -- Path to the captured image used for recognition
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (marked_by) REFERENCES users(id),
    UNIQUE(student_id, class_id, date)
);

-- SESSION LOGS TABLE
CREATE TABLE IF NOT EXISTS session_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP,
    ip_address TEXT,
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
); 