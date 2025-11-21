-- SQL Script to add sijil_tambahan column to application_education table
-- Author: Nefi
-- Date: 2025-11-22
-- Purpose: Support additional certificate upload for education entries

-- Add sijil_tambahan column to application_education table
ALTER TABLE `application_education` 
ADD COLUMN `sijil_tambahan` VARCHAR(255) NULL 
AFTER `sijil_path`;

-- Verify the column was added
SHOW COLUMNS FROM `application_education` LIKE 'sijil_tambahan';
