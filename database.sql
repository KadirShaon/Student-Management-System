-- ================================================
--  Student Management System — SE322
--  Database: sms_db
-- ================================================

CREATE DATABASE IF NOT EXISTS sms_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sms_db;

-- ------------------------------------------------
-- Table: classes
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS classes (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(80)  NOT NULL,
    section    VARCHAR(10)  NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------
-- Table: students
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id             INT           AUTO_INCREMENT PRIMARY KEY,
    student_code   VARCHAR(20)   NOT NULL UNIQUE,
    full_name      VARCHAR(150)  NOT NULL,
    email          VARCHAR(150)  NOT NULL UNIQUE,
    phone          VARCHAR(20)   DEFAULT NULL,
    gender         ENUM('Male','Female','Other') NOT NULL,
    dob            DATE          DEFAULT NULL,
    class_id       INT           DEFAULT NULL,
    address        TEXT          DEFAULT NULL,
    status         ENUM('Active','Inactive','Suspended') DEFAULT 'Active',
    enrolled_on    DATE          DEFAULT (CURRENT_DATE),
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_class
        FOREIGN KEY (class_id) REFERENCES classes(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------
-- Table: subjects
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS subjects (
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    code        VARCHAR(20)  NOT NULL UNIQUE,
    class_id    INT          DEFAULT NULL,
    teacher     VARCHAR(100) DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subject_class
        FOREIGN KEY (class_id) REFERENCES classes(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------
-- Table: marks
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS marks (
    id             INT            AUTO_INCREMENT PRIMARY KEY,
    student_id     INT            NOT NULL,
    subject_id     INT            NOT NULL,
    exam_type      ENUM('Class Test','Mid Term','Final Exam','Assignment') NOT NULL,
    marks_obtained DECIMAL(6,2)   NOT NULL,
    total_marks    DECIMAL(6,2)   NOT NULL DEFAULT 100,
    exam_date      DATE           DEFAULT NULL,
    created_at     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mark_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_mark_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------
-- Table: attendance
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS attendance (
    id         INT  AUTO_INCREMENT PRIMARY KEY,
    student_id INT  NOT NULL,
    att_date   DATE NOT NULL,
    status     ENUM('Present','Absent','Late','Excused') NOT NULL DEFAULT 'Present',
    note       VARCHAR(200) DEFAULT NULL,
    UNIQUE KEY uq_att (student_id, att_date),
    CONSTRAINT fk_att_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------
-- Table: notices
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS notices (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(200) NOT NULL,
    content    TEXT         NOT NULL,
    author     VARCHAR(100) DEFAULT 'Admin',
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ================================================
--  SAMPLE DATA
-- ================================================

INSERT INTO classes (name, section) VALUES
    ('Class Six',   'A'),
    ('Class Seven', 'A'),
    ('Class Eight', 'B'),
    ('Class Nine',  'A'),
    ('Class Ten',   'B');

INSERT INTO subjects (name, code, class_id, teacher) VALUES
    ('Mathematics',     'MATH-6',  1, 'Mr. Karim Uddin'),
    ('English',         'ENG-6',   1, 'Ms. Rina Akter'),
    ('General Science', 'SCI-6',   1, 'Mr. Rafiq Hossain'),
    ('Bangla',          'BAN-7',   2, 'Ms. Priti Das'),
    ('History',         'HIS-7',   2, 'Mr. Jahangir Alam'),
    ('Physics',         'PHY-9',   4, 'Mr. Sohel Rana'),
    ('Chemistry',       'CHEM-9',  4, 'Ms. Nasrin Jahan'),
    ('Mathematics',     'MATH-10', 5, 'Mr. Aminul Islam');

INSERT INTO students
    (student_code, full_name, email, phone, gender, dob, class_id, address, status)
VALUES
    ('STU-001','Rahim Ahmed',    'rahim@example.com',   '01711-000001','Male',  '2010-05-15',1,'Dhaka, Bangladesh',   'Active'),
    ('STU-002','Fatima Begum',   'fatima@example.com',  '01711-000002','Female','2010-08-20',1,'Chittagong, Bangladesh','Active'),
    ('STU-003','Karim Hossain',  'karim@example.com',   '01711-000003','Male',  '2009-12-10',2,'Sylhet, Bangladesh',   'Active'),
    ('STU-004','Nadia Islam',    'nadia@example.com',   '01711-000004','Female','2011-03-25',4,'Rajshahi, Bangladesh', 'Active'),
    ('STU-005','Sohel Rana',     'sohel@example.com',   '01711-000005','Male',  '2010-07-18',2,'Khulna, Bangladesh',   'Active'),
    ('STU-006','Riya Chowdhury', 'riya@example.com',    '01711-000006','Female','2009-09-05',5,'Barisal, Bangladesh',  'Active'),
    ('STU-007','Tariq Aziz',     'tariq@example.com',   '01711-000007','Male',  '2010-11-22',4,'Mymensingh, Bangladesh','Active'),
    ('STU-008','Suma Khatun',    'suma@example.com',    '01711-000008','Female','2011-01-14',3,'Comilla, Bangladesh',  'Inactive');

INSERT INTO marks (student_id, subject_id, exam_type, marks_obtained, total_marks, exam_date) VALUES
    (1, 1, 'Mid Term',   78, 100, '2025-03-15'),
    (1, 2, 'Mid Term',   85, 100, '2025-03-16'),
    (1, 3, 'Mid Term',   72, 100, '2025-03-17'),
    (2, 1, 'Mid Term',   91, 100, '2025-03-15'),
    (2, 2, 'Mid Term',   88, 100, '2025-03-16'),
    (3, 4, 'Final Exam', 74, 100, '2025-06-10'),
    (4, 6, 'Final Exam', 82, 100, '2025-06-12'),
    (5, 4, 'Class Test', 65,  80, '2025-02-20'),
    (6, 8, 'Assignment', 45,  50, '2025-04-01'),
    (7, 6, 'Mid Term',   69, 100, '2025-03-15');

INSERT INTO attendance (student_id, att_date, status) VALUES
    (1, CURDATE(), 'Present'),
    (2, CURDATE(), 'Present'),
    (3, CURDATE(), 'Absent'),
    (4, CURDATE(), 'Late'),
    (5, CURDATE(), 'Present'),
    (6, CURDATE(), 'Present'),
    (7, CURDATE(), 'Excused'),
    (8, CURDATE(), 'Absent');

INSERT INTO notices (title, content, author) VALUES
    ('Annual Examination Schedule 2025',
     'The Annual Examination will begin on 15th June 2025. All students are required to complete their syllabus and prepare accordingly. Admit cards will be distributed one week before the exam.',
     'Principal'),
    ('Sports Day 2025 Announcement',
     'Annual Sports Day is scheduled for 20th May 2025. Participation is compulsory for all students. Please collect your event registration forms from the class teacher.',
     'Sports Committee'),
    ('Fee Submission Reminder',
     'Students who have not yet submitted their monthly tuition fees are requested to do so by 10th of this month. Late fees will be charged after the deadline.',
     'Admin');
