-- Migration: add missing columns used by views
-- Run using the provided PHP migration runner: php migrations/migrate.php

ALTER TABLE users
  ADD COLUMN profile_photo VARCHAR(255) NULL,
  ADD COLUMN home_address TEXT NULL;

ALTER TABLE clinics
  ADD COLUMN signature_path VARCHAR(255) NULL,
  ADD COLUMN seal_path VARCHAR(255) NULL;

-- (Optional) you can add indexes if needed
-- ALTER TABLE users ADD INDEX idx_username (username);
