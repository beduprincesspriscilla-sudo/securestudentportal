CREATE DATABASE IF NOT EXISTS securestudentportal;
USE securestudentportal;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id VARCHAR(20) UNIQUE NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  phone_enc TEXT NOT NULL,                -- phone number stored ENCRYPTED (AES-256-GCM)
  password_hash VARCHAR(255) NOT NULL,    -- bcrypt hash, never plain text
  role ENUM('student','admin') DEFAULT 'student',   -- RBAC
  otp_hash VARCHAR(255) NULL,             -- MFA code (hashed)
  otp_expires DATETIME NULL,              -- MFA code expiry
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  course VARCHAR(60),
  grade VARCHAR(3),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE security_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(40),
  detail VARCHAR(255),
  ip VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
