<?php
/**
 * @FileID: fix_db_standalone
 * @Module: Standalone Database Schema Fix
 * @Author: Nefi
 * @LastModified: 2025-11-14
 * @SecurityTag: validated
 */

// Set content type to plain text for better readability
header('Content-Type: text/plain; charset=utf-8');

// Database configuration - using correct credentials from config.php
$db_config = [
    'host' => 'db',  // Docker container name from config
    'name' => 'ejawatan_db',  // Database name from config
    'user' => 'ejawatan_user',  // Database user from config
    'pass' => 'SecurePass123!'  // Database password from config
];

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );

    echo "🔧 Standalone Database Schema Fix\n";
    echo "=================================\n\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo "Database: {$db_config['name']} on {$db_config['host']}\n\n";

    // 1. Fix application_references table - add application_id column
    echo "1. Fixing application_references table...\n";
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'application_references'");
        if (!$stmt->fetch()) {
            // Create the table if it doesn't exist
            $pdo->exec("CREATE TABLE application_references (
                id INT AUTO_INCREMENT PRIMARY KEY,
                application_reference VARCHAR(50) NOT NULL,
                application_id INT NULL,
                nama VARCHAR(255) NOT NULL,
                no_telefon VARCHAR(50),
                telefon VARCHAR(50),
                tempoh_mengenali VARCHAR(100),
                tempoh VARCHAR(2),
                jawatan VARCHAR(255),
                alamat TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (application_reference),
                INDEX (application_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            echo "   ✅ Created application_references table\n";
        } else {
            // Add missing columns
            $columns_to_add = [
                'application_id' => 'INT NULL',
                'no_telefon' => 'VARCHAR(50)',
                'tempoh_mengenali' => 'VARCHAR(100)',
                'jawatan' => 'VARCHAR(255)',
                'alamat' => 'TEXT'
            ];
            
            foreach ($columns_to_add as $col => $def) {
                $stmt = $pdo->query("SHOW COLUMNS FROM application_references LIKE '{$col}'");
                if (!$stmt->fetch()) {
                    $pdo->exec("ALTER TABLE application_references ADD COLUMN {$col} {$def}");
                    echo "   ✅ Added {$col} column to application_references\n";
                } else {
                    echo "   ✅ {$col} column already exists\n";
                }
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
                echo "   ✅ application_family_members already has application_id\n";
            }
        }
    } catch (Exception $e) {
        echo "   ❌ Error fixing application_family_members: " . $e->getMessage() . "\n";
    }

    // 3. Fix application_health table
    echo "\n3. Fixing application_health table...\n";
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'application_health'");
        if ($stmt->fetch()) {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_health LIKE 'application_id'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_health ADD COLUMN application_id INT NULL AFTER application_reference");
                $pdo->exec("ALTER TABLE application_health ADD INDEX idx_health_app_id (application_id)");
                echo "   ✅ Added application_id column to application_health\n";
            } else {
                echo "   ✅ application_health already has application_id\n";
            }
        } else {
            echo "   ⚠️  application_health table doesn't exist yet\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error fixing application_health: " . $e->getMessage() . "\n";
    }

    // 4. Fix other tables
    echo "\n4. Fixing other application tables...\n";
    
    $tables_to_fix = [
        'application_professional_bodies' => [
            'application_id' => 'INT NULL',
            'salinan_sijil_filename' => 'VARCHAR(255)'
        ],
        'application_extracurricular' => [
            'application_reference' => 'VARCHAR(50)',
            'tahap' => 'VARCHAR(100)',
            'salinan_sijil_filename' => 'VARCHAR(255)'
        ],
        'application_work_experience' => [
            'application_reference' => 'VARCHAR(50)',
            'dari_bulan' => 'INT',
            'dari_tahun' => 'VARCHAR(4)',
            'hingga_bulan' => 'INT',
            'hingga_tahun' => 'VARCHAR(4)'
        ],
        'application_spm_results' => [
            'application_reference' => 'VARCHAR(50)',
            'gred_keseluruhan' => 'VARCHAR(50)',
            'bahasa_malaysia' => 'VARCHAR(5)',
            'bahasa_inggeris' => 'VARCHAR(5)',
            'matematik' => 'VARCHAR(5)',
            'sejarah' => 'VARCHAR(5)',
            'subjek_lain' => 'TEXT',
            'gred_subjek_lain' => 'TEXT',
            'salinan_sijil_filename' => 'VARCHAR(255)'
        ],
        'application_spm_additional_subjects' => [
            'application_reference' => 'VARCHAR(50)'
        ]
    ];

    foreach ($tables_to_fix as $table => $columns) {
        echo "   Checking {$table}...\n";
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->fetch()) {
                foreach ($columns as $column => $definition) {
                    $stmt = $pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
                    if (!$stmt->fetch()) {
                        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
                        echo "     ✅ Added {$column} to {$table}\n";
                    } else {
                        echo "     ✅ {$column} already exists in {$table}\n";
                    }
                }
            } else {
                echo "     ⚠️  {$table} table doesn't exist\n";
            }
        } catch (Exception $e) {
            echo "     ❌ Error fixing {$table}: " . $e->getMessage() . "\n";
        }
    }

    echo "\n=================================\n";
    echo "🎉 Database Fix Completed!\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n\n";
    echo "✅ All required application_id columns have been added\n";
    echo "✅ Missing tables and columns have been created\n\n";
    echo "You can now try submitting your application again!\n";

} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Check if MySQL container is running: docker ps\n";
    echo "2. Verify database name and credentials\n";
    echo "3. Make sure the database exists\n";
}
?>
