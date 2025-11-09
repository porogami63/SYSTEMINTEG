-- ============================================
-- MediArchive Database Schema
-- Digital Medical Certificate & Verification System
-- ============================================

CREATE DATABASE IF NOT EXISTS mediarchive;
USE mediarchive;

-- Users table (for both clinic admins and patients)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('clinic_admin', 'patient') NOT NULL DEFAULT 'patient',
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clinics table
CREATE TABLE IF NOT EXISTS clinics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    clinic_name VARCHAR(100) NOT NULL,
    license_number VARCHAR(50),
    medical_license VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    address TEXT,
    contact_phone VARCHAR(20),
    is_available BOOLEAN DEFAULT TRUE,
    available_from TIME DEFAULT '09:00:00',
    available_to TIME DEFAULT '17:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_specialization (specialization),
    INDEX idx_is_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Patients table
CREATE TABLE IF NOT EXISTS patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    patient_code VARCHAR(20) UNIQUE NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_patient_code (patient_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Medical certificates table
CREATE TABLE IF NOT EXISTS certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cert_id VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique certificate ID for QR validation',
    clinic_id INT NOT NULL,
    patient_id INT NOT NULL,
    issued_by VARCHAR(100) NOT NULL COMMENT 'Doctor name',
    doctor_license VARCHAR(50),
    issue_date DATE NOT NULL,
    expiry_date DATE,
    purpose VARCHAR(200) NOT NULL,
    diagnosis TEXT,
    recommendations TEXT,
    file_path VARCHAR(255) COMMENT 'Path to uploaded certificate file',
    status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_cert_id (cert_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_clinic_id (clinic_id),
    INDEX idx_issue_date (issue_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Certificate requests table
CREATE TABLE IF NOT EXISTS certificate_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    clinic_id INT NOT NULL,
    requested_specialization VARCHAR(100) NOT NULL,
    purpose VARCHAR(200) NOT NULL,
    details TEXT,
    spec_answers TEXT COMMENT 'JSON data for specialization-specific questions',
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id),
    INDEX idx_clinic_id (clinic_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verification logs table (for tracking QR scans)
CREATE TABLE IF NOT EXISTS verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cert_id INT NOT NULL,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    FOREIGN KEY (cert_id) REFERENCES certificates(id) ON DELETE CASCADE,
    INDEX idx_cert_id (cert_id),
    INDEX idx_verified_at (verified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. John Smith', 'clinic_admin'),
('patient1', 'patient1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Johnson', 'patient'),
('patient2', 'patient2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Williams', 'patient');

-- Password for all demo users: password

INSERT INTO clinics (user_id, clinic_name, license_number, medical_license, specialization, address, contact_phone, is_available) VALUES
(1, 'Green Valley Medical Center', 'CL-2024-001', 'MD-LIC-2024001', 'General Medicine', '123 Medical Blvd, City', '555-0100', TRUE);

INSERT INTO patients (user_id, patient_code, date_of_birth, gender, address) VALUES
(2, 'PAT-0001', '1990-05-15', 'Female', '456 Oak Street, City'),
(3, 'PAT-0002', '1985-08-22', 'Male', '789 Pine Avenue, City');

