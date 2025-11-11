-- ============================================
-- Appointments and Profile Enhancements
-- Adds appointment results and doctor about description
-- ============================================

USE mediarchive;

-- Add about_description to clinics table
ALTER TABLE clinics 
ADD COLUMN IF NOT EXISTS about_description TEXT NULL COMMENT 'Doctor/Clinic about description';

-- Create appointment_results table
CREATE TABLE IF NOT EXISTS appointment_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    doctor_id INT NOT NULL COMMENT 'clinic_id',
    patient_id INT NOT NULL,
    diagnosis TEXT,
    treatment_notes TEXT,
    prescriptions TEXT,
    follow_up_instructions TEXT,
    attachments TEXT COMMENT 'JSON array of file paths',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES clinics(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_patient_id (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

