<?php
/**
 * @FileID: verify_db_structure
 * @Module: Database Structure Verification
 * @Author: Nefi
 * @LastModified: 2025-11-14
 * @SecurityTag: validated
 */

// Set content type to HTML for better browser display
header('Content-Type: text/html; charset=utf-8');

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

    echo "<!DOCTYPE html><html><head><title>Database Structure Verification</title>";
    echo "<style>body{font-family:monospace;background:#f5f5f5;padding:20px;} .container{background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);} .success{color:#059669;} .error{color:#dc2626;} .warning{color:#d97706;} pre{background:#f8f9fa;padding:10px;border-radius:4px;border-left:4px solid #3b82f6;}</style>";
    echo "</head><body><div class='container'>";
    echo "<h1>🔍 Database Structure Verification</h1>";
    echo "<hr>";
    echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Database:</strong> {$db_config['name']} on {$db_config['host']}</p><br>";

    // Define expected table structures
    $expected_structures = [
        'application_application_main' => [
            'required_columns' => ['id', 'application_reference', 'job_id', 'nama_penuh', 'nombor_ic', 'email'],
            'optional_columns' => ['alamat_surat_sama', 'lesen_memandu_set', 'created_at', 'updated_at']
        ],
        'application_references' => [
            'required_columns' => ['id', 'application_reference', 'application_id', 'nama'],
            'optional_columns' => ['no_telefon', 'tempoh_mengenali', 'jawatan', 'alamat', 'telefon', 'tempoh']
        ],
        'application_family_members' => [
            'required_columns' => ['id', 'application_reference', 'application_id', 'nama'],
            'optional_columns' => ['hubungan', 'pekerjaan', 'telefon', 'kewarganegaraan']
        ],
        'application_health' => [
            'required_columns' => ['id', 'application_reference', 'application_id'],
            'optional_columns' => ['darah_tinggi', 'kencing_manis', 'penyakit_buah_pinggang', 'berat_kg', 'tinggi_cm']
        ],
        'application_professional_bodies' => [
            'required_columns' => ['id', 'application_reference', 'application_id', 'nama_lembaga'],
            'optional_columns' => ['no_ahli', 'sijil_diperoleh', 'tahun', 'salinan_sijil_filename', 'salinan_sijil']
        ],
        'application_extracurricular' => [
            'required_columns' => ['id', 'application_id', 'application_reference', 'sukan_persatuan_kelab'],
            'optional_columns' => ['jawatan', 'peringkat', 'tahap', 'tahun', 'salinan_sijil_filename', 'salinan_sijil']
        ],
        'application_work_experience' => [
            'required_columns' => ['id', 'application_id', 'application_reference', 'nama_syarikat'],
            'optional_columns' => ['jawatan', 'dari_bulan', 'dari_tahun', 'hingga_bulan', 'hingga_tahun', 'gaji', 'alasan_berhenti']
        ],
        'application_spm_results' => [
            'required_columns' => ['id', 'application_id', 'application_reference', 'tahun', 'angka_giliran'],
            'optional_columns' => ['gred_keseluruhan', 'bahasa_malaysia', 'bahasa_inggeris', 'matematik', 'sejarah', 'subjek_lain', 'gred_subjek_lain', 'salinan_sijil_filename']
        ],
        'application_spm_additional_subjects' => [
            'required_columns' => ['id', 'application_id', 'application_reference', 'subjek'],
            'optional_columns' => ['tahun', 'angka_giliran', 'gred', 'salinan_sijil']
        ],
        'application_language_skills' => [
            'required_columns' => ['id', 'application_reference', 'application_id', 'bahasa'],
            'optional_columns' => ['tahap_lisan', 'tahap_penulisan', 'gred_spm']
        ],
        'application_computer_skills' => [
            'required_columns' => ['id', 'application_reference', 'application_id', 'nama_perisian'],
            'optional_columns' => ['tahap_kemahiran']
        ],
        'application_education' => [
            'required_columns' => ['id', 'application_id', 'application_reference', 'nama_institusi'],
            'optional_columns' => ['dari_tahun', 'hingga_tahun', 'kelayakan', 'pangkat_gred_cgpa', 'sijil_path']
        ]
    ];

    $issues_found = [];
    $tables_checked = 0;

    foreach ($expected_structures as $table_name => $structure) {
        $tables_checked++;
        echo "<h3>📋 Checking {$table_name}...</h3>";
        
        try {
            // Check if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table_name}'");
            if (!$stmt->fetch()) {
                echo "<p class='error'>❌ Table does not exist</p>";
                $issues_found[] = "Table {$table_name} does not exist";
                continue;
            }
            
            // Get all columns in the table
            $stmt = $pdo->query("SHOW COLUMNS FROM {$table_name}");
            $existing_columns = [];
            while ($row = $stmt->fetch()) {
                $existing_columns[] = $row['Field'];
            }
            
            echo "<p>📊 Found " . count($existing_columns) . " columns</p>";
            
            // Check required columns
            $missing_required = [];
            foreach ($structure['required_columns'] as $required_col) {
                if (!in_array($required_col, $existing_columns)) {
                    $missing_required[] = $required_col;
                }
            }
            
            if (empty($missing_required)) {
                echo "<p class='success'>✅ All required columns present</p>";
            } else {
                echo "<p class='error'>❌ Missing required columns: " . implode(', ', $missing_required) . "</p>";
                $issues_found[] = "Table {$table_name} missing required columns: " . implode(', ', $missing_required);
            }
            
            // Show all columns for reference
            echo "<p>📝 Columns: " . implode(', ', $existing_columns) . "</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error checking table: " . htmlspecialchars($e->getMessage()) . "</p>";
            $issues_found[] = "Error checking table {$table_name}: " . $e->getMessage();
        }
        
        echo "<hr>";
    }

    echo "<h2>📊 VERIFICATION SUMMARY</h2>";
    echo "<p><strong>Tables checked:</strong> {$tables_checked}</p>";
    echo "<p><strong>Issues found:</strong> " . count($issues_found) . "</p>";

    if (empty($issues_found)) {
        echo "<div style='background:#d1fae5;border:1px solid #10b981;padding:15px;border-radius:8px;margin:20px 0;'>";
        echo "<h3 class='success'>🎉 All tables have the correct structure!</h3>";
        echo "<p class='success'>✅ Database is ready for application saving.</p>";
        echo "</div>";
    } else {
        echo "<div style='background:#fef3c7;border:1px solid #f59e0b;padding:15px;border-radius:8px;margin:20px 0;'>";
        echo "<h3 class='warning'>⚠️ Issues found:</h3>";
        echo "<ul>";
        foreach ($issues_found as $issue) {
            echo "<li>" . htmlspecialchars($issue) . "</li>";
        }
        echo "</ul>";
        echo "<p><strong>🔧 Run the database fix script to resolve these issues:</strong></p>";
        echo "<p><a href='fix-db-standalone.php' style='background:#3b82f6;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Run Database Fix</a></p>";
        echo "</div>";
    }
    
    echo "</div></body></html>";

} catch (Exception $e) {
    echo "<!DOCTYPE html><html><head><title>Database Verification Error</title>";
    echo "<style>body{font-family:monospace;background:#f5f5f5;padding:20px;} .container{background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);} .error{color:#dc2626;}</style>";
    echo "</head><body><div class='container'>";
    echo "<h1 class='error'>❌ Fatal Error</h1>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>🔧 Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li>Check if MySQL container is running: <code>docker ps</code></li>";
    echo "<li>Verify database name and credentials</li>";
    echo "<li>Make sure the database exists</li>";
    echo "</ol>";
    echo "</div></body></html>";
}
?>
