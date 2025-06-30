CREATE DATABASE IF NOT EXISTS kenha_lms;
USE kenha_lms;
-- USERS TABLE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    dob DATE NOT NULL,
    department VARCHAR(100),
    region VARCHAR(100),
    role ENUM('employee', 'dept-head', 'hr', 'trainer') DEFAULT 'employee',
    profile_photo VARCHAR(255) DEFAULT NULL,
    doe DATE DEFAULT CURRENT_DATE
);

-- TRAININGS TABLE
CREATE TABLE trainings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('mandatory', 'management', 'external', 'retirement', 'erp', 'isms') NOT NULL,
    department VARCHAR(100),
    region VARCHAR(100),
    material_link TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- TRAINING REQUESTS TABLE
CREATE TABLE training_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    training_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
);

-- SCHEDULED TRAININGS TABLE
CREATE TABLE scheduled_trainings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    training_id INT NOT NULL,
    scheduled_by INT NOT NULL,
    schedule_date DATE NOT NULL,
    location VARCHAR(255),
    FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE,
    FOREIGN KEY (scheduled_by) REFERENCES users(id) ON DELETE CASCADE
);

-- SCHEDULES TABLE
CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    training_id INT NOT NULL,
    date DATE NOT NULL,
    time_from TIME,
    time_to TIME,
    location VARCHAR(255),
    region VARCHAR(100),
    FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
);

-- ENROLLMENTS TABLE
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    training_id INT NOT NULL,
    enrollment_date DATE DEFAULT CURRENT_DATE,
    status ENUM('enrolled', 'completed', 'dropped') DEFAULT 'enrolled',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
);

-- CERTIFICATES TABLE
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    training_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    issued_on DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
);

-- REPORTS TABLE
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    created_by INT,
    report_date DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- REQUESTS TABLE (general HR/admin requests)
CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('training', 'certificate', 'other'),
    details TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


