<?php
/**
 * Add job_code column to application_application_main table
 * This script adds the job_code column and updates existing records
 */

// Load database configuration
$result = require __DIR__ . '/../config.php';
$config = $result['config'] ?? $result;

try {
    // Connect to database
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    
    // Check if job_code column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM application_application_main LIKE 'job_code'");
    $column_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$column_exists) {
        echo "Adding job_code column to application_application_main table...\n";
        
        // Add job_code column
        $pdo->exec("ALTER TABLE application_application_main ADD COLUMN job_code VARCHAR(50) AFTER job_id");
        
        // Add index on job_code
        $pdo->exec("ALTER TABLE application_application_main ADD INDEX (job_code)");
        
        echo "Column added successfully.\n";
        
        // Update existing records with job_code values from job_postings table
        echo "Updating existing records with job_code values...\n";
        
        $pdo->exec("
            UPDATE application_application_main a
            JOIN job_postings j ON a.job_id = j.id
            SET a.job_code = j.job_code
            WHERE a.job_code IS NULL
        ");
        
        echo "Existing records updated successfully.\n";
    } else {
        echo "job_code column already exists in application_application_main table.\n";
    }
    
    // Check if job_code column exists in job_applications table (legacy)
    $stmt = $pdo->query("SHOW COLUMNS FROM job_applications LIKE 'job_code'");
    $legacy_column_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$legacy_column_exists) {
        echo "Adding job_code column to job_applications table (legacy)...\n";
        
        // Add job_code column to legacy table
        $pdo->exec("ALTER TABLE job_applications ADD COLUMN job_code VARCHAR(50) AFTER job_id");
        
        // Add index on job_code
        $pdo->exec("ALTER TABLE job_applications ADD INDEX (job_code)");
        
        echo "Column added to legacy table successfully.\n";
        
        // Update existing records in legacy table
        echo "Updating existing records in legacy table with job_code values...\n";
        
        $pdo->exec("
            UPDATE job_applications a
            JOIN job_postings j ON a.job_id = j.id
            SET a.job_code = j.job_code
            WHERE a.job_code IS NULL
        ");
        
        echo "Existing legacy records updated successfully.\n";
    } else {
        echo "job_code column already exists in job_applications table.\n";
    }
    
    echo "Database update completed successfully.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
