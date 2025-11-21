<?php
/**
 * @FileID: fix_address_fields_migration
 * @Module: Database Migration Tool
 * @Author: AI Assistant
 * @LastModified: 2025-11-14
 * @SecurityTag: validated
 */

// Quick fix for address field constraints
require_once __DIR__ . '/../config.php';

try {
    echo "Fixing address field constraints...\n";
    
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Check if application_application_main table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'application_application_main'");
    if (!$stmt->fetch()) {
        echo "application_application_main table doesn't exist. Creating tables...\n";
        require_once __DIR__ . '/../includes/schema.php';
        create_tables($pdo);
        echo "Tables created successfully.\n";
        exit(0);
    }
    
    echo "Checking address field constraints...\n";
    
    // Make address fields nullable
    $address_fields = [
        'alamat_surat' => 'TEXT',
        'bandar_surat' => 'VARCHAR(100)', 
        'negeri_surat' => 'VARCHAR(100)',
        'poskod_surat' => 'VARCHAR(10)'
    ];
    
    foreach ($address_fields as $field => $type) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_application_main LIKE '{$field}'");
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($column) {
                if ($column['Null'] === 'NO') {
                    echo "Making {$field} nullable...\n";
                    $pdo->exec("ALTER TABLE application_application_main MODIFY COLUMN {$field} {$type} NULL");
                    echo "✅ {$field} is now nullable.\n";
                } else {
                    echo "✅ {$field} is already nullable.\n";
                }
            } else {
                echo "⚠️  {$field} column doesn't exist.\n";
            }
        } catch (Exception $e) {
            echo "❌ Error updating {$field}: " . $e->getMessage() . "\n";
        }
    }
    
    // Check alamat_surat_sama column
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM application_application_main LIKE 'alamat_surat_sama'");
        if (!$stmt->fetch()) {
            echo "Adding alamat_surat_sama column...\n";
            $pdo->exec("ALTER TABLE application_application_main ADD COLUMN alamat_surat_sama TINYINT(1) DEFAULT 0 AFTER poskod_surat");
            echo "✅ alamat_surat_sama column added.\n";
        } else {
            echo "✅ alamat_surat_sama column already exists.\n";
        }
    } catch (Exception $e) {
        echo "❌ Error with alamat_surat_sama column: " . $e->getMessage() . "\n";
    }
    
    echo "\nAddress field fixes completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
