<?php
/**
 * @FileID: save_application_001
 * @Module: ApplicationSave
 * @Author: Nefi
 * @LastModified: 2025-11-13T00:00:00Z
 * @SecurityTag: validated
 */

require_once __DIR__ . '/includes/ErrorHandler.php';
require_once __DIR__ . '/modules/FileUploaderImplementation.php';
require_once __DIR__ . '/includes/schema.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$result = require __DIR__ . '/config.php';
$config = $result['config'] ?? $result;

$errors = [];
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Ensure all tables exist
    create_tables($pdo);

    // --- Validation ---
    $required_fields = ['nama_penuh', 'nombor_ic', 'email', 'job_id'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Medan mandatori `{$field}` tidak diisi.";
        }
    }

    $is_edit = !empty($_POST['edit']);
    if (!$is_edit && empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM job_applications WHERE job_id = ? AND nombor_ic = ? LIMIT 1');
        $stmt->execute([$job_id, trim($_POST['nombor_ic'] ?? '')]);
        if ($stmt->fetch()) {
            $errors[] = 'Permohonan untuk jawatan ini dengan NRIC tersebut sudah wujud.';
        }
    }

    if (!empty($errors)) {
        throw new Exception(implode('; ', $errors));
    }

    // --- Begin Transaction ---
    $pdo->beginTransaction();

    // --- Collect and Insert Main Application Data ---
    $main_data = [];
    $table_columns = array_map(fn($col) => $col['Field'], $pdo->query("DESCRIBE job_applications")->fetchAll(PDO::FETCH_ASSOC));
    
    foreach ($table_columns as $column) {
        if (isset($_POST[$column])) {
            $main_data[$column] = $_POST[$column];
        }
    }

    // Override/set specific fields
    $main_data['job_id'] = $job_id;
    $main_data['status'] = 'PENDING';
    $main_data['submission_locked'] = 0;
    
    // Convert array fields to JSON (only fields that belong in job_applications table as JSON)
    $json_fields = ['lesen_memandu', 'kemahiran_bahasa', 'kemahiran_komputer', 'spm_subjek_lain', 'ahli_keluarga'];
    foreach ($json_fields as $field) {
        if (isset($main_data[$field]) && is_array($main_data[$field])) {
            $main_data[$field] = json_encode($main_data[$field]);
        }
    }
    
    // Handle kegiatan_luar -> maklumat_kegiatan_luar mapping
    if (isset($main_data['kegiatan_luar']) && is_array($main_data['kegiatan_luar'])) {
        $main_data['maklumat_kegiatan_luar'] = json_encode($main_data['kegiatan_luar']);
    }
    
    // Handle persekolahan -> maklumat_persekolahan mapping
    if (isset($main_data['persekolahan']) && is_array($main_data['persekolahan'])) {
        $main_data['maklumat_persekolahan'] = json_encode($main_data['persekolahan']);
    }
    
    $main_data['updated_at'] = date('Y-m-d H:i:s');

    // Unset fields that should not be in the main insert
    // These are handled in separate tables or are control fields
    unset(
        $main_data['id'], 
        $main_data['action'], 
        $main_data['csrf_token'], 
        $main_data['redirect_to_preview'], 
        $main_data['final_submission'],
        $main_data['pengalaman_kerja'],   // Handled in application_work_experience table
        $main_data['pendidikan'],         // Handled in application_education table
        $main_data['persekolahan'],       // Handled in application_education table
        $main_data['badan_profesional'],  // Handled in application_professional_bodies table
        $main_data['kemahiran_bahasa'],   // Handled in application_language_skills table
        $main_data['kemahiran_komputer'], // Handled in application_computer_skills table
        $main_data['kegiatan_luar'],      // Handled in application_extracurricular table
        $main_data['rujukan_1_nama'],     // Handled in application_references table
        $main_data['rujukan_1_telefon'],  // Handled in application_references table
        $main_data['rujukan_1_tempoh'],   // Handled in application_references table
        $main_data['rujukan_2_nama'],     // Handled in application_references table
        $main_data['rujukan_2_telefon'],  // Handled in application_references table
        $main_data['rujukan_2_tempoh']    // Handled in application_references table
    );

    if ($is_edit && !empty($_POST['application_id'])) {
        $application_id = (int)$_POST['application_id'];
        unset($main_data['created_at']); // Don't update creation time
        $update_fields = [];
        foreach ($main_data as $key => $val) {
            $update_fields[] = "`{$key}` = :{$key}";
        }
        $stmt = $pdo->prepare("UPDATE job_applications SET " . implode(', ', $update_fields) . " WHERE id = :id");
        $main_data['id'] = $application_id;
        $stmt->execute($main_data);
    } else {
        $main_data['created_at'] = date('Y-m-d H:i:s');
        $main_data['application_reference'] = 'APP-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $stmt = $pdo->prepare("INSERT INTO job_applications (" . implode(',', array_keys($main_data)) . ") VALUES (:" . implode(',:', array_keys($main_data)) . ")");
        $stmt->execute($main_data);
        $application_id = $pdo->lastInsertId();
    }

    // --- Get Application Reference ---
    $stmt = $pdo->prepare("SELECT application_reference FROM job_applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $application_reference = $stmt->fetchColumn();

    if (!$application_reference) {
        throw new Exception("Gagal mendapatkan rujukan permohonan.");
    }

    // --- Handle File Uploads ---
    $file_fields = ['gambar_passport', 'salinan_ic', 'salinan_surat_beranak', 'salinan_lesen_memandu'];
    foreach ($file_fields as $field_name) {
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
            $file_path = uploadApplicationDocument($field_name, $application_reference, $field_name);
            if ($file_path) {
                $pdo->prepare("UPDATE job_applications SET {$field_name}_path = ? WHERE id = ?")->execute([$file_path, $application_id]);
            }
        }
    }
    
    // --- Handle Work Experience ---
    if ($is_edit) {
        $pdo->prepare("DELETE FROM application_work_experience WHERE application_reference = ?")->execute([$application_reference]);
    }
    if (isset($_POST['pengalaman_kerja']) && is_array($_POST['pengalaman_kerja'])) {
        $work_stmt = $pdo->prepare(
            "INSERT INTO application_work_experience (application_reference, application_id, nama_syarikat, jawatan, gaji, bidang_tugas, alasan_berhenti, mula_berkhidmat, tamat_berkhidmat, unit_bahagian, gred, taraf_jawatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($_POST['pengalaman_kerja'] as $work) {
            if (!empty($work['nama_syarikat']) || !empty($work['majikan'])) {
                $work_stmt->execute([
                    $application_reference,
                    $application_id,
                    trim($work['nama_syarikat'] ?? $work['majikan'] ?? ''),
                    trim($work['jawatan'] ?? ''),
                    trim($work['gaji'] ?? $work['gaji_bulanan'] ?? ''),
                    strtoupper(trim($work['bidang_tugas'] ?? '')),
                    strtoupper(trim($work['alasan_berhenti'] ?? '')),
                    !empty($work['mula_berkhidmat']) && $work['mula_berkhidmat'] !== '' ? 
                        (strlen($work['mula_berkhidmat']) === 7 ? $work['mula_berkhidmat'] . '-01' : $work['mula_berkhidmat']) : 
                        (!empty($work['tarikh_mula_kerja']) ? $work['tarikh_mula_kerja'] : null),
                    !empty($work['tamat_berkhidmat']) && $work['tamat_berkhidmat'] !== '' ? 
                        (strlen($work['tamat_berkhidmat']) === 7 ? $work['tamat_berkhidmat'] . '-01' : $work['tamat_berkhidmat']) : 
                        (!empty($work['tarikh_akhir_kerja']) ? $work['tarikh_akhir_kerja'] : null),
                    trim($work['unit_bahagian'] ?? ''),
                    trim($work['gred'] ?? ''),
                    trim($work['taraf_jawatan'] ?? ''),
                ]);
            }
        }
    }

    // --- Handle Education ---
    if ($is_edit) {
        $pdo->prepare("DELETE FROM application_education WHERE application_id = ?")->execute([$application_id]);
    }
    if (isset($_POST['pendidikan']) && is_array($_POST['pendidikan'])) {
        $edu_stmt = $pdo->prepare(
            "INSERT INTO application_education (application_id, tahap_pendidikan, nama_sekolah, pengkhususan, tahun_graduasi) VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($_POST['pendidikan'] as $edu) {
            if (!empty($edu['tahap_pendidikan'])) {
                $edu_stmt->execute([
                    $application_id,
                    trim($edu['tahap_pendidikan'] ?? ''),
                    trim($edu['nama_sekolah'] ?? ''),
                    trim($edu['pengkhususan'] ?? ''),
                    trim($edu['tahun_graduasi'] ?? ''),
                ]);
            }
        }
    }

    // --- Handle Professional Bodies ---
    if ($is_edit) {
        $pdo->prepare("DELETE FROM application_professional_bodies WHERE application_reference = ?")->execute([$application_reference]);
    }
    if (isset($_POST['badan_profesional']) && is_array($_POST['badan_profesional'])) {
        $prof_stmt = $pdo->prepare(
            "INSERT INTO application_professional_bodies (application_reference, nama_lembaga, sijil_diperoleh, no_ahli, tahun, salinan_sijil) VALUES (?, ?, ?, ?, ?, ?)"
        );
        foreach ($_POST['badan_profesional'] as $prof) {
            if (!empty($prof['nama_lembaga'])) {
                // Extract year from tarikh_sijil if provided
                $tahun = '';
                if (!empty($prof['tarikh_sijil'])) {
                    $tahun = date('Y', strtotime($prof['tarikh_sijil']));
                }
                
                $prof_stmt->execute([
                    $application_reference,
                    trim($prof['nama_lembaga'] ?? ''),
                    trim($prof['sijil'] ?? ''),
                    trim($prof['no_ahli'] ?? ''),
                    $tahun,
                    trim($prof['salinan_sijil'] ?? ''),
                ]);
            }
        }
    }

    // --- Handle Language Skills ---
    if ($is_edit) {
        $pdo->prepare("DELETE FROM application_language_skills WHERE application_reference = ?")->execute([$application_reference]);
    }
    if (isset($_POST['kemahiran_bahasa']) && is_array($_POST['kemahiran_bahasa'])) {
        $lang_stmt = $pdo->prepare(
            "INSERT INTO application_language_skills (application_reference, application_id, bahasa, pertuturan, penulisan, gred_spm) VALUES (?, ?, ?, ?, ?, ?)"
        );
        foreach ($_POST['kemahiran_bahasa'] as $lang) {
            if (!empty($lang['bahasa'])) {
                $lang_stmt->execute([
                    $application_reference,
                    $application_id,
                    strtoupper(trim($lang['bahasa'] ?? '')),
                    strtoupper(trim($lang['pertuturan'] ?? '')),
                    strtoupper(trim($lang['penulisan'] ?? '')),
                    trim($lang['gred_spm'] ?? ''),
                ]);
            }
        }
    }

    // --- Handle Computer Skills ---
    if ($is_edit) {
        $pdo->prepare("DELETE FROM application_computer_skills WHERE application_id = ?")->execute([$application_id]);
    }
    if (isset($_POST['kemahiran_komputer']) && is_array($_POST['kemahiran_komputer'])) {
        $comp_stmt = $pdo->prepare(
            "INSERT INTO application_computer_skills (application_id, nama_perisian, tahap_kemahiran) VALUES (?, ?, ?)"
        );
        foreach ($_POST['kemahiran_komputer'] as $comp) {
            if (!empty($comp['nama_perisian'])) {
                $comp_stmt->execute([
                    $application_id,
                    strtoupper(trim($comp['nama_perisian'] ?? '')),
                    strtoupper(trim($comp['tahap_kemahiran'] ?? '')),
                ]);
            }
        }
    }

    // --- Handle Extracurricular Activities ---
    if ($is_edit) {
        $pdo->prepare("DELETE FROM application_extracurricular WHERE application_reference = ?")->execute([$application_reference]);
    }
    if (isset($_POST['kegiatan_luar']) && is_array($_POST['kegiatan_luar'])) {
        $extra_stmt = $pdo->prepare(
            "INSERT INTO application_extracurricular (application_reference, application_id, sukan_persatuan_kelab, jawatan, peringkat, tarikh_sijil, salinan_sijil) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($_POST['kegiatan_luar'] as $extra) {
            if (!empty($extra['sukan_persatuan_kelab'])) {
                $extra_stmt->execute([
                    $application_reference,
                    $application_id,
                    strtoupper(trim($extra['sukan_persatuan_kelab'] ?? '')),
                    strtoupper(trim($extra['jawatan'] ?? '')),
                    trim($extra['peringkat'] ?? ''),
                    trim($extra['tarikh_sijil'] ?? ''),
                    trim($extra['salinan_sijil'] ?? ''),
                ]);
            }
        }
    }

    // --- Handle References ---
    if ($is_edit) {
        $pdo->prepare("DELETE FROM application_references WHERE application_reference = ?")->execute([$application_reference]);
    }
    
    // Handle Reference 1
    if (!empty($_POST['rujukan_1_nama'])) {
        $ref_stmt = $pdo->prepare(
            "INSERT INTO application_references (application_reference, nama, no_telefon, tempoh_mengenali, jawatan, alamat) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $ref_stmt->execute([
            $application_reference,
            strtoupper(trim($_POST['rujukan_1_nama'] ?? '')),
            trim($_POST['rujukan_1_telefon'] ?? ''),
            trim($_POST['rujukan_1_tempoh'] ?? '') . ' tahun',
            '', // jawatan not collected in form
            '', // alamat not collected in form
        ]);
    }
    
    // Handle Reference 2
    if (!empty($_POST['rujukan_2_nama'])) {
        if (!isset($ref_stmt)) {
            $ref_stmt = $pdo->prepare(
                "INSERT INTO application_references (application_reference, nama, no_telefon, tempoh_mengenali, jawatan, alamat) VALUES (?, ?, ?, ?, ?, ?)"
            );
        }
        $ref_stmt->execute([
            $application_reference,
            strtoupper(trim($_POST['rujukan_2_nama'] ?? '')),
            trim($_POST['rujukan_2_telefon'] ?? ''),
            trim($_POST['rujukan_2_tempoh'] ?? '') . ' tahun',
            '', // jawatan not collected in form
            '', // alamat not collected in form
        ]);
    }

    // --- Commit and Redirect ---
    $pdo->commit();

    $_SESSION['application_reference'] = $application_reference;
    $target_url = '/preview-application.php?app_id=' . urlencode($application_id) . '&ref=' . urlencode($application_reference);
    
    if (ob_get_level()) ob_end_clean();
    session_write_close();
    
    if (!headers_sent()) {
        header('Location: ' . $target_url);
    }
    echo "<html><head><meta http-equiv=\"refresh\" content=\"0;url=\" . htmlspecialchars($target_url, ENT_QUOTES, 'UTF-8') . \"\"></head><body><p>Redirecting...</p></body></html>";
    exit();

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the full error for debugging
    log_frontend_error('Application Save Failed', [
        'exception' => $e->getMessage(), 
        'trace' => $e->getTraceAsString(),
        'post' => $_POST
    ]);
    
    // Provide user-friendly error message
    $user_message = 'Ralat semasa menyimpan permohonan. Sila semak maklumat yang diisi dan cuba lagi.';
    
    // Check for specific error types
    if (strpos($e->getMessage(), 'Invalid JSON') !== false) {
        $user_message = 'Ralat semasa memproses data. Sila pastikan semua maklumat diisi dengan betul.';
    } elseif (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'sudah wujud') !== false) {
        $user_message = $e->getMessage(); // Show duplicate message as-is
    }
    
    $_SESSION['application_errors'] = [$user_message];
    $_SESSION['application_data'] = $_POST;
    
    $fallback_url = '/job-application-full.php?job_id=' . $job_id . '&error=1';
    if (ob_get_level()) ob_end_clean();
    session_write_close();

    if (!headers_sent()) {
        header('Location: ' . $fallback_url);
        exit();
    }
    echo "<html><head><meta http-equiv=\"refresh\" content=\"0;url=" . htmlspecialchars($fallback_url, ENT_QUOTES, 'UTF-8') . "\"></head><body><p>Ralat berlaku. Anda akan dikembalikan ke borang...</p></body></html>";
    exit();
}
?>