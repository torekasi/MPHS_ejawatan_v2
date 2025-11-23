<?php
/**
 * @FileID: job_application_full_001
 * @Module: JobApplicationFullForm
 * @Author: Nefi
 * @LastModified: 2025-11-09T00:00:00Z
 * @SecurityTag: validated
 */

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com https://static.cloudflareinsights.com; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://www.google.com https://www.gstatic.com; frame-src 'self' https://www.google.com;");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// Prevent direct include of section files
define('APP_SECURE', true);

session_start();
ob_start();

// Initialize/reset session-based form timeout window (client + server enforcement)
$_SESSION['form_start_time'] = time();
// Default timeout: 30 minutes (1800s). Can be surfaced via env later without editing config
if (!isset($_SESSION['form_timeout_seconds']) || (int)$_SESSION['form_timeout_seconds'] <= 0) {
    $_SESSION['form_timeout_seconds'] = 1800;
}

require_once 'includes/ErrorHandler.php';
// Optional: used later for uploads in consolidated form
@require_once 'modules/FileUploaderImplementation.php';
@require_once 'modules/ApplicationDataLoader.php';

// Load config safely (do not modify config.php per rules)
$result = require 'config.php';
$config = $result['config'] ?? $result;

$recaptcha_v3_site_key = $config['recaptcha_v3_site_key'] ?? (getenv('RECAPTCHA_V3_SITE_KEY') ?: '');
$recaptcha_v3_action = $config['recaptcha_v3_action'] ?? 'job_application';


$pdo = null;
$error = '';
$job = null;
$application = null;

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Helpers
function db_connect_from_config(array $config) {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    return new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
}

// Input params
$job_code = $_GET['job_code'] ?? null;
$job_id = isset($_GET['job_id']) && is_numeric($_GET['job_id']) ? (int)$_GET['job_id'] : (isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null);
$application_id = isset($_GET['app_id']) && is_numeric($_GET['app_id']) ? (int)$_GET['app_id'] : null;
$application_ref = $_GET['ref'] ?? null;
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === '1';
// Optional edit token (security)
$edit_token = isset($_GET['edit_token']) ? trim($_GET['edit_token']) : null;

