<?php
// Start output buffering to prevent any accidental output
ob_start();

// Start session at the very beginning
session_start();

// Check if form was submitted first, before including heavy dependencies
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Clean up any output buffer before redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Location: index.php');
    exit();
}

// Only include heavy dependencies for POST requests
try {
    require_once 'includes/ErrorHandler.php';
    require_once 'includes/LogManager.php';
} catch (Exception $e) {
    // If there's an error loading dependencies, just redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Location: index.php');
    exit();
}

// Get database connection from config
$result = require 'config.php';
$config = $result['config'] ?? $result;

// Initialize variables
$pdo = null;
$errors = [];
$upload_dir = 'uploads/applications/';

// Create upload directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Function to handle document uploads using FileUploadHandler
function handleDocumentUpload($file, $job_id, $ic_number, $file_type) {
    global $errors, $upload_dir;
    
    // Skip if no file was uploaded
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    // Initialize FileUploadHandler
    require_once 'includes/FileUploadHandler.php';
    $fileHandler = new FileUploadHandler($upload_dir);
    
    // Set allowed types for documents (only JPG, PNG, GIF)
    $fileHandler->setAllowedTypes([
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif'
    ]);
    
    // Set maximum file size (2MB)
    $fileHandler->setMaxFileSize(2 * 1024 * 1024);
    
    // Create subfolder path
    $subfolder = $job_id . '/' . preg_replace('/[^0-9]/', '', $ic_number);
    
    // Upload the file
    $upload_result = $fileHandler->uploadFile(
        $file,
        $subfolder,
        $file_type,
        null,
        $file_type
    );
    
    // Handle upload result
    if ($upload_result['success']) {
        return $upload_result['file_path'];
    } else {
        $errors[] = "Ralat muat naik {$file_type}: " . $upload_result['error'];
        return null;
    }
}

// Connect to database
try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
} catch (PDOException $e) {
    error_log('Database connection error in application processing: ' . $e->getMessage());
    die('Ralat sambungan ke pangkalan data. Sila cuba sebentar lagi.');
}

// Validate required fields with some flexibility
$required_fields = [
    'job_id', 'nama_penuh', 'nombor_surat_beranak', 
    'nombor_ic', 'agama', 'taraf_perkahwinan', 'jantina', 'tarikh_lahir', 'umur', 
    'email', 'negeri_kelahiran', 'bangsa', 'warganegara', 'tempoh_bermastautin_selangor',
    'nombor_telefon', 'alamat_tetap', 'bandar_tetap', 'negeri_tetap', 'poskod_tetap'
];

// Check required fields
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $errors[] = "Medan {$field} diperlukan.";
    }
}

// Validate job exists and is still open
$job_id = intval($_POST['job_id']);
try {
    $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();
    
    if (!$job) {
        $errors[] = 'Jawatan tidak dijumpai.';
    } else {
        $today = new DateTime(date('Y-m-d'));
        $ad_close_date = new DateTime($job['ad_close_date']);
        
        if ($ad_close_date < $today) {
            $errors[] = 'Permohonan untuk jawatan ini telah ditutup.';
        }
    }
} catch (PDOException $e) {
    error_log('Error validating job in application processing: ' . $e->getMessage() . ' - Job ID: ' . $job_id);
    $errors[] = 'Ralat mengesahkan jawatan.';
}

// Process document uploads
$gambar_passport_path = null;
$salinan_ic_path = null;
$salinan_surat_beranak_path = null;

// Get IC number for folder structure
$ic_number = trim($_POST['nombor_ic'] ?? '');
$job_id = intval($_POST['job_id']);

// Check for regular file uploads
if (isset($_FILES['gambar_passport']) && $_FILES['gambar_passport']['error'] !== UPLOAD_ERR_NO_FILE) {
    $gambar_passport_path = handleDocumentUpload($_FILES['gambar_passport'], $job_id, $ic_number, 'gambar_passport');
}

if (isset($_FILES['salinan_ic']) && $_FILES['salinan_ic']['error'] !== UPLOAD_ERR_NO_FILE) {
    $salinan_ic_path = handleDocumentUpload($_FILES['salinan_ic'], $job_id, $ic_number, 'salinan_ic');
}

