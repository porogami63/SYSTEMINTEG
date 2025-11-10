-- ============================================
-- MediArchive Database Schema - Complete Setup
-- Digital Medical Certificate & Verification System
-- Version: 4.1 (Production Ready)
-- Last Updated: November 10, 2025
-- ============================================
-- 
-- This file contains the complete database schema including:
-- - Core tables (users, clinics, patients, certificates)
-- - Feature tables (appointments, chat, notifications)
-- - Security audits and comprehensive audit logging
-- - System statistics and analytics
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

-- Security audits table (for security assessment tracking)
CREATE TABLE IF NOT EXISTS security_audits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    audit_data TEXT NOT NULL COMMENT 'JSON data containing audit results',
    score INT NOT NULL COMMENT 'Security score out of 100',
    status VARCHAR(20) NOT NULL COMMENT 'excellent, good, fair, or poor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Insert demo users (password for all: "password")
INSERT INTO users (username, email, password, full_name, role, phone) VALUES
-- Clinic Admins (Doctors)
('dr.smith', 'dr.smith@greenvalley.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. John Smith', 'clinic_admin', '555-0100'),
('dr.garcia', 'dr.garcia@cityhospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Maria Garcia', 'clinic_admin', '555-0200'),
('dr.chen', 'dr.chen@heartcare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. David Chen', 'clinic_admin', '555-0300'),
('dr.patel', 'dr.patel@pediatrics.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Priya Patel', 'clinic_admin', '555-0400'),
('dr.johnson', 'dr.johnson@orthoclinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Michael Johnson', 'clinic_admin', '555-0500'),

-- Patients
('alice.j', 'alice.johnson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Johnson', 'patient', '555-1001'),
('bob.w', 'bob.williams@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Williams', 'patient', '555-1002'),
('carol.d', 'carol.davis@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carol Davis', 'patient', '555-1003'),
('david.m', 'david.miller@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Miller', 'patient', '555-1004'),
('emma.w', 'emma.wilson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma Wilson', 'patient', '555-1005'),
('frank.t', 'frank.taylor@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Frank Taylor', 'patient', '555-1006'),
('grace.a', 'grace.anderson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Grace Anderson', 'patient', '555-1007'),
('henry.t', 'henry.thomas@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Henry Thomas', 'patient', '555-1008'),
('isabel.m', 'isabel.martinez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Isabel Martinez', 'patient', '555-1009'),
('james.l', 'james.lee@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James Lee', 'patient', '555-1010'),

-- Web Admin
('webadmin', 'webadmin@mediarchive.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Web Administrator', 'web_admin', '555-9999');

-- Insert demo clinics
INSERT INTO clinics (user_id, clinic_name, license_number, medical_license, specialization, address, contact_phone, is_available) VALUES
(1, 'Green Valley Medical Center', 'CL-2024-001', 'MD-LIC-2024001', 'General Medicine', '123 Medical Blvd, Metro City', '555-0100', TRUE),
(2, 'City Hospital Clinic', 'CL-2024-002', 'MD-LIC-2024002', 'Internal Medicine', '456 Healthcare Ave, Metro City', '555-0200', TRUE),
(3, 'Heart Care Specialists', 'CL-2024-003', 'MD-LIC-2024003', 'Cardiology', '789 Cardiac Lane, Metro City', '555-0300', TRUE),
(4, 'Little Stars Pediatrics', 'CL-2024-004', 'MD-LIC-2024004', 'Pediatrics', '321 Children Way, Metro City', '555-0400', TRUE),
(5, 'OrthoClinic Plus', 'CL-2024-005', 'MD-LIC-2024005', 'Orthopedics', '654 Bone Street, Metro City', '555-0500', TRUE);

-- Insert demo patients
INSERT INTO patients (user_id, patient_code, date_of_birth, gender, address, is_available) VALUES
(6, 'PAT-0001', '1990-05-15', 'Female', '456 Oak Street, Metro City', 1),
(7, 'PAT-0002', '1985-08-22', 'Male', '789 Pine Avenue, Metro City', 1),
(8, 'PAT-0003', '1992-03-10', 'Female', '123 Maple Drive, Metro City', 1),
(9, 'PAT-0004', '1988-11-30', 'Male', '456 Birch Road, Metro City', 1),
(10, 'PAT-0005', '1995-07-18', 'Female', '789 Cedar Lane, Metro City', 1),
(11, 'PAT-0006', '1982-09-25', 'Male', '321 Elm Street, Metro City', 1),
(12, 'PAT-0007', '1993-12-05', 'Female', '654 Willow Way, Metro City', 1),
(13, 'PAT-0008', '1987-04-14', 'Male', '987 Spruce Court, Metro City', 1),
(14, 'PAT-0009', '1991-06-28', 'Female', '147 Ash Boulevard, Metro City', 1),
(15, 'PAT-0010', '1989-02-17', 'Male', '258 Poplar Place, Metro City', 1);

