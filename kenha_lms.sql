CREATE DATABASE IF NOT EXISTS kenha_lms;
USE kenha_lms;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  dob DATE NOT NULL,
  doe DATE NOT NULL,
  role ENUM('employee', 'hr', 'hr-department', 'dept-head', 'trainer') NOT NULL,
  department VARCHAR(50) NOT NULL,
  region VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
