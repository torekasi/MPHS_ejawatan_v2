<?php
session_start();
require_once 'includes/ErrorHandler.php';

$csrf_token = $_SESSION['csrf_token'] ?? '';
if ($csrf_token === '') {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); } catch (Throwable $e) { $_SESSION['csrf_token'] = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 16); }
}

$result = require 'config.php';
$config = $result['config'] ?? $result;

$pdo = null;
$error = '';
$application = null;
$job = null;

if (isset($_SESSION['application_submitted']) && !empty($_SESSION['application_submitted']['reference'])) {
    $application_reference = $_SESSION['application_submitted']['reference'];
    $application_data = $_SESSION['application_submitted'];
    unset($_SESSION['application_submitted']);
} elseif (isset($_GET['ref']) && !empty($_GET['ref'])) {
    $application_reference = $_GET['ref'];
} else {
    $error = 'Rujukan permohonan tidak ditemui.';
}

try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
} catch (PDOException $e) {
    $error = 'Ralat sambungan ke pangkalan data.';
    log_error('Database connection error in application thank you page', ['exception' => $e->getMessage()]);
}

if (!$error && $pdo && isset($application_reference)) {
    try {
        $stmt = $pdo->prepare(
            'SELECT a.*, j.job_title, j.kod_gred, j.job_code FROM application_application_main a LEFT JOIN job_postings j ON a.job_id = j.id WHERE a.application_reference = ? LIMIT 1'
        );
        $stmt->execute([$application_reference]);
        $application = $stmt->fetch();

        if (!$application) {
            $stmt = $pdo->prepare(
                'SELECT a.*, j.job_title, j.kod_gred, j.job_code FROM job_applications a LEFT JOIN job_postings j ON a.job_id = j.id WHERE a.application_reference = ? LIMIT 1'
            );
            $stmt->execute([$application_reference]);
            $application = $stmt->fetch();
        }

        if (!$application) {
            $error = 'Rekod permohonan tidak dijumpai.';
            log_warning('Application record not found in thank you page', ['application_reference' => $application_reference]);
        } else {
            $job = [
                'job_title' => $application['job_title'] ?? '',
                'kod_gred' => $application['kod_gred'] ?? '',
                'job_id' => $application['job_id'] ?? 0,
                'job_code' => $application['job_code'] ?? ''
            ];

            log_public_action('Application thank you page accessed', 'VIEW', 'APPLICATION', $application['id'], [
                'application_reference' => $application_reference
            ]);
        }
    } catch (PDOException $e) {
        $error = 'Ralat mendapatkan maklumat permohonan.';
        log_error('Error fetching application details in thank you page', ['exception' => $e->getMessage(), 'application_reference' => $application_reference]);
    }
}

if (!$error && $pdo && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_confirmation_email'])) {
    $token_ok = isset($_POST['csrf_token']) && isset($_SESSION['csrf_token']) && hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token']);
    if (!$token_ok) {
        $_SESSION['error'] = 'Permintaan tidak sah.';
        header('Location: application-thank-you.php?ref=' . urlencode($application_reference ?? ($_POST['application_reference'] ?? '')));
        exit;
    }
    try {
        $ref = (string)($_POST['application_reference'] ?? ($application['application_reference'] ?? ''));
        if ($ref === '') { throw new Exception('Rujukan kosong'); }
        $stmtR = $pdo->prepare('SELECT a.*, j.job_title, j.kod_gred, j.job_code FROM application_application_main a LEFT JOIN job_postings j ON a.job_id = j.id WHERE a.application_reference = ? LIMIT 1');
        $stmtR->execute([$ref]);
        $appRow = $stmtR->fetch();
        if (!$appRow) {
            $stmtR = $pdo->prepare('SELECT a.*, j.job_title, j.kod_gred, j.job_code FROM job_applications a LEFT JOIN job_postings j ON a.job_id = j.id WHERE a.application_reference = ? LIMIT 1');
            $stmtR->execute([$ref]);
            $appRow = $stmtR->fetch();
        }
        if ($appRow && !empty($appRow['email']) && filter_var($appRow['email'], FILTER_VALIDATE_EMAIL)) {
            require_once 'includes/ApplicationEmailTemplates.php';
            require_once 'includes/MailSender.php';
            $htmlEmail = generateApplicationConfirmationEmail($appRow);
            $subjectEmail = 'Pengesahan Permohonan Jawatan - ' . (string)($appRow['application_reference'] ?? '');
            $mailer = new MailSender($config);
            $mailer->send((string)$appRow['email'], $subjectEmail, $htmlEmail);
            $_SESSION['email_sent_to'] = (string)$appRow['email'];
            $_SESSION['success'] = 'Emel pengesahan telah dihantar semula.';
        } else {
            $_SESSION['error'] = 'Tidak dapat menghantar emel: emel tidak sah.';
        }
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Ralat menghantar emel: ' . htmlspecialchars($e->getMessage());
    }
    header('Location: application-thank-you.php?ref=' . urlencode($application_reference ?? ($_POST['application_reference'] ?? '')));
    exit;
}

