<?php
// Save Application with Token Generation
// This script handles form submissions and generates edit tokens

require_once 'includes/ErrorHandler.php';
require_once 'modules/FileUploaderImplementation.php';
session_start();

// Get database connection
$result = require 'config.php';
$config = $result['config'] ?? $result;

header('Content-Type: application/json');

try {
    // Connect to database
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

    // Get form data
    $job_code = $_POST['job_code'] ?? '';
    $payment_reference = $_POST['payment_reference'] ?? null;
    $edit_token = $_POST['edit_token'] ?? null;
    $application_id = $_POST['application_id'] ?? null;
    $application_reference = $_POST['application_reference'] ?? null;

    // Find job ID from job code
    $stmt = $pdo->prepare('SELECT id FROM job_postings WHERE job_code = ? LIMIT 1');
    $stmt->execute([$job_code]);
    $job = $stmt->fetch();

    if (!$job) {
        throw new Exception('Kod jawatan tidak sah');
    }

    $job_id = $job['id'];

    // Prepare application data from form
    $application_data = [
        'id' => $application_id,
        'application_reference' => $application_reference,
        'job_id' => $job_id,
        'jawatan_dipohon' => $_POST['jawatan_dipohon'] ?? null,
        'nama_penuh' => $_POST['nama_penuh'] ?? '',
        'nombor_ic' => $_POST['nombor_ic'] ?? '',
        'nombor_surat_beranak' => $_POST['nombor_surat_beranak'] ?? '',
        'email' => $_POST['email'] ?? '',
        'nombor_telefon' => $_POST['nombor_telefon'] ?? '',
        'agama' => $_POST['agama'] ?? '',
        'taraf_perkahwinan' => $_POST['taraf_perkahwinan'] ?? '',
        'jantina' => $_POST['jantina'] ?? '',
        'tarikh_lahir' => $_POST['tarikh_lahir'] ?? '',
        'umur' => $_POST['umur'] ?? '',
        'negeri_kelahiran' => $_POST['negeri_kelahiran'] ?? '',
        'bangsa' => $_POST['bangsa'] ?? '',
        'warganegara' => $_POST['warganegara'] ?? '',
        'tempoh_bermastautin_selangor' => $_POST['tempoh_bermastautin_selangor'] ?? '',
        'alamat_tetap' => $_POST['alamat_tetap'] ?? '',
        'poskod_tetap' => $_POST['poskod_tetap'] ?? '',
        'bandar_tetap' => $_POST['bandar_tetap'] ?? '',
        'negeri_tetap' => $_POST['negeri_tetap'] ?? '',
        'alamat_surat' => $_POST['alamat_surat'] ?? '',
        'poskod_surat' => $_POST['poskod_surat'] ?? '',
        'bandar_surat' => $_POST['bandar_surat'] ?? '',
        'negeri_surat' => $_POST['negeri_surat'] ?? '',
        'alamat_surat_sama' => isset($_POST['alamat_surat_sama']) ? 1 : 0,
        // Spouse information (maklumat pasangan)
        'nama_pasangan' => $_POST['nama_pasangan'] ?? null,
        'telefon_pasangan' => $_POST['telefon_pasangan'] ?? null,
        'bilangan_anak' => (!empty($_POST['bilangan_anak']) && $_POST['bilangan_anak'] !== '') ? (int)$_POST['bilangan_anak'] : 0,
        'status_pasangan' => $_POST['status_pasangan'] ?? null,
        'pekerjaan_pasangan' => $_POST['pekerjaan_pasangan'] ?? null,
        'nama_majikan_pasangan' => $_POST['nama_majikan_pasangan'] ?? null,
        'telefon_pejabat_pasangan' => $_POST['telefon_pejabat_pasangan'] ?? null,
        'alamat_majikan_pasangan' => $_POST['alamat_majikan_pasangan'] ?? null,
        'bandar_majikan_pasangan' => $_POST['bandar_majikan_pasangan'] ?? null,
        'negeri_majikan_pasangan' => $_POST['negeri_majikan_pasangan'] ?? null,
        'poskod_majikan_pasangan' => $_POST['poskod_majikan_pasangan'] ?? null,
        'payment_reference' => $payment_reference,
        'submission_status' => 'draft',
        'submission_locked' => 0,
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];

    // Handle family members data
    if (isset($_POST['ahli_keluarga']) && is_array($_POST['ahli_keluarga'])) {
        foreach ($_POST['ahli_keluarga'] as $index => $member) {
            $hubungan = $member['hubungan'] ?? '';
            if ($hubungan === 'IBU') {
                $application_data['ibu_nama'] = $member['nama'] ?? '';
                $application_data['ibu_pekerjaan'] = $member['pekerjaan'] ?? '';
                $application_data['ibu_telefon'] = $member['telefon'] ?? '';
                $application_data['ibu_kewarganegaraan'] = $member['kewarganegaraan'] ?? '';
            } elseif ($hubungan === 'AYAH') {
                $application_data['ayah_nama'] = $member['nama'] ?? '';
                $application_data['ayah_pekerjaan'] = $member['pekerjaan'] ?? '';
                $application_data['ayah_telefon'] = $member['telefon'] ?? '';
                $application_data['ayah_kewarganegaraan'] = $member['kewarganegaraan'] ?? '';
            }
        }
    }

    // Handle driving license data
    if (isset($_POST['lesen_memandu']) && is_array($_POST['lesen_memandu'])) {
        $application_data['lesen_memandu_set'] = implode(',', $_POST['lesen_memandu']);
    }
    // Only set expiry date if it's not empty
    if (isset($_POST['tarikh_tamat_lesen']) && !empty($_POST['tarikh_tamat_lesen'])) {
        $application_data['tarikh_tamat_lesen'] = $_POST['tarikh_tamat_lesen'];
    } else {
        $application_data['tarikh_tamat_lesen'] = null;
    }

    // Validate required fields
    $required_fields = ['nama_penuh', 'nombor_ic', 'email', 'nombor_telefon'];
    foreach ($required_fields as $field) {
        if (empty($application_data[$field])) {
            throw new Exception("Medan {$field} diperlukan");
        }
    }

    // Check if this is an edit or new application
    if ($edit_token && $application_id) {
        // Edit mode: validate token first
        $stmt = $pdo->prepare('
            SELECT id, application_id, ip_address, created_at, last_activity
            FROM user_sessions
            WHERE id = ? AND application_id = ?
            LIMIT 1
        ');
        $stmt->execute([$edit_token, $application_id]);
        $session = $stmt->fetch();

        if (!$session) {
            throw new Exception('Token edit tidak sah');
        }

        // Check if token is expired (12 hours)
        $created_ts = strtotime($session['created_at']);
        if ((time() - $created_ts) > (12 * 3600)) {
            // Lock the application and delete token
            $pdo->prepare('UPDATE application_application_main SET submission_locked = 1 WHERE id = ?')->execute([$application_id]);
            $pdo->prepare('DELETE FROM user_sessions WHERE id = ?')->execute([$edit_token]);
            throw new Exception('Token telah tamat tempoh. Permohonan telah dikunci.');
        }

        // Update last activity
        $pdo->prepare('UPDATE user_sessions SET last_activity = CURRENT_TIMESTAMP WHERE id = ?')->execute([$edit_token]);

        // Update existing application
        $application_data['updated_at'] = date('Y-m-d H:i:s');

        $update_fields = [];
        $update_values = [];
        
        foreach ($application_data as $field => $value) {
            if ($field !== 'id' && $field !== 'created_at') {
                // Normalize integer fields
                if ($field === 'bilangan_anak') {
                    // bilangan_anak defaults to 0 if empty
                    $normalized_value = ($value === '' || $value === null) ? 0 : (int)$value;
                } elseif ($field === 'umur' || $field === 'tempoh_bermastautin_selangor') {
                    // umur and tempoh_bermastautin_selangor use null if empty
                    $normalized_value = ($value === '' || $value === null) ? null : (int)$value;
                } else {
                    $normalized_value = $value;
                }
                $update_fields[] = "$field = ?";
                $update_values[] = $normalized_value;
            }
        }
        $update_values[] = $application_id;

        $stmt = $pdo->prepare("UPDATE application_application_main SET " . implode(', ', $update_fields) . " WHERE id = ?");
        $stmt->execute($update_values);

        log_info('Application updated via token', [
            'application_id' => $application_id,
            'token' => substr($edit_token, 0, 8) . '...',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

    } else {
        // New application: use the saveApplicationWithToken function
        $result = saveApplicationWithToken($pdo, $application_data, $job_id, $payment_reference);

        if (!$result['success']) {
            throw new Exception($result['error']);
        }

        $application_id = $result['application_id'];
        $application_reference = $result['application_reference'];
        $edit_token = $result['token'];
    }

    // Handle file uploads using centralized FileUploader module
    // Directory structure: /uploads/applications/<year>/<application_reference>/
    $uploaded_files = [];

    // Handle document uploads
    $file_fields = [
        'gambar_passport' => 'passport',
        'salinan_ic' => 'ic',
        'salinan_surat_beranak' => 'birth_cert',
        'salinan_lesen_memandu' => 'driving_license'
    ];

    foreach ($file_fields as $field_name => $file_prefix) {
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
            // Use centralized FileUploader module
            $file_path = uploadApplicationDocument($field_name, $application_reference, $file_prefix);
            
            if ($file_path) {
                // Update database with file path
                $pdo->prepare("UPDATE application_application_main SET {$field_name}_path = ? WHERE id = ?")
                    ->execute([$file_path, $application_id]);
                $uploaded_files[] = $field_name;
            } else {
                error_log("Failed to upload {$field_name} for application {$application_reference}");
            }
        }
    }

    // Store form data in session for next steps
    $_SESSION['application_form_data'] = $application_data;
    $_SESSION['application_id'] = $application_id;
    $_SESSION['application_reference'] = $application_reference;
    $_SESSION['edit_token'] = $edit_token;

    log_info('Application saved successfully', [
        'application_id' => $application_id,
        'application_reference' => $application_reference,
        'uploaded_files' => $uploaded_files,
        'token' => substr($edit_token, 0, 8) . '...'
    ]);

    // Return success response
    echo json_encode([
        'success' => true,
        'application_id' => $application_id,
        'application_reference' => $application_reference,
        'token' => $edit_token,
        'message' => 'Data berjaya disimpan'
    ]);

} catch (Exception $e) {
    log_error('Error saving application with token', [
        'exception' => $e->getMessage(),
        'post_data' => $_POST,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Function to save application with token (from job-application-1.php)
function saveApplicationWithToken($pdo, $application_data, $job_id, $payment_reference = null) {
    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Generate unique application reference if not exists
        if (empty($application_data['application_reference'])) {
            $application_data['application_reference'] = 'APP-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
        }

        // Prepare application data
        $fields = [
            'job_id' => $job_id,
            'application_reference' => $application_data['application_reference'],
            'nama_penuh' => $application_data['nama_penuh'] ?? '',
            'nombor_ic' => $application_data['nombor_ic'] ?? '',
            'email' => $application_data['email'] ?? '',
            'nombor_telefon' => $application_data['nombor_telefon'] ?? '',
            'submission_locked' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Add optional fields
        $optional_fields = [
            'jawatan_dipohon', 'nombor_surat_beranak', 'agama', 'taraf_perkahwinan', 'jantina', 'tarikh_lahir',
            'umur', 'negeri_kelahiran', 'bangsa', 'warganegara', 'tempoh_bermastautin_selangor',
            'alamat_tetap', 'poskod_tetap', 'bandar_tetap', 'negeri_tetap',
            'alamat_surat', 'poskod_surat', 'bandar_surat', 'negeri_surat', 'alamat_surat_sama',
            'payment_reference', 'status', 'submission_status', 'lesen_memandu_set', 'tarikh_tamat_lesen',
            // Spouse fields (maklumat pasangan)
            'nama_pasangan', 'telefon_pasangan', 'bilangan_anak', 'status_pasangan', 'pekerjaan_pasangan',
            'nama_majikan_pasangan', 'telefon_pejabat_pasangan', 'alamat_majikan_pasangan',
            'bandar_majikan_pasangan', 'negeri_majikan_pasangan', 'poskod_majikan_pasangan'
        ];

        foreach ($optional_fields as $field) {
            if (isset($application_data[$field])) {
                $value = $application_data[$field];
                // Normalize integer fields - convert empty strings
                if ($field === 'bilangan_anak') {
                    // bilangan_anak defaults to 0 if empty
                    $fields[$field] = ($value === '' || $value === null) ? 0 : (int)$value;
                } elseif ($field === 'umur' || $field === 'tempoh_bermastautin_selangor') {
                    // umur and tempoh_bermastautin_selangor use null if empty
                    $fields[$field] = ($value === '' || $value === null) ? null : (int)$value;
                } else {
                    $fields[$field] = $value;
                }
            }
        }

        // Add family data
        $family_fields = ['ibu_nama', 'ibu_pekerjaan', 'ibu_telefon', 'ibu_kewarganegaraan',
                         'ayah_nama', 'ayah_pekerjaan', 'ayah_telefon', 'ayah_kewarganegaraan'];
        foreach ($family_fields as $field) {
            if (isset($application_data[$field])) {
                $fields[$field] = $application_data[$field];
            }
        }

        // Check if application already exists
        if (!empty($application_data['id'])) {
            // Update existing application
            $update_fields = [];
            $update_values = [];
            
            foreach ($fields as $field => $value) {
                if ($field !== 'id' && $field !== 'created_at') {
                    // Normalize integer fields
                    if ($field === 'bilangan_anak') {
                        // bilangan_anak defaults to 0 if empty
                        $normalized_value = ($value === '' || $value === null) ? 0 : (int)$value;
                    } elseif ($field === 'umur' || $field === 'tempoh_bermastautin_selangor') {
                        // umur and tempoh_bermastautin_selangor use null if empty
                        $normalized_value = ($value === '' || $value === null) ? null : (int)$value;
                    } else {
                        $normalized_value = $value;
                    }
                    $update_fields[] = "$field = ?";
                    $update_values[] = $normalized_value;
                }
            }
            $update_values[] = $application_data['id'];

            $stmt = $pdo->prepare("UPDATE application_application_main SET " . implode(', ', $update_fields) . " WHERE id = ?");
            $stmt->execute($update_values);

            $application_id = $application_data['id'];
        } else {
            // Insert new application
            $insert_fields = array_keys($fields);
            $insert_placeholders = str_repeat('?,', count($insert_fields) - 1) . '?';

            $stmt = $pdo->prepare("INSERT INTO application_application_main (" . implode(',', $insert_fields) . ") VALUES ($insert_placeholders)");
            $stmt->execute(array_values($fields));

            $application_id = $pdo->lastInsertId();
        }

        // Generate edit token for this application
        $token = generateEditToken(
            $pdo,
            $application_data['user_id'] ?? null,
            $application_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );

        if (!$token) {
            throw new Exception('Failed to generate edit token');
        }

        // Commit transaction
        $pdo->commit();

        return [
            'success' => true,
            'application_id' => $application_id,
            'application_reference' => $application_data['application_reference'],
            'token' => $token
        ];

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        throw $e;
    }
}

// Function to generate edit token
function generateEditToken($pdo, $user_id = null, $application_id = null, $ip_address = null, $user_agent = null) {
    try {
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));

        // Get client information
        $ip_address = $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? '');
        $user_agent = $user_agent ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');

        // Insert token into user_sessions table
        $stmt = $pdo->prepare('
            INSERT INTO user_sessions (id, user_id, application_id, ip_address, user_agent, last_activity, created_at)
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        $stmt->execute([$token, $user_id, $application_id, $ip_address, $user_agent]);

        return $token;
    } catch (Exception $e) {
        return false;
    }
}
?>
