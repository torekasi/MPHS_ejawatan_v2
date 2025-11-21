<?php
// Start output buffering to prevent any accidental output
ob_start();

// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Temporary debugging - enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Form submission check already done above

// Debug logging - track all incoming data (only for POST requests)
error_log("=== FORM SUBMISSION DEBUG ===");
error_log("Form submission received - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data keys: " . implode(', ', array_keys($_POST)));
error_log("POST data count: " . count($_POST));
error_log("FILES data keys: " . implode(', ', array_keys($_FILES)));
error_log("FILES data count: " . count($_FILES));
error_log("Job ID from POST: " . ($_POST['job_id'] ?? 'not set'));
error_log("Nama penuh from POST: " . ($_POST['nama_penuh'] ?? 'not set'));
error_log("Email from POST: " . ($_POST['email'] ?? 'not set'));
error_log("Client debug info: " . ($_POST['client_debug'] ?? 'not set'));

// Check for preview page file data
$preview_file_keys = array_filter(array_keys($_POST), function($key) {
    return strpos($key, '_content') !== false;
});
error_log("Preview file keys found: " . implode(', ', $preview_file_keys));
error_log("Full POST data: " . print_r($_POST, true));

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
        return null;
    }
}

// Function to handle education certificate uploads - uses the same method as other document uploads
function handleEducationCertificateUpload($file, $job_id, $ic_number, $education_index) {
    global $errors, $upload_dir;
    
    // Skip if no file was uploaded
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    // Initialize FileUploadHandler
    require_once 'includes/FileUploadHandler.php';
    $fileHandler = new FileUploadHandler($upload_dir);
    
    // Set allowed types for education certificates (images and PDF)
    $fileHandler->setAllowedTypes([
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'
    ]);
    
    // Set maximum file size (5MB)
    $fileHandler->setMaxFileSize(5 * 1024 * 1024);
    
    // Create subfolder path
    $subfolder = $job_id . '/' . preg_replace('/[^0-9]/', '', $ic_number);
    
    // Upload the file
    $upload_result = $fileHandler->uploadFile(
        $file,
        $subfolder,
        'sijil-' . $education_index,
        null,
        'sijil'
    );
    
    // Handle upload result
    if ($upload_result['success']) {
        // Return an array with file information like the original function
        return [
            'path' => $upload_result['file_path'],
            'name' => $file['name'],
            'type' => $file['type'],
            'ext' => strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))
        ];
    } else {
        return null;
    }
}

// Function to handle file data from preview page (base64 encoded)
function handlePreviewFileUpload($file_content, $file_name, $file_type, $job_id, $ic_number, $file_category) {
    global $errors, $upload_dir;
    
    // Decode base64 content
    $decoded_content = base64_decode($file_content);
    if ($decoded_content === false) {
        $errors[] = "Ralat memproses fail {$file_name}.";
        return null;
    }
    
    // Initialize FileUploadHandler
    require_once 'includes/FileUploadHandler.php';
    $fileHandler = new FileUploadHandler($upload_dir);
    
    // Set allowed types and max file size based on category
    if ($file_category === 'document') {
        // For passport photo, IC, birth certificate
        $fileHandler->setAllowedTypes([
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif'
        ]);
        $fileHandler->setMaxFileSize(2 * 1024 * 1024); // 2MB
    } elseif ($file_category === 'certificate') {
        // For education certificates
        $fileHandler->setAllowedTypes([
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'
        ]);
        $fileHandler->setMaxFileSize(5 * 1024 * 1024); // 5MB
    }
    
    // Create subfolder path
    $subfolder = $job_id . '/' . preg_replace('/[^0-9]/', '', $ic_number);
    
    // Create a temporary file from the decoded content
    $temp_file = tempnam(sys_get_temp_dir(), 'preview_');
    file_put_contents($temp_file, $decoded_content);
    
    // Create a file array similar to $_FILES structure
    $file_array = [
        'name' => $file_name,
        'type' => $file_type,
        'tmp_name' => $temp_file,
        'error' => 0,
        'size' => strlen($decoded_content)
    ];
    
    // Upload the file
    $upload_result = $fileHandler->uploadFile(
        $file_array,
        $subfolder,
        $file_category,
        null,
        $file_category
    );
    
    // Clean up the temporary file
    @unlink($temp_file);
    
    // Handle upload result
    if ($upload_result['success']) {
        return $upload_result['file_path'];
    } else {
        $errors[] = "Gagal memuat naik fail {$file_name}: " . $upload_result['error'];
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

// Validate required fields
// For Save & Preview, only enforce minimal DB-required fields
$redirect_to_preview_flag = isset($_POST['redirect_to_preview']) && $_POST['redirect_to_preview'] === 'true';
$required_fields = $redirect_to_preview_flag
    ? ['job_id', 'nama_penuh', 'nombor_ic', 'email']
    : [
        'job_id', 'nama_penuh', 'nombor_surat_beranak',
        'nombor_ic', 'agama', 'taraf_perkahwinan', 'jantina', 'tarikh_lahir', 'umur',
        'email', 'negeri_kelahiran', 'bangsa', 'warganegara', 'tempoh_bermastautin_selangor',
        'nombor_telefon', 'alamat_tetap', 'bandar_tetap', 'negeri_tetap', 'poskod_tetap',
        'darah_tinggi', 'kencing_manis', 'penyakit_buah_pinggang', 'penyakit_jantung',
        'batuk_kering_tibi', 'kanser', 'aids', 'penagih_dadah', 'penyakit_lain', 'perokok',
        'berat_kg', 'tinggi_cm', 'pemegang_kad_oku', 'memakai_cermin_mata',
        'pekerja_perkhidmatan_awam', 'pertalian_kakitangan', 'pernah_bekerja_mphs',
        'tindakan_tatatertib', 'kesalahan_undangundang', 'muflis'
      ];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        $errors[] = "Medan {$field} diperlukan.";
    }
}

