-- ============================================
-- MediArchive Database Schema - Complete Setup
-- Digital Medical Certificate & Verification System
-- Version: 4.0 (Production Ready)
-- Last Updated: November 9, 2025
-- ============================================
-- 
-- This file contains the complete database schema including:
-- - Core tables (users, clinics, patients, certificates)
-- - Feature tables (appointments, chat, notifications)
-- - Audit logging and analytics
-- - All migrations consolidated
-- 
-- Simply import this file to set up the complete database
-- ============================================

CREATE DATABASE IF NOT EXISTS mediarchive;
USE mediarchive;

-- ============================================
-- CORE TABLES
-- ============================================

-- Users table (clinic admins, patients, and web admins)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('clinic_admin', 'patient', 'web_admin') NOT NULL DEFAULT 'patient',
    phone VARCHAR(20),
    profile_photo VARCHAR(255) NULL COMMENT 'Path to user profile photo',
    home_address TEXT NULL COMMENT 'User home address',
    dashboard_layout TEXT DEFAULT NULL COMMENT 'JSON for widget positions and preferences',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
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
    signature_path VARCHAR(255) NULL COMMENT 'Path to doctor signature image',
    seal_path VARCHAR(255) NULL COMMENT 'Path to clinic seal image',
    is_available BOOLEAN DEFAULT TRUE COMMENT 'Clinic availability for chat/appointments',
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
    is_available TINYINT(1) DEFAULT 1 COMMENT 'Patient availability for chat',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_patient_code (patient_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- CERTIFICATE MANAGEMENT
-- ============================================

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
    INDEX idx_issue_date (issue_date),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_status (status)
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

-- Certificate notes table (internal notes for clinic staff)
CREATE TABLE IF NOT EXISTS certificate_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    certificate_id INT NOT NULL,
    user_id INT NOT NULL COMMENT 'User who created the note',
    note TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT TRUE COMMENT 'Internal notes only visible to clinic staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (certificate_id) REFERENCES certificates(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_certificate_id (certificate_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- APPOINTMENTS SYSTEM
-- ============================================

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    clinic_id INT NOT NULL,
    requested_specialization VARCHAR(100) NOT NULL,
    appointment_date DATE NOT NULL,
    time_slot TIME NOT NULL,
    purpose VARCHAR(200) NOT NULL,
    details TEXT,
    status ENUM('pending','approved','rescheduled','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id),
    INDEX idx_clinic_id (clinic_id),
    INDEX idx_date_time (appointment_date, time_slot),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- CHAT SYSTEM
-- ============================================

-- Chat conversations table
CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    clinic_id INT NOT NULL,
    last_message_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    UNIQUE KEY unique_conversation (patient_id, clinic_id),
    INDEX idx_last_message (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chat messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL COMMENT 'user_id of sender',
    message TEXT NOT NULL,
    attachment_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to attached file',
    attachment_name VARCHAR(255) DEFAULT NULL COMMENT 'Original filename',
    attachment_type VARCHAR(50) DEFAULT NULL COMMENT 'MIME type',
    attachment_size INT DEFAULT NULL COMMENT 'File size in bytes',
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (conversation_id),
    INDEX idx_sender (sender_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- NOTIFICATIONS SYSTEM
-- ============================================

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    category VARCHAR(50) DEFAULT NULL COMMENT 'e.g., certificate_created, expiry_warning, system_update',
    is_important BOOLEAN DEFAULT FALSE,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL COMMENT 'e.g., certificate_created, expiry_warning, system_update',
    enabled BOOLEAN DEFAULT TRUE,
    email_notification BOOLEAN DEFAULT TRUE,
    in_app_notification BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, category),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- AUDIT & VERIFICATION
-- ============================================

-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL COMMENT 'e.g., CREATE_CERTIFICATE, VIEW_CERTIFICATE, DELETE_CERTIFICATE',
    entity_type VARCHAR(50) NOT NULL COMMENT 'e.g., certificate, patient, user',
    entity_id INT COMMENT 'ID of the entity affected',
    details TEXT COMMENT 'JSON or text details of the action',
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_created_at (created_at)
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

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Insert demo users (password for all: "password")
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. John Smith', 'clinic_admin'),
('patient1', 'patient1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Johnson', 'patient'),
('patient2', 'patient2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Williams', 'patient'),
('webadmin', 'webadmin@mediarchive.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Web Administrator', 'web_admin');

-- Insert demo clinic
INSERT INTO clinics (user_id, clinic_name, license_number, medical_license, specialization, address, contact_phone, is_available) VALUES
(1, 'Green Valley Medical Center', 'CL-2024-001', 'MD-LIC-2024001', 'General Medicine', '123 Medical Blvd, City', '555-0100', TRUE);

-- Insert demo patients
INSERT INTO patients (user_id, patient_code, date_of_birth, gender, address, is_available) VALUES
(2, 'PAT-0001', '1990-05-15', 'Female', '456 Oak Street, City', 1),
(3, 'PAT-0002', '1985-08-22', 'Male', '789 Pine Avenue, City', 1);

-- ============================================
-- DATABASE SETUP COMPLETE
-- ============================================
-- 
-- Default Login Credentials:
-- 
-- Clinic Admin:
--   Username: admin
--   Password: password
-- 
-- Patient:
--   Username: patient1
--   Password: password
-- 
-- Web Admin:
--   Username: webadmin
--   Password: password
-- 
-- ============================================
