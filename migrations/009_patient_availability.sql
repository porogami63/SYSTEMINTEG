-- Add availability field for patients
-- This allows patients to indicate when they're available for chat

ALTER TABLE patients 
ADD COLUMN is_available TINYINT(1) DEFAULT 1 AFTER patient_code;

-- Update existing patients to be available by default
UPDATE patients SET is_available = 1 WHERE is_available IS NULL;
