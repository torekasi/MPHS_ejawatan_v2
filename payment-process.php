<?php
// Payment processing script for ToyyibPay integration
require_once 'includes/ErrorHandler.php';

// Get database connection from config
$result = require 'config.php';
$config = $result;

// Initialize variables
$pdo = null;
$error = '';

// Check if payment is enabled
if (!isset($config['payment']['enabled']) || !$config['payment']['enabled']) {
    header('Location: index.php');
    exit;
}

// Check if payment data is in session
session_start();
if (!isset($_SESSION['payment_data']) || empty($_SESSION['payment_data'])) {
    header('Location: index.php');
    exit;
}

// Extract payment data from session
$payment_data = $_SESSION['payment_data'];

// Validate required session data
$required_fields = ['job_id', 'amount', 'applicant_name', 'applicant_nric', 'applicant_email', 'applicant_phone'];
foreach ($required_fields as $field) {
    if (!isset($payment_data[$field]) || empty($payment_data[$field])) {
        $error = "Medan '$field' diperlukan.";
        break;
    }
}

if (!$error) {
    // Sanitize and validate input data
    $job_id = filter_var($payment_data['job_id'], FILTER_VALIDATE_INT);
    $amount = filter_var($payment_data['amount'], FILTER_VALIDATE_FLOAT);
    $applicant_name = trim(strtoupper($payment_data['applicant_name']));
    $applicant_nric = preg_replace('/[^0-9]/', '', $payment_data['applicant_nric']);
    $applicant_email = filter_var(trim($payment_data['applicant_email']), FILTER_VALIDATE_EMAIL);
    $applicant_phone = preg_replace('/[^0-9]/', '', $payment_data['applicant_phone']);
    
    // Validate data
    if (!$job_id || $job_id <= 0) {
        $error = 'Kod jawatan tidak sah.';
    } elseif (!$amount || $amount != $config['payment']['amount']) {
        $error = 'Jumlah pembayaran tidak sah.';
    } elseif (strlen($applicant_name) < 3 || strlen($applicant_name) > 255) {
        $error = 'Nama penuh mestilah antara 3 hingga 255 aksara.';
    } elseif (strlen($applicant_nric) !== 12) {
        $error = 'No. Kad Pengenalan mestilah 12 digit.';
    } elseif (!$applicant_email) {
        $error = 'Alamat emel tidak sah.';
    } elseif (strlen($applicant_phone) < 7 || strlen($applicant_phone) > 15) {
        $error = 'Nombor telefon mestilah antara 7 hingga 15 digit.';
    }
}

// Connect to database
if (!$error) {
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    } catch (PDOException $e) {
        $error = 'Ralat sambungan ke pangkalan data. Sila cuba sebentar lagi.';
        log_error('Database connection error in payment processing', ['exception' => $e->getMessage(), 'job_id' => $job_id]);
    }
}

// Verify job exists and is still active
if (!$error && $pdo) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
        $stmt->execute([$job_id]);
        $job = $stmt->fetch();
        
        if (!$job) {
            $error = 'Jawatan tidak dijumpai.';
        } else {
            // Check if job is still open for applications
            $today = new DateTime(date('Y-m-d'));
            $ad_close_date = new DateTime($job['ad_close_date']);
            
            if ($ad_close_date < $today) {
                $error = 'Permohonan untuk jawatan ini telah ditutup.';
            }
        }
    } catch (PDOException $e) {
        $error = 'Ralat semakan maklumat jawatan.';
        log_error('Error verifying job for payment', ['exception' => $e->getMessage(), 'job_id' => $job_id]);
    }
}

// Generate unique payment reference
if (!$error) {
    $payment_reference = 'PAY-MPHS-' . date('Y') . '-' . str_pad($job_id, 3, '0', STR_PAD_LEFT) . '-' . time();
    $bill_code = 'MPHS' . time() . rand(100, 999);
}

// Create payment transaction record
if (!$error && $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions (
                job_id, payment_reference, bill_code, applicant_name, applicant_nric,
                applicant_email, applicant_phone, amount, payment_status, status_id,
                expires_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())
        ");
        
        $stmt->execute([
            $job_id, $payment_reference, $bill_code, $applicant_name, $applicant_nric,
            $applicant_email, $applicant_phone, $amount
        ]);
        
        $payment_id = $pdo->lastInsertId();
        
        log_public_action('Payment transaction created', 'CREATE', 'PAYMENT', $payment_id, [
            'payment_reference' => $payment_reference,
            'job_id' => $job_id,
            'amount' => $amount,
            'applicant_email' => $applicant_email
        ]);
        
    } catch (PDOException $e) {
        $error = 'Ralat mencipta rekod pembayaran.';
        log_error('Error creating payment transaction', ['exception' => $e->getMessage(), 'payment_reference' => $payment_reference]);
    }
}