-- Insert sample medical certificates
INSERT INTO certificates (clinic_id, patient_id, cert_id, diagnosis, treatment, start_date, end_date, status, created_at) VALUES
(1, 6, 'MED-20251108-00001', 'Acute Upper Respiratory Infection', 'Rest, hydration, and prescribed antibiotics', '2025-11-08', '2025-11-15', 'active', '2025-11-08 09:30:00'),
(1, 7, 'MED-20251107-00002', 'Lower Back Pain', 'Physical therapy and pain management', '2025-11-07', '2025-11-14', 'active', '2025-11-07 14:20:00'),
(2, 8, 'MED-20251106-00003', 'Migraine Headache', 'Prescribed medication and rest', '2025-11-06', '2025-11-08', 'expired', '2025-11-06 11:15:00'),
(3, 9, 'MED-20251105-00004', 'Hypertension Follow-up', 'Blood pressure monitoring and medication adjustment', '2025-11-05', '2025-12-05', 'active', '2025-11-05 10:00:00'),
(4, 10, 'MED-20251104-00005', 'Common Cold', 'Symptomatic treatment and rest', '2025-11-04', '2025-11-07', 'expired', '2025-11-04 15:45:00'),
(5, 11, 'MED-20251109-00006', 'Sprained Ankle', 'RICE protocol and physical therapy', '2025-11-09', '2025-11-23', 'active', '2025-11-09 08:30:00'),
(1, 12, 'MED-20251103-00007', 'Gastroenteritis', 'Hydration and dietary modifications', '2025-11-03', '2025-11-06', 'expired', '2025-11-03 13:00:00'),
(2, 13, 'MED-20251110-00008', 'Allergic Rhinitis', 'Antihistamines and allergen avoidance', '2025-11-10', '2025-11-17', 'active', '2025-11-10 10:20:00'),
(3, 14, 'MED-20251102-00009', 'Chest Pain Evaluation', 'Cardiac monitoring and lifestyle counseling', '2025-11-02', '2025-11-09', 'active', '2025-11-02 16:00:00'),
(4, 15, 'MED-20251101-00010', 'Fever and Cough', 'Antipyretics and cough suppressants', '2025-11-01', '2025-11-05', 'expired', '2025-11-01 09:00:00');

-- Insert sample appointments
INSERT INTO appointments (patient_id, clinic_id, appointment_date, appointment_time, reason, status, created_at) VALUES
(6, 2, '2025-11-15', '10:00:00', 'Annual checkup', 'approved', '2025-11-08 14:00:00'),
(7, 3, '2025-11-16', '14:30:00', 'Cardiology consultation', 'approved', '2025-11-08 15:30:00'),
(8, 1, '2025-11-17', '09:00:00', 'Follow-up visit', 'pending', '2025-11-09 10:00:00'),
(9, 4, '2025-11-18', '11:00:00', 'Pediatric checkup', 'approved', '2025-11-09 11:30:00'),
(10, 5, '2025-11-19', '15:00:00', 'Orthopedic consultation', 'pending', '2025-11-09 16:00:00'),
(11, 1, '2025-11-12', '10:30:00', 'General consultation', 'completed', '2025-11-05 09:00:00'),
(12, 2, '2025-11-13', '13:00:00', 'Blood pressure check', 'completed', '2025-11-06 10:00:00'),
(13, 3, '2025-11-20', '16:00:00', 'Heart health screening', 'approved', '2025-11-10 08:00:00');

-- ============================================
-- DATABASE SETUP COMPLETE
-- ============================================
-- 
-- Default Login Credentials (All passwords: "password"):
-- 
-- CLINIC ADMINS (Doctors):
--   Dr. John Smith       - Username: dr.smith    - Green Valley Medical Center (General Medicine)
--   Dr. Maria Garcia     - Username: dr.garcia   - City Hospital Clinic (Internal Medicine)
--   Dr. David Chen       - Username: dr.chen     - Heart Care Specialists (Cardiology)
--   Dr. Priya Patel      - Username: dr.patel    - Little Stars Pediatrics (Pediatrics)
--   Dr. Michael Johnson  - Username: dr.johnson  - OrthoClinic Plus (Orthopedics)
-- 
-- PATIENTS:
--   Alice Johnson   - Username: alice.j   - PAT-0001
--   Bob Williams    - Username: bob.w     - PAT-0002
--   Carol Davis     - Username: carol.d   - PAT-0003
--   David Miller    - Username: david.m   - PAT-0004
--   Emma Wilson     - Username: emma.w    - PAT-0005
--   Frank Taylor    - Username: frank.t   - PAT-0006
--   Grace Anderson  - Username: grace.a   - PAT-0007
--   Henry Thomas    - Username: henry.t   - PAT-0008
--   Isabel Martinez - Username: isabel.m  - PAT-0009
--   James Lee       - Username: james.l   - PAT-0010
-- 
-- WEB ADMIN:
--   Username: webadmin
--   Password: password
-- 
-- SAMPLE DATA INCLUDED:
--   - 5 Doctors with different specializations
--   - 10 Patients with complete profiles
--   - 5 Active clinics
--   - 10 Medical certificates (mix of active and expired)
--   - 8 Appointments (pending, approved, and completed)
-- 
-- ============================================
