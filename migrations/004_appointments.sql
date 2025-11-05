-- ============================================
-- Appointments Feature Migration
-- ============================================

USE mediarchive;

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    clinic_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    time_slot TIME NOT NULL,
    purpose VARCHAR(200) NOT NULL,
    details TEXT,
    spec_answers TEXT,
    status ENUM('pending','approved','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id),
    INDEX idx_clinic_id (clinic_id),
    INDEX idx_datetime (appointment_date, time_slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


