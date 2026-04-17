-- ============================================
-- COLLEGE MANAGEMENT SYSTEM - DATABASE SCHEMA
-- ============================================

CREATE DATABASE IF NOT EXISTS college_management;
USE college_management;

-- USERS TABLE (shared login for all roles)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('director','coordinator','faculty','student') NOT NULL,
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    phone VARCHAR(15),
    address TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- DEPARTMENTS
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CLASSES
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    section VARCHAR(10),
    department_id INT,
    semester INT,
    academic_year VARCHAR(10),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- STUDENTS
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    roll_number VARCHAR(30) UNIQUE NOT NULL,
    class_id INT,
    department_id INT,
    admission_year YEAR,
    date_of_birth DATE,
    gender ENUM('male','female','other'),
    guardian_name VARCHAR(100),
    guardian_phone VARCHAR(15),
    total_fees DECIMAL(10,2) DEFAULT 0,
    fees_paid DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- TEACHERS / FACULTY
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    employee_id VARCHAR(30) UNIQUE NOT NULL,
    department_id INT,
    designation VARCHAR(100),
    qualification VARCHAR(100),
    joining_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- SUBJECTS
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    department_id INT,
    class_id INT,
    teacher_id INT,
    credits INT DEFAULT 3,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

-- ATTENDANCE
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present','absent','late') DEFAULT 'absent',
    marked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance (student_id, subject_id, date)
);

-- FEES PAYMENTS
CREATE TABLE fee_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_mode ENUM('cash','online','cheque','dd') DEFAULT 'cash',
    receipt_number VARCHAR(50) UNIQUE,
    description TEXT,
    received_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL
);

-- CLASS TESTS
CREATE TABLE class_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    total_marks INT NOT NULL,
    test_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- TEST SUBMISSIONS / RESULTS
CREATE TABLE test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    student_id INT NOT NULL,
    marks_obtained DECIMAL(5,2),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT,
    FOREIGN KEY (test_id) REFERENCES class_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_result (test_id, student_id)
);

-- NOTICES
CREATE TABLE notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    posted_by INT,
    target_role ENUM('all','student','faculty','coordinator') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================
-- SAMPLE DATA
-- =====================

-- Departments
INSERT INTO departments (name, code) VALUES
('Computer Science', 'CS'),
('Information Technology', 'IT'),
('Electronics', 'EC'),
('Mechanical', 'ME');

-- Classes
INSERT INTO classes (name, section, department_id, semester, academic_year) VALUES
('FY-CS-A', 'A', 1, 1, '2024-25'),
('SY-CS-A', 'A', 1, 3, '2024-25'),
('TY-CS-A', 'A', 1, 5, '2024-25'),
('FY-IT-A', 'A', 2, 1, '2024-25');

-- Users (password = 'password123' hashed)
INSERT INTO users (name, email, password, role, phone) VALUES
('Dr. Rajesh Kumar', 'director@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'director', '9876543210'),
('Prof. Anita Sharma', 'coordinator@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coordinator', '9876543211'),
('Prof. Suresh Patil', 'faculty@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', '9876543212'),
('Prof. Meera Joshi', 'meera@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', '9876543213'),
('Amit Desai', 'amit@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9876543220'),
('Priya Kulkarni', 'priya@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9876543221'),
('Rahul Shinde', 'rahul@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9876543222'),
('Sneha More', 'sneha@student.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '9876543223');

-- Teachers
INSERT INTO teachers (user_id, employee_id, department_id, designation, qualification, joining_date) VALUES
(2, 'EMP001', 1, 'Faculty Coordinator', 'M.Tech CS', '2018-07-01'),
(3, 'EMP002', 1, 'Assistant Professor', 'M.Tech CS', '2019-08-01'),
(4, 'EMP003', 1, 'Associate Professor', 'Ph.D CS', '2015-06-01');

-- Students
INSERT INTO students (user_id, roll_number, class_id, department_id, admission_year, date_of_birth, gender, guardian_name, guardian_phone, total_fees, fees_paid) VALUES
(5, 'CS2024001', 1, 1, 2024, '2005-03-15', 'male', 'Vijay Desai', '9876543230', 85000, 60000),
(6, 'CS2024002', 1, 1, 2024, '2005-07-22', 'female', 'Sunil Kulkarni', '9876543231', 85000, 85000),
(7, 'CS2024003', 1, 1, 2024, '2004-11-10', 'male', 'Prakash Shinde', '9876543232', 85000, 42500),
(8, 'CS2024004', 1, 1, 2024, '2005-01-05', 'female', 'Ashok More', '9876543233', 85000, 70000);

-- Subjects
INSERT INTO subjects (name, code, department_id, class_id, teacher_id, credits) VALUES
('Data Structures', 'CS101', 1, 1, 2, 4),
('Mathematics I', 'MA101', 1, 1, 3, 4),
('Programming in C', 'CS102', 1, 1, 2, 3),
('Digital Electronics', 'EC101', 1, 1, 3, 3);

-- Sample Attendance
INSERT INTO attendance (student_id, subject_id, class_id, date, status, marked_by) VALUES
(1, 1, 1, '2025-01-06', 'present', 3),
(1, 1, 1, '2025-01-07', 'present', 3),
(1, 1, 1, '2025-01-08', 'absent', 3),
(1, 1, 1, '2025-01-09', 'present', 3),
(2, 1, 1, '2025-01-06', 'present', 3),
(2, 1, 1, '2025-01-07', 'absent', 3),
(2, 1, 1, '2025-01-08', 'present', 3),
(2, 1, 1, '2025-01-09', 'present', 3),
(3, 1, 1, '2025-01-06', 'absent', 3),
(3, 1, 1, '2025-01-07', 'present', 3),
(3, 1, 1, '2025-01-08', 'present', 3),
(3, 1, 1, '2025-01-09', 'absent', 3);

-- Sample Fee Payments
INSERT INTO fee_payments (student_id, amount, payment_date, payment_mode, receipt_number, description, received_by) VALUES
(1, 60000, '2024-07-15', 'online', 'REC001', 'First installment', 1),
(2, 85000, '2024-07-10', 'cash', 'REC002', 'Full fees', 1),
(3, 42500, '2024-07-20', 'cheque', 'REC003', 'First installment', 1),
(4, 70000, '2024-07-18', 'online', 'REC004', 'Partial payment', 1);

-- Class Tests
INSERT INTO class_tests (title, subject_id, class_id, teacher_id, total_marks, test_date) VALUES
('Unit Test 1 - DS', 1, 1, 2, 25, '2025-01-15'),
('Unit Test 1 - Math', 2, 1, 3, 25, '2025-01-16'),
('Assignment 1 - C Programming', 3, 1, 2, 10, '2025-01-20');

-- Test Results
INSERT INTO test_results (test_id, student_id, marks_obtained) VALUES
(1, 1, 20),
(1, 2, 23),
(1, 3, 18),
(1, 4, 21),
(2, 1, 19),
(2, 2, 22),
(3, 1, 9),
(3, 2, 10);

-- Notices
INSERT INTO notices (title, content, posted_by, target_role) VALUES
('Welcome to New Semester', 'Dear Students and Faculty, Welcome to the new academic semester 2024-25. All the best!', 1, 'all'),
('Fee Payment Reminder', 'Last date for fee payment is 31st January 2025. Kindly pay your dues.', 1, 'student'),
('Faculty Meeting', 'All faculty members are requested to attend the meeting on 20th January at 10 AM.', 2, 'faculty');