function formatJobId($id) {
    return 'JOB-' . str_pad((int)$id, 6, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permohonan Berjaya Di Hantar- Majlis Perbandaran Hulu Selangor</title>
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
        @media print {
            html, body { background: #ffffff !important; }
            .body-bg-image { background: #ffffff !important; }
            header, footer, .no-print { display: none !important; }
            .standard-container:not(.print-receipt) { display: none !important; }
            .print-receipt { display: block !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .print-receipt { display: none; }
        .print-header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #ffffff; padding: 20px; text-align: center; }
        .print-title { font-size: 20px; font-weight: 700; }
        .print-subtitle { font-size: 14px; opacity: 0.9; }
        .print-body { background: #ffffff; border: 1px solid #e5e7eb; border-top: none; padding: 20px; }
        .print-row { display: grid; grid-template-columns: 220px 1fr; gap: 12px; padding: 8px 0; border-bottom: 1px dashed #e5e7eb; }
        .print-label { color: #6b7280; font-size: 13px; }
        .print-value { color: #111827; font-weight: 600; font-size: 14px; }
        .print-footer { padding: 12px 20px; font-size: 12px; color: #4b5563; text-align: center; }
        @page { size: A4; margin: 12mm; }
        .print-preview .print-receipt { display: block; }
        .print-preview header, .print-preview footer, .print-preview .standard-container:not(.print-receipt) { display: none; }
    </style>
</head>
<body class="min-h-screen body-bg-image">
    <?php include 'header.php'; ?>

    <main class="standard-container px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-2xl mx-auto">
            <?php if ($error): ?>
                <!-- Error State -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-red-600 text-white p-6 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <h1 class="text-2xl font-bold">Ralat</h1>
                    </div>
                    
                    <div class="p-6 text-center">
                        <p class="text-gray-700 mb-6"><?php echo htmlspecialchars($error); ?></p>
                        <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition">
                            Kembali ke Halaman Utama
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Success State -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-green-600 text-white p-6 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h1 class="text-2xl font-bold">Permohonan Berjaya Dihantar</h1>
                        <p class="mt-2">Terima kasih kerana memohon jawatan di Majlis Perbandaran Hulu Selangor</p>
                        <?php if (!empty($application['email']) || !empty($_SESSION['email_sent_to'])): ?>
                        <p class="mt-2 text-sm">Emel pengesahan telah dihantar ke <strong><?php echo htmlspecialchars($_SESSION['email_sent_to'] ?? $application['email']); ?></strong></p>
                        <?php unset($_SESSION['email_sent_to']); endif; ?>
                    </div>
                    
                    <div class="p-6">
                        <!-- Application Details -->
                        <div class="bg-green-50 rounded-lg p-4 mb-6">
                            <h3 class="font-semibold text-green-800 mb-3">Maklumat Permohonan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <?php if ($application['payment_reference']): ?>
                                <div>
                                    <span class="text-gray-600">Rujukan Pembayaran:</span>
                                    <div class="font-medium"><?php echo htmlspecialchars($application['payment_reference']); ?></div>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <span class="text-gray-600">Status:</span>
                                    <div class="font-medium text-blue-600">Permohonan Diterima</div>
                                </div>
                            </div>
                        </div>


                        <div class="border border-blue-200 rounded-lg p-4 mb-6 bg-blue-50">
                            <h3 class="font-semibold text-blue-800 mb-3">Salinan Emel Pengesahan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Nombor Rujukan:</span>
                                    <div class="font-medium"><?php echo htmlspecialchars($application['application_reference']); ?></div>
                                </div>
                                <div>
                                    <span class="text-gray-600">Tarikh Permohonan:</span>
                                    <div class="font-medium"><?php 
                                        $date_field2 = $application['created_at'] ?? $application['application_date'] ?? $application['submission_date'] ?? $application['submitted_at'] ?? null;
                                        if ($date_field2 && !empty($date_field2)) {
                                            $timestamp2 = strtotime($date_field2);
                                            echo ($timestamp2 !== false) ? date('d/m/Y H:i', $timestamp2) : 'N/A';
                                        } else { echo 'N/A'; }
                                    ?></div>
                                </div>
                                <div>
                                    <span class="text-gray-600">Jawatan Dipohon:</span>
                                    <div class="font-medium"><?php echo htmlspecialchars($job['job_title'] ?? 'N/A'); ?></div>
                                </div>
                                <div>
                                    <span class="text-gray-600">Kod Gred:</span>
                                    <div class="font-medium"><?php echo htmlspecialchars($job['kod_gred'] ?? 'N/A'); ?></div>
                                </div>
                                <div>
                                    <span class="text-gray-600">Kod Jawatan:</span>
                                    <div class="font-medium"><?php echo htmlspecialchars($job['job_code'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-600 mt-3">Ini adalah salinan kandungan utama emel pengesahan yang telah dihantar ke emel anda.</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-4">
                            <div class="text-center">
                                <?php if (!empty($config['navigation']['show_status_check'])): ?>
                                <a href="semak-status.php?app_ref=<?php echo urlencode($application['application_reference']); ?>&nric=<?php echo urlencode($application['nombor_ic'] ?? ''); ?>" 
                                   class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg transition">
                                    Semak Status Permohonan
                                </a>
                                <p class="text-sm text-gray-600 mt-2">
                                    Anda boleh menyemak status permohonan anda pada bila-bila masa
                                </p>
                                <?php endif; ?>
                                    <div class="mt-4 flex justify-center space-x-3">
                                        <button type="button" onclick="window.print()" class="bg-gray-700 hover:bg-gray-800 text-white font-medium py-2 px-6 rounded-lg transition">Cetak Penerimaan borang</button>
                                        <a href="download-receipt.php?ref=<?php echo urlencode($application['application_reference']); ?>" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg transition">Muat Turun PDF</a>
                                        <form method="post" class="inline-block">
                                            <input type="hidden" name="resend_confirmation_email" value="1">
                                            <input type="hidden" name="application_reference" value="<?php echo htmlspecialchars($application['application_reference']); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition">Hantar Semula Emel Penerimaan</button>
                                        </form>
                                    </div>
                            </div>
                            
                            <!-- Additional actions -->
                            <div class="flex justify-center space-x-4">
                                <a href="view-job.php?job_code=<?php echo urlencode($job['job_code'] ?? ''); ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Lihat Maklumat Jawatan
                                </a>
                                <a href="index.php" 
                                   class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                                    Halaman Utama
                                </a>
                            </div>
                        </div>

                        <!-- Important Notes -->
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <h4 class="font-medium text-blue-800 mb-2">📋 Nota Penting</h4>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>• Simpan rujukan permohonan ini untuk rekod anda</li>
                                <li>• Hanya calon yang layak akan dipanggil untuk temu duga</li>
                                <li>• Untuk sebarang pertanyaan, sila hubungi pihak MPHS</li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <div class="standard-container print-receipt">
        <div class="print-header">
            <div class="print-title">Penerimaan Permohonan</div>
            <div class="print-subtitle">Sistem eJawatan • Majlis Perbandaran Hulu Selangor</div>
        </div>
        <div class="print-body">
            <div class="print-row"><div class="print-label">Rujukan Permohonan</div><div class="print-value"><?php echo htmlspecialchars($application['application_reference'] ?? ''); ?></div></div>
            <div class="print-row"><div class="print-label">Tarikh Permohonan</div><div class="print-value"><?php 
                $date_field = $application['created_at'] ?? $application['application_date'] ?? $application['submission_date'] ?? $application['submitted_at'] ?? null;
                if ($date_field && !empty($date_field)) { $ts = strtotime($date_field); echo ($ts !== false) ? date('d/m/Y H:i', $ts) : 'N/A'; } else { echo 'N/A'; }
            ?></div></div>
            <div class="print-row"><div class="print-label">Nama Pemohon</div><div class="print-value"><?php echo htmlspecialchars($application['nama_penuh'] ?? ''); ?></div></div>
            <div class="print-row"><div class="print-label">Emel Pemohon</div><div class="print-value"><?php echo htmlspecialchars($application['email'] ?? ''); ?></div></div>
            <div class="print-row"><div class="print-label">Jawatan</div><div class="print-value"><?php echo htmlspecialchars($job['job_title'] ?? ''); ?></div></div>
            <div class="print-row"><div class="print-label">Kod Gred</div><div class="print-value"><?php echo htmlspecialchars($job['kod_gred'] ?? ''); ?></div></div>
            <div class="print-row"><div class="print-label">Kod Jawatan</div><div class="print-value"><?php echo htmlspecialchars($job['job_code'] ?? ''); ?></div></div>
            <div class="print-row"><div class="print-label">Status</div><div class="print-value">Permohonan Diterima</div></div>
        </div>
        <div class="print-footer">Resit penerimaan ini dijana secara automatik. Untuk maklumat lanjut, rujuk halaman Semak Status Permohonan.</div>
    </div>

    
    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html>