if (isset($_FILES['salinan_surat_beranak']) && $_FILES['salinan_surat_beranak']['error'] !== UPLOAD_ERR_NO_FILE) {
    $salinan_surat_beranak_path = handleDocumentUpload($_FILES['salinan_surat_beranak'], $job_id, $ic_number, 'salinan_surat_beranak');
}

// Process form data if no errors
if (empty($errors)) {
    try {
        $pdo->beginTransaction();
        
        // Create tables if they don't exist
        require_once 'includes/schema.php';
        create_tables($pdo);
        
        // Generate unique application reference
        $application_reference = 'APP-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        
        // Get job_code from job_postings table
        $job_code = '';
        if ($job) {
            $job_code = $job['job_code'] ?? '';
        }
        
        // Prepare SQL statement for job_applications table
        $stmt = $pdo->prepare("
            INSERT INTO job_applications (
                job_id, job_code, application_reference, jawatan_dipohon, payment_reference,
                nama_penuh, nombor_ic, nombor_surat_beranak, email, agama, 
                taraf_perkahwinan, jantina, tarikh_lahir, umur, negeri_kelahiran, 
                bangsa, warganegara, tempoh_bermastautin_selangor, nombor_telefon,
                alamat_tetap, bandar_tetap, negeri_tetap, poskod_tetap,
                alamat_surat_sama, alamat_surat, bandar_surat, negeri_surat, poskod_surat,
                gambar_passport, salinan_ic, salinan_surat_beranak,
                darah_tinggi, kencing_manis, penyakit_buah_pinggang, penyakit_jantung,
                batuk_kering_tibi, kanser, aids, penagih_dadah, penyakit_lain, perokok,
                berat_kg, tinggi_cm, pemegang_kad_oku, jenis_oku, memakai_cermin_mata, jenis_rabun,
                lesen_memandu, tarikh_tamat_lesen,
                pekerja_perkhidmatan_awam, pertalian_kakitangan, pernah_bekerja_mphs,
                tindakan_tatatertib, kesalahan_undangundang, muflis,
                rujukan, status, submitted_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, 'PENDING', NOW()
            )
        ");
        
        // Format data for JSON fields
        $lesen_memandu = isset($_POST['lesen_memandu']) ? json_encode($_POST['lesen_memandu']) : null;
        $jenis_oku = isset($_POST['jenis_oku']) ? json_encode($_POST['jenis_oku']) : null;
        $rujukan = isset($_POST['rujukan']) ? json_encode($_POST['rujukan']) : null;
        
        // Execute the statement with form data
        $stmt->execute([
            $job_id, $job_code, $application_reference, $_POST['jawatan_dipohon'] ?? $job['job_title'], $_POST['payment_reference'] ?? null,
            $_POST['nama_penuh'], $_POST['nombor_ic'], $_POST['nombor_surat_beranak'], $_POST['email'], $_POST['agama'],
            $_POST['taraf_perkahwinan'], $_POST['jantina'], $_POST['tarikh_lahir'], $_POST['umur'], $_POST['negeri_kelahiran'],
            $_POST['bangsa'], $_POST['warganegara'], $_POST['tempoh_bermastautin_selangor'], $_POST['nombor_telefon'],
            $_POST['alamat_tetap'], $_POST['bandar_tetap'], $_POST['negeri_tetap'], $_POST['poskod_tetap'],
            isset($_POST['alamat_surat_sama']) ? 1 : 0, $_POST['alamat_surat'] ?? null, $_POST['bandar_surat'] ?? null, $_POST['negeri_surat'] ?? null, $_POST['poskod_surat'] ?? null,
            $gambar_passport_path, $salinan_ic_path, $salinan_surat_beranak_path,
            $_POST['darah_tinggi'] ?? null, $_POST['kencing_manis'] ?? null, $_POST['penyakit_buah_pinggang'] ?? null, $_POST['penyakit_jantung'] ?? null,
            $_POST['batuk_kering_tibi'] ?? null, $_POST['kanser'] ?? null, $_POST['aids'] ?? null, $_POST['penagih_dadah'] ?? null, $_POST['penyakit_lain'] ?? null, $_POST['perokok'] ?? null,
            $_POST['berat_kg'] ?? null, $_POST['tinggi_cm'] ?? null, $_POST['pemegang_kad_oku'] ?? null, $jenis_oku, $_POST['memakai_cermin_mata'] ?? null, $_POST['jenis_rabun'] ?? null,
            $lesen_memandu, $_POST['tarikh_tamat_lesen'] ?? null,
            $_POST['pekerja_perkhidmatan_awam'] ?? null, $_POST['pertalian_kakitangan'] ?? null, $_POST['pernah_bekerja_mphs'] ?? null,
            $_POST['tindakan_tatatertib'] ?? null, $_POST['kesalahan_undangundang'] ?? null, $_POST['muflis'] ?? null,
            $rujukan, 'PENDING', date('Y-m-d H:i:s')
        ]);
        
        // Get the inserted application ID
        $application_id = $pdo->lastInsertId();
        
        // Process language skills
        if (isset($_POST['kemahiran_bahasa']) && is_array($_POST['kemahiran_bahasa'])) {
            $stmt = $pdo->prepare("
                INSERT INTO application_language_skills 
                (application_id, bahasa, tahap_lisan, tahap_penulisan) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($_POST['kemahiran_bahasa'] as $skill) {
                if (!empty($skill['bahasa'])) {
                    $stmt->execute([
                        $application_id,
                        strtoupper($skill['bahasa']),
                        strtoupper($skill['pertuturan'] ?? ''),
                        strtoupper($skill['penulisan'] ?? '')
                    ]);
                }
            }
        }
        
        // Process computer skills
        if (isset($_POST['kemahiran_komputer']) && is_array($_POST['kemahiran_komputer'])) {
            $stmt = $pdo->prepare("
                INSERT INTO application_computer_skills
                (application_id, nama_perisian, tahap_kemahiran)
                VALUES (?, ?, ?)
            ");
            
            foreach ($_POST['kemahiran_komputer'] as $skill) {
                if (!empty($skill['nama_perisian'])) {
                    $stmt->execute([
                        $application_id,
                        strtoupper($skill['nama_perisian']),
                        strtoupper($skill['tahap_kemahiran'] ?? '')
                    ]);
                }
            }
        }
        
        // Process education data
        if (isset($_POST['persekolahan']) && is_array($_POST['persekolahan'])) {
            $stmt = $pdo->prepare("
                INSERT INTO application_education 
                (application_id, nama_institusi, dari_tahun, hingga_tahun, kelayakan, pangkat_gred_cgpa) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['persekolahan'] as $edu) {
                if (!empty($edu['institusi'])) {
                    $stmt->execute([
                        $application_id,
                        strtoupper($edu['institusi']),
                        $edu['dari_tahun'] ?? '',
                        $edu['hingga_tahun'] ?? '',
                        strtoupper($edu['kelayakan'] ?? ''),
                        strtoupper($edu['gred'] ?? '')
                    ]);
                }
            }
        }
        
        // Process work experience
        if (isset($_POST['pengalaman_kerja']) && is_array($_POST['pengalaman_kerja'])) {
            // Store work experience in JSON format
            $pengalaman_kerja_json = json_encode($_POST['pengalaman_kerja']);
            
            // Update the application record with work experience data
            $stmt = $pdo->prepare("
                UPDATE job_applications 
                SET pengalaman_kerja = ?, ada_pengalaman_kerja = ? 
                WHERE id = ?
            ");
            
            $stmt->execute([
                $pengalaman_kerja_json,
                $_POST['ada_pengalaman_kerja'] ?? 'Tidak',
                $application_id
            ]);
        }
        
        // Commit the transaction
        $pdo->commit();
        
        // Store application ID in session for preview
        $_SESSION['application_id'] = $application_id;
        $_SESSION['application_reference'] = $application_reference;
        
        // Redirect to preview page
        header("Location: preview-application.php?app_id=" . $application_id);
        exit();
        
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        error_log('Error processing application: ' . $e->getMessage());
        $errors[] = 'Ralat memproses permohonan: ' . $e->getMessage();
    }
}

// If there are errors, display them and go back to the form
if (!empty($errors)) {
    $_SESSION['application_errors'] = $errors;
    header("Location: job-application-1.php?job_id=" . $job_id);
    exit();
}