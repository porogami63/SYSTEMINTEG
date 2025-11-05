-- ============================================
-- Appointments v2 - clean rebuild
-- Safe to run multiple times
-- ============================================

USE mediarchive;

-- Drop old appointments table if exists
DROP TABLE IF EXISTS appointments;

-- Recreate appointments table (v2)
CREATE TABLE appointments (
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