try {
    $pdo = db_connect_from_config($config);
    if ($edit_mode === true && !$error) {
        $tokenCandidate = $edit_token ?: ($_SESSION['edit_token'] ?? '');
        if (empty($tokenCandidate)) {
            $_SESSION['error'] = 'Akses edit memerlukan token pengesahan yang sah.';
            header('Location: semak-status.php');
            exit;
        }
        $tokenAppId = $application_id;
        if (!$tokenAppId && !empty($application_ref)) {
            $stmt = $pdo->prepare('SELECT id FROM application_application_main WHERE application_reference = ? LIMIT 1');
            $stmt->execute([$application_ref]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $stmt = $pdo->prepare('SELECT id FROM job_applications WHERE application_reference = ? LIMIT 1');
                $stmt->execute([$application_ref]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            $tokenAppId = $row ? (int)$row['id'] : null;
        }
        if (!$tokenAppId) {
            $_SESSION['error'] = 'Rujukan permohonan tidak sah untuk mod edit.';
            header('Location: semak-status.php');
            exit;
        }
        $stmt = $pdo->prepare('SELECT id, application_id, created_at FROM user_sessions WHERE id = ? AND application_id = ? LIMIT 1');
        $stmt->execute([$tokenCandidate, $tokenAppId]);
        $sessionRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sessionRow) {
            $_SESSION['error'] = 'Token edit tidak sah atau tidak sepadan.';
            header('Location: semak-status.php');
            exit;
        }
        $createdTs = strtotime($sessionRow['created_at'] ?? '');
        if (!$createdTs || (time() - $createdTs) > (12 * 3600)) {
            $_SESSION['error'] = 'Token telah tamat tempoh. Sila sahkan semula.';
            header('Location: semak-status.php');
            exit;
        }
        $_SESSION['edit_application_verified'] = true;
        $_SESSION['verified_application_id'] = $tokenAppId;
        $_SESSION['edit_token'] = $tokenCandidate;
    }

    // If application reference/id provided, load application first (supports edit flow)
    if ($application_id || $application_ref) {
        if ($application_id) {
            $stmt = $pdo->prepare('SELECT * FROM application_application_main WHERE id = ? LIMIT 1');
            $stmt->execute([$application_id]);
            $application = $stmt->fetch();
            if (!$application) {
                $stmt = $pdo->prepare('SELECT * FROM job_applications WHERE id = ? LIMIT 1');
                $stmt->execute([$application_id]);
                $application = $stmt->fetch();
            }
        } else {
            $stmt = $pdo->prepare('SELECT * FROM application_application_main WHERE application_reference = ? LIMIT 1');
            $stmt->execute([$application_ref]);
            $application = $stmt->fetch();
            if (!$application) {
                $stmt = $pdo->prepare('SELECT * FROM job_applications WHERE application_reference = ? LIMIT 1');
                $stmt->execute([$application_ref]);
                $application = $stmt->fetch();
            }
        }
        if (!$application) {
            $error = 'Permohonan tidak dijumpai. Sila mulakan semula.';
        } else {
            $job_id = (int)$application['job_id'];
            // Map upload path keys to legacy keys expected by sections for prefill
            $upload_key_map = [
                'gambar_passport_path' => 'gambar_passport',
                'salinan_ic_path' => 'salinan_ic',
                'salinan_surat_beranak_path' => 'salinan_surat_beranak',
                'spm_salinan_sijil_path' => 'spm_salinan_sijil',
            ];
            foreach ($upload_key_map as $src => $dst) {
                if (!empty($application[$src]) && empty($application[$dst])) {
                    $application[$dst] = $application[$src];
                }
            }
        }
    }

    // Load job by id or code
    if (!$error) {
        if ($job_id) {
            $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
            $stmt->execute([$job_id]);
            $job = $stmt->fetch();
        } elseif (!empty($job_code)) {
            $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE job_code = ? LIMIT 1');
            $stmt->execute([$job_code]);
            $job = $stmt->fetch();
            if ($job) { $job_id = (int)$job['id']; }
        }
        if (!$job) {
            $error = 'Kod jawatan tidak sah atau jawatan tidak dijumpai.';
        }
    }

    // Prefill arrays used in sections (from existing structures)
    $prefill_languages = [];
    $prefill_computers = [];
    $prefill_work_experience = [];
    $prefill_professional_bodies = [];
    $prefill_extracurriculars = [];
    $prefill_references = [];
    $prefill_education = [];
    $prefill_spm_results = [];
    $prefill_spm_additional = [];
    $prefill_family_members = [];
    $malaysian_states = [
        'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan',
        'Pahang', 'Perak', 'Perlis', 'Pulau Pinang', 'Sabah',
        'Sarawak', 'Selangor', 'Terengganu', 'Wilayah Persekutuan Kuala Lumpur',
        'Wilayah Persekutuan Labuan', 'Wilayah Persekutuan Putrajaya'
    ];

    if ($pdo && ($application_ref ?? $application['application_reference'] ?? null)) {
        $ref = $application_ref ?: $application['application_reference'];
        
        // Use the new data loader for comprehensive data loading
        if (class_exists('ApplicationDataLoader')) {
            try {
                $dataLoader = new ApplicationDataLoader($pdo, $ref, $application_id);
                $dataLoader->logDataCounts();
                $formattedData = $dataLoader->formatForPrefill();
                
                // Assign formatted data to prefill arrays
                $prefill_languages = $formattedData['languages'];
                $prefill_computers = $formattedData['computers'];
                $prefill_education = $formattedData['education'];
                $prefill_spm_results = $formattedData['spm_results'];
                $prefill_spm_additional = $formattedData['spm_additional'];
                $prefill_work_experience = $formattedData['work_experience'];
                $prefill_professional_bodies = $formattedData['professional_bodies'];
                $prefill_extracurriculars = $formattedData['extracurriculars'];
                $prefill_references = $formattedData['references'];
                $prefill_family_members = $formattedData['family_members'];
                
            } catch (Exception $e) {
                error_log('ApplicationDataLoader error: ' . $e->getMessage());
            }
        }
        
        try {
            if (empty($prefill_languages)) {
                $stmt = $pdo->prepare("SELECT * FROM application_language_skills WHERE application_reference = ? ORDER BY id ASC");
                $stmt->execute([$ref]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prefill_languages[] = [
                        'bahasa' => $row['bahasa'] ?? '',
                        'pertuturan' => $row['tahap_lisan'] ?? ($row['pertuturan'] ?? ''),
                        'penulisan' => $row['tahap_penulisan'] ?? ($row['penulisan'] ?? ''),
                    ];
                }
                if (empty($prefill_languages) && !empty($application_id)) {
                    $stmt = $pdo->prepare("SELECT bahasa, tahap_lisan, tahap_penulisan, pertuturan, penulisan FROM application_language_skills WHERE application_id = ? ORDER BY id ASC");
                    $stmt->execute([$application_id]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $prefill_languages[] = [
                            'bahasa' => $row['bahasa'] ?? '',
                            'pertuturan' => $row['tahap_lisan'] ?? ($row['pertuturan'] ?? ''),
                            'penulisan' => $row['tahap_penulisan'] ?? ($row['penulisan'] ?? ''),
                        ];
                    }
                }
            }
            if (empty($prefill_computers)) {
                $stmt = $pdo->prepare("SELECT * FROM application_computer_skills WHERE application_reference = ? ORDER BY id ASC");
                $stmt->execute([$ref]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prefill_computers[] = [
                        'nama_perisian' => $row['nama_perisian'] ?? ($row['nama_kemahiran'] ?? ''),
                        'tahap_kemahiran' => $row['tahap_kemahiran'] ?? ($row['tahap'] ?? ''),
                    ];
                }
                if (empty($prefill_computers) && !empty($application_id)) {
                    $stmt = $pdo->prepare("SELECT nama_perisian, tahap_kemahiran, nama_kemahiran, tahap FROM application_computer_skills WHERE application_id = ? ORDER BY id ASC");
                    $stmt->execute([$application_id]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $prefill_computers[] = [
                            'nama_perisian' => $row['nama_perisian'] ?? ($row['nama_kemahiran'] ?? ''),
                            'tahap_kemahiran' => $row['tahap_kemahiran'] ?? ($row['tahap'] ?? ''),
                        ];
                    }
                }
            }
            if (empty($prefill_work_experience)) {
                $stmt = $pdo->prepare("SELECT * FROM application_work_experience WHERE application_reference = ? ORDER BY id ASC");
                $stmt->execute([$ref]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prefill_work_experience[] = [
                        'nama_syarikat' => $row['nama_syarikat'] ?? '',
                        'jawatan' => $row['jawatan'] ?? '',
                        'mula_berkhidmat' => $row['mula_berkhidmat'] ? date('Y-m', strtotime($row['mula_berkhidmat'])) : '',
                        'tamat_berkhidmat' => $row['tamat_berkhidmat'] ? date('Y-m', strtotime($row['tamat_berkhidmat'])) : '',
                        'unit_bahagian' => $row['unit_bahagian'] ?? '',
                        'gred' => $row['gred'] ?? '',
                        'gaji' => $row['gaji'] ?? '',
                        'taraf_jawatan' => $row['taraf_jawatan'] ?? '',
                        'bidang_tugas' => $row['bidang_tugas'] ?? '',
                        'alasan_berhenti' => $row['alasan_berhenti'] ?? '',
                    ];
                }
                if (empty($prefill_work_experience) && !empty($application_id)) {
                    $stmt = $pdo->prepare("SELECT * FROM application_work_experience WHERE application_id = ? ORDER BY id ASC");
                    $stmt->execute([$application_id]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $prefill_work_experience[] = [
                            'nama_syarikat' => $row['nama_syarikat'] ?? '',
                            'jawatan' => $row['jawatan'] ?? '',
                            'mula_berkhidmat' => $row['mula_berkhidmat'] ? date('Y-m', strtotime($row['mula_berkhidmat'])) : '',
                            'tamat_berkhidmat' => $row['tamat_berkhidmat'] ? date('Y-m', strtotime($row['tamat_berkhidmat'])) : '',
                            'unit_bahagian' => $row['unit_bahagian'] ?? '',
                            'gred' => $row['gred'] ?? '',
                            'gaji' => $row['gaji'] ?? '',
                            'taraf_jawatan' => $row['taraf_jawatan'] ?? '',
                            'bidang_tugas' => $row['bidang_tugas'] ?? '',
                            'alasan_berhenti' => $row['alasan_berhenti'] ?? '',
                        ];
                    }
                }
            }
            if (empty($prefill_professional_bodies)) {
                $stmt = $pdo->prepare("SELECT * FROM application_professional_bodies WHERE application_reference = ? ORDER BY id ASC");
                $stmt->execute([$ref]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $tarikh = $row['tarikh_sijil'] ?? null;
                    if (empty($tarikh)) {
                        $tahun = $row['tahun'] ?? '';
                        if ($tahun !== '') {
                            $tarikh = preg_match('/^\d{4}$/', (string)$tahun) ? ($tahun . '-01-01') : $tahun;
                        }
                    }
                    $salinan = $row['salinan_sijil_filename'] ?? ($row['salinan_sijil_path'] ?? ($row['salinan_sijil'] ?? ''));
                    $prefill_professional_bodies[] = [
                        'nama_lembaga' => $row['nama_lembaga'] ?? '',
                        'no_ahli' => $row['no_ahli'] ?? '',
                        'sijil' => $row['sijil_diperoleh'] ?? ($row['sijil'] ?? ''),
                        'tarikh_sijil' => $tarikh ?? '',
                        'salinan_sijil' => $salinan,
                    ];
                }
                if (empty($prefill_professional_bodies) && !empty($application_id)) {
                    $stmt = $pdo->prepare("SELECT * FROM application_professional_bodies WHERE application_id = ? ORDER BY id ASC");
                    $stmt->execute([$application_id]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $tarikh = $row['tarikh_sijil'] ?? null;
                        if (empty($tarikh)) {
                            $tahun = $row['tahun'] ?? '';
                            if ($tahun !== '') {
                                $tarikh = preg_match('/^\d{4}$/', (string)$tahun) ? ($tahun . '-01-01') : $tahun;
                            }
                        }
                        $salinan = $row['salinan_sijil_filename'] ?? ($row['salinan_sijil_path'] ?? ($row['salinan_sijil'] ?? ''));
                        $prefill_professional_bodies[] = [
                            'nama_lembaga' => $row['nama_lembaga'] ?? '',
                            'no_ahli' => $row['no_ahli'] ?? '',
                            'sijil' => $row['sijil_diperoleh'] ?? ($row['sijil'] ?? ''),
                            'tarikh_sijil' => $tarikh ?? '',
                            'salinan_sijil' => $salinan,
                        ];
                    }
                }
            }
            if (empty($prefill_education)) {
                $stmt = $pdo->prepare("SELECT * FROM application_education WHERE application_reference = ? ORDER BY id ASC");
                $stmt->execute([$ref]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prefill_education[] = [
                        'institusi' => $row['nama_institusi'] ?? '',
                        'dari_tahun' => $row['dari_tahun'] ?? '',
                        'hingga_tahun' => $row['hingga_tahun'] ?? '',
                        'kelayakan' => $row['kelayakan'] ?? '',
                        'gred' => $row['pangkat_gred_cgpa'] ?? '',
                        'sijil' => $row['sijil_path'] ?? '',
                    ];
                }
                if (empty($prefill_education) && !empty($application_id)) {
                    $stmt = $pdo->prepare("SELECT * FROM application_education WHERE application_id = ? ORDER BY id ASC");
                    $stmt->execute([$application_id]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $prefill_education[] = [
                            'institusi' => $row['nama_institusi'] ?? '',
                            'dari_tahun' => $row['dari_tahun'] ?? '',
                            'hingga_tahun' => $row['hingga_tahun'] ?? '',
                            'kelayakan' => $row['kelayakan'] ?? '',
                            'gred' => $row['pangkat_gred_cgpa'] ?? '',
                            'sijil' => $row['sijil_path'] ?? '',
                        ];
                    }
                }
            }
            
            if (empty($prefill_spm_results)) {
                $stmt = $pdo->prepare("SELECT * FROM application_spm_results WHERE application_reference = ? ORDER BY id ASC");
                $stmt->execute([$ref]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prefill_spm_results[] = [
                        'tahun' => $row['tahun'] ?? '',
                        'gred_keseluruhan' => $row['gred_keseluruhan'] ?? '',
                        'angka_giliran' => $row['angka_giliran'] ?? '',
                        'bahasa_malaysia' => $row['bahasa_malaysia'] ?? '',
                        'bahasa_inggeris' => $row['bahasa_inggeris'] ?? '',
                        'matematik' => $row['matematik'] ?? '',
                        'sejarah' => $row['sejarah'] ?? '',
                        'subjek_lain' => $row['subjek_lain'] ?? '',
                        'gred_subjek_lain' => $row['gred_subjek_lain'] ?? '',
                        'salinan_sijil' => $row['salinan_sijil_filename'] ?? '',
                    ];
                }
                if (empty($prefill_spm_results) && !empty($application_id)) {
                    $stmt = $pdo->prepare("SELECT * FROM application_spm_results WHERE application_id = ? ORDER BY id ASC");
                    $stmt->execute([$application_id]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $prefill_spm_results[] = [
                            'tahun' => $row['tahun'] ?? '',
                            'gred_keseluruhan' => $row['gred_keseluruhan'] ?? '',
                            'angka_giliran' => $row['angka_giliran'] ?? '',
                            'bahasa_malaysia' => $row['bahasa_malaysia'] ?? '',
                            'bahasa_inggeris' => $row['bahasa_inggeris'] ?? '',
                            'matematik' => $row['matematik'] ?? '',
                            'sejarah' => $row['sejarah'] ?? '',
                            'subjek_lain' => $row['subjek_lain'] ?? '',
                            'gred_subjek_lain' => $row['gred_subjek_lain'] ?? '',
                            'salinan_sijil' => $row['salinan_sijil_filename'] ?? '',
                        ];
                    }
                }
            }
            
            if (empty($prefill_spm_additional)) {
                $stmt = $pdo->prepare("SELECT * FROM application_spm_additional_subjects WHERE application_reference = ? ORDER BY id ASC");
                $stmt->execute([$ref]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prefill_spm_additional[] = [
                        'tahun' => $row['tahun'] ?? '',
                        'angka_giliran' => $row['angka_giliran'] ?? '',
                        'subjek' => $row['subjek'] ?? '',
                        'gred' => $row['gred'] ?? '',
                        'salinan_sijil' => $row['salinan_sijil'] ?? '',
                    ];
                }
                if (empty($prefill_spm_additional) && !empty($application_id)) {
                    $stmt = $pdo->prepare("SELECT * FROM application_spm_additional_subjects WHERE application_id = ? ORDER BY id ASC");
                    $stmt->execute([$application_id]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $prefill_spm_additional[] = [
                            'tahun' => $row['tahun'] ?? '',
                            'angka_giliran' => $row['angka_giliran'] ?? '',
                            'subjek' => $row['subjek'] ?? '',
                            'gred' => $row['gred'] ?? '',
                            'salinan_sijil' => $row['salinan_sijil'] ?? '',
                        ];
                    }
                }
            }
            
            if (empty($prefill_family_members)) {
                $stmt = $pdo->prepare("SELECT * FROM application_family_members WHERE application_reference = ? ORDER BY id ASC");
                $stmt->execute([$ref]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prefill_family_members[] = [
                        'hubungan' => $row['hubungan'] ?? '',
                        'nama' => $row['nama'] ?? '',
                        'pekerjaan' => $row['pekerjaan'] ?? '',
                        'telefon' => $row['telefon'] ?? '',
                        'kewarganegaraan' => $row['kewarganegaraan'] ?? '',
                    ];
                }
                if (empty($prefill_family_members) && !empty($application_id)) {
                    $stmt = $pdo->prepare("SELECT * FROM application_family_members WHERE application_id = ? ORDER BY id ASC");
                    $stmt->execute([$application_id]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $prefill_family_members[] = [
                            'hubungan' => $row['hubungan'] ?? '',
                            'nama' => $row['nama'] ?? '',
                            'pekerjaan' => $row['pekerjaan'] ?? '',
                            'telefon' => $row['telefon'] ?? '',
                            'kewarganegaraan' => $row['kewarganegaraan'] ?? '',
                        ];
                    }
                }
            }
            
            if (empty($prefill_extracurriculars)) {
                $stmt = $pdo->prepare("SELECT * FROM application_extracurricular WHERE application_reference = ? ORDER BY id ASC");
                $stmt->execute([$ref]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $salinan = $row['salinan_sijil_filename'] ?? ($row['salinan_sijil_path'] ?? ($row['salinan_sijil'] ?? ''));
                    $prefill_extracurriculars[] = [
                        'sukan_persatuan_kelab' => $row['sukan_persatuan_kelab'] ?? '',
                        'jawatan' => $row['jawatan'] ?? '',
                        'peringkat' => $row['peringkat'] ?? '',
                        'tarikh_sijil' => $row['tarikh_sijil'] ?? (!empty($row['tahun']) ? ($row['tahun'] . '-01-01') : ''),
                        'salinan_sijil' => $salinan,
                    ];
                }
                if (empty($prefill_extracurriculars) && !empty($application_id)) {
                    $stmt = $pdo->prepare("SELECT * FROM application_extracurricular WHERE application_id = ? ORDER BY id ASC");
                    $stmt->execute([$application_id]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $salinan = $row['salinan_sijil_filename'] ?? ($row['salinan_sijil_path'] ?? ($row['salinan_sijil'] ?? ''));
                        $prefill_extracurriculars[] = [
                            'sukan_persatuan_kelab' => $row['sukan_persatuan_kelab'] ?? '',
                            'jawatan' => $row['jawatan'] ?? '',
                            'peringkat' => $row['peringkat'] ?? '',
                            'tarikh_sijil' => $row['tarikh_sijil'] ?? (!empty($row['tahun']) ? ($row['tahun'] . '-01-01') : ''),
                            'salinan_sijil' => $salinan,
                        ];
                    }
                }
            }
            
            if (empty($prefill_references)) {
                $stmt = $pdo->prepare("SELECT * FROM application_references WHERE application_reference = ? ORDER BY id ASC");
                $stmt->execute([$ref]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prefill_references[] = [
                        'nama' => $row['nama'] ?? '',
                        'no_telefon' => $row['no_telefon'] ?? '',
                        'tempoh_mengenali' => str_replace(' tahun', '', $row['tempoh_mengenali'] ?? ''),
                        'jawatan' => $row['jawatan'] ?? '',
                        'alamat' => $row['alamat'] ?? '',
                    ];
                }
                if (empty($prefill_references) && !empty($application_id)) {
                    $stmt = $pdo->prepare("SELECT * FROM application_references WHERE application_id = ? ORDER BY id ASC");
                    $stmt->execute([$application_id]);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $prefill_references[] = [
                            'nama' => $row['nama'] ?? '',
                            'no_telefon' => $row['no_telefon'] ?? '',
                            'tempoh_mengenali' => str_replace(' tahun', '', $row['tempoh_mengenali'] ?? ''),
                            'jawatan' => $row['jawatan'] ?? '',
                            'alamat' => $row['alamat'] ?? '',
                        ];
                    }
                }
            }
            // Health data merge for prefill
            $stmt = $pdo->prepare('SELECT * FROM application_health WHERE application_reference = ? LIMIT 1');
            $stmt->execute([$ref]);
            $health = $stmt->fetch(PDO::FETCH_ASSOC);
            // DEBUG: Check health data retrieval
            if ($health) {
                error_log("DEBUG: Health data found for ref: " . $ref);
                error_log("DEBUG: jenis_oku from health: " . ($health['jenis_oku'] ?? 'NULL'));
                error_log("DEBUG: pemegang_kad_oku from health: " . ($health['pemegang_kad_oku'] ?? 'NULL'));
            } else {
                error_log("DEBUG: No health data found for ref: " . $ref);
            }
            if ($health) {
                $merge_fields = [
                    'darah_tinggi','kencing_manis','penyakit_buah_pinggang','penyakit_jantung','batuk_kering_tibi','kanser','aids','penagih_dadah','perokok','penyakit_lain','penyakit_lain_nyatakan','pemegang_kad_oku','jenis_oku','memakai_cermin_mata','jenis_rabun','berat_kg','tinggi_cm','salinan_kad_oku'
                ];
                foreach ($merge_fields as $f) {
                    $application[$f] = $health[$f] ?? ($application[$f] ?? null);
                }
            }
            
            // Handle license data properly
            if (!empty($application['lesen_memandu'])) {
                if (is_string($application['lesen_memandu'])) {
                    $json_decoded = json_decode($application['lesen_memandu'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json_decoded)) {
                        $application['lesen_memandu_set'] = implode(',', $json_decoded);
                    } else {
                        $application['lesen_memandu_set'] = $application['lesen_memandu'];
                    }
                } elseif (is_array($application['lesen_memandu'])) {
                    $application['lesen_memandu_set'] = implode(',', $application['lesen_memandu']);
                }
            }
        } catch (Throwable $e) {
            error_log('Prefill error (full form): ' . $e->getMessage());
        }
        
        // Debug: Log what prefill data we have
        error_log('Prefill data loaded:');
        error_log('- Languages: ' . count($prefill_languages));
        error_log('- Computers: ' . count($prefill_computers));
        error_log('- Education: ' . count($prefill_education));
        error_log('- SPM Results: ' . count($prefill_spm_results));
        error_log('- SPM Additional: ' . count($prefill_spm_additional));
        error_log('- Work Experience: ' . count($prefill_work_experience));
        error_log('- Professional Bodies: ' . count($prefill_professional_bodies));
        error_log('- Extracurriculars: ' . count($prefill_extracurriculars));
        error_log('- References: ' . count($prefill_references));
        error_log('- Family Members: ' . count($prefill_family_members));
    }
} catch (Throwable $e) {
    $error = 'Ralat pangkalan data.';
    error_log('Error loading full application form: ' . $e->getMessage());
}

