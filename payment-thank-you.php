<?php
// Payment thank you page - shows payment status and allows proceeding to application
require_once 'includes/ErrorHandler.php';

// Get database connection from config
$result = require 'config.php';
$config = $result;

// Initialize variables
$pdo = null;
$error = '';
$payment = null;
$job = null;
$is_simulation = isset($_GET['simulation']) && $_GET['simulation'] === '1';

// Check if payment reference is provided
if (!isset($_GET['ref']) || empty($_GET['ref'])) {
    $error = 'Rujukan pembayaran tidak ditemui.';
} else {
    $payment_reference = $_GET['ref'];
    
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
        $error = 'Ralat sambungan ke pangkalan data.';
        log_error('Database connection error in payment thank you page', ['exception' => $e->getMessage()]);
    }
    
    // Fetch payment details
    if (!$error && $pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT pt.*, jp.job_title, jp.job_code, jp.kod_gred, jp.ad_close_date
                FROM payment_transactions pt
                LEFT JOIN job_postings jp ON pt.job_id = jp.id
                WHERE pt.payment_reference = ?
                LIMIT 1
            ");
            $stmt->execute([$payment_reference]);
            $payment = $stmt->fetch();
            
            if (!$payment) {
                $error = 'Rekod pembayaran tidak dijumpai.';
                log_warning('Payment record not found in thank you page', ['payment_reference' => $payment_reference]);
            } else {
                $job = $payment; // Contains job info from LEFT JOIN

                // Harmonize status using return URL parameters if provided by ToyyibPay
                // Ref: Return URL Parameter in ToyyibPay docs
                // ToyyibPay status_id: 1 = Success, 2 = Pending, 3 = Failed
                $incoming_status = null;
                $incoming_status_id = null;
                if (isset($_GET['status_id'])) {
                    $incoming_status_id = (int) $_GET['status_id'];
                    log_public_action('ToyyibPay status_id received', 'STATUS', 'PAYMENT', null, [
                        'status_id' => $incoming_status_id,
                        'payment_reference' => $payment_reference
                    ]);
                } elseif (isset($_GET['status'])) {
                    // Some integrations provide status as text
                    $statusText = strtolower((string) $_GET['status']);
                    if ($statusText === '1' || $statusText === 'success' || $statusText === 'paid') {
                        $incoming_status_id = 1;
                    } elseif ($statusText === '2' || $statusText === 'pending' || $statusText === 'process') {
                        $incoming_status_id = 2;
                    } elseif ($statusText === '3' || $statusText === 'failed' || $statusText === 'fail') {
                        $incoming_status_id = 3;
                    }
                    log_public_action('ToyyibPay status text received', 'STATUS', 'PAYMENT', null, [
                        'status_text' => $statusText,
                        'mapped_status_id' => $incoming_status_id,
                        'payment_reference' => $payment_reference
                    ]);
                }

                // Get transaction_id from URL if available
                $transaction_id = null;
                if (isset($_GET['transaction_id'])) {
                    $transaction_id = $_GET['transaction_id'];
                } elseif (isset($_GET['billcode'])) {
                    // Some integrations provide billcode as transaction_id
                    $transaction_id = $_GET['billcode'];
                }

                if ($incoming_status_id !== null) {
                    // Map ToyyibPay status_id to local status strings and numeric status_id
                    // ToyyibPay: 1=Success, 2=Pending, 3=Failed
                    // Our system: 1=Success/Paid, 0=Pending, -1=Failed
                    $map = [
                        1 => ['status' => 'paid', 'status_id' => 1],      // ToyyibPay Success -> Our Success
                        2 => ['status' => 'pending', 'status_id' => 0],   // ToyyibPay Pending -> Our Pending
                        3 => ['status' => 'failed', 'status_id' => -1],   // ToyyibPay Failed -> Our Failed
                    ];
                    $mapped = $map[$incoming_status_id] ?? null;
                    if ($mapped && ($mapped['status'] !== $payment['payment_status'] || $transaction_id)) {
                        // Update DB to reflect the most recent status from Return URL and store transaction_id
                        try {
                            $updateSql = "UPDATE payment_transactions SET 
                                payment_status = ?, 
                                status_id = ?, " .
                                ($transaction_id ? "transaction_id = ?, " : "") .
                                ($mapped['status'] === 'paid' ? "payment_date = NOW(), " : "") .
                                "updated_at = NOW() 
                                WHERE id = ?";
                            
                            $params = [$mapped['status'], $mapped['status_id']];
                            if ($transaction_id) {
                                $params[] = $transaction_id;
                            }
                            $params[] = $payment['id'];
                            
                            $stmt2 = $pdo->prepare($updateSql);
                            $stmt2->execute($params);
                            // Reflect change locally
                            $payment['payment_status'] = $mapped['status'];
                            $payment['status_id'] = $mapped['status_id'];
                        } catch (Exception $e) {
                            log_error('Failed to update payment status from return URL', [
                                'exception' => $e->getMessage(),
                                'payment_id' => $payment['id'],
                                'incoming_status_id' => $incoming_status_id,
                                'transaction_id' => $transaction_id
                            ]);
                        }
                    }
                }

                log_public_action('Payment thank you page accessed', 'VIEW', 'PAYMENT', $payment['id'], [
                    'payment_reference' => $payment_reference,
                    'payment_status' => $payment['payment_status'],
                    'transaction_id' => $transaction_id ?? $payment['transaction_id'] ?? null
                ]);
            }
        } catch (PDOException $e) {
            $error = 'Ralat mendapatkan maklumat pembayaran.';
            log_error('Error fetching payment details in thank you page', ['exception' => $e->getMessage(), 'payment_reference' => $payment_reference]);
        }
    }
}

