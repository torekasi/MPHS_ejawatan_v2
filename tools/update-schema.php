<?php
/**
 * @FileID: schema_update_tool
 * @Module: Schema Update Tool
 * @Author: AI Assistant
 * @LastModified: 2025-11-14
 * @SecurityTag: validated
 */

// Schema update tool to ensure database is properly configured
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/schema.php';

try {
    echo "Starting schema update...\n";
    
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "Database connected successfully.\n";
    
    // Create/update all tables
    create_tables($pdo);
    
    echo "Schema update completed successfully.\n";
    echo "All tables have been created/updated according to the latest schema.\n";
    
    // Show table status
    echo "\n=== Table Status ===\n";
    $tables = [
        'application_application_main',
        'application_computer_skills',
        'application_education', 
        'application_extracurricular',
        'application_health',
        'application_language_skills',
        'application_professional_bodies',
        'application_references',
        'application_spm_additional_subjects',
        'application_spm_results',
        'application_work_experience',
        'application_family_members'
    ];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $stmt->fetchColumn();
            echo "{$table}: {$count} records\n";
        } catch (Exception $e) {
            echo "{$table}: ERROR - " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
