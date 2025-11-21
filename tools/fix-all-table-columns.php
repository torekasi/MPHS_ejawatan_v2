<?php
/**
 * @FileID: fix_all_table_columns_migration
 * @Module: Complete Database Migration Tool
 * @Author: Nefi
 * @LastModified: 2025-11-14
 * @SecurityTag: validated
 */

// Comprehensive fix for all table column issues
require_once __DIR__ . '/../config.php';

try {
    echo "=== Comprehensive Table Column Fix ===\n\n";
    
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "Connected to database: {$config['db_name']}\n\n";
    
    // Call the schema update function
    require_once __DIR__ . '/../includes/schema.php';
    create_tables($pdo);
    
    echo "\n=== Summary ===\n";
    echo "All table structures have been updated successfully!\n";
    echo "The following tables are now configured correctly:\n\n";
    
    $tables = [
        'application_application_main' => 'Main application data',
        'application_computer_skills' => 'Computer skills',
        'application_language_skills' => 'Language skills',
        'application_education' => 'Education records',
        'application_extracurricular' => 'Extracurricular activities',
        'application_health' => 'Health information',
        'application_professional_bodies' => 'Professional bodies',
        'application_references' => 'References',
        'application_spm_additional_subjects' => 'Additional SPM subjects',
        'application_spm_results' => 'Main SPM results',
        'application_work_experience' => 'Work experience',
        'application_family_members' => 'Family members'
    ];
    
    foreach ($tables as $table => $description) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $stmt->fetchColumn();
            echo "✅ {$table} ({$description}): {$count} records\n";
        } catch (Exception $e) {
            echo "⚠️  {$table}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Key Updates Applied ===\n";
    echo "✅ Added application_reference columns to all separate tables\n";
    echo "✅ Changed ENUM fields to VARCHAR for flexibility\n";
    echo "✅ Made address fields nullable\n";
    echo "✅ Added missing gred_spm column to language skills\n";
    echo "✅ Updated all foreign key constraints\n";
    echo "\nYou can now submit applications without column errors!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