// Helper function to format job ID for display
function formatJobId($id) {
    return 'JOB-' . str_pad($id, 6, '0', STR_PAD_LEFT);
}

// Function to get status icon and color
function getPaymentStatusInfo($status) {
    switch ($status) {
        case 'paid':
            return [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'color' => 'green',
                'bg_color' => 'bg-green-600',
                'text_color' => 'text-green-600',
                'bg_light' => 'bg-green-100',
                'title' => 'Pembayaran Berjaya',
                'message' => 'Terima kasih! Pembayaran anda telah berjaya diproses.'
            ];
        case 'pending':
            return [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'color' => 'yellow',
                'bg_color' => 'bg-yellow-600',
                'text_color' => 'text-yellow-600',
                'bg_light' => 'bg-yellow-100',
                'title' => 'Pembayaran Dalam Proses',
                'message' => 'Pembayaran anda sedang diproses. Sila tunggu beberapa minit.'
            ];
        case 'failed':
        default:
            return [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'color' => 'red',
                'bg_color' => 'bg-red-600',
                'text_color' => 'text-red-600',
                'bg_light' => 'bg-red-100',
                'title' => 'Pembayaran Gagal',
                'message' => 'Pembayaran anda tidak berjaya. Sila cuba lagi.'
            ];
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pembayaran - Majlis Perbandaran Hulu Selangor</title>
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
        .pulse-animation {
            animation: pulse 2s infinite;
        }
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
            <?php else: 
                $status_info = getPaymentStatusInfo($payment['payment_status']);
            ?>
                <!-- Payment Status Display -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="<?php echo $status_info['bg_color']; ?> text-white p-6 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 <?php echo $payment['payment_status'] === 'pending' ? 'pulse-animation' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $status_info['icon']; ?>
                        </svg>
                        <h1 class="text-2xl font-bold"><?php echo $status_info['title']; ?></h1>
                        <p class="mt-2"><?php echo $status_info['message']; ?></p>
                    </div>
                    
                    <div class="p-6">
                        <!-- Payment Details -->
                        <div class="<?php echo $status_info['bg_light']; ?> rounded-lg p-4 mb-6">
                            <h3 class="font-semibold <?php echo $status_info['text_color']; ?> mb-3">Maklumat Pembayaran</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Rujukan Pembayaran:</span>
                                    <div class="font-medium"><?php echo htmlspecialchars($payment['payment_reference']); ?></div>
                                </div>
                                <div>
                                    <span class="text-gray-600">Jumlah:</span>
                                    <div class="font-medium">RM <?php echo number_format($payment['amount'], 2); ?></div>
                                </div>
                                <div>
                                    <span class="text-gray-600">Tarikh:</span>
                                    <div class="font-medium"><?php 
                                        $date_field = $payment['created_at'] ?? null;
                                        if ($date_field && !empty($date_field)) {
                                            $timestamp = strtotime($date_field);
                                            echo ($timestamp !== false) ? date('d/m/Y H:i', $timestamp) : 'N/A';
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?></div>
                                </div>
                                <div>
                                    <span class="text-gray-600">Status:</span>
                                    <div class="font-medium <?php echo $status_info['text_color']; ?>">
                                        <?php 
                                        switch ($payment['payment_status']) {
                                            case 'paid': echo 'Berjaya'; break;
                                            case 'pending': echo 'Dalam Proses'; break;
                                            case 'failed': echo 'Gagal'; break;
                                            default: echo 'Tidak Diketahui';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php if ($payment['transaction_id']): ?>
                                <div class="md:col-span-2">
                                    <span class="text-gray-600">ID Transaksi:</span>
                                    <div class="font-medium"><?php echo htmlspecialchars($payment['transaction_id']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Job Details -->
                        <div class="border border-gray-200 rounded-lg p-4 mb-6">
                            <h3 class="font-semibold text-gray-800 mb-3">Maklumat Jawatan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Jawatan:</span>
                                    <div class="font-medium"><?php echo htmlspecialchars($job['job_title']); ?></div>
                                </div>
                                <div>
                                    <span class="text-gray-600">Kod Gred:</span>
                                    <div class="font-medium"><?php echo htmlspecialchars($job['kod_gred']); ?></div>
                                </div>
                                <div>
                                    <span class="text-gray-600">Kod Jawatan:</span>
                                    <div class="font-medium"><?php echo htmlspecialchars($job['job_code']); ?></div>
                                </div>
                                <div>
                                    <span class="text-gray-600">Tarikh Tutup:</span>
                                    <div class="font-medium"><?php 
                                        $date_field = $job['ad_close_date'] ?? null;
                                        if ($date_field && !empty($date_field)) {
                                            $timestamp = strtotime($date_field);
                                            echo ($timestamp !== false) ? date('d/m/Y', $timestamp) : 'N/A';
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-4">
                            <?php if ($payment['payment_status'] === 'paid'): ?>
                                <!-- Payment successful - can proceed to application -->
                                <div class="text-center">
                                    <a href="job-application-1.php?job_id=<?php echo urlencode($payment['job_id']); ?>&payment_ref=<?php echo urlencode($payment['payment_reference']); ?>&name=<?php echo urlencode($payment['applicant_name']); ?>&phone=<?php echo urlencode($payment['applicant_phone']); ?>&email=<?php echo urlencode($payment['applicant_email']); ?>" 
                                       class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg transition">
                                        Teruskan ke Borang Permohonan
                                    </a>
                                    <p class="text-sm text-gray-600 mt-2">
                                        Anda kini boleh meneruskan permohonan jawatan anda
                                    </p>
                                </div>
                            <?php elseif ($payment['payment_status'] === 'pending'): ?>
                                <!-- Payment pending - wait or refresh -->
                                <div class="text-center">
                                    <button onclick="window.location.reload()" 
                                            class="inline-block bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-3 px-8 rounded-lg transition">
                                        Muat Semula Status
                                    </button>
                                    <p class="text-sm text-gray-600 mt-2">
                                        Sila tekan butang di atas untuk menyemak status pembayaran
                                    </p>
                                </div>
                            <?php else: ?>
                                <!-- Payment failed - retry -->
                                <div class="text-center">
                                    <a href="payment-form.php?job_id=<?php echo urlencode($payment['job_id']); ?>" 
                                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg transition">
                                        Cuba Bayar Semula
                                    </a>
                                    <p class="text-sm text-gray-600 mt-2">
                                        Sila cuba lagi atau hubungi pihak MPHS untuk bantuan
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Additional actions -->
                            <!--div class="flex justify-center space-x-4">
                                <a href="view-job.php?job_code=<?php echo urlencode($job['job_code'] ?? ''); ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Lihat Maklumat Jawatan
                                </a>
                                <a href="index.php" 
                                   class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                                    Halaman Utama
                                </a>
                            </div-->
                        </div>

                        <!-- Important Notes -->
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <h4 class="font-medium text-blue-800 mb-2">📋 Nota Penting</h4>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>• Simpan rujukan pembayaran ini untuk rekod anda</li>
                                <?php if ($payment['payment_status'] === 'paid'): ?>
                                <li>• Resit pembayaran akan dihantar ke alamat emel: <?php echo htmlspecialchars($payment['applicant_email']); ?></li>
                                <?php endif; ?>
                                <li>• Untuk sebarang pertanyaan, sila hubungi pihak MPHS</li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- No auto-refresh or polling -->
</body>
</html>
