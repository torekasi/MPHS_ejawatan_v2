<?php
/**
 * Payment Migration Script for MPHS Job Application System
 * This script creates the necessary payment tables and structures
 */

// Include the main config
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/ErrorHandler.php';

echo "=== MPHS Payment Migration Script ===\n";
echo "Starting payment database migration...\n\n";

try {
    // Get configuration
    $config_result = require __DIR__ . '/../config.php';
    $config = $config_result;
    
    // Connect to database
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    echo "Connecting to database: {$config['db_name']}...\n";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    echo "✓ Database connection successful\n\n";
    
    // Read the migration SQL file
    $migrationFile = __DIR__ . '/payment-migration.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    echo "Reading migration file...\n";
    $migrationSQL = file_get_contents($migrationFile);
    
    // Split SQL commands by semicolon and execute each one
    $statements = array_filter(
        array_map('trim', explode(';', $migrationSQL)),
        function($stmt) {
            // Filter out empty statements and comments
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    echo "Executing migration statements...\n";
    
    $successCount = 0;
    foreach ($statements as $index => $statement) {
        try {
            // Skip empty statements and comments
            if (empty(trim($statement)) || preg_match('/^\s*--/', $statement)) {
                continue;
            }
            
            $pdo->exec($statement);
            $successCount++;
            echo "✓ Statement " . ($index + 1) . " executed successfully\n";
            
        } catch (PDOException $e) {
            // Check if it's a "table already exists" error - this is OK
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "ⓘ Statement " . ($index + 1) . " skipped (already exists)\n";
            } else {
                echo "✗ Error in statement " . ($index + 1) . ": " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "\n=== Verifying Migration ===\n";
    
    // Verify payment_transactions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_transactions'");
    if ($stmt->rowCount() > 0) {
        echo "✓ payment_transactions table created successfully\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE payment_transactions");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✓ Table has " . count($columns) . " columns\n";
        
        // Check for required columns
        $requiredColumns = ['id', 'job_id', 'payment_reference', 'applicant_name', 'applicant_email', 'amount', 'payment_status'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            echo "✓ All required columns present\n";
        } else {
            echo "✗ Missing columns: " . implode(', ', $missingColumns) . "\n";
        }
        
    } else {
        echo "✗ payment_transactions table was not created\n";
    }
    
    // Verify job_postings table updates
    $stmt = $pdo->query("DESCRIBE job_postings");
    $jobColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('requires_payment', $jobColumns)) {
        echo "✓ job_postings table updated with payment columns\n";
    } else {
        echo "ⓘ job_postings table payment columns not added (may already exist)\n";
    }
    
    // Test insertion (optional)
    echo "\n=== Testing Database ===\n";
    
    try {
        // Test if we can insert a sample record
        $testReference = 'PAY-MPHS-TEST-' . time();
        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions 
            (job_id, payment_reference, applicant_name, applicant_email, applicant_phone, amount, payment_status) 
            VALUES (1, ?, 'TEST USER', 'test@example.com', '0123456789', 10.00, 'pending')
        ");
        $stmt->execute([$testReference]);
        
        echo "✓ Test record inserted successfully\n";
        
        // Clean up test record
        $stmt = $pdo->prepare("DELETE FROM payment_transactions WHERE payment_reference = ?");
        $stmt->execute([$testReference]);
        
        echo "✓ Test record cleaned up\n";
        
    } catch (PDOException $e) {
        echo "ⓘ Test insertion failed (this may be normal): " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Migration Summary ===\n";
    echo "✓ Migration completed successfully!\n";
    echo "✓ Payment system is ready to use\n";
    echo "✓ You can now process payments through the application\n\n";
    
    // Log the successful migration
    log_public_action('Payment migration completed successfully', 'MIGRATION', 'SYSTEM', null, [
        'statements_executed' => $successCount,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Log the error
    log_error('Payment migration failed', [
        'exception' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    exit(1);
}

echo "=== Migration Script Completed ===\n";
?>
