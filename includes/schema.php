<?php

/**
 * Database Schema for MPHS Job Application System
 * This file contains the SQL schema for creating all necessary tables
 */

// Function to create tables if they don't exist
function create_tables($pdo) {
    try {
        // Create application_application_main table (new split form structure)
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_application_main (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_reference VARCHAR(50) UNIQUE NOT NULL,
            job_id INT NOT NULL,
            job_code VARCHAR(50),
            payment_reference VARCHAR(100),
            status_id INT NULL,
            status ENUM('PENDING','SHORTLISTED','INTERVIEWED','OFFERED','ACCEPTED','REJECTED','PROCESSING') DEFAULT 'PENDING',
            status_notes TEXT,
            reviewed_at DATETIME NULL,
            approved_at DATETIME NULL,
            reviewed_by INT NULL,
            approved_by INT NULL,
            application_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            -- Declaration Section (Part 1)
            pengistiharan TINYINT(1) DEFAULT 1,
            jawatan_dipohon VARCHAR(255),
            
            -- Personal Information (Part 1)
            nama_penuh VARCHAR(255) NOT NULL,
            nombor_ic VARCHAR(20) NOT NULL,
            nombor_surat_beranak VARCHAR(100),
            jantina VARCHAR(20),
            tarikh_lahir DATE,
            umur INT,
            agama VARCHAR(50),
            bangsa VARCHAR(50),
            warganegara VARCHAR(50),
            negeri_kelahiran VARCHAR(100),
            taraf_perkahwinan VARCHAR(50),
            email VARCHAR(255) NOT NULL,
            nombor_telefon VARCHAR(20),
            tempoh_bermastautin_selangor VARCHAR(10),
            
            -- Address Information (Part 1)
            alamat_tetap TEXT,
            bandar_tetap VARCHAR(100),
            negeri_tetap VARCHAR(100),
            poskod_tetap VARCHAR(10),
            alamat_surat TEXT NULL,
            bandar_surat VARCHAR(100) NULL,
            negeri_surat VARCHAR(100) NULL,
            poskod_surat VARCHAR(10) NULL,
            alamat_surat_sama TINYINT(1) DEFAULT 0,
            
            -- Driving License (Part 1)
            lesen_memandu_set TEXT,
            tarikh_tamat_lesen DATE,
            
            -- Document Uploads (Part 1)
            gambar_passport_path VARCHAR(255) NULL,
            salinan_ic_path VARCHAR(255) NULL,
            salinan_surat_beranak_path VARCHAR(255) NULL,
            salinan_lesen_memandu_path VARCHAR(255) NULL,
            
            -- Family Information - Parents (Part 1)
            ibu_nama VARCHAR(255),
            ibu_pekerjaan VARCHAR(255),
            ibu_telefon VARCHAR(50),
            ibu_kewarganegaraan VARCHAR(50),
            ayah_nama VARCHAR(255),
            ayah_pekerjaan VARCHAR(255),
            ayah_telefon VARCHAR(50),
            ayah_kewarganegaraan VARCHAR(50),
            ahli_keluarga JSON,
            
            -- Spouse Information (Part 1)
            nama_pasangan VARCHAR(255),
            telefon_pasangan VARCHAR(50),
            bilangan_anak INT,
            status_pasangan VARCHAR(50),
            pekerjaan_pasangan VARCHAR(255),
            nama_majikan_pasangan VARCHAR(255),
            telefon_pejabat_pasangan VARCHAR(50),
            alamat_majikan_pasangan TEXT,
            bandar_majikan_pasangan VARCHAR(100),
            negeri_majikan_pasangan VARCHAR(100),
            poskod_majikan_pasangan VARCHAR(10),
            
            -- Submission Control
            submission_locked TINYINT(1) DEFAULT 0 COMMENT '1 = locked (cannot edit), 0 = editable',
            token_expiry DATETIME NULL COMMENT 'Token expiration time for edit access',

            -- Timestamps
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX (job_id),
            INDEX (job_code),
            INDEX (application_reference),
            INDEX (status),
            INDEX (nombor_ic),
            INDEX (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Fix lesen_memandu_set column if it's JSON instead of TEXT
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_application_main LIKE 'lesen_memandu_set'");
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($column && stripos($column['Type'], 'json') !== false) {
                $pdo->exec("ALTER TABLE application_application_main MODIFY COLUMN lesen_memandu_set TEXT");
            }
        } catch (PDOException $e) {
            // Ignore if column doesn't exist or can't be modified
            error_log("Schema update for lesen_memandu_set: " . $e->getMessage());
        }
        
        // Add alamat_surat_sama column if it doesn't exist
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_application_main LIKE 'alamat_surat_sama'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_application_main ADD COLUMN alamat_surat_sama TINYINT(1) DEFAULT 0 AFTER poskod_surat");
                error_log("Added alamat_surat_sama column to application_application_main table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for alamat_surat_sama: " . $e->getMessage());
        }
        
        // Make address fields nullable to prevent required field errors
        try {
            $address_fields = ['alamat_surat', 'bandar_surat', 'negeri_surat', 'poskod_surat'];
            foreach ($address_fields as $field) {
                $stmt = $pdo->query("SHOW COLUMNS FROM application_application_main LIKE '{$field}'");
                $column = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($column && strpos($column['Null'], 'NO') !== false) {
                    $type = $column['Type'];
                    $pdo->exec("ALTER TABLE application_application_main MODIFY COLUMN {$field} {$type} NULL");
                    error_log("Made {$field} column nullable in application_application_main table");
                }
            }
        } catch (PDOException $e) {
            error_log("Schema update for address fields: " . $e->getMessage());
        }
        
        // Update application_computer_skills table structure
        try {
            // Add application_reference column if missing
            $stmt = $pdo->query("SHOW COLUMNS FROM application_computer_skills LIKE 'application_reference'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_computer_skills ADD COLUMN application_reference VARCHAR(50) NULL AFTER id");
                $pdo->exec("ALTER TABLE application_computer_skills ADD INDEX idx_comp_app_ref (application_reference)");
                error_log("Added application_reference column to application_computer_skills table");
            }
            
            // Change tahap_kemahiran from ENUM to VARCHAR if needed
            $stmt = $pdo->query("SHOW COLUMNS FROM application_computer_skills LIKE 'tahap_kemahiran'");
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($column && strpos($column['Type'], 'enum') !== false) {
                $pdo->exec("ALTER TABLE application_computer_skills MODIFY COLUMN tahap_kemahiran VARCHAR(50) NULL");
                error_log("Changed tahap_kemahiran to VARCHAR in application_computer_skills table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_computer_skills: " . $e->getMessage());
        }
        
        // Update application_language_skills table structure
        try {
            // Add application_reference column if missing
            $stmt = $pdo->query("SHOW COLUMNS FROM application_language_skills LIKE 'application_reference'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_language_skills ADD COLUMN application_reference VARCHAR(50) NULL AFTER id");
                $pdo->exec("ALTER TABLE application_language_skills ADD INDEX idx_lang_app_ref (application_reference)");
                error_log("Added application_reference column to application_language_skills table");
            }
            
            // Add gred_spm column if missing
            $stmt = $pdo->query("SHOW COLUMNS FROM application_language_skills LIKE 'gred_spm'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_language_skills ADD COLUMN gred_spm VARCHAR(10) NULL AFTER tahap_penulisan");
                error_log("Added gred_spm column to application_language_skills table");
            }
            
            // Change tahap_lisan from ENUM to VARCHAR if needed
            $stmt = $pdo->query("SHOW COLUMNS FROM application_language_skills LIKE 'tahap_lisan'");
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($column && strpos($column['Type'], 'enum') !== false) {
                $pdo->exec("ALTER TABLE application_language_skills MODIFY COLUMN tahap_lisan VARCHAR(50) NULL");
                error_log("Changed tahap_lisan to VARCHAR in application_language_skills table");
            }
            
            // Change tahap_penulisan from ENUM to VARCHAR if needed
            $stmt = $pdo->query("SHOW COLUMNS FROM application_language_skills LIKE 'tahap_penulisan'");
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($column && strpos($column['Type'], 'enum') !== false) {
                $pdo->exec("ALTER TABLE application_language_skills MODIFY COLUMN tahap_penulisan VARCHAR(50) NULL");
                error_log("Changed tahap_penulisan to VARCHAR in application_language_skills table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_language_skills: " . $e->getMessage());
        }
        
        // Fix document path columns to allow NULL
        $document_columns = [
            'gambar_passport_path',
            'salinan_ic_path',
            'salinan_surat_beranak_path',
            'salinan_lesen_memandu_path'
        ];
        foreach ($document_columns as $col) {
            try {
                $pdo->exec("ALTER TABLE application_application_main MODIFY COLUMN `$col` VARCHAR(255) NULL");
            } catch (PDOException $e) {
                // Ignore if column doesn't exist or can't be modified
                error_log("Schema update for $col: " . $e->getMessage());
            }
        }
        
        // Ensure workflow columns exist
        $workflow_columns = [
            'status_id' => "INT NULL",
            'status' => "ENUM('PENDING','SHORTLISTED','INTERVIEWED','OFFERED','ACCEPTED','REJECTED','PROCESSING') DEFAULT 'PENDING'",
            'status_notes' => 'TEXT',
            'reviewed_at' => 'DATETIME NULL',
            'approved_at' => 'DATETIME NULL',
            'reviewed_by' => 'INT NULL',
            'approved_by' => 'INT NULL',
            'application_date' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'ada_pengalaman_kerja' => 'VARCHAR(10)',
            'pekerja_perkhidmatan_awam' => 'VARCHAR(10)',
            'pekerja_perkhidmatan_awam_nyatakan' => 'TEXT',
            'pertalian_kakitangan' => 'VARCHAR(10)',
            'pertalian_kakitangan_nyatakan' => 'TEXT',
            'nama_kakitangan_pertalian' => 'VARCHAR(255)',
            'pernah_bekerja_mphs' => 'VARCHAR(10)',
            'pernah_bekerja_mphs_nyatakan' => 'TEXT',
            'tindakan_tatatertib' => 'VARCHAR(10)',
            'tindakan_tatatertib_nyatakan' => 'TEXT',
            'kesalahan_undangundang' => 'VARCHAR(10)',
            'kesalahan_undangundang_nyatakan' => 'TEXT',
            'muflis' => 'VARCHAR(10)',
            'muflis_nyatakan' => 'TEXT'
        ];

        foreach ($workflow_columns as $column => $definition) {
            try {
                $pdo->exec("ALTER TABLE application_application_main ADD COLUMN `$column` $definition");
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                    error_log("Schema update workflow column $column: " . $e->getMessage());
                }
            }
        }

        // Ensure foreign keys reference application_application_main(id) for application_id columns
        $fk_tables = [
            'application_language_skills' => 'fk_lang_app',
            'application_computer_skills' => 'fk_comp_app',
            'application_education' => 'fk_edu_app',
            'application_professional_bodies' => 'fk_prof_app',
            'application_extracurricular' => 'fk_extra_app',
            'application_spm_results' => 'fk_spm_app',
            'application_spm_additional_subjects' => 'fk_spm_add_app',
            'application_work_experience' => 'fk_work_app'
        ];

        foreach ($fk_tables as $table => $constraintName) {
            try {
                $stmt = $pdo->prepare(
                    "SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                     FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                     WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = ? 
                       AND COLUMN_NAME = 'application_id' 
                       AND REFERENCED_TABLE_NAME IS NOT NULL 
                     LIMIT 1"
                );
                $stmt->execute([$table]);
                $fk_info = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($fk_info) {
                    $needsFix = ($fk_info['REFERENCED_TABLE_NAME'] !== 'job_applications') || ($fk_info['REFERENCED_COLUMN_NAME'] !== 'id');
                    if ($needsFix) {
                        $pdo->exec("ALTER TABLE `$table` DROP FOREIGN KEY `{$fk_info['CONSTRAINT_NAME']}`");
                        $pdo->exec("ALTER TABLE `$table` ADD CONSTRAINT `$constraintName` FOREIGN KEY (`application_id`) REFERENCES job_applications(`id`) ON DELETE CASCADE");
                    }
                } else {
                    // No foreign key present, add the correct one
                    $pdo->exec("ALTER TABLE `$table` ADD CONSTRAINT `$constraintName` FOREIGN KEY (`application_id`) REFERENCES application_application_main(`id`) ON DELETE CASCADE");
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'errno: 150') !== false || strpos($e->getMessage(), 'foreign key') !== false) {
                    error_log("FK update for $table encountered an issue: " . $e->getMessage());
                } else {
                    error_log("Schema FK update for $table: " . $e->getMessage());
                }
            }
        }

        // Create job_applications table aligned with process-application.php
        $pdo->exec("CREATE TABLE IF NOT EXISTS job_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            application_reference VARCHAR(50) UNIQUE,
            application_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            status_id INT NULL,
            status ENUM('PENDING','SHORTLISTED','INTERVIEWED','OFFERED','ACCEPTED','REJECTED','PROCESSING') DEFAULT 'PENDING',
            status_notes TEXT,
            
            pengistiharan TINYINT(1) DEFAULT 1,
            jawatan_dipohon VARCHAR(255),
            payment_reference VARCHAR(100),
            
            gambar_passport VARCHAR(255),
            salinan_ic VARCHAR(255),
            salinan_surat_beranak VARCHAR(255),
            
            nama_penuh VARCHAR(255) NOT NULL,
            nombor_surat_beranak VARCHAR(100),
            nombor_ic VARCHAR(20) NOT NULL,
            agama VARCHAR(50),
            taraf_perkahwinan VARCHAR(50),
            jantina VARCHAR(20),
            tarikh_lahir DATE,
            umur INT,
            email VARCHAR(255) NOT NULL,
            negeri_kelahiran VARCHAR(100),
            bangsa VARCHAR(50),
            warganegara VARCHAR(50),
            tempoh_bermastautin_selangor VARCHAR(10),
            nombor_telefon VARCHAR(20),
            
            alamat_tetap TEXT,
            bandar_tetap VARCHAR(100),
            negeri_tetap VARCHAR(100),
            poskod_tetap VARCHAR(10),
            
            alamat_surat_sama TINYINT(1) DEFAULT 0,
            alamat_surat TEXT,
            bandar_surat VARCHAR(100),
            negeri_surat VARCHAR(100),
            poskod_surat VARCHAR(10),
            
            lesen_memandu JSON,
            tarikh_tamat_lesen DATE,
            ahli_keluarga JSON,
            
            kemahiran_bahasa JSON,
            kemahiran_komputer JSON,
            maklumat_kegiatan_luar JSON,
            
            darah_tinggi VARCHAR(10),
            kencing_manis VARCHAR(10),
            penyakit_buah_pinggang VARCHAR(10),
            penyakit_jantung VARCHAR(10),
            batuk_kering_tibi VARCHAR(10),
            kanser VARCHAR(10),
            aids VARCHAR(10),
            penagih_dadah VARCHAR(10),
            penyakit_lain VARCHAR(10),
            perokok VARCHAR(10),
            berat_kg DECIMAL(5,2),
            tinggi_cm DECIMAL(5,2),
            pemegang_kad_oku VARCHAR(10),
            penyakit_lain_nyatakan TEXT,
            memakai_cermin_mata VARCHAR(10),
            jenis_rabun VARCHAR(50),
            jenis_oku JSON,
            kecacatan_anggota VARCHAR(100),
            kecacatan_penglihatan VARCHAR(100),
            kecacatan_pendengaran VARCHAR(100),
            jenis_kanta VARCHAR(100),
            
            maklumat_persekolahan JSON,
            kelulusan_dimiliki JSON,
            
            ada_pengalaman_kerja VARCHAR(10),
            pengalaman_kerja JSON,
            
            pekerja_perkhidmatan_awam VARCHAR(10),
            pekerja_perkhidmatan_awam_nyatakan TEXT,
            pertalian_kakitangan VARCHAR(10),
            pertalian_kakitangan_nyatakan TEXT,
            pernah_bekerja_mphs VARCHAR(10),
            pernah_bekerja_mphs_nyatakan TEXT,
            tindakan_tatatertib VARCHAR(10),
            tindakan_tatatertib_nyatakan TEXT,
            kesalahan_undangundang VARCHAR(10),
            kesalahan_undangundang_nyatakan TEXT,
            muflis VARCHAR(10),
            muflis_nyatakan TEXT,
            
            rujukan JSON,
            -- Spouse details
            nama_pasangan VARCHAR(255),
            telefon_pasangan VARCHAR(50),
            bilangan_anak INT,
            status_pasangan VARCHAR(50),
            pekerjaan_pasangan VARCHAR(255),
            nama_majikan_pasangan VARCHAR(255),
            telefon_pejabat_pasangan VARCHAR(50),
            alamat_majikan_pasangan TEXT,
            poskod_majikan_pasangan VARCHAR(10),
            bandar_majikan_pasangan VARCHAR(100),
            negeri_majikan_pasangan VARCHAR(100),
            
            -- Workflow + timestamps + notes
            submitted_at DATETIME NULL,
            completed_at DATETIME NULL,
            notes TEXT,
            admin_notes TEXT,
            reviewed_by INT NULL,
            reviewed_at DATETIME NULL,
            approved_by INT NULL,
            approved_at DATETIME NULL,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (job_id),
            INDEX (application_date),
            INDEX (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        try {
            $pdo->exec("ALTER TABLE job_applications ADD UNIQUE KEY uniq_job_ic (job_id, nombor_ic)");
        } catch (PDOException $e) {
        }

        // Ensure columns exist for evolving schema (MySQL-compatible)
        $columns_to_add = [
            'status_id' => 'INT NULL',
            'submitted_at' => 'DATETIME NULL',
            'completed_at' => 'DATETIME NULL',
            'submission_locked' => 'TINYINT(1) DEFAULT 0 COMMENT "1 = locked (cannot edit), 0 = editable"',
            'notes' => 'TEXT',
            'admin_notes' => 'TEXT',
            'reviewed_by' => 'INT NULL',
            'reviewed_at' => 'DATETIME NULL',
            'approved_by' => 'INT NULL',
            'approved_at' => 'DATETIME NULL',
            'nama_pasangan' => 'VARCHAR(255)',
            'telefon_pasangan' => 'VARCHAR(50)',
            'bilangan_anak' => 'INT',
            'status_pasangan' => 'VARCHAR(50)',
            'pekerjaan_pasangan' => 'VARCHAR(255)',
            'nama_majikan_pasangan' => 'VARCHAR(255)',
            'telefon_pejabat_pasangan' => 'VARCHAR(50)',
            'alamat_majikan_pasangan' => 'TEXT',
            'poskod_majikan_pasangan' => 'VARCHAR(10)',
            'bandar_majikan_pasangan' => 'VARCHAR(100)',
            'negeri_majikan_pasangan' => 'VARCHAR(100)',
            'salinan_lesen_memandu' => 'VARCHAR(255)',
            'ada_pengalaman_kerja' => 'VARCHAR(10)',
            'gambar_passport_path' => 'VARCHAR(255)',
            'salinan_ic_path' => 'VARCHAR(255)',
            'salinan_surat_beranak_path' => 'VARCHAR(255)',
            'salinan_lesen_memandu_path' => 'VARCHAR(255)',
            'spm_tahun' => 'VARCHAR(4)',
            'spm_gred_keseluruhan' => 'VARCHAR(50)',
            'spm_angka_giliran' => 'VARCHAR(50)',
            'spm_bahasa_malaysia' => 'VARCHAR(5)',
            'spm_bahasa_inggeris' => 'VARCHAR(5)',
            'spm_matematik' => 'VARCHAR(5)',
            'spm_sejarah' => 'VARCHAR(5)',
            'spm_subjek_lain' => 'JSON',
            'spm_salinan_sijil' => 'VARCHAR(255)',
            'rujukan_1_nama' => 'VARCHAR(255)',
            'rujukan_1_telefon' => 'VARCHAR(50)',
            'rujukan_1_tempoh' => 'VARCHAR(10)',
            'rujukan_2_nama' => 'VARCHAR(255)',
            'rujukan_2_telefon' => 'VARCHAR(50)',
            'rujukan_2_tempoh' => 'VARCHAR(10)',
            'pengisytiharan_pengesahan' => 'VARCHAR(10)',
            'nama_kakitangan_pertalian' => 'VARCHAR(255)'
        ];
        
        foreach ($columns_to_add as $column => $definition) {
            try {
                $pdo->exec("ALTER TABLE job_applications ADD COLUMN `$column` $definition");
            } catch (PDOException $e) {
                // Column already exists, ignore error
                if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                    error_log("Schema update warning: " . $e->getMessage());
                }
            }
        }

        // Create application_education table
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_education (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            application_reference VARCHAR(50),
            nama_institusi VARCHAR(255) NOT NULL,
            dari_tahun VARCHAR(4),
            hingga_tahun VARCHAR(4),
            kelayakan VARCHAR(255),
            pangkat_gred_cgpa VARCHAR(50),
            sijil_path VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
            INDEX (application_id),
            INDEX (application_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_education LIKE 'application_id'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$col) {
                $pdo->exec("ALTER TABLE application_education ADD COLUMN application_id INT NULL");
                try { $pdo->exec("ALTER TABLE application_education ADD INDEX idx_app_id (application_id)"); } catch (PDOException $e) {}
                try { $pdo->exec("ALTER TABLE application_education ADD CONSTRAINT fk_edu_app FOREIGN KEY (application_id) REFERENCES application_application_main(id) ON DELETE CASCADE"); } catch (PDOException $e) {}
            }
        } catch (PDOException $e) {
        }
        
        // Add application_reference column if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE application_education ADD COLUMN application_reference VARCHAR(50)");
        } catch (PDOException $e) {
            // Column already exists or other error, ignore
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                error_log("Schema update application_education: " . $e->getMessage());
            }
        }
        try {
            $pdo->exec("ALTER TABLE application_education ADD INDEX idx_app_ref (application_reference)");
        } catch (PDOException $e) {
            // Index already exists, ignore
        }

        // Create application_computer_skills table
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_computer_skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_reference VARCHAR(50) NULL,
            application_id INT NULL,
            nama_perisian VARCHAR(255) NOT NULL,
            tahap_kemahiran VARCHAR(50) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (application_reference),
            INDEX (application_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Create application_language_skills table
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_language_skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_reference VARCHAR(50) NULL,
            application_id INT NULL,
            bahasa VARCHAR(50) NOT NULL,
            tahap_lisan VARCHAR(50) NULL,
            tahap_penulisan VARCHAR(50) NULL,
            gred_spm VARCHAR(10) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (application_reference),
            INDEX (application_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_language_skills LIKE 'application_id'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$col) {
                $pdo->exec("ALTER TABLE application_language_skills ADD COLUMN application_id INT NULL");
                try { $pdo->exec("ALTER TABLE application_language_skills ADD INDEX idx_lang_app_id (application_id)"); } catch (PDOException $e) {}
            }
        } catch (PDOException $e) {
        }

        // Create application_extracurricular table
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_extracurricular (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            application_reference VARCHAR(50),
            sukan_persatuan_kelab VARCHAR(255) NOT NULL,
            jawatan VARCHAR(255),
            peringkat VARCHAR(100),
            tahap VARCHAR(100),
            tahun VARCHAR(4),
            salinan_sijil_filename VARCHAR(255),
            salinan_sijil VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
            INDEX (application_id),
            INDEX (application_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Add missing columns to application_extracurricular
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_extracurricular LIKE 'application_id'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$col) {
                $pdo->exec("ALTER TABLE application_extracurricular ADD COLUMN application_id INT NULL");
                try { $pdo->exec("ALTER TABLE application_extracurricular ADD INDEX idx_extra_app_id (application_id)"); } catch (PDOException $e) {}
            }
        } catch (PDOException $e) {
        }
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_extracurricular LIKE 'application_reference'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_extracurricular ADD COLUMN application_reference VARCHAR(50) AFTER application_id");
                $pdo->exec("ALTER TABLE application_extracurricular ADD INDEX idx_extra_app_ref (application_reference)");
                error_log("Added application_reference column to application_extracurricular table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_extracurricular application_reference: " . $e->getMessage());
        }
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_extracurricular LIKE 'tahap'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_extracurricular ADD COLUMN tahap VARCHAR(100) AFTER peringkat");
                error_log("Added tahap column to application_extracurricular table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_extracurricular tahap: " . $e->getMessage());
        }
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_extracurricular LIKE 'salinan_sijil_filename'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_extracurricular ADD COLUMN salinan_sijil_filename VARCHAR(255) AFTER tahun");
                error_log("Added salinan_sijil_filename column to application_extracurricular table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_extracurricular salinan_sijil_filename: " . $e->getMessage());
        }

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_extracurricular LIKE 'tarikh_sijil'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_extracurricular ADD COLUMN tarikh_sijil DATE AFTER tahun");
            }
        } catch (PDOException $e) {
        }

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_extracurricular LIKE 'tahun'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_extracurricular ADD COLUMN tahun VARCHAR(4) AFTER peringkat");
                error_log("Added tahun column to application_extracurricular table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_extracurricular tahun: " . $e->getMessage());
        }

        // Ensure application_work_experience table has application_id
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'application_work_experience'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS application_work_experience (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    application_id INT NOT NULL,
                    nama_syarikat VARCHAR(255),
                    jawatan VARCHAR(255),
                    dari_bulan INT,
                    dari_tahun VARCHAR(4),
                    hingga_bulan INT,
                    hingga_tahun VARCHAR(4),
                    gaji DECIMAL(10,2),
                    bidang_tugas TEXT,
                    alasan_berhenti VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
                    INDEX (application_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            } else {
                try {
                    $colStmt = $pdo->query("SHOW COLUMNS FROM application_work_experience LIKE 'application_id'");
                    $col = $colStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$col) {
                        $pdo->exec("ALTER TABLE application_work_experience ADD COLUMN application_id INT NULL");
                        try { $pdo->exec("ALTER TABLE application_work_experience ADD INDEX idx_work_app_id (application_id)"); } catch (PDOException $e) {}
                    }
                } catch (PDOException $e) {}
            }
        } catch (PDOException $e) {
        }

        // Ensure application_computer_skills table has application_id
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_computer_skills LIKE 'application_id'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$col) {
                $pdo->exec("ALTER TABLE application_computer_skills ADD COLUMN application_id INT NULL");
                try { $pdo->exec("ALTER TABLE application_computer_skills ADD INDEX idx_comp_app_id (application_id)"); } catch (PDOException $e) {}
            }
        } catch (PDOException $e) {
        }

        // Ensure application_spm_subjects table exists and has application_id
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'application_spm_subjects'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS application_spm_subjects (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    application_id INT NOT NULL,
                    mata_pelajaran VARCHAR(255) NOT NULL,
                    gred VARCHAR(5) NOT NULL,
                    tahun VARCHAR(4),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
                    INDEX (application_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            } else {
                try {
                    $colStmt = $pdo->query("SHOW COLUMNS FROM application_spm_subjects LIKE 'application_id'");
                    $col = $colStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$col) {
                        $pdo->exec("ALTER TABLE application_spm_subjects ADD COLUMN application_id INT NULL");
                        try { $pdo->exec("ALTER TABLE application_spm_subjects ADD INDEX idx_spm_app_id (application_id)"); } catch (PDOException $e) {}
                    }
                } catch (PDOException $e) {}
            }
        } catch (PDOException $e) {
        }

        // Create application_references table
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_references (
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
        
        // Check and add application_reference column if it doesn't exist (migration from old structure)
        $columns = $pdo->query("SHOW COLUMNS FROM application_references LIKE 'application_reference'")->fetchAll();
        if (empty($columns)) {
            $pdo->exec("ALTER TABLE application_references 
                ADD COLUMN application_reference VARCHAR(50) NOT NULL AFTER id,
                ADD INDEX (application_reference)");
        }
        
        // Add missing columns to application_references
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_references LIKE 'application_id'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_references ADD COLUMN application_id INT NULL AFTER application_reference");
                $pdo->exec("ALTER TABLE application_references ADD INDEX idx_ref_app_id (application_id)");
                error_log("Added application_id column to application_references table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_references application_id: " . $e->getMessage());
        }
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_references LIKE 'no_telefon'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_references ADD COLUMN no_telefon VARCHAR(50) AFTER nama");
                error_log("Added no_telefon column to application_references table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_references no_telefon: " . $e->getMessage());
        }
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_references LIKE 'tempoh_mengenali'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_references ADD COLUMN tempoh_mengenali VARCHAR(100) AFTER telefon");
                error_log("Added tempoh_mengenali column to application_references table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_references tempoh_mengenali: " . $e->getMessage());
        }
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_references LIKE 'jawatan'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_references ADD COLUMN jawatan VARCHAR(255) AFTER tempoh");
                error_log("Added jawatan column to application_references table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_references jawatan: " . $e->getMessage());
        }
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_references LIKE 'alamat'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_references ADD COLUMN alamat TEXT AFTER jawatan");
                error_log("Added alamat column to application_references table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_references alamat: " . $e->getMessage());
        }
        
        // Update tempoh column to VARCHAR(2) if it's INT
        $tempoh_col = $pdo->query("SHOW COLUMNS FROM application_references WHERE Field = 'tempoh'")->fetch();
        if ($tempoh_col && (strpos($tempoh_col['Type'], 'int') !== false || strpos($tempoh_col['Type'], 'INT') !== false)) {
            $pdo->exec("ALTER TABLE application_references MODIFY tempoh VARCHAR(2)");
        }

        // Create application_work_experience table
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_work_experience (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            application_reference VARCHAR(50),
            nama_syarikat VARCHAR(255) NOT NULL,
            jawatan VARCHAR(255),
            unit_bahagian VARCHAR(255),
            gred VARCHAR(50),
            taraf_jawatan VARCHAR(50),
            dari_bulan INT,
            dari_tahun VARCHAR(4),
            hingga_bulan INT,
            hingga_tahun VARCHAR(4),
            gaji DECIMAL(10,2),
            mula_berkhidmat DATE,
            tamat_berkhidmat DATE,
            bidang_tugas TEXT,
            alasan_berhenti TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
            INDEX (application_id),
            INDEX (application_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Add missing columns to application_work_experience
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_work_experience LIKE 'application_reference'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_work_experience ADD COLUMN application_reference VARCHAR(50) AFTER application_id");
                $pdo->exec("ALTER TABLE application_work_experience ADD INDEX idx_work_app_ref (application_reference)");
                error_log("Added application_reference column to application_work_experience table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_work_experience application_reference: " . $e->getMessage());
        }
        
        $work_columns = [
            'dari_bulan' => 'INT',
            'dari_tahun' => 'VARCHAR(4)',
            'hingga_bulan' => 'INT',
            'hingga_tahun' => 'VARCHAR(4)'
        ];
        
        foreach ($work_columns as $col => $def) {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM application_work_experience LIKE '{$col}'");
                if (!$stmt->fetch()) {
                    $pdo->exec("ALTER TABLE application_work_experience ADD COLUMN {$col} {$def}");
                    error_log("Added {$col} column to application_work_experience table");
                }
            } catch (PDOException $e) {
                error_log("Schema update for application_work_experience {$col}: " . $e->getMessage());
            }
        }

        // Create application_professional_bodies table
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_professional_bodies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_reference VARCHAR(50) NOT NULL,
            application_id INT NULL,
            nama_lembaga VARCHAR(255) NOT NULL,
            sijil_diperoleh VARCHAR(255),
            no_ahli VARCHAR(100),
            tahun VARCHAR(4),
            salinan_sijil_filename VARCHAR(255),
            salinan_sijil VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (application_reference),
            INDEX (application_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Add missing columns to application_professional_bodies
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_professional_bodies LIKE 'application_id'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_professional_bodies ADD COLUMN application_id INT NULL AFTER application_reference");
                $pdo->exec("ALTER TABLE application_professional_bodies ADD INDEX idx_prof_app_id (application_id)");
                error_log("Added application_id column to application_professional_bodies table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_professional_bodies application_id: " . $e->getMessage());
        }
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_professional_bodies LIKE 'salinan_sijil_filename'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_professional_bodies ADD COLUMN salinan_sijil_filename VARCHAR(255) AFTER tahun");
                error_log("Added salinan_sijil_filename column to application_professional_bodies table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_professional_bodies salinan_sijil_filename: " . $e->getMessage());
        }

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_professional_bodies LIKE 'tahun'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_professional_bodies ADD COLUMN tahun VARCHAR(4) AFTER no_ahli");
                error_log("Added tahun column to application_professional_bodies table");
            } else {
                // Check if tahun is DATE type and change it to VARCHAR(4)
                $stmt = $pdo->query("SHOW COLUMNS FROM application_professional_bodies LIKE 'tahun'");
                $column = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($column && strpos($column['Type'], 'date') !== false) {
                    $pdo->exec("ALTER TABLE application_professional_bodies MODIFY COLUMN tahun VARCHAR(4)");
                    error_log("Modified tahun column from DATE to VARCHAR(4) in application_professional_bodies table");
                }
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_professional_bodies tahun: " . $e->getMessage());
        }

        // Create application_spm_results table
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_spm_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            application_reference VARCHAR(50),
            tahun VARCHAR(4) NOT NULL,
            gred_keseluruhan VARCHAR(50),
            angka_giliran VARCHAR(50) NOT NULL,
            bahasa_malaysia VARCHAR(5),
            bahasa_inggeris VARCHAR(5),
            matematik VARCHAR(5),
            sejarah VARCHAR(5),
            subjek_lain TEXT,
            gred_subjek_lain TEXT,
            subjek VARCHAR(255),
            gred VARCHAR(10),
            salinan_sijil_filename VARCHAR(255),
            salinan_sijil VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES application_application_main(id) ON DELETE CASCADE,
            INDEX (application_id),
            INDEX (application_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Add missing columns to application_spm_results
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_spm_results LIKE 'application_reference'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_spm_results ADD COLUMN application_reference VARCHAR(50) AFTER application_id");
                $pdo->exec("ALTER TABLE application_spm_results ADD INDEX idx_spm_app_ref (application_reference)");
                error_log("Added application_reference column to application_spm_results table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_spm_results application_reference: " . $e->getMessage());
        }
        
        $spm_columns = [
            'gred_keseluruhan' => 'VARCHAR(50)',
            'bahasa_malaysia' => 'VARCHAR(5)',
            'bahasa_inggeris' => 'VARCHAR(5)',
            'matematik' => 'VARCHAR(5)',
            'sejarah' => 'VARCHAR(5)',
            'subjek_lain' => 'TEXT',
            'gred_subjek_lain' => 'TEXT',
            'salinan_sijil_filename' => 'VARCHAR(255)'
        ];
        
        foreach ($spm_columns as $col => $def) {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM application_spm_results LIKE '{$col}'");
                if (!$stmt->fetch()) {
                    $pdo->exec("ALTER TABLE application_spm_results ADD COLUMN {$col} {$def}");
                    error_log("Added {$col} column to application_spm_results table");
                }
            } catch (PDOException $e) {
                error_log("Schema update for application_spm_results {$col}: " . $e->getMessage());
            }
        }

        // Create application_spm_additional_subjects table
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_spm_additional_subjects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            application_reference VARCHAR(50),
            tahun VARCHAR(4) NOT NULL,
            angka_giliran VARCHAR(50) NOT NULL,
            subjek VARCHAR(255) NOT NULL,
            gred VARCHAR(10) NOT NULL,
            salinan_sijil VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES application_application_main(id) ON DELETE CASCADE,
            INDEX (application_id),
            INDEX (application_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Add application_reference to application_spm_additional_subjects if missing
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_spm_additional_subjects LIKE 'application_reference'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_spm_additional_subjects ADD COLUMN application_reference VARCHAR(50) AFTER application_id");
                $pdo->exec("ALTER TABLE application_spm_additional_subjects ADD INDEX idx_spm_add_app_ref (application_reference)");
                error_log("Added application_reference column to application_spm_additional_subjects table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_spm_additional_subjects application_reference: " . $e->getMessage());
        }
        
        // Create application_family_members table
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_family_members (
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
        
        // Add missing columns to application_family_members
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_family_members LIKE 'application_id'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_family_members ADD COLUMN application_id INT NULL AFTER application_reference");
                $pdo->exec("ALTER TABLE application_family_members ADD INDEX idx_fam_app_id (application_id)");
                error_log("Added application_id column to application_family_members table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_family_members application_id: " . $e->getMessage());
        }
        
        // Create application_health table (Part 2 health data linked by application_reference)
        $pdo->exec("CREATE TABLE IF NOT EXISTS application_health (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_reference VARCHAR(50) NOT NULL,
            application_id INT NULL,
            job_id INT NOT NULL,
            darah_tinggi VARCHAR(10),
            kencing_manis VARCHAR(10),
            penyakit_buah_pinggang VARCHAR(10),
            penyakit_jantung VARCHAR(10),
            batuk_kering_tibi VARCHAR(10),
            kanser VARCHAR(10),
            aids VARCHAR(10),
            penagih_dadah VARCHAR(10),
            perokok VARCHAR(10),
            penyakit_lain VARCHAR(10),
            penyakit_lain_nyatakan TEXT,
            pemegang_kad_oku VARCHAR(10),
            jenis_oku TEXT,
            salinan_kad_oku VARCHAR(255),
            memakai_cermin_mata VARCHAR(10),
            jenis_rabun VARCHAR(50),
            berat_kg DECIMAL(5,2),
            tinggi_cm DECIMAL(5,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (job_id),
            INDEX (application_reference),
            INDEX (application_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Add application_id to application_health if missing
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM application_health LIKE 'application_id'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_health ADD COLUMN application_id INT NULL AFTER application_reference");
                $pdo->exec("ALTER TABLE application_health ADD INDEX idx_health_app_id (application_id)");
                error_log("Added application_id column to application_health table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_health application_id: " . $e->getMessage());
        }
        
        // Remove UNIQUE constraint from application_reference in application_health if it exists
        try {
            $stmt = $pdo->query("SHOW INDEXES FROM application_health WHERE Key_name = 'application_reference' AND Non_unique = 0");
            if ($stmt->fetch()) {
                $pdo->exec("ALTER TABLE application_health DROP INDEX application_reference");
                error_log("Removed UNIQUE constraint from application_reference in application_health table");
            }
        } catch (PDOException $e) {
            error_log("Schema update for application_health unique constraint: " . $e->getMessage());
        }
        
        // Create payment_transactions table for ToyyibPay integration
        $pdo->exec("CREATE TABLE IF NOT EXISTS payment_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            payment_reference VARCHAR(100) UNIQUE NOT NULL,
            bill_code VARCHAR(50),
            toyyibpay_bill_id VARCHAR(100),
            toyyibpay_reference VARCHAR(100),
            applicant_name VARCHAR(255) NOT NULL,
            applicant_nric VARCHAR(20) NOT NULL,
            applicant_email VARCHAR(255) NOT NULL,
            applicant_phone VARCHAR(20) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_status ENUM('pending', 'paid', 'failed', 'expired', 'cancelled') DEFAULT 'pending',
            status_id INT DEFAULT 0 COMMENT '-1=Failed, 0=Pending, 1=Paid/Success',
            payment_method VARCHAR(50),
            payment_date DATETIME NULL,
            callback_data TEXT,
            transaction_id VARCHAR(100),
            notes TEXT,
            expires_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_job_id (job_id),
            INDEX idx_payment_reference (payment_reference),
            INDEX idx_payment_status (payment_status),
            INDEX idx_status_id (status_id),
            INDEX idx_applicant_nric (applicant_nric),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (job_id) REFERENCES job_postings(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Add status_id and toyyibpay_reference columns if they don't exist (for existing tables)
        try {
            $pdo->exec("ALTER TABLE payment_transactions ADD COLUMN status_id INT DEFAULT 0 COMMENT '-1=Failed, 0=Pending, 1=Paid/Success'");
        } catch (PDOException $e) {
            // Column might already exist
        }
        
        try {
            $pdo->exec("ALTER TABLE payment_transactions ADD COLUMN toyyibpay_reference VARCHAR(100)");
        } catch (PDOException $e) {
            // Column might already exist
        }
        
        try {
            $pdo->exec("ALTER TABLE payment_transactions ADD INDEX idx_status_id (status_id)");
        } catch (PDOException $e) {
            // Index might already exist
        }
        
        // Create user_sessions table for edit tokens and session management
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
            id VARCHAR(255) NOT NULL PRIMARY KEY COMMENT 'Session token/ID',
            user_id INT NULL COMMENT 'User ID if authenticated user',
            application_id INT NULL COMMENT 'Application ID for edit sessions',
            ip_address VARCHAR(45) NULL COMMENT 'Client IP address',
            user_agent TEXT NULL COMMENT 'Client user agent string',
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last activity timestamp',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Session creation timestamp',
            
            INDEX idx_user_id (user_id),
            INDEX idx_application_id (application_id),
            INDEX idx_last_activity (last_activity),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User sessions for authentication and edit tokens'");
        
        return true;
    } catch (PDOException $e2) {
        error_log("Error creating tables (compat fallback): " . $e2->getMessage());
        return false;
    }
}