// Create ToyyibPay bill
if (!$error) {
    try {
        // Build and sanitize fields per ToyyibPay constraints
        // Ref: https://toyyibpay.com/apireference/
        $rawBillName = 'Permohonan ' . strtoupper($job['job_title'] ?? 'JAWATAN');
        $sanitizedBillName = preg_replace('/[^A-Za-z0-9_ ]/', '', $rawBillName);
        $sanitizedBillName = substr($sanitizedBillName, 0, 30); // Max 30 chars

        $rawBillDesc = 'Yuran permohonan: ' . strtoupper($job['job_title'] ?? 'JAWATAN') . ' (Kod Gred: ' . ($job['kod_gred'] ?? '') . ')';
        $sanitizedBillDesc = preg_replace('/[^A-Za-z0-9_ ]/', '', $rawBillDesc);
        $sanitizedBillDesc = substr($sanitizedBillDesc, 0, 100); // Max 100 chars

        // Prepare ToyyibPay API data
        $toyyibpay_data = [
            'userSecretKey' => $config['payment']['user_secret_key'],
            'categoryCode' => $config['payment']['category_code'],
            'billName' => $sanitizedBillName,
            'billDescription' => $config['payment']['bill_description'],
            'billPriceSetting' => 1, // Fixed price
            'billPayorInfo' => 1, // Collect payer info
            'billAmount' => (int) round($amount * 100), // Convert to cents
            'billReturnUrl' => $config['payment']['return_url'] . '?ref=' . urlencode($payment_reference),
            'billCallbackUrl' => $config['payment']['callback_url'],
            'billExternalReferenceNo' => $payment_reference,
            'billTo' => $applicant_name,
            'billEmail' => $applicant_email,
            'billPhone' => $applicant_phone,
            'billSplitPayment' => 0,
            'billSplitPaymentArgs' => '',
            'billPaymentChannel' => '2', // 0=FPX,1=CC,2=Both
            'billContentEmail' => substr('Terima kasih kerana memohon jawatan di Majlis Perbandaran Hulu Selangor.', 0, 1000),
            'billChargeToCustomer' => 1, // Charge processing fee to customer
            'billExpiryDate' => date('d-m-Y H:i:s', strtotime('+30 minutes')),
            'billExpiryDays' => 0
        ];
        
        // Test network connectivity first
        $can_reach_toyyibpay = false;
        
        // Test if we can reach ToyyibPay API (derive host from configured API URL)
        if (function_exists('gethostbyname')) {
            $apiHost = parse_url($config['payment']['api_url'] ?? '', PHP_URL_HOST);
            if (!$apiHost) { $apiHost = 'toyyibpay.com'; }
            $resolved_ip = gethostbyname($apiHost);
            $can_reach_toyyibpay = ($resolved_ip !== $apiHost);
        }
        
        // Make API call to ToyyibPay
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $config['payment']['api_url'] . 'createBill',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($toyyibpay_data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
            // Add SSL options for better connectivity
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'MPHS Payment Gateway/1.0'
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);
        
        if ($curl_error) {
            throw new Exception("CURL Error: " . $curl_error);
        }
        
        if ($http_code !== 200) {
            throw new Exception("HTTP Error: " . $http_code);
        }
        
        $toyyibpay_response = json_decode($response, true);
        
        if (!$toyyibpay_response) {
            log_error('ToyyibPay returned invalid JSON response', [
                'raw_response' => substr($response, 0, 500),
                'http_code' => $http_code,
                'payment_reference' => $payment_reference
            ]);
            throw new Exception("Invalid JSON response from ToyyibPay");
        }
        
        log_public_action('ToyyibPay API Response', 'API', 'PAYMENT', $payment_id, [
            'response' => $toyyibpay_response,
            'http_code' => $http_code
        ]);
        
        // Check if bill creation was successful
        if (isset($toyyibpay_response[0]['BillCode'])) {
            $toyyibpay_bill_code = $toyyibpay_response[0]['BillCode'];
            
            // Update payment record with ToyyibPay bill code
            $stmt = $pdo->prepare("
                UPDATE payment_transactions 
                SET toyyibpay_bill_id = ?, callback_data = ? 
                WHERE payment_reference = ?
            ");
            $stmt->execute([$toyyibpay_bill_code, json_encode($toyyibpay_response), $payment_reference]);
            
            // Check if simulation mode is enabled (for testing without real payment)
            $force_simulation = isset($config['payment']['simulation_mode']) && $config['payment']['simulation_mode'] === true;
            
            // Redirect to ToyyibPay payment page - Environment aware
            if ($force_simulation) {
                // For simulation mode, mark payment as paid and redirect to thank you page
                $stmt = $pdo->prepare("
                    UPDATE payment_transactions 
                    SET payment_status = 'paid', payment_date = NOW(), payment_method = 'simulation' 
                    WHERE payment_reference = ?
                ");
                $stmt->execute([$payment_reference]);
                
                $payment_url = $config['base_url'] . '/payment-thank-you.php?ref=' . urlencode($payment_reference) . '&status=paid&simulation=1';
            } else {
                // Real ToyyibPay integration - redirect to actual payment page
                $payment_url = $config['payment']['payment_url'] . $toyyibpay_bill_code;
            }
        
            log_public_action('Redirecting to ToyyibPay', 'REDIRECT', 'PAYMENT', $payment_id, [
                'bill_code' => $toyyibpay_bill_code,
                'payment_url' => $payment_url
            ]);
            
            header('Location: ' . $payment_url);
            exit;
            
        } else {
            // Handle ToyyibPay error
            $error_msg = 'Ralat mencipta bil pembayaran.';
            $error_details = [];
            
            // Extract detailed error information from ToyyibPay response
            if (is_array($toyyibpay_response)) {
                if (isset($toyyibpay_response[0]['error'])) {
                    $error_details['api_error'] = $toyyibpay_response[0]['error'];
                    $error_msg .= ' Mesej: ' . $toyyibpay_response[0]['error'];
                }
                if (isset($toyyibpay_response[0]['msg'])) {
                    $error_details['api_message'] = $toyyibpay_response[0]['msg'];
                }
            }
            
            log_error('ToyyibPay API Bill Creation Failed', [
                'payment_reference' => $payment_reference,
                'toyyibpay_response' => $toyyibpay_response,
                'error_details' => $error_details,
                'sent_data' => $toyyibpay_data
            ]);
            
            throw new Exception($error_msg);
        }
    
    } catch (Exception $e) {
        $error = 'Ralat menghubungi gateway pembayaran: ' . $e->getMessage();
        log_error('ToyyibPay API Error', [
            'exception' => $e->getMessage(),
            'payment_reference' => $payment_reference,
            'toyyibpay_data' => $toyyibpay_data ?? null,
            'response' => $response ?? null
        ]);
        
        // Update payment status to failed
        if ($pdo && isset($payment_reference)) {
            try {
                $stmt = $pdo->prepare("UPDATE payment_transactions SET payment_status = 'failed', notes = ? WHERE payment_reference = ?");
                $stmt->execute([$e->getMessage(), $payment_reference]);
            } catch (PDOException $db_e) {
                log_error('Error updating payment status to failed', ['exception' => $db_e->getMessage()]);
            }
        }
    }
}

// If we reach here, there was an error
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ralat Pembayaran - Majlis Perbandaran Hulu Selangor</title>
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
    </style>
</head>
<body class="min-h-screen body-bg-image">
    <?php include 'header.php'; ?>

    <main class="standard-container px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-md mx-auto">
            <!-- Error Message -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-red-600 text-white p-6 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <h1 class="text-2xl font-bold">Ralat Pembayaran</h1>
                </div>
                
                <div class="p-6">
                    <div class="text-center mb-6">
                        <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($error); ?></p>
                        <p class="text-sm text-gray-500">Sila cuba lagi atau hubungi pihak MPHS untuk bantuan.</p>
                    </div>
                    
                    <div class="space-y-3">
                        <a href="<?php echo isset($payment_data['job_code']) ? 'payment-form.php?job_code=' . urlencode($payment_data['job_code']) : 'index.php'; ?>"
                           class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg text-center transition">
                            Cuba Lagi
                        </a>
                        
                        <a href="index.php" 
                           class="block w-full bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-3 px-4 rounded-lg text-center transition">
                            Kembali ke Halaman Utama
                        </a>
                    </div>
                    
                    <?php if (isset($payment_reference)): ?>
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-600">
                            Rujukan: <?php echo htmlspecialchars($payment_reference); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html>
