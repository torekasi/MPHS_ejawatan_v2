<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start output buffering to prevent header issues
ob_start();

require_once 'includes/ErrorHandler.php';
require_once 'includes/DuplicateApplicationChecker.php';

// Get database connection from config
$result = require 'config.php';
$config = $result['config'] ?? $result;

// Check if status check feature is enabled
if (!isset($config['navigation']['show_status_check']) || !$config['navigation']['show_status_check']) {
    http_response_code(404);
    include '404.php';
    exit;
}

// Define status message function
function getStatusMessage($status) {
    switch ($status) {
        case 'DRAFT':
            return 'Permohonan masih dalam draf. Sila lengkapkan dan hantar permohonan anda.';
        case 'PENDING':
            return 'Permohonan anda sedang menunggu untuk diproses.';
        case 'SUBMITTED':
            return 'Permohonan anda telah berjaya dihantar dan sedang menunggu semakan.';
        case 'PROCESSING':
            return 'Permohonan anda sedang diproses oleh pegawai kami.';
        case 'SHORTLISTED':
            return 'Tahniah! Anda telah disenarai pendek untuk jawatan ini.';
        case 'INTERVIEWED':
            return 'Anda telah menjalani sesi temuduga. Sila tunggu keputusan.';
        case 'OFFERED':
            return 'Tahniah! Anda telah ditawarkan jawatan ini.';
        case 'ACCEPTED':
            return 'Anda telah menerima tawaran jawatan ini.';
        case 'REJECTED':
            return 'Maaf, permohonan anda tidak berjaya kali ini.';
        default:
            return 'Status permohonan tidak dapat ditentukan.';
    }
}

// Define status colors function
function getStatusColor($status) {
    switch (strtoupper($status)) {
        case 'DRAFT':
            return 'text-gray-700 bg-gray-100';
        case 'SUBMITTED':
            return 'text-blue-700 bg-blue-100';
        case 'PENDING':
            return 'text-blue-700 bg-blue-100';
        case 'SCREENING':
            return 'text-yellow-700 bg-yellow-100';
        case 'TEST_INTERVIEW':
            return 'text-indigo-700 bg-indigo-100';
        case 'AWAITING_RESULT':
            return 'text-blue-700 bg-blue-100';
        case 'PASSED_INTERVIEW':
            return 'text-green-800 bg-green-200';
        case 'OFFER_APPOINTMENT':
            return 'text-teal-800 bg-teal-100';
        case 'APPOINTED':
            return 'text-green-800 bg-green-200';
        case 'REVIEWED':
            return 'text-blue-700 bg-blue-100';
        case 'APPROVED':
            return 'text-green-800 bg-green-200';
        case 'REJECTED':
            return 'text-red-700 bg-red-100';
        default:
            return 'text-gray-600 bg-gray-100';
    }
}

// Function to mask phone number (first 4 and last 2 digits visible)
function maskPhoneNumber($phone) {
    if (empty($phone) || strlen($phone) < 6) {
        return $phone;
    }

    $phone = preg_replace('/[^0-9]/', '', $phone); // Remove non-numeric characters

    if (strlen($phone) <= 6) {
        return $phone;
    }

    $firstFour = substr($phone, 0, 4);
    $lastTwo = substr($phone, -2);
    $middleLength = strlen($phone) - 6;

    return $firstFour . str_repeat('*', $middleLength) . $lastTwo;
}

// Function to mask email address (first 3 chars of local part, mask domain except extension)
function maskEmail($email) {
    if (empty($email)) {
        return $email;
    }

    $atPos = strpos($email, '@');
    if ($atPos === false || $atPos < 3) {
        return $email;
    }

    $localPart = substr($email, 0, $atPos);
    $domainPart = substr($email, $atPos);

    if (strlen($localPart) <= 3) {
        return $email;
    }

    $firstThree = substr($localPart, 0, 3);
    $maskedLocal = $firstThree . str_repeat('*', strlen($localPart) - 3);

    // For domain part, only show the extension (.com, .edu, etc.)
    $dotPos = strrpos($domainPart, '.');
    if ($dotPos !== false && $dotPos > 1) {
        $extension = substr($domainPart, $dotPos);
        $domainPart = str_repeat('*', $dotPos - 1) . $extension;
    } else {
        // If no extension found, mask entire domain
        $domainPart = str_repeat('*', strlen($domainPart));
    }

    return $maskedLocal . $domainPart;
}

