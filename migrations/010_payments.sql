-- ============================================
-- Payment System for Certificates and Appointments
-- ============================================

CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    payment_type ENUM('certificate', 'appointment') NOT NULL,
    reference_id INT NOT NULL COMMENT 'ID of certificate or appointment',
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'gcash', 'paymaya', 'bank_transfer') NOT NULL DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    transaction_id VARCHAR(100) UNIQUE COMMENT 'External payment gateway transaction ID',
    payment_date DATETIME DEFAULT NULL,
    payment_details TEXT COMMENT 'JSON data for payment information',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_payment_type (payment_type),
    INDEX idx_reference_id (reference_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_transaction_id (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add payment_required and payment_amount to certificates
ALTER TABLE certificates 
ADD COLUMN IF NOT EXISTS payment_required BOOLEAN DEFAULT FALSE COMMENT 'Whether payment is required for this certificate',
ADD COLUMN IF NOT EXISTS payment_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Amount to be paid for certificate';

-- Add payment_required and payment_amount to appointments
ALTER TABLE appointments 
ADD COLUMN IF NOT EXISTS payment_required BOOLEAN DEFAULT FALSE COMMENT 'Whether payment is required for this appointment',
ADD COLUMN IF NOT EXISTS payment_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Amount to be paid for appointment';
