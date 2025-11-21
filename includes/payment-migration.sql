-- ================================================
-- MPHS Job Application System - Payment Migration
-- ================================================
-- This file creates the payment_transactions table
-- for handling payment gateway integration
-- ================================================

-- Create payment_transactions table
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `payment_reference` varchar(100) NOT NULL,
  `bill_code` varchar(50) DEFAULT NULL,
  `toyyibpay_bill_id` varchar(100) DEFAULT NULL,
  `status_id` tinyint(1) DEFAULT 0,
  `applicant_name` varchar(255) NOT NULL,
  `applicant_email` varchar(255) NOT NULL,
  `applicant_phone` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'RM',
  `payment_status` enum('pending','paid','failed','cancelled','expired') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `toyyibpay_reference` varchar(100) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `callback_data` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_payment_reference` (`payment_reference`),
  KEY `idx_job_id` (`job_id`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_applicant_email` (`applicant_email`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_bill_code` (`bill_code`),
  KEY `idx_toyyibpay_bill_id` (`toyyibpay_bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add payment-related columns to job_postings table if they don't exist
ALTER TABLE `job_postings` 
ADD COLUMN IF NOT EXISTS `requires_payment` tinyint(1) DEFAULT 1 COMMENT 'Whether this job requires payment for application',
ADD COLUMN IF NOT EXISTS `application_fee` decimal(10,2) DEFAULT 25.00 COMMENT 'Application fee amount';

-- Create view for active payments
CREATE OR REPLACE VIEW `active_payments` AS
SELECT 
    pt.*,
    jp.job_title,
    jp.kod_gred,
    jp.ad_close_date
FROM payment_transactions pt
LEFT JOIN job_postings jp ON pt.job_id = jp.id
WHERE pt.payment_status IN ('pending', 'paid')
ORDER BY pt.created_at DESC;

-- Insert sample data for testing (optional - comment out for production)
-- INSERT INTO payment_transactions (
--     job_id, payment_reference, applicant_name, applicant_email, 
--     applicant_phone, amount, payment_status
-- ) VALUES (
--     1, 'PAY-MPHS-2025-001-TEST', 'AHMAD BIN ALI', 'ahmad@example.com', 
--     '0123456789', 25.00, 'pending'
-- );

-- ================================================
-- Migration completed successfully
-- ================================================
