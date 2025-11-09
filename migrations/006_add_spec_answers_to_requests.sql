-- ============================================
-- Add spec_answers column to certificate_requests
-- This column stores JSON data for specialization-specific questions
-- ============================================

USE mediarchive;

-- Add spec_answers column to certificate_requests if it doesn't exist
ALTER TABLE certificate_requests 
ADD COLUMN IF NOT EXISTS spec_answers TEXT DEFAULT NULL 
COMMENT 'JSON data for specialization-specific questions and answers';