// Strict edit gate: require token presence and session marker when in edit mode
try {
    if ($edit_mode === true && !$error) {
        $hasSessionVerification = isset($_SESSION['edit_application_verified']) && $_SESSION['edit_application_verified'] === true;
        $hasToken = !empty($_SESSION['edit_token']) || !empty($edit_token);
        if (!($hasSessionVerification && $hasToken)) {
            $_SESSION['error'] = 'Akses edit memerlukan token pengesahan yang sah.';
            header('Location: semak-status.php');
            exit;
        }
    }
} catch (Throwable $gateErr) {
    error_log('Edit verification gate error: ' . $gateErr->getMessage());
}

// Merge error data back into form if submission failed
if (isset($_SESSION['application_data']) && !empty($_SESSION['application_data'])) {
    // Merge the failed submission data with existing application data
    if (!$application) {
        $application = [];
    }
    $application = array_merge($application, $_SESSION['application_data']);
    
    // Also preserve any array data like languages, computer skills, etc.
    if (isset($_SESSION['application_data']['kemahiran_bahasa'])) {
        $prefill_languages = $_SESSION['application_data']['kemahiran_bahasa'];
    }
    if (isset($_SESSION['application_data']['kemahiran_komputer'])) {
        $prefill_computers = $_SESSION['application_data']['kemahiran_komputer'];
    }
    
    // Clear the session data after using it
    unset($_SESSION['application_data']);
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borang Permohonan Jawatan - Borang Penuh</title>
    <link href="assets/css/tailwind.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; 
            background-color: #f7f9fc; 
            overflow-x: hidden;
        }
        .section-title { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 1rem; font-weight: 600; font-size: 1.125rem; }
        .required { color: #dc2626; }
        .standard-container { max-width: 1050px; margin: 0 auto; width: 100%; padding-left: 1rem; padding-right: 1rem; }
        .add-row-btn { background-color: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; }
        .add-row-btn:hover { background-color: #059669; }
        .uppercase-input { text-transform: uppercase; }
        .nav-pill { 
            display: inline-block; 
            margin-right: 8px; 
            margin-bottom: 8px;
            padding: 6px 12px; 
            background: #eef2ff; 
            color: #1e3a8a; 
            border-radius: 9999px; 
            font-size: 12px;
            white-space: nowrap;
        }
        .nav-pills-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 1rem;
        }
        .bg-white.rounded-lg.shadow-md { margin-top: 24px; }
        
        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            .nav-pill {
                font-size: 10px;
                padding: 5px 10px;
                margin-right: 4px;
                margin-bottom: 6px;
            }
            .nav-pills-container {
                padding: 0.75rem 0.5rem;
                gap: 4px;
            }
            .standard-container {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            .section-title {
                font-size: 1rem;
                padding: 0.75rem;
            }
        }
        
        /* Timer bar spacing */
        body.has-timer-bar {
            padding-top: 33px; /* Height of timer bar (6px padding top + 13px font + 6px padding bottom + some extra) */
        }
        
        @media (max-width: 480px) {
            .nav-pill {
                font-size: 9px;
                padding: 4px 8px;
            }
            body.has-timer-bar {
                padding-top: 30px; /* Slightly less on mobile */
            }
        }
    </style>
</head>
<body class="min-h-screen body-bg-image">

<?php include 'header.php'; ?>

<main class="standard-container px-4 sm:px-6 lg:px-8 py-8">
    <?php if ($error || isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
            <p><?php echo htmlspecialchars($error ?: $_SESSION['error']); ?></p>
            <p class="mt-2"><a href="index.php" class="font-medium underline">Kembali ke halaman utama</a></p>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['application_errors']) && !empty($_SESSION['application_errors'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
            <p class="font-bold">Ralat dalam permohonan:</p>
            <ul class="list-disc list-inside mt-2">
                <?php foreach ($_SESSION['application_errors'] as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="mt-3 text-sm">Sila betulkan maklumat di atas dan cuba lagi.</p>
            
            <!-- Debug Information -->
            <?php if (isset($_SESSION['debug_info']) && !empty($_SESSION['debug_info'])): ?>
                <div class="mt-4 p-3 bg-gray-100 rounded text-xs">
                    <p class="font-semibold">Debug Information:</p>
                    <pre class="whitespace-pre-wrap"><?php echo htmlspecialchars($_SESSION['debug_info']); ?></pre>
                </div>
                <?php unset($_SESSION['debug_info']); ?>
            <?php endif; ?>
            
            <!-- Error Stack Trace -->
            <?php if (isset($_SESSION['error_trace']) && !empty($_SESSION['error_trace'])): ?>
                <div class="mt-4 p-3 bg-gray-50 rounded text-xs">
                    <p class="font-semibold">Error Trace:</p>
                    <pre class="whitespace-pre-wrap"><?php echo htmlspecialchars($_SESSION['error_trace']); ?></pre>
                </div>
                <?php unset($_SESSION['error_trace']); ?>
            <?php endif; ?>
        </div>
        <?php unset($_SESSION['application_errors']); ?>
    <?php endif; ?>
    <?php if ($job): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-blue-600 text-white p-6">
                <h1 class="text-2xl font-bold">Borang Permohonan Jawatan - Borang Penuh</h1>
                <p class="mt-2"><?php echo htmlspecialchars(strtoupper($job['job_title'])); ?></p>
                <p class="text-blue-200 text-sm">Kod Gred: <?php echo htmlspecialchars($job['kod_gred'] ?? ''); ?></p>
                <?php if ($application_ref): ?>
                <p class="text-blue-200 text-sm">Rujukan Permohonan: <?php echo htmlspecialchars($application_ref); ?></p>
                <?php endif; ?>
            </div>
            <div class="nav-pills-container">
                <span class="nav-pill"><a href="#agreement">Pengakuan</a></span>
                <span class="nav-pill"><a href="#uploads">Dokumen</a></span>
                <span class="nav-pill"><a href="#personal">Peribadi</a></span>
                <span class="nav-pill"><a href="#family">Keluarga</a></span>
                <span class="nav-pill"><a href="#health">Kesihatan</a></span>
                <span class="nav-pill"><a href="#skills">Pendidikan</a></span>
                <span class="nav-pill"><a href="#declarations">Pengisytiharan & Rujukan</a></span>
            </div>
        </div>

        <form id="applicationFormFull" action="routes/application.php" method="POST" enctype="multipart/form-data" onsubmit="return submitFullForm();" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="save_full">
            <input type="hidden" name="application_id" value="<?php echo (int)($application['id'] ?? $application_id ?? 0); ?>">
            <input type="hidden" name="application_reference" value="<?php echo htmlspecialchars($application['application_reference'] ?? $application_ref ?? ''); ?>">
            <input type="hidden" name="job_id" value="<?php echo (int)$job_id; ?>">
            <input type="hidden" name="redirect_to_preview" value="true">
            <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
            <?php if ($edit_mode): ?>
            <input type="hidden" name="edit" value="1">
            <?php endif; ?>

            <div id="agreement"></div>
            <?php require __DIR__ . '/application_section/00-agreement.php'; ?>

            <div id="uploads"></div>
            <?php require __DIR__ . '/application_section/01-uploads.php'; ?>

            <div id="personal"></div>
            <?php require __DIR__ . '/application_section/02-personal-info.php'; ?>

            <div id="family"></div>
            <?php require __DIR__ . '/application_section/04-family-members.php'; ?>

            <div id="health"></div>
            <?php require __DIR__ . '/application_section/03-health-oku.php'; ?>

            <div id="skills"></div>
<?php require __DIR__ . '/application_section/pendidikan.php'; ?>
            <div id="declarations"></div>
            <?php require __DIR__ . '/application_section/05-declarations-references.php'; ?>

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 flex justify-between items-center">
                    <a href="index.php" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-3 px-6 rounded-lg transition duration-200">Kembali</a>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg transition duration-200">Simpan & Pratonton</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>

<script>
// Countdown bar for session timeout
(() => {
  const timeoutSeconds = <?php echo (int)($_SESSION['form_timeout_seconds'] ?? 1800); ?>;
  const startTs = <?php echo (int)($_SESSION['form_start_time'] ?? time()); ?>;
  const endTs = startTs + timeoutSeconds;

  const bar = document.createElement('div');
  bar.id = 'timeoutBar';
  bar.style.cssText = 'position:fixed;top:0;left:0;width:100%;z-index:9998;background:#1f2937;color:#fff;padding:6px 12px;font-size:13px;display:flex;justify-content:center;align-items:center;gap:8px;';
  bar.innerHTML = '<span>Tempoh sesi borang: </span><strong id="timeoutCountdown"></strong>';
  const headerEl = document.querySelector('header');
  if (headerEl && headerEl.parentNode) {
    headerEl.parentNode.insertBefore(bar, headerEl.nextSibling);
  } else {
    document.body.insertBefore(bar, document.body.firstChild);
  }
  
  // Add padding to body to account for fixed timer bar
  document.body.classList.add('has-timer-bar');

  const cd = document.getElementById('timeoutCountdown');
  const form = document.getElementById('applicationFormFull');
  const warnThreshold = 5 * 60; // 5 minutes

  const tick = () => {
    const now = Math.floor(Date.now() / 1000);
    const remain = Math.max(0, endTs - now);
    const mm = String(Math.floor(remain / 60)).padStart(2, '0');
    const ss = String(remain % 60).padStart(2, '0');
    if (cd) cd.textContent = `${mm}:${ss}`;
    if (remain <= warnThreshold) {
      bar.style.background = '#b45309'; // amber warning
    }
    if (remain <= 0) {
      // Disable form interactions and redirect
      if (form) {
        Array.from(form.elements).forEach(el => { try { el.disabled = true; } catch(e){} });
      }
      const overlay = document.createElement('div');
      overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center;';
      overlay.innerHTML = '<div style="background:#fff;padding:16px 20px;border-radius:8px;max-width:420px;text-align:center;color:#111827">Sesi borang telah tamat. Anda akan dipindahkan ke halaman utama.<div style="margin-top:10px;font-size:12px;color:#6b7280">Untuk keselamatan, sila mulakan semula atau sahkan identiti untuk menyambung edit.</div></div>';
      document.body.appendChild(overlay);
      setTimeout(() => { window.location.href = 'index.php'; }, 2000);
      return; // stop further ticks
    }
    requestAnimationFrame(tick);
  };
  requestAnimationFrame(tick);
})();

function submitFullForm() {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;flex-direction:column;justify-content:center;align-items:center;';
    overlay.innerHTML = '<div style="text-align:center;padding:20px;background:white;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);"><div style="margin-bottom:12px;">Memproses permohonan...</div><div class="animate-spin" style="height:24px;width:24px;border:4px solid #3b82f6;border-top-color:transparent;border-radius:50%;"></div></div>';
    document.body.appendChild(overlay);
    var siteKey = '<?php echo htmlspecialchars($recaptcha_v3_site_key); ?>';
    if (siteKey) {
        var form = document.getElementById('applicationFormFull');
        var tokenInput = document.getElementById('recaptcha_token');
        if (window.grecaptcha && typeof grecaptcha.execute === 'function') {
            try {
                grecaptcha.ready(function(){
                    grecaptcha.execute(siteKey, { action: '<?php echo htmlspecialchars($recaptcha_v3_action); ?>' }).then(function(token){
                        if (tokenInput) tokenInput.value = token;
                        if (form) form.submit();
                    });
                });
                return false;
            } catch (e) {
                return true;
            }
        }
    }
    return true;
}

// Simple toggles for Part 2 (OKU / penyakit lain)
document.addEventListener('change', function(e){
    if (e.target && e.target.name === 'penyakit_lain') {
        const show = e.target.value === 'Ya';
        const el = document.getElementById('penyakit_lain_field');
        if (el) el.style.display = show ? 'block' : 'none';
    }
    if (e.target && e.target.name === 'pemegang_kad_oku') {
        const show = e.target.value === 'Ya';
        const el = document.getElementById('oku_field');
        if (el) el.style.display = show ? 'block' : 'none';
    }
    if (e.target && e.target.name === 'memakai_cermin_mata') {
        const show = e.target.value === 'Ya';
        const el = document.getElementById('cermin_mata_field');
        if (el) el.style.display = show ? 'block' : 'none';
    }
    if (e.target && e.target.name === 'status_pasangan') {
        const hide = e.target.value === 'Tidak Bekerja';
        const ids = [
          'pekerjaan_pasangan',
          'nama_majikan_pasangan',
          'telefon_pejabat_pasangan',
          'alamat_majikan_pasangan',
          'poskod_majikan_pasangan',
          'bandar_majikan_pasangan',
          'negeri_majikan_pasangan'
        ];
        ids.forEach(function(id){
          const input = document.getElementById(id);
          if (!input) return;
          const wrapper = input.closest('div');
          if (wrapper) wrapper.style.display = hide ? 'none' : '';
        });
    }
    if (e.target && e.target.id === 'taraf_perkahwinan') {
        const val = e.target.value;
        const container = document.getElementById('maklumat_pasangan_field');
        const statusSel = document.getElementById('status_pasangan');
        const show = (val === 'Berkahwin');
        if (container) container.style.display = show ? '' : 'none';
        if (statusSel) {
            statusSel.required = !!show;
            statusSel.disabled = !show;
        }
    }
    // Declarations nyatakan fields toggle
    const declKeys = ['pekerja_perkhidmatan_awam','pertalian_kakitangan','pernah_bekerja_mphs','tindakan_tatatertib','kesalahan_undangundang','muflis'];
    if (e.target && declKeys.includes(e.target.name)) {
        const show = e.target.value === 'Ya';
        const fieldId = e.target.name + '_nyatakan_field';
        const el = document.getElementById(fieldId);
        if (el) el.style.display = show ? 'block' : 'none';
    }
});

// Initialize spouse employment field visibility on load
document.addEventListener('DOMContentLoaded', function(){
  const sel = document.getElementById('status_pasangan');
  const marital = document.getElementById('taraf_perkahwinan');
  const container = document.getElementById('maklumat_pasangan_field');
  if (!sel) return;
  const fakeEvent = { target: sel };
  document.dispatchEvent(new Event('change'));
  const hide = sel.value === 'Tidak Bekerja';
  const ids = [
    'pekerjaan_pasangan',
    'nama_majikan_pasangan',
    'telefon_pejabat_pasangan',
    'alamat_majikan_pasangan',
    'poskod_majikan_pasangan',
    'bandar_majikan_pasangan',
    'negeri_majikan_pasangan'
  ];
  ids.forEach(function(id){
    const input = document.getElementById(id);
    if (!input) return;
    const wrapper = input.closest('div');
    if (wrapper) wrapper.style.display = hide ? 'none' : '';
  });
  if (marital && container) {
    const show = marital.value === 'Berkahwin';
    container.style.display = show ? '' : 'none';
    if (sel) {
      sel.required = !!show;
      sel.disabled = !show;
    }
  }
});

// Dynamic add/remove rows for Part 3 (language/computer)
document.addEventListener('click', function(e){
    if (e.target && e.target.id === 'addLanguageSkill') {
        const container = document.getElementById('language-skills-container');
        const entries = container.querySelectorAll('.language-skill-entry');
        const idx = entries.length;
        const tpl = entries[0].cloneNode(true);
        tpl.querySelectorAll('input, select').forEach(function(el){
            el.value = '';
            el.name = el.name.replace(/\[\d+\]/, '['+idx+']');
        });
        tpl.querySelector('.remove-language-btn').style.display = '';
        container.appendChild(tpl);
    }
    if (e.target && e.target.classList.contains('remove-language-btn')) {
        const entry = e.target.closest('.language-skill-entry');
        if (entry) entry.remove();
    }
    if (e.target && e.target.id === 'addComputerSkill') {
        const container = document.getElementById('computer-skills-container');
        const entries = container.querySelectorAll('.computer-skill-entry');
        const idx = entries.length;
        const tpl = entries[0].cloneNode(true);
        tpl.querySelectorAll('input, select').forEach(function(el){
            el.value = '';
            el.name = el.name.replace(/\[\d+\]/, '['+idx+']');
        });
        tpl.querySelector('.remove-computer-btn').style.display = '';
        container.appendChild(tpl);
    }
    if (e.target && e.target.classList.contains('remove-computer-btn')) {
        const entry = e.target.closest('.computer-skill-entry');
        if (entry) entry.remove();
    }
});
</script>

<?php if (!empty($recaptcha_v3_site_key)): ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($recaptcha_v3_site_key); ?>"></script>
<?php endif; ?>

<!-- IC auto-populate and NRIC format enforcement -->
<script src="assets/js/ic-auto-populate.js"></script>

<script>
(function(){
  var form = document.getElementById('applicationFormFull');
  var icInput = document.getElementById('nombor_ic');
  var submitBtn = form ? form.querySelector('button[type="submit"]') : null;
  var csrf = form ? form.querySelector('input[name="csrf_token"]') : null;
  var jobIdInput = form ? form.querySelector('input[name="job_id"]') : null;
  var appIdInput = form ? form.querySelector('input[name="application_id"]') : null;
  var duplicate = false;
  function checkDup() {
    if (!form || !icInput || !csrf || !jobIdInput) return;
    var nric = icInput.value.trim();
    var jobId = jobIdInput.value;
    var appId = appIdInput ? appIdInput.value : '';
    if (!nric || !jobId) return;
    var body = new URLSearchParams();
    body.append('action','check_nric');
    body.append('csrf_token', csrf.value);
    body.append('job_id', jobId);
    body.append('nombor_ic', nric);
    if (appId) body.append('application_id', appId);
    fetch('routes/application.php', { method: 'POST', headers: { 'Content-Type':'application/x-www-form-urlencoded' }, body: body.toString() })
      .then(function(r){ return r.json(); })
      .then(function(res){
        duplicate = !!res.exists;
        if (duplicate) {
          if (submitBtn) submitBtn.disabled = true;
          icInput.classList.add('border-red-500');
          alert('Permohonan untuk jawatan ini dengan NRIC tersebut sudah wujud.');
        } else {
          if (submitBtn) submitBtn.disabled = false;
          icInput.classList.remove('border-red-500');
        }
      })
      .catch(function(){ /* noop */ });
  }
  if (icInput) {
    icInput.addEventListener('blur', checkDup);
    icInput.addEventListener('change', checkDup);
  }
  if (form) {
    form.addEventListener('submit', function(e){ if (duplicate) { e.preventDefault(); } });
  }
})();
</script>

<div id="scrollControls" style="position:fixed;right:16px;bottom:16px;z-index:9999;display:flex;flex-direction:column;gap:10px;">
  <button type="button" id="scrollToTopBtn" class="bg-gray-700 hover:bg-gray-800 text-white rounded-full shadow-lg" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M18 15l-6-6-6 6"></path>
    </svg>
  </button>
  <button type="button" id="scrollToBottomBtn" class="bg-gray-700 hover:bg-gray-800 text-white rounded-full shadow-lg" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M6 9l6 6 6-6"></path>
    </svg>
  </button>
  </div>
<script>
  (function(){
    const topBtn = document.getElementById('scrollToTopBtn');
    const bottomBtn = document.getElementById('scrollToBottomBtn');
    if (topBtn) topBtn.addEventListener('click', function(){ window.scrollTo({ top: 0, behavior: 'smooth' }); });
    if (bottomBtn) bottomBtn.addEventListener('click', function(){ const h = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight); window.scrollTo({ top: h, behavior: 'smooth' }); });
  })();
</script>

</body>
</html>