// Generate a short-lived edit token tied to an application
function generate_edit_token($pdo, $application_id) {
    try {
        if (!$pdo || !$application_id) { return null; }
        $token = bin2hex(random_bytes(32));
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $pdo->prepare('
            INSERT INTO user_sessions (id, user_id, application_id, ip_address, user_agent, last_activity, created_at)
            VALUES (?, NULL, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        $stmt->execute([$token, $application_id, $ip_address, $user_agent]);
        return $token;
    } catch (Exception $e) {
        // Avoid leaking details; rely on standard error logging
        error_log('DEBUG: Failed to generate edit token: ' . $e->getMessage());
        return null;
    }
}

// Initialize variables
$pdo = null;
$error = '';
$status_data = null;
$nric = null;
$application_ref = null;
$edit_token = null;
$job_code = $_GET['job_code'] ?? '';

// Get any flash messages
session_start();
$flash_error = $_SESSION['error'] ?? null;
$flash_success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);

// Simple test endpoint
if (isset($_GET['test']) && $_GET['test'] == '1') {
    echo json_encode(['status' => 'ok', 'message' => 'Backend connection successful']);
    exit;
}

if (isset($_GET['nric']) && isset($_GET['application_ref'])) {
    $nric = trim($_GET['nric']);
    $application_ref = trim($_GET['application_ref']);
    $job_code = is_string($job_code) ? $job_code : '';
    
    // Normalize NRIC (handle with-hyphens and digits-only)
    $clean_nric = preg_replace('/[^0-9]/', '', $nric);
    $formatted_nric = '';
    if (strlen($clean_nric) === 12) {
        $formatted_nric = substr($clean_nric, 0, 6) . '-' . substr($clean_nric, 6, 2) . '-' . substr($clean_nric, 8, 4);
    }
    
    // Initialize duplicate application checker
    if (!empty($job_code) && !empty($nric) && $pdo) {
        $checker = new DuplicateApplicationChecker($pdo);
        $duplicate_check = $checker->checkDuplicateApplication($nric, $job_code);
    }

    // Debug logging
    error_log("DEBUG: Received NRIC: '$nric', App Ref: '$application_ref'");

    // Validate inputs exist
    if (empty($nric)) {
        $error = 'No. Kad Pengenalan diperlukan.';
    } elseif (empty($application_ref)) {
        $error = 'Rujukan Permohonan diperlukan.';
    }

    // Validate NRIC format: allow hyphenated or 12-digit
    if (
        !$error &&
        !(preg_match('/^\d{6}-\d{2}-\d{4}$/', $nric) || preg_match('/^\d{12}$/', $clean_nric))
    ) {
        $error = 'Format No. Kad Pengenalan tidak betul. Sila gunakan format: XXXXXX-XX-XXXX atau 12 digit.';
    }

    // Validate application reference format (exact format as stored in database)
    if (!$error && (!preg_match('/^APP-[A-Z0-9-]+$/', $application_ref) || strlen($application_ref) < 8)) {
        $error = 'Format Rujukan Permohonan tidak sah. Format yang betul: APP-XXXXXXXX (contoh: APP-2025-0003-95B0FF34)';
    }

// Connect to database
try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    // Debug connection info (remove in production)
    error_log("DEBUG: Connecting to database - Host: {$config['db_host']}, Database: {$config['db_name']}");
    
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    error_log("DEBUG: Database connection successful");
        
        $stmtMain = $pdo->prepare("\n            SELECT\n                aam.*,\n                jp.job_title,\n                jp.kod_gred,\n                jp.id as job_posting_id,\n                aam.created_at as application_date,\n                st.code AS status_code,\n                st.name AS status_name,\n                st.description AS status_description\n            FROM application_application_main aam\n            LEFT JOIN job_postings jp ON aam.job_id = jp.id\n            LEFT JOIN application_statuses st ON st.code = aam.submission_status\n            WHERE aam.application_reference = ?\n              AND (aam.nombor_ic = ? OR aam.nombor_ic = ?)\n            LIMIT 1\n        ");
        error_log("DEBUG: SQL Query (application_application_main) - App Ref: '$application_ref', NRIC clean: '$clean_nric', NRIC formatted: '$formatted_nric'");
        $stmtMain->execute([$application_ref, $clean_nric, $formatted_nric ?: $nric]);
        $application = $stmtMain->fetch();

        if (!$application) {
            $stmtLegacy = $pdo->prepare("\n                SELECT\n                    ja.*,\n                    jp.job_title,\n                    jp.kod_gred,\n                    jp.id as job_posting_id,\n                    ja.application_date as application_date\n                FROM job_applications ja\n                LEFT JOIN job_postings jp ON ja.job_id = jp.id\n                WHERE ja.application_reference = ?\n                  AND (ja.nombor_ic = ? OR ja.nombor_ic = ?)\n                LIMIT 1\n            ");
            error_log("DEBUG: Fallback query (job_applications) - App Ref: '$application_ref', NRIC clean: '$clean_nric', NRIC formatted: '$formatted_nric'");
            $stmtLegacy->execute([$application_ref, $clean_nric, $formatted_nric ?: $nric]);
            $application = $stmtLegacy->fetch();
        }
        
        // Debug query result
        error_log("DEBUG: Query completed - Rows returned: " . ($application ? "1" : "0"));

        if ($application) {
            $dbgCode = $application['status_code'] ?? ($application['submission_status'] ?? 'N/A');
            $dbgName = $application['status_name'] ?? '';
            error_log("DEBUG: Application found - ID: {$application['id']}, StatusCode: {$dbgCode}, StatusName: {$dbgName}");
        } else {
            error_log("DEBUG: No application found for NRIC: $nric, App Ref: $application_ref");
        }

        if ($application && is_array($application)) {
            // Ensure all required fields exist
            $job_title = isset($application['job_title']) ? $application['job_title'] : 'N/A';
            $kod_gred = isset($application['kod_gred']) ? $application['kod_gred'] : 'N/A';
            $status_code = strtoupper($application['status_code'] ?? ($application['submission_status'] ?? ''));
            if ($status_code === '') {
                if (!empty($application['approved_at'])) { $status_code = 'APPROVED'; }
                elseif (!empty($application['reviewed_at'])) { $status_code = 'REVIEWED'; }
                elseif ((int)($application['submission_locked'] ?? 0) === 1) { $status_code = 'SUBMITTED'; }
                else { $status_code = 'DRAFT'; }
            }
            $status_name = $application['status_name'] ?? $status_code;
            $status_desc = $application['status_description'] ?? null;

            // Get latest status history notes
            $status_notes = null;
            try {
                $notes_stmt = $pdo->prepare("
                    SELECT notes, status_description, changed_by, changed_at 
                    FROM application_status_history 
                    WHERE application_id = ? 
                    ORDER BY changed_at DESC 
                    LIMIT 1
                ");
                $notes_stmt->execute([$application['id']]);
                $status_notes = $notes_stmt->fetch();
            } catch (PDOException $e) {
                error_log("DEBUG: Could not fetch status notes: " . $e->getMessage());
            }

            $status_data = [
                'found' => true,
                'application' => $application,
                'job' => [
                    'job_title' => $job_title,
                    'kod_gred' => $kod_gred
                ],
                'status_code' => $status_code,
                'status_name' => $status_name,
                'status_message' => $status_desc ?: getStatusMessage($status_code),
                'status_notes' => $status_notes
            ];

            // Prepare edit token if application is unlocked (draft)
            if ((int)($application['submission_locked'] ?? 0) !== 1) {
                $edit_token = generate_edit_token($pdo, $application['id']);
                if ($edit_token) {
                    // Store minimal session markers to smooth edit flow
                    $_SESSION['edit_application_verified'] = true;
                    $_SESSION['verified_application_id'] = $application['id'];
                    $_SESSION['verified_application_ref'] = $application['application_reference'];
                    $_SESSION['edit_token'] = $edit_token;
                }
            }
        } else {
            $status_data = [
                'found' => false,
                'status_message' => 'Tiada permohonan dijumpai dengan maklumat yang diberikan.'
            ];
        }
        
    } catch (PDOException $e) {
        $error = 'Ralat sambungan ke pangkalan data. Sila cuba sebentar lagi.';
        log_error('Database connection error on application status', [
            'exception' => $e->getMessage(),
            'nric' => $nric,
            'application_ref' => $application_ref,
            'trace' => $e->getTraceAsString()
        ]);
        
        // Add detailed error for debugging (remove in production)
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<pre>DATABASE ERROR: " . $e->getMessage() . "\n\n";
            echo "Trace: " . $e->getTraceAsString() . "</pre>";
            exit;
        }
    } catch (Exception $e) {
        $error = 'Ralat sistem telah berlaku. Sila cuba lagi kemudian atau hubungi pentadbir sistem.';
        log_error('General error on application status', [
            'exception' => $e->getMessage(),
            'nric' => $nric,
            'application_ref' => $application_ref,
            'trace' => $e->getTraceAsString()
        ]);
        
        // Add detailed error for debugging (remove in production)
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo "<pre>GENERAL ERROR: " . $e->getMessage() . "\n\n";
            echo "Trace: " . $e->getTraceAsString() . "</pre>";
            exit;
        }
    }
} else {
    $error = 'Parameter tidak lengkap. Sila cuba lagi.';
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Permohonan - eJawatan MPHS</title>
    <link href="assets/css/tailwind.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f7f9fc;
        }
        .standard-container {
            max-width: 1050px;
            margin: 0 auto;
            width: 100%;
        }
        .status-card {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
        }
        .status-pending { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .status-processing { background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); }
        .status-shortlisted { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .status-interviewed { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .status-offered { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
        .status-accepted { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .status-rejected { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    </style>
</head>
<body class="min-h-screen body-bg-image">
    <?php include 'header.php'; ?>

    <main class="standard-container px-4 sm:px-6 lg:px-8 py-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="javascript:history.back()" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded shadow transition duration-150 ease-in-out">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali
            </a>
        </div>

        <?php if ($error || $flash_error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p><?php echo htmlspecialchars($error ?: $flash_error); ?></p>
                        <p class="mt-2">
                            <a href="semak-status.php" class="font-medium underline">Kembali ke halaman semakan</a>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($flash_success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-8" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p><?php echo htmlspecialchars($flash_success); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($status_data): ?>
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="bg-blue-600 text-white p-6">
                    <h1 class="text-2xl font-bold">Status Permohonan Jawatan</h1>
                    <p class="mt-2 text-blue-200">Semakan status permohonan anda</p>
                </div>
            </div>

            <?php if ($status_data['found']): ?>
                <?php $application = $status_data['application']; ?>
                <?php $job = $status_data['job']; ?>
                
                <!-- Application Found -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="p-6">
                        <div class="flex items-center mb-6">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-medium text-gray-900">Permohonan Dijumpai</h3>
                                <p class="text-sm text-gray-500">Maklumat permohonan jawatan anda</p>
                        </div>
                    </div>

                        <!-- Status Display -->
                        <?php 
                        $current_code = strtoupper($status_data['status_code'] ?? '');
                        $current_name = $status_data['status_name'] ?? $current_code;
                        $status_color = getStatusColor($current_code);
                        ?>
                        <div class="mb-6 p-4 rounded-lg border-2 border-dashed border-gray-200 bg-gray-50">
                            <div class="text-center">
                                <h4 class="text-sm font-medium text-gray-600 mb-2">Status Semasa</h4>
                                <div class="inline-flex items-center px-4 py-2 rounded-full text-lg font-bold <?php echo $status_color; ?>">
                                    <?php echo htmlspecialchars($current_name); ?>
                                </div>
                                <p class="mt-3 text-sm text-gray-700">
                                    <?php echo htmlspecialchars($status_data['status_message']); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Status Notes -->
                        <?php if (isset($status_data['status_notes']) && $status_data['status_notes'] && !empty($status_data['status_notes']['notes'])): ?>
                        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-blue-800">Nota Terkini</h4>
                                    <p class="mt-1 text-sm text-blue-700"><?php echo htmlspecialchars($status_data['status_notes']['notes']); ?></p>
                                    <p class="mt-2 text-xs text-blue-600">
                                        Dikemaskini: <?php echo date('d/m/Y H:i', strtotime($status_data['status_notes']['changed_at'])); ?>
                                        <?php if (!empty($status_data['status_notes']['changed_by'])): ?>
                                        oleh <?php echo htmlspecialchars($status_data['status_notes']['changed_by']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    
                        <!-- Application Details -->
                        <?php if ($application['submission_locked'] != 1): ?>
                        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <h4 class="font-semibold text-blue-800 mb-2">📝 Edit Permohonan</h4>
                            <p class="text-sm text-blue-700 mb-3">Permohonan anda masih boleh diedit. Klik butang di bawah untuk meneruskan editan.</p>
                            <a href="job-application-full.php?ref=<?php echo urlencode($application['application_reference']); ?>&job_id=<?php echo (int)$application['job_id']; ?>&edit=1<?php echo (!empty($edit_token) ? '&edit_token=' . urlencode($edit_token) : ''); ?>"
                               class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded shadow transition duration-150 ease-in-out">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit Permohonan
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="border-t border-gray-200 pt-6">
                            <!-- Basic Information -->
                            <div class="mb-6">
                                <h4 class="text-md font-semibold text-gray-900 mb-4">Maklumat Asas</h4>
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Nama Penuh</dt>
                                        <dd class="mt-1 text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars(strtoupper($application['nama_penuh'] ?? 'N/A')); ?></dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">No. Telefon</dt>
                                        <dd class="mt-1 text-sm text-gray-900 font-mono"><?php echo htmlspecialchars(maskPhoneNumber($application['nombor_telefon'] ?? 'N/A')); ?></dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                                        <dd class="mt-1 text-sm text-gray-900 font-mono"><?php echo htmlspecialchars(maskEmail($application['email'] ?? 'N/A')); ?></dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Application Information -->
                            <div class="mb-6 pt-4 border-t border-gray-200">
                                <h4 class="text-md font-semibold text-gray-900 mb-4">Maklumat Permohonan</h4>
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Rujukan Permohonan</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($application['application_reference'] ?? 'N/A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tarikh Permohonan</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?php 
                                            $date_field = isset($application['application_date']) ? $application['application_date'] : (isset($application['created_at']) ? $application['created_at'] : null);
                                        if ($date_field && !empty($date_field)) {
                                            $timestamp = strtotime($date_field);
                                            echo ($timestamp !== false) ? date('d/m/Y H:i', $timestamp) : 'N/A';
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?></dd>
                                </div>
                                    <?php if (isset($job) && $job): ?>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Jawatan</dt>
                                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars(strtoupper($job['job_title'] ?? 'N/A')); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Kod Gred</dt>
                                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($job['kod_gred'] ?? 'N/A'); ?></dd>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Status Penguncian</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <?php if ($application['submission_locked'] == 1): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Dikunci (Dihantar)
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Tidak Dikunci (Draf)
                                                </span>
                                            <?php endif; ?>
                                        </dd>
                                    </div>
                                    <?php if (!empty($application['submission_date'])): ?>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Tarikh Dihantar</dt>
                                        <dd class="mt-1 text-sm text-gray-900"><?php echo date('d/m/Y H:i', strtotime($application['submission_date'])); ?></dd>
                                </div>
                                <?php endif; ?>
                            </dl>
                                </div>

                            <!-- Address Information (removed per request) -->
                            <?php /* Address and correspondence sections hidden */ ?>

                            <!-- Health Information -->
                            <?php if (!empty($application['pemegang_kad_oku']) || !empty($application['memakai_cermin_mata'])): ?>
                            <div class="mb-6 pt-4 border-t border-gray-200">
                                <h4 class="text-md font-semibold text-gray-900 mb-4">Maklumat Kesihatan</h4>
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                    <?php if (!empty($application['pemegang_kad_oku'])): ?>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Pemegang Kad OKU</dt>
                                        <dd class="mt-1 text-sm text-gray-900 <?php echo (strtoupper($application['pemegang_kad_oku']) === 'YA') ? 'font-bold text-red-600' : ''; ?>">
                                            <?php echo htmlspecialchars(strtoupper($application['pemegang_kad_oku'])); ?>
                                        </dd>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($application['memakai_cermin_mata'])): ?>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Memakai Cermin Mata</dt>
                                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars(strtoupper($application['memakai_cermin_mata'])); ?></dd>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($application['tinggi_cm']) || !empty($application['berat_kg'])): ?>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Tinggi / Berat</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($application['tinggi_cm'] ?? 'N/A'); ?> cm / 
                                            <?php echo htmlspecialchars($application['berat_kg'] ?? 'N/A'); ?> kg
                                        </dd>
                                    </div>
                                    <?php endif; ?>
                                </dl>
                            </div>
                            <?php endif; ?>

                            <!-- Declaration Information (removed per request) -->
                            <?php /* Declarations block hidden */ ?>
                        </div>
                    </div>
                </div>

                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col space-y-4">

                            <!-- Other Actions -->
                            <div class="flex flex-wrap gap-4">
                                <?php if (isset($application['job_id']) && $application['job_id']): ?>
                                <a href="view-job.php?job_code=<?php echo urlencode($job_code ?? ($application['job_code'] ?? '')); ?>"
                                   class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded shadow transition duration-150 ease-in-out">
                                    Lihat Maklumat Jawatan
                                </a>
                                <?php endif; ?>

                                <a href="semak-status.php"
                                   class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded shadow transition duration-150 ease-in-out">
                                    Semak Status Lain
                                </a>
                                
                                <a href="index.php" 
                                   class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded shadow transition duration-150 ease-in-out">
                                    Cari Jawatan Lain
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- No Application Found -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tiada Permohonan Dijumpai</h3>
                        <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($status_data['status_message']); ?></p>
                        <div class="mt-6 space-y-3">
                            <p class="text-sm text-gray-600">
                                Sila pastikan:
                            </p>
                            <ul class="text-sm text-gray-600 text-left max-w-md mx-auto space-y-1">
                                <li>• Format No. Kad Pengenalan adalah betul (XXXXXX-XX-XXXX)</li>
                                <li>• Rujukan Permohonan adalah tepat (contoh: APP-2025-0003-95B0FF34)</li>
                                <li>• Maklumat yang dimasukkan adalah sama dengan yang didaftarkan</li>
                            </ul>
                            <div class="pt-4">
                                <a href="semak-status.php"
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                    </svg>
                                    Cuba Semula
                                </a>
                                <a href="debug-application-status.php?nric=<?php echo urlencode($nric ?? ''); ?>&app_ref=<?php echo urlencode($application_ref ?? ''); ?>" 
                                   class="inline-flex items-center px-4 py-2 ml-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Bantuan Teknikal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>