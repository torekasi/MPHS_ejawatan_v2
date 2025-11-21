<?php
/**
 * @FileID: fix_database_web
 * @Module: Database Schema Web Fix
 * @Author: Nefi
 * @LastModified: 2025-11-14
 * @SecurityTag: validated
 */

// Include database configuration
require_once __DIR__ . '/../.config.php';
require_once __DIR__ . '/../includes/schema.php';

// Set content type to plain text for better readability
header('Content-Type: text/plain; charset=utf-8');

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );

    echo "🔧 Emergency Database Schema Fix\n";
    echo "================================\n\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

    // 1. Fix application_references table - add application_id column
    echo "1. Fixing application_references table...\n";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM application_references LIKE 'application_id'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE application_references ADD COLUMN application_id INT NULL AFTER application_reference");
            $pdo->exec("ALTER TABLE application_references ADD INDEX idx_ref_app_id (application_id)");
            echo "   ✅ Added application_id column to application_references\n";
        } else {
            echo "   ✅ application_id column already exists in application_references\n";
        }
        
        // Add other missing columns
        $missing_columns = [
            'no_telefon' => 'VARCHAR(50)',
            'tempoh_mengenali' => 'VARCHAR(100)',
            'jawatan' => 'VARCHAR(255)',
            'alamat' => 'TEXT'
        ];
        
        foreach ($missing_columns as $col => $def) {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_references LIKE '{$col}'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_references ADD COLUMN {$col} {$def}");
                echo "   ✅ Added {$col} column to application_references\n";
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error fixing application_references: " . $e->getMessage() . "\n";
    }

    // 2. Fix application_family_members table
    echo "\n2. Fixing application_family_members table...\n";
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'application_family_members'");
        if (!$stmt->fetch()) {
            $pdo->exec("CREATE TABLE application_family_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                application_reference VARCHAR(50) NOT NULL,
                application_id INT NULL,
                hubungan VARCHAR(100),
                nama VARCHAR(255) NOT NULL,
                pekerjaan VARCHAR(255),
                telefon VARCHAR(50),
                kewarganegaraan VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (application_reference),
                INDEX (application_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            echo "   ✅ Created application_family_members table\n";
        } else {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_family_members LIKE 'application_id'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_family_members ADD COLUMN application_id INT NULL AFTER application_reference");
                $pdo->exec("ALTER TABLE application_family_members ADD INDEX idx_fam_app_id (application_id)");
                echo "   ✅ Added application_id column to application_family_members\n";
            } else {
                echo "   ✅ application_family_members table is already correct\n";
            }
        }
    } catch (Exception $e) {
        echo "   ❌ Error fixing application_family_members: " . $e->getMessage() . "\n";
    }

    // 3. Fix application_health table
    echo "\n3. Fixing application_health table...\n";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM application_health LIKE 'application_id'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE application_health ADD COLUMN application_id INT NULL AFTER application_reference");
            $pdo->exec("ALTER TABLE application_health ADD INDEX idx_health_app_id (application_id)");
            echo "   ✅ Added application_id column to application_health\n";
        } else {
            echo "   ✅ application_id column already exists in application_health\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error fixing application_health: " . $e->getMessage() . "\n";
    }

    // 4. Run the full schema update
    echo "\n4. Running full schema update...\n";
    try {
        create_tables($pdo);
        echo "   ✅ Full schema update completed successfully\n";
    } catch (Exception $e) {
        echo "   ❌ Error in full schema update: " . $e->getMessage() . "\n";
    }

    // 5. Verify the fixes
    echo "\n5. Verifying database structure...\n";
    $tables_to_check = [
        'application_references' => ['application_id', 'no_telefon', 'tempoh_mengenali', 'jawatan', 'alamat'],
        'application_family_members' => ['application_id', 'hubungan', 'nama'],
        'application_health' => ['application_id', 'application_reference'],
        'application_professional_bodies' => ['application_id', 'salinan_sijil_filename'],
        'application_extracurricular' => ['application_reference', 'tahap', 'salinan_sijil_filename'],
        'application_work_experience' => ['application_reference', 'dari_bulan', 'dari_tahun'],
        'application_spm_results' => ['application_reference', 'gred_keseluruhan', 'bahasa_malaysia'],
        'application_spm_additional_subjects' => ['application_reference']
    ];

    foreach ($tables_to_check as $table => $columns) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->fetch()) {
                echo "   📋 {$table}:\n";
                foreach ($columns as $column) {
                    $stmt = $pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
                    if ($stmt->fetch()) {
                        echo "      ✅ {$column}\n";
                    } else {
                        echo "      ❌ {$column} (missing)\n";
                    }
                }
            } else {
                echo "   ❌ {$table} (table missing)\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Error checking {$table}: " . $e->getMessage() . "\n";
        }
    }

    echo "\n================================\n";
    echo "🎉 Database Fix Completed!\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n\n";
    echo "You can now try submitting your application again.\n";
    echo "If you still get errors, please share the error message.\n";

} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in .config.php\n";
}
?>
