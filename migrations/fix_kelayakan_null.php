<?php
/**
 * Migration: Fix kelayakan column to allow NULL values
 * Date: 2025-11-23
 * Purpose: Allow kelayakan field in application_education to be nullable
 */

require_once __DIR__ . '/../config.php';

$config = require __DIR__ . '/../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

try {
    echo "Starting migration: Fix kelayakan column to allow NULL...\n";
    
    // Check if column exists and get its current definition
    $stmt = $pdo->query("SHOW COLUMNS FROM application_education LIKE 'kelayakan'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "Found kelayakan column. Current definition: {$column['Type']} {$column['Null']}\n";
        
        // Alter column to allow NULL
        $pdo->exec("ALTER TABLE application_education MODIFY COLUMN kelayakan VARCHAR(100) NULL");
        echo "✓ Successfully altered kelayakan column to allow NULL\n";
        
        // Verify the change
        $stmt = $pdo->query("SHOW COLUMNS FROM application_education LIKE 'kelayakan'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "New definition: {$column['Type']} {$column['Null']}\n";
    } else {
        echo "✗ Column 'kelayakan' not found in application_education table\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