if (empty($errors)) {
    $is_edit = isset($_POST['edit']) && $_POST['edit'] === '1';
    try {
        $stmt = $pdo->prepare('SELECT id FROM job_applications WHERE job_id = ? AND nombor_ic = ? LIMIT 1');
        $stmt->execute([(int)($_POST['job_id'] ?? 0), trim($_POST['nombor_ic'] ?? '')]);
        $existing = $stmt->fetch();
        if ($existing && !$is_edit) {
            $errors[] = 'Permohonan untuk jawatan ini dengan NRIC tersebut sudah wujud.';
        }
    } catch (PDOException $e) {
        error_log('Duplicate NRIC check failed: ' . $e->getMessage());
    }
}

 

// Handle pengistiharan field with default
if (!isset($_POST['pengistiharan'])) {
    $_POST['pengistiharan'] = '1'; // Default to checked
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
        if (empty($_POST['jawatan_dipohon'])) {
            $_POST['jawatan_dipohon'] = $job['job_title'];
        }
    }
} catch (PDOException $e) {
    error_log('Error validating job in application processing: ' . $e->getMessage() . ' - Job ID: ' . $job_id);
    $errors[] = 'Ralat mengesahkan jawatan.';
}

// Check if user has already applied for this job with more intelligent logic
if (!empty($_POST['email']) && !empty($_POST['nombor_ic']) && empty($errors)) {
    try {
        // More relaxed duplicate checking - only prevent true duplicates within 1 hour
        $stmt = $pdo->prepare('
            SELECT id, status, application_date, nama_penuh 
            FROM job_applications 
            WHERE job_id = ? AND email = ? AND nombor_ic = ?
            AND status IN ("PENDING", "APPROVED", "PROCESSING")
            AND application_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY application_date DESC 
            LIMIT 1
        ');
        $stmt->execute([$job_id, strtolower(trim($_POST['email'])), trim($_POST['nombor_ic'])]);
        $existing_application = $stmt->fetch();
        
        if ($existing_application) {
            // Check if it's exactly the same person with same details
            $current_name = strtoupper(trim($_POST['nama_penuh']));
            $existing_name = strtoupper(trim($existing_application['nama_penuh']));
            
            if ($existing_name === $current_name) {
                $time_diff = (time() - strtotime($existing_application['application_date'])) / 60;
                if ($time_diff < 60) {
                    $errors[] = 'Anda telah memohon untuk jawatan ini baru-baru ini. Sila tunggu ' . ceil(60 - $time_diff) . ' minit sebelum memohon semula atau hubungi pihak pentadbiran jika menghadapi masalah.';
                    error_log("Duplicate application within 1 hour - Job ID: $job_id, Email: {$_POST['email']}, IC: {$_POST['nombor_ic']}, Time diff: {$time_diff} minutes");
                }
            } else {
                error_log("Info: Different applicant name with same credentials allowed - Job ID: $job_id, Current: $current_name, Existing: $existing_name");
            }
        }
    } catch (PDOException $e) {
        // Don't fail the application for duplicate check errors, just log
        error_log('Warning: Error checking existing application: ' . $e->getMessage());
    }
}


// Process document uploads
$gambar_passport_path = null;
$salinan_ic_path = null;
$salinan_surat_beranak_path = null;

// Get IC number for folder structure
$ic_number = trim($_POST['nombor_ic'] ?? '');
$job_id = intval($_POST['job_id']);

// Process file uploads - handle both regular uploads and preview page uploads
$gambar_passport_path = null;
$salinan_ic_path = null;
$salinan_surat_beranak_path = null;

// Check for regular file uploads first
if (isset($_FILES['gambar_passport']) && $_FILES['gambar_passport']['error'] !== UPLOAD_ERR_NO_FILE) {
    $gambar_passport_path = handleDocumentUpload($_FILES['gambar_passport'], $job_id, $ic_number, 'gambar_passport');
} elseif (isset($_POST['gambar_passport_content']) && isset($_POST['gambar_passport_name'])) {
    // Handle preview page file upload
    $gambar_passport_path = handlePreviewFileUpload(
        $_POST['gambar_passport_content'],
        $_POST['gambar_passport_name'],
        $_POST['gambar_passport_type'] ?? 'image/jpeg',
        $job_id,
        $ic_number,
        'document'
    );
}

if (isset($_FILES['salinan_ic']) && $_FILES['salinan_ic']['error'] !== UPLOAD_ERR_NO_FILE) {
    $salinan_ic_path = handleDocumentUpload($_FILES['salinan_ic'], $job_id, $ic_number, 'salinan_ic');
} elseif (isset($_POST['salinan_ic_content']) && isset($_POST['salinan_ic_name'])) {
    // Handle preview page file upload
    $salinan_ic_path = handlePreviewFileUpload(
        $_POST['salinan_ic_content'],
        $_POST['salinan_ic_name'],
        $_POST['salinan_ic_type'] ?? 'image/jpeg',
        $job_id,
        $ic_number,
        'document'
    );
}

if (isset($_FILES['salinan_surat_beranak']) && $_FILES['salinan_surat_beranak']['error'] !== UPLOAD_ERR_NO_FILE) {
    $salinan_surat_beranak_path = handleDocumentUpload($_FILES['salinan_surat_beranak'], $job_id, $ic_number, 'salinan_surat_beranak');
} elseif (isset($_POST['salinan_surat_beranak_content']) && isset($_POST['salinan_surat_beranak_name'])) {
    // Handle preview page file upload
    $salinan_surat_beranak_path = handlePreviewFileUpload(
        $_POST['salinan_surat_beranak_content'],
        $_POST['salinan_surat_beranak_name'],
        $_POST['salinan_surat_beranak_type'] ?? 'image/jpeg',
        $job_id,
        $ic_number,
        'document'
    );
}

// Process computer skills data will be handled after application_id is created

// Process form data if no errors
if (empty($errors)) {
    try {
        $pdo->beginTransaction();
        
        // Process education certificate uploads
        
        
        // Process language skills data and insert into application_language_skills table
        if (isset($_POST['kemahiran_bahasa']) && is_array($_POST['kemahiran_bahasa']) && isset($application_id) && $application_id) {
            $stmt = $pdo->prepare("INSERT INTO application_language_skills 
                (application_id, bahasa, tahap_lisan, tahap_penulisan) 
                VALUES (?, ?, ?, ?)");

            foreach ($_POST['kemahiran_bahasa'] as $lang) {
                if (!empty($lang['bahasa'])) {
                    try {
                        $stmt->execute([
                            $application_id,
                            strtoupper(trim($lang['bahasa'])),
                            strtoupper(trim($lang['lisan'] ?? 'ASAS')),
                            strtoupper(trim($lang['penulisan'] ?? 'ASAS'))
                        ]);
                    } catch (PDOException $e) {
                        error_log("Error inserting language skill record: " . $e->getMessage());
                        $errors[] = "Ralat memasukkan maklumat kemahiran bahasa.";
                    }
                }
            }
        
        // Process work experience data and insert into application_work_experience table
        // Process work experience data if available
        $work_experience = isset($_POST['pengalaman_kerja']) && is_array($_POST['pengalaman_kerja']) 
            ? $_POST['pengalaman_kerja'] 
            : [];

        if (!empty($work_experience) && isset($application_id) && $application_id) {
            $stmt = $pdo->prepare("INSERT INTO application_work_experience 
                (application_id, nama_syarikat, jawatan, unit_bahagian, gred, taraf_jawatan, dari_bulan, dari_tahun, hingga_bulan, hingga_tahun, gaji, bidang_tugas, alasan_berhenti) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($_POST['pengalaman_kerja'] as $work) {
                if (!empty($work['syarikat'])) {
                    try {
                        $stmt->execute([
                            $application_id,
                            strtoupper(trim($work['syarikat'] ?? '')),
                            strtoupper(trim($work['jawatan'] ?? '')),
                            strtoupper(trim($work['unit_bahagian'] ?? '')),
                            strtoupper(trim($work['gred'] ?? '')),
                            strtoupper(trim($work['taraf_jawatan'] ?? '')),
                            intval($work['dari_bulan'] ?? 0),
                            $work['dari_tahun'] ?? '',
                            intval($work['hingga_bulan'] ?? 0),
                            $work['hingga_tahun'] ?? '',
                            floatval($work['gaji'] ?? 0),
                            strtoupper(trim($work['bidang_tugas'] ?? '')),
                            strtoupper(trim(($work['alasan'] ?? ($work['alasan_berhenti'] ?? ''))))
                        ]);
                    } catch (PDOException $e) {
                        error_log("Error inserting work experience record: " . $e->getMessage());
                        $errors[] = "Ralat memasukkan maklumat pengalaman kerja.";
                    }
                }
            }
        }
        
        // Process computer skills data and insert into application_computer_skills table
        if (isset($_POST['kemahiran_komputer']) && is_array($_POST['kemahiran_komputer']) && isset($application_id) && $application_id) {
            $stmt = $pdo->prepare("INSERT INTO application_computer_skills
                (application_id, nama_perisian, tahap_kemahiran)
                VALUES (?, ?, ?)");

            foreach ($_POST['kemahiran_komputer'] as $skill) {
                if (!empty($skill['nama_perisian'])) {
                    try {
                        $stmt->execute([
                            $application_id,
                            strtoupper(trim($skill['nama_perisian'])),
                            strtoupper(trim($skill['tahap_kemahiran'] ?? 'ASAS'))
                        ]);
                    } catch (PDOException $e) {
                        error_log("Error inserting computer skill record: " . $e->getMessage());
                        $errors[] = "Ralat memasukkan maklumat kemahiran komputer.";
                    }
                }
            }
        }
        
        // Process SPM subjects data and insert into application_spm_subjects table
        if (isset($_POST['kelulusan_spm']) && is_array($_POST['kelulusan_spm']) && isset($application_id) && $application_id) {
            $stmt = $pdo->prepare("INSERT INTO application_spm_subjects 
                (application_id, mata_pelajaran, gred, tahun) 
                VALUES (?, ?, ?, ?)");

            foreach ($_POST['kelulusan_spm'] as $subject) {
                if (!empty($subject['mata_pelajaran']) && !empty($subject['gred'])) {
                    try {
                        $stmt->execute([
                            $application_id,
                            strtoupper(trim($subject['mata_pelajaran'])),
                            strtoupper(trim($subject['gred'])),
                            intval($subject['tahun'] ?? date('Y'))
                        ]);
                    } catch (PDOException $e) {
                        error_log("Error inserting SPM subject record: " . $e->getMessage());
                        $errors[] = "Ralat memasukkan maklumat mata pelajaran SPM.";
                    }
                }
            }
        }

        // Process references data
        if (isset($_POST['rujukan']) && is_array($_POST['rujukan']) && isset($application_id) && $application_id) {
            $stmt = $pdo->prepare("INSERT INTO application_references 
                (application_id, nama, telefon, tempoh) 
                VALUES (?, ?, ?, ?)");

            foreach ($_POST['rujukan'] as $ref) {
                if (!empty($ref['nama'])) {
                    try {
                        $stmt->execute([
                            $application_id,
                            strtoupper(trim($ref['nama'])),
                            trim($ref['telefon'] ?? ''),
                            intval($ref['tempoh'] ?? 0)
                        ]);
                    } catch (PDOException $e) {
                        error_log("Error inserting reference record: " . $e->getMessage());
                        $errors[] = "Ralat memasukkan maklumat rujukan.";
                    }
                }
            }
        }
        
        // Process OKU data
        $jenis_oku_data = [];
        if (isset($_POST['jenis_oku']) && is_array($_POST['jenis_oku'])) {
            $jenis_oku_data = $_POST['jenis_oku'];
        }
        
        // First, check what fields actually exist in the database
        $stmt = $pdo->query('DESCRIBE job_applications');
        $db_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $existing_fields = array_column($db_columns, 'Field');
        
        error_log("Existing database fields: " . implode(', ', $existing_fields));
        
        // Prepare comprehensive data for insertion mapping all database fields
        $data = [
            'job_id' => $job_id,
            'pengistiharan' => !empty($_POST['pengistiharan']) ? 1 : 0,
            'jawatan_dipohon' => !empty($_POST['jawatan_dipohon']) ? strtoupper(trim($_POST['jawatan_dipohon'])) : null,
            'payment_reference' => !empty($_POST['payment_reference']) ? strtoupper(trim($_POST['payment_reference'])) : null,
            
            // File uploads - store paths in database
            'gambar_passport' => $gambar_passport_path,
            'salinan_ic' => $salinan_ic_path,
            'salinan_surat_beranak' => $salinan_surat_beranak_path,
            
            // Personal Information
            'nama_penuh' => strtoupper(trim($_POST['nama_penuh'])),
            'nombor_surat_beranak' => strtoupper(trim($_POST['nombor_surat_beranak'])),
            'nombor_ic' => trim($_POST['nombor_ic']),
            'agama' => strtoupper($_POST['agama']),
            'taraf_perkahwinan' => strtoupper($_POST['taraf_perkahwinan']),
            'jantina' => strtoupper($_POST['jantina']),
            'tarikh_lahir' => $_POST['tarikh_lahir'],
            'umur' => intval($_POST['umur']),
            'email' => strtolower(trim($_POST['email'])),
            'negeri_kelahiran' => strtoupper($_POST['negeri_kelahiran']),
            'bangsa' => strtoupper($_POST['bangsa']),
            'warganegara' => strtoupper($_POST['warganegara']),
            'tempoh_bermastautin_selangor' => strtoupper(trim($_POST['tempoh_bermastautin_selangor'])),
            'nombor_telefon' => trim($_POST['nombor_telefon']),
            
            // Addresses
            'alamat_tetap' => strtoupper(trim($_POST['alamat_tetap'])),
            'bandar_tetap' => strtoupper(trim($_POST['bandar_tetap'])),
            'negeri_tetap' => strtoupper($_POST['negeri_tetap']),
            'poskod_tetap' => trim($_POST['poskod_tetap']),
            
            'alamat_surat_sama' => !empty($_POST['alamat_surat_sama']) ? 1 : 0,
            'alamat_surat' => !empty($_POST['alamat_surat']) ? strtoupper(trim($_POST['alamat_surat'])) : null,
            'bandar_surat' => !empty($_POST['bandar_surat']) ? strtoupper(trim($_POST['bandar_surat'])) : null,
            'negeri_surat' => !empty($_POST['negeri_surat']) ? strtoupper($_POST['negeri_surat']) : null,
            'poskod_surat' => !empty($_POST['poskod_surat']) ? trim($_POST['poskod_surat']) : null,
            
            // Additional personal fields
            'lesen_memandu' => !empty($_POST['lesen_memandu']) ? json_encode($_POST['lesen_memandu']) : null,
            'tarikh_tamat_lesen' => !empty($_POST['tarikh_tamat_lesen']) ? $_POST['tarikh_tamat_lesen'] : null,
            'ahli_keluarga' => !empty($_POST['ahli_keluarga']) ? json_encode($_POST['ahli_keluarga']) : null,
            
            // Skills and activities
            // Language and computer skills are now stored in separate tables
            'kemahiran_bahasa' => null, // Now stored in application_language_skills table
            'kemahiran_komputer' => null, // Now stored in application_computer_skills table
            // Professional bodies are now stored in a separate table
'maklumat_kegiatan_luar' => isset($_POST['maklumat_kegiatan_luar']) && !empty($_POST['maklumat_kegiatan_luar']) ? json_encode($_POST['maklumat_kegiatan_luar']) : null,
            
            // Health Information
            'darah_tinggi' => strtoupper($_POST['darah_tinggi']),
            'kencing_manis' => strtoupper($_POST['kencing_manis']),
            'penyakit_buah_pinggang' => strtoupper($_POST['penyakit_buah_pinggang']),
            'penyakit_jantung' => strtoupper($_POST['penyakit_jantung']),
            'batuk_kering_tibi' => strtoupper($_POST['batuk_kering_tibi']),
            'kanser' => strtoupper($_POST['kanser']),
            'aids' => strtoupper($_POST['aids']),
            'penagih_dadah' => strtoupper($_POST['penagih_dadah']),
            'penyakit_lain' => strtoupper($_POST['penyakit_lain']),
            'perokok' => strtoupper($_POST['perokok']),
            'berat_kg' => floatval($_POST['berat_kg']),
            'tinggi_cm' => floatval($_POST['tinggi_cm']),
            'pemegang_kad_oku' => strtoupper($_POST['pemegang_kad_oku']),
            'penyakit_lain_nyatakan' => !empty($_POST['penyakit_lain_nyatakan']) ? strtoupper(trim($_POST['penyakit_lain_nyatakan'])) : null,
            'memakai_cermin_mata' => strtoupper($_POST['memakai_cermin_mata']),
            'jenis_rabun' => !empty($_POST['jenis_rabun']) ? strtoupper($_POST['jenis_rabun']) : null,
            'jenis_oku' => !empty($jenis_oku_data) ? json_encode($jenis_oku_data) : null,
            
            // Additional disability fields
            'kecacatan_anggota' => !empty($_POST['kecacatan_anggota']) ? strtoupper($_POST['kecacatan_anggota']) : null,
            'kecacatan_penglihatan' => !empty($_POST['kecacatan_penglihatan']) ? strtoupper($_POST['kecacatan_penglihatan']) : null,
            'kecacatan_pendengaran' => !empty($_POST['kecacatan_pendengaran']) ? strtoupper($_POST['kecacatan_pendengaran']) : null,
            'jenis_kanta' => !empty($_POST['jenis_kanta']) ? strtoupper($_POST['jenis_kanta']) : null,
            
            // Education
            'maklumat_persekolahan' => json_encode($education_data),
            'kelulusan_dimiliki' => json_encode($_POST['kelulusan_dimiliki'] ?? []),
            
            // Specific qualification fields
            'kelulusan_spm' => null, // Now stored in application_spm_subjects table
            'kelulusan_stpm' => !empty($_POST['kelulusan_stpm']) ? json_encode($_POST['kelulusan_stpm']) : null,
            'kelulusan_ipt_1' => !empty($_POST['kelulusan_ipt_1']) ? json_encode($_POST['kelulusan_ipt_1']) : null,
            'kelulusan_ipt_2' => !empty($_POST['kelulusan_ipt_2']) ? json_encode($_POST['kelulusan_ipt_2']) : null,
            
            // Work Experience
            'ada_pengalaman_kerja' => strtoupper($_POST['ada_pengalaman_kerja'] ?? 'TIDAK'),
            'pengalaman_kerja' => json_encode($work_experience_data),
            
            // Declarations
            'pekerja_perkhidmatan_awam' => strtoupper($_POST['pekerja_perkhidmatan_awam']),
            'pekerja_perkhidmatan_awam_nyatakan' => !empty($_POST['pekerja_perkhidmatan_awam_nyatakan']) ? strtoupper(trim($_POST['pekerja_perkhidmatan_awam_nyatakan'])) : null,
            'pertalian_kakitangan' => strtoupper($_POST['pertalian_kakitangan']),
            'pertalian_kakitangan_nyatakan' => !empty($_POST['pertalian_kakitangan_nyatakan']) ? strtoupper(trim($_POST['pertalian_kakitangan_nyatakan'])) : null,
            'pernah_bekerja_mphs' => strtoupper($_POST['pernah_bekerja_mphs']),
            'pernah_bekerja_mphs_nyatakan' => !empty($_POST['pernah_bekerja_mphs_nyatakan']) ? strtoupper(trim($_POST['pernah_bekerja_mphs_nyatakan'])) : null,
            'tindakan_tatatertib' => strtoupper($_POST['tindakan_tatatertib']),
            'tindakan_tatatertib_nyatakan' => !empty($_POST['tindakan_tatatertib_nyatakan']) ? strtoupper(trim($_POST['tindakan_tatatertib_nyatakan'])) : null,
            'kesalahan_undangundang' => strtoupper($_POST['kesalahan_undangundang']),
            'kesalahan_undangundang_nyatakan' => !empty($_POST['kesalahan_undangundang_nyatakan']) ? strtoupper(trim($_POST['kesalahan_undangundang_nyatakan'])) : null,
            'muflis' => strtoupper($_POST['muflis']),
            'muflis_nyatakan' => !empty($_POST['muflis_nyatakan']) ? strtoupper(trim($_POST['muflis_nyatakan'])) : null,
            
            // References
            'rujukan' => json_encode($references_data),
            
            // Status and metadata
            'status' => 'PENDING',
            'application_reference' => null // Will be generated after insertion
        ];
        
        // Filter data to only include fields that exist in the database
        $filtered_data = array_intersect_key($data, array_flip($existing_fields));
        
        error_log("Fields to insert: " . implode(', ', array_keys($filtered_data)));
        error_log("Missing fields: " . implode(', ', array_diff(array_keys($data), $existing_fields)));
        
        // Remove application_reference from initial data for separate handling
        unset($filtered_data['application_reference']);
        
        // Insert application into database
        $sql = "INSERT INTO job_applications (" . implode(',', array_keys($filtered_data)) . ") VALUES (:" . implode(',:', array_keys($filtered_data)) . ")";
        $stmt = $pdo->prepare($sql);
        
        // Log the SQL for debugging
        error_log("SQL Query: " . $sql);
        error_log("Data keys: " . implode(', ', array_keys($filtered_data)));
        
        $stmt->execute($filtered_data);
        
        $application_id = $pdo->lastInsertId();
        
        if (isset($_POST['persekolahan']) && is_array($_POST['persekolahan']) && isset($application_id) && $application_id) {
            require_once 'includes/schema.php';
            create_tables($pdo);

            $stmt = $pdo->prepare("INSERT INTO application_education 
                (application_id, nama_institusi, dari_tahun, hingga_tahun, kelayakan, pangkat_gred_cgpa, sijil_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");

            foreach ($_POST['persekolahan'] as $index => $edu) {
                $sijil_path = null;
                if (isset($_FILES['persekolahan']['name'][$index]['sijil']) && 
                    $_FILES['persekolahan']['error'][$index]['sijil'] !== UPLOAD_ERR_NO_FILE) {
                    $file = [
                        'name' => $_FILES['persekolahan']['name'][$index]['sijil'],
                        'type' => $_FILES['persekolahan']['type'][$index]['sijil'],
                        'tmp_name' => $_FILES['persekolahan']['tmp_name'][$index]['sijil'],
                        'error' => $_FILES['persekolahan']['error'][$index]['sijil'],
                        'size' => $_FILES['persekolahan']['size'][$index]['sijil']
                    ];
                    $upload_result = handleEducationCertificateUpload($file, $_POST['job_id'], $_POST['nombor_ic'], $index);
                    if ($upload_result) {
                        $sijil_path = $upload_result['path'];
                    }
                }

                try {
                    $stmt->execute([
                        $application_id,
                        strtoupper(trim($edu['institusi'] ?? '')),
                        $edu['dari_tahun'] ?? '',
                        $edu['hingga_tahun'] ?? '',
                        strtoupper(trim($edu['kelayakan'] ?? '')),
                        strtoupper(trim($edu['gred'] ?? '')),
                        $sijil_path
                    ]);
                } catch (PDOException $e) {
                    error_log("Error inserting education record: " . $e->getMessage());
                    $errors[] = "Ralat memasukkan maklumat pendidikan.";
                }
            }
        }
        
        // Store professional bodies data in separate table
        if (!empty($_POST['badan_profesional']) && is_array($_POST['badan_profesional']) && isset($application_id) && $application_id) {
            foreach ($_POST['badan_profesional'] as $key => $body) {
                if (!empty($body['nama_lembaga']) && !empty($body['sijil'])) {
                    // Handle file upload for professional body certificate first
                    $certificate_path = null;
                    if (!empty($_FILES['badan_profesional']['name'][$key]['salinan_sijil']) && 
                        $_FILES['badan_profesional']['error'][$key]['salinan_sijil'] === UPLOAD_ERR_OK) {
                        
                        // Initialize FileUploadHandler
                        require_once 'includes/FileUploadHandler.php';
                        $fileHandler = new FileUploadHandler($upload_dir);
                        
                        // Set allowed types for professional body certificates
                        $fileHandler->setAllowedTypes([
                            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'
                        ]);
                        
                        // Set maximum file size (5MB)
                        $fileHandler->setMaxFileSize(5 * 1024 * 1024);
                        
                        // Create file data array
                        $file_data = [
                            'name' => $_FILES['badan_profesional']['name'][$key]['salinan_sijil'],
                            'type' => $_FILES['badan_profesional']['type'][$key]['salinan_sijil'],
                            'tmp_name' => $_FILES['badan_profesional']['tmp_name'][$key]['salinan_sijil'],
                            'error' => $_FILES['badan_profesional']['error'][$key]['salinan_sijil'],
                            'size' => $_FILES['badan_profesional']['size'][$key]['salinan_sijil']
                        ];
                        
                        // Upload the file
                        $upload_result = $fileHandler->uploadFile(
                            $file_data,
                            $application_id,
                            'prof_body_' . $key,
                            $application_id,
                            'salinan_sijil'
                        );
                        
                        if ($upload_result['success']) {
                            $certificate_path = $upload_result['file_path'];
                        } else {
                            error_log("Professional body certificate upload failed: " . $upload_result['error']);
                        }
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO application_professional_bodies 
                        (application_id, nama_lembaga, sijil_diperoleh, no_ahli, tahun, salinan_sijil) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $application_id,
                        $body['nama_lembaga'],
                        $body['sijil'],
                        $body['no_ahli'] ?? '',
                        !empty($body['tarikh_sijil']) ? date('Y', strtotime($body['tarikh_sijil'])) : null,
                        $certificate_path
                    ]);
                }
            }
        }
        
        // Store extracurricular activities data in separate table
        if (!empty($_POST['kegiatan_luar']) && is_array($_POST['kegiatan_luar']) && isset($application_id) && $application_id) {
            foreach ($_POST['kegiatan_luar'] as $key => $activity) {
                if (!empty($activity['sukan_persatuan_kelab']) && !empty($activity['jawatan']) && 
                    !empty($activity['peringkat']) && !empty($activity['tahun'])) {
                    
                    $certificate_path = null;
                    
                    // Handle certificate file upload
                    if (isset($_FILES['kegiatan_luar']['name'][$key]['salinan_sijil']) && 
                        $_FILES['kegiatan_luar']['error'][$key]['salinan_sijil'] === UPLOAD_ERR_OK) {
                        
                        // Initialize FileUploadHandler
                        require_once 'includes/FileUploadHandler.php';
                        $fileHandler = new FileUploadHandler($upload_dir);
                        
                        // Set allowed types for extracurricular certificates
                        $fileHandler->setAllowedTypes([
                            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'
                        ]);
                        
                        // Set maximum file size (5MB)
                        $fileHandler->setMaxFileSize(5 * 1024 * 1024);
                        
                        // Create file data array
                        $file_data = [
                            'name' => $_FILES['kegiatan_luar']['name'][$key]['salinan_sijil'],
                            'type' => $_FILES['kegiatan_luar']['type'][$key]['salinan_sijil'],
                            'tmp_name' => $_FILES['kegiatan_luar']['tmp_name'][$key]['salinan_sijil'],
                            'error' => $_FILES['kegiatan_luar']['error'][$key]['salinan_sijil'],
                            'size' => $_FILES['kegiatan_luar']['size'][$key]['salinan_sijil']
                        ];
                        
                        // Upload the file
                        $upload_result = $fileHandler->uploadFile(
                            $file_data,
                            $application_id,
                            'extracurricular_' . $key,
                            $application_id,
                            'salinan_sijil'
                        );
                        
                        if ($upload_result['success']) {
                            $certificate_path = $upload_result['file_path'];
                        } else {
                            error_log("Extracurricular certificate upload failed: " . $upload_result['error']);
                            continue;
                        }
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO application_extracurricular 
                        (application_id, sukan_persatuan_kelab, jawatan, peringkat, tahun, salinan_sijil) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $application_id,
                        strtoupper(trim($activity['sukan_persatuan_kelab'])),
                        strtoupper(trim($activity['jawatan'])),
                        $activity['peringkat'],
                        intval($activity['tahun']),
                        $certificate_path
                    ]);
                }
            }
        }
        
        // Store SPM results data in separate table
        if (!empty($_POST['spm_results']) && is_array($_POST['spm_results'])) {
            foreach ($_POST['spm_results'] as $key => $spm_result) {
                if (!empty($spm_result['tahun']) && !empty($spm_result['angka_giliran'])) {
                    
                    $certificate_path = null;
                    
                    // Handle certificate file upload
                    if (isset($_FILES['spm_results']['name'][$key]['salinan_sijil']) && 
                        $_FILES['spm_results']['error'][$key]['salinan_sijil'] === UPLOAD_ERR_OK) {
                        
                        $file_tmp = $_FILES['spm_results']['tmp_name'][$key]['salinan_sijil'];
                        $file_name = $_FILES['spm_results']['name'][$key]['salinan_sijil'];
                        $file_size = $_FILES['spm_results']['size'][$key]['salinan_sijil'];
                        $file_type = $_FILES['spm_results']['type'][$key]['salinan_sijil'];
                        
                        // Validate file size (2MB max)
                        if ($file_size > 2 * 1024 * 1024) {
                            error_log("SPM certificate file too large: {$file_size} bytes");
                            continue;
                        }
                        
                        // Validate file type
                        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
                        if (!in_array($file_type, $allowed_types)) {
                            error_log("Invalid SPM certificate file type: {$file_type}");
                            continue;
                        }
                        
                        // Generate unique filename
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $new_filename = "spm_{$application_id}_{$key}.{$file_ext}";
                        $upload_path = $upload_dir . $application_id . '/';
                        
                        // Create directory if it doesn't exist
                        if (!is_dir($upload_path)) {
                            mkdir($upload_path, 0755, true);
                        }
                        
                        // Move uploaded file
                        if (move_uploaded_file($file_tmp, $upload_path . $new_filename)) {
                            $certificate_path = $upload_path . $new_filename;
                        }
                    }
                    
                    // Insert core subjects (Bahasa Malaysia, English, Mathematics, History)
                    $core_subjects = [
                        'Bahasa Malaysia' => $spm_result['bahasa_malaysia'] ?? '',
                        'Bahasa Inggeris' => $spm_result['bahasa_inggeris'] ?? '',
                        'Matematik' => $spm_result['matematik'] ?? '',
                        'Sejarah' => $spm_result['sejarah'] ?? ''
                    ];
                    
                    foreach ($core_subjects as $subject => $grade) {
                        if (!empty($grade)) {
                            $stmt = $pdo->prepare("INSERT INTO application_spm_results 
                                (application_id, tahun, angka_giliran, subjek, gred, salinan_sijil) 
                                VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $application_id,
                                intval($spm_result['tahun']),
                                strtoupper(trim($spm_result['angka_giliran'])),
                                $subject,
                                strtoupper(trim($grade)),
                                $certificate_path
                            ]);
                        }
                    }
                    
                    // Insert other subject if provided
                    if (!empty($spm_result['subjek_lain']) && !empty($spm_result['gred_subjek_lain'])) {
                        $stmt = $pdo->prepare("INSERT INTO application_spm_results 
                            (application_id, tahun, angka_giliran, subjek, gred, salinan_sijil) 
                            VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $application_id,
                            intval($spm_result['tahun']),
                            strtoupper(trim($spm_result['angka_giliran'])),
                            strtoupper(trim($spm_result['subjek_lain'])),
                            strtoupper(trim($spm_result['gred_subjek_lain'])),
                            $certificate_path
                        ]);
                    }
                    
                    // Insert overall grade if provided
                    if (!empty($spm_result['gred_keseluruhan'])) {
                        $stmt = $pdo->prepare("INSERT INTO application_spm_results 
                            (application_id, tahun, angka_giliran, subjek, gred, salinan_sijil) 
                            VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $application_id,
                            intval($spm_result['tahun']),
                            strtoupper(trim($spm_result['angka_giliran'])),
                            'GRED KESELURUHAN',
                            strtoupper(trim($spm_result['gred_keseluruhan'])),
                            $certificate_path
                        ]);
                    }
                }
            }
        }
        
        // Process Additional Subjects (Dynamic Matapelajaran Lain)
        if (isset($_POST['additional_subjects']) && is_array($_POST['additional_subjects'])) {
            require_once 'includes/FileUploadHandler.php';
            $fileHandler = new FileUploadHandler();
            $fileHandler->setAllowedTypes(['jpg', 'jpeg', 'gif', 'png', 'pdf']);
            $fileHandler->setMaxFileSize(2 * 1024 * 1024); // 2MB
            
            foreach ($_POST['additional_subjects'] as $index => $additional_subject) {
                // Validate required fields
                if (empty($additional_subject['tahun']) || 
                    empty($additional_subject['angka_giliran']) || 
                    empty($additional_subject['subjek']) || 
                    empty($additional_subject['gred'])) {
                    continue; // Skip incomplete entries
                }
                
                $certificate_path = null;
                
                // Handle certificate file upload
                if (isset($_FILES['additional_subjects']['name'][$index]['salinan_sijil']) && 
                    !empty($_FILES['additional_subjects']['name'][$index]['salinan_sijil'])) {
                    
                    // Prepare file data for upload
                    $file_data = [
                        'name' => $_FILES['additional_subjects']['name'][$index]['salinan_sijil'],
                        'type' => $_FILES['additional_subjects']['type'][$index]['salinan_sijil'],
                        'tmp_name' => $_FILES['additional_subjects']['tmp_name'][$index]['salinan_sijil'],
                        'error' => $_FILES['additional_subjects']['error'][$index]['salinan_sijil'],
                        'size' => $_FILES['additional_subjects']['size'][$index]['salinan_sijil']
                    ];
                    
                    try {
                        $upload_result = $fileHandler->uploadFile(
                            $file_data,
                            'certificates',
                            'additional_subject_' . $index,
                            null,
                            'salinan_sijil'
                        );
                        if ($upload_result['success']) {
                            $certificate_path = $upload_result['file_path'];
                        } else {
                            error_log("Additional subject certificate upload failed: " . $upload_result['error']);
                            continue; // Skip this entry if file upload fails
                        }
                    } catch (Exception $e) {
                        error_log("Additional subject certificate upload exception: " . $e->getMessage());
                        continue; // Skip this entry if file upload fails
                    }
                }
                
                // Insert additional subject into database
                try {
                    $stmt = $pdo->prepare("INSERT INTO application_spm_additional_subjects 
                        (application_id, tahun, angka_giliran, subjek, gred, salinan_sijil) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $application_id,
                        intval($additional_subject['tahun']),
                        strtoupper(trim($additional_subject['angka_giliran'])),
                        strtoupper(trim($additional_subject['subjek'])),
                        strtoupper(trim($additional_subject['gred'])),
                        $certificate_path
                    ]);
                    
                    log_frontend_info('Additional subject processed', [
                        'application_id' => $application_id,
                        'tahun' => $additional_subject['tahun'],
                        'subjek' => $additional_subject['subjek'],
                        'gred' => $additional_subject['gred'],
                        'certificate_uploaded' => !empty($certificate_path)
                    ]);
                } catch (Exception $e) {
                    error_log("Failed to insert additional subject: " . $e->getMessage());
                }
            }
        }
        
        // Generate application reference after insertion
        $year = date('Y');
        $job_part = str_pad($job_id, 4, '0', STR_PAD_LEFT);
        $unique_data = time() . uniqid();
        if (!empty($_POST['email'])) $unique_data .= $_POST['email'];
        if (!empty($_POST['nombor_ic'])) $unique_data .= $_POST['nombor_ic'];
        $unique_part = strtoupper(substr(md5($unique_data), 0, 8));
        $application_reference = "APP-{$year}-{$job_part}-{$unique_part}";
        
        // Update the application with the reference if the field exists
        if (in_array('application_reference', $existing_fields)) {
            $update_stmt = $pdo->prepare("UPDATE job_applications SET application_reference = ? WHERE id = ?");
            $update_stmt->execute([$application_reference, $application_id]);
        }
        
        // Log successful application submission
        error_log("Job application submitted successfully - ID: $application_id, Job: $job_id, Email: {$filtered_data['email']}");
        
        // Log the application submission
        log_frontend_info('Job application submitted', [
            'application_id' => $application_id,
            'job_id' => $job_id,
            'email' => $filtered_data['email'],
            'nama_penuh' => $filtered_data['nama_penuh'],
            'application_reference' => $application_reference
        ]);
        
        $pdo->commit();
        
        // Send notifications asynchronously after successful database insertion
        // This prevents the page from hanging during notification sending
        try {
            // Store notification data for background processing
            $notification_data = [
                'application_id' => $application_id,
                'email' => $filtered_data['email'],
                'phone' => $filtered_data['nombor_telefon'],
                'nama_penuh' => $filtered_data['nama_penuh'],
                'application_reference' => $application_reference,
                'job_title' => $job['job_title'] ?? '',
                'kod_gred' => $job['kod_gred'] ?? ''
            ];
            
            // Store in session for background processing
            $_SESSION['pending_notifications'][] = $notification_data;
            
            // Log that notification will be sent in background
            log_frontend_info('Notification queued for background processing', [
                'application_id' => $application_id,
                'email' => $filtered_data['email'],
                'phone' => $filtered_data['nombor_telefon']
            ]);
            
        } catch (Exception $e) {
            log_frontend_error('Error queuing notification', [
                'application_id' => $application_id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the application submission if notification queuing fails
        }
        
        // Store application reference and data in session for retrieval if needed
        $_SESSION['last_application_reference'] = $application_reference;
        $_SESSION['last_application_id'] = $application_id;
        $_SESSION['last_application_email'] = $filtered_data['email'] ?? null;
        $_SESSION['last_application_name'] = $filtered_data['nama_penuh'] ?? null;
        $_SESSION['last_application_job_id'] = $job_id;
        $_SESSION['last_application_timestamp'] = date('Y-m-d H:i:s');
        
        // Log successful redirection
        error_log("Redirecting to thank you page with reference: {$application_reference}");
        
        // Check if this is a final submission from preview page
        $is_final_submission = isset($_POST['final_submission']) && $_POST['final_submission'] === 'true';
        $redirect_to_thank_you = isset($_POST['redirect_to_thank_you']) && $_POST['redirect_to_thank_you'] === 'true';
        
        error_log("Final submission: " . ($is_final_submission ? 'Yes' : 'No'));
        error_log("Redirect flag: " . ($redirect_to_thank_you ? 'Yes' : 'No'));
        
        // Store application reference in session for thank you page
        $_SESSION['application_reference'] = $application_reference;
        
        // Decide where to redirect after database insertion
        $redirect_to_preview = isset($_POST['redirect_to_preview']) && $_POST['redirect_to_preview'] === 'true';

        // Clean up any output buffer before redirect
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Force session write before redirect
        session_write_close();
        
        $target = $redirect_to_preview
            ? '/preview-application.php?app_id=' . urlencode($application_id) . '&ref=' . urlencode($application_reference)
            : '/application-thank-you.php?ref=' . urlencode($application_reference);

        if (!headers_sent()) {
            header('Location: ' . $target);
            echo "<html><head><meta http-equiv=\"refresh\" content=\"0;url=" . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . "\"></head><body>";
            echo "<p>Redirecting...</p>";
            echo "<script>window.location.replace('" . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . "');</script>";
            echo "</body></html>";
            exit();
        } else {
            echo "<html><head><meta http-equiv=\"refresh\" content=\"0;url=" . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . "\"></head><body>";
            echo "<p>Redirecting...</p>";
            echo "<script>window.location.replace('" . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . "');</script>";
            echo "</body></html>";
            exit();
        }
        
    }
} catch (PDOException $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Detailed error logging
        $error_message = 'Error inserting job application: ' . $e->getMessage();
        $error_details = [
            'exception' => $e->getMessage(),
            'job_id' => $job_id,
            'sql_state' => $e->getCode(),
            'trace' => $e->getTraceAsString()
        ];
        
        error_log($error_message);
        error_log('Error details: ' . print_r($error_details, true));
        
        // Log the error
        log_frontend_error('Database error in application processing', $error_details);
        
        $errors[] = 'Ralat menyimpan permohonan: ' . $e->getMessage();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        
        log_frontend_error('General error in application processing', [
            'exception' => $e->getMessage()
        ]);
        $errors[] = 'Ralat umum: ' . $e->getMessage();
    }
}

// If there are errors, redirect back with errors
if (!empty($errors)) {
    $_SESSION['application_errors'] = $errors;
    $_SESSION['application_data'] = $_POST;
    $_SESSION['application_error_time'] = date('Y-m-d H:i:s');
    
    // Log the errors
    log_frontend_error('Application submission failed', [
        'errors' => $errors,
        'job_id' => $job_id,
        'email' => $_POST['email'] ?? 'unknown',
        'submission_token' => $_POST['submission_token'] ?? 'unknown',
        'submission_timestamp' => $_POST['submission_timestamp'] ?? 'unknown'
    ]);
    
    // Store error information for debugging
    error_log('Application submission failed with errors: ' . print_r($errors, true));
    error_log('Redirecting back to job application form with job_id: ' . $job_id);
    
    // Check if this is a final submission from preview page
    $is_final_submission = isset($_POST['final_submission']) && $_POST['final_submission'] === 'true';
    
    // Clean up any output buffer before redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Force session write before redirect
    session_write_close();
    
    // If headers already sent, use JavaScript redirect
    if (headers_sent()) {
        echo '<script>console.log("Error occurred, redirecting back to full application form");</script>';
        echo '<script>window.location.href = "/job-application-full.php?job_id=' . urlencode($job_id) . '&error=1";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=/job-application-full.php?job_id=' . urlencode($job_id) . '&error=1"></noscript>';
        echo '<p>Ralat berlaku. Kembali ke <a href="/job-application-full.php?job_id=' . urlencode($job_id) . '&error=1">borang permohonan penuh</a>...</p>';
        exit();
    } else {
        header('Location: /job-application-full.php?job_id=' . urlencode($job_id) . '&error=1');
        exit();
    }
}

// Clean up output buffer if we reach here
if (ob_get_level()) {
    ob_end_clean();
}
?>