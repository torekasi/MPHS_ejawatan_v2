<?php
// Payment callback handler for ToyyibPay
require_once 'includes/ErrorHandler.php';

// Get database connection from config
$result = require 'config.php';
$config = $result;

// Initialize variables
$pdo = null;

// Log the callback request
log_public_action('Payment callback received', 'CALLBACK', 'PAYMENT', null, [
    'method' => $_SERVER['REQUEST_METHOD'],
    'get_data' => $_GET,
    'post_data' => $_POST,
    'headers' => getallheaders(),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

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
    log_error('Database connection error in payment callback', ['exception' => $e->getMessage()]);
    http_response_code(500);
    echo "Database connection error";
    exit;
}

// Process callback data
$callback_data = $_POST; // ToyyibPay sends data via POST

// Validate required callback parameters
$required_params = ['billcode', 'order_id', 'status_id', 'msg'];
foreach ($required_params as $param) {
    if (!isset($callback_data[$param])) {
        log_error('Missing required callback parameter', [
            'missing_param' => $param,
            'callback_data' => $callback_data
        ]);
        http_response_code(400);
        echo "Missing required parameter: $param";
        exit;
    }
}

$bill_code = $callback_data['billcode'];
$order_id = $callback_data['order_id']; // This should be our payment_reference
$status_id = (int)$callback_data['status_id'];
$status_msg = $callback_data['msg'];
$transaction_id = $callback_data['transaction_id'] ?? null;
$payment_date = $callback_data['transaction_time'] ?? date('Y-m-d H:i:s');

// Map ToyyibPay status to our status
// ToyyibPay status_id: 1=Success, 2=Pending, 3=Failed
// Our internal status_id: 1=Success/Paid, 0=Pending, -1=Failed
$payment_status_map = [
    1 => ['status' => 'paid', 'status_id' => 1],      // ToyyibPay Success -> Our Success (status_id=1)
    2 => ['status' => 'pending', 'status_id' => 0],   // ToyyibPay Pending -> Our Pending (status_id=0)
    3 => ['status' => 'failed', 'status_id' => -1],   // ToyyibPay Failed -> Our Failed (status_id=-1)
];

$mapped = $payment_status_map[$status_id] ?? ['status' => 'failed', 'status_id' => 0];
$payment_status = $mapped['status'];
$internal_status_id = $mapped['status_id'];

// Find the payment transaction
try {
    $stmt = $pdo->prepare("
        SELECT * FROM payment_transactions 
        WHERE payment_reference = ? OR toyyibpay_bill_id = ?
        LIMIT 1
    ");
    $stmt->execute([$order_id, $bill_code]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        log_error('Payment transaction not found in callback', [
            'order_id' => $order_id,
            'bill_code' => $bill_code,
            'callback_data' => $callback_data
        ]);
        http_response_code(404);
        echo "Payment transaction not found";
        exit;
    }
    
    // Update payment transaction
    $update_data = [
        'payment_status' => $payment_status,
        'transaction_id' => $transaction_id,
        'toyyibpay_reference' => $bill_code,
        'callback_data' => json_encode($callback_data),
        'notes' => $status_msg,
        'status_id' => $internal_status_id
    ];
    
    // Set payment date if payment is successful
    if ($payment_status === 'paid') {
        $update_data['payment_date'] = $payment_date;
    }
    
    $update_sql = "
        UPDATE payment_transactions 
        SET payment_status = ?, 
            transaction_id = ?, 
            toyyibpay_reference = ?, 
            callback_data = ?, 
            notes = ?,
            status_id = ?,
            " . ($payment_status === 'paid' ? 'payment_date = ?,' : '') . "
            updated_at = NOW()
        WHERE id = ?
    ";
    
    $update_params = [
        $payment_status,
        $transaction_id,
        $bill_code,
        json_encode($callback_data),
        $status_msg,
        $internal_status_id
    ];
    
    if ($payment_status === 'paid') {
        $update_params[] = $payment_date;
    }
    
    $update_params[] = $payment['id'];
    
    $stmt = $pdo->prepare($update_sql);
    $stmt->execute($update_params);
    
    log_public_action('Payment status updated', 'UPDATE', 'PAYMENT', $payment['id'], [
        'old_status' => $payment['payment_status'],
        'new_status' => $payment_status,
        'transaction_id' => $transaction_id,
        'toyyibpay_reference' => $bill_code
    ]);
    
    // Send confirmation email if payment is successful
    if ($payment_status === 'paid') {
        try {
            // Get job details for email
            $stmt = $pdo->prepare("SELECT * FROM job_postings WHERE id = ?");
            $stmt->execute([$payment['job_id']]);
            $job = $stmt->fetch();
            
            if ($job) {
                // Send professional payment confirmation email
                require_once 'includes/EmailTemplateHelper.php';
                $emailHelper = new EmailTemplateHelper($config);
                
                $email_sent = $emailHelper->sendPaymentConfirmation($payment, $job);
                
                if ($email_sent) {
                    log_public_action('Payment confirmation email sent successfully', 'EMAIL', 'PAYMENT', $payment['id'], [
                        'recipient' => $payment['applicant_email'],
                        'job_title' => $job['job_title'],
                        'amount' => $payment['amount'],
                        'payment_reference' => $payment['payment_reference']
                    ]);
                } else {
                    log_warning('Payment confirmation email failed to send', [
                        'recipient' => $payment['applicant_email'],
                        'payment_id' => $payment['id']
                    ]);
                }
            }
        } catch (Exception $e) {
            log_error('Error sending payment confirmation email', [
                'exception' => $e->getMessage(),
                'payment_id' => $payment['id']
            ]);
        }
    } elseif ($payment_status === 'failed') {
        // Send payment failed notification
        try {
            $stmt = $pdo->prepare("SELECT * FROM job_postings WHERE id = ?");
            $stmt->execute([$payment['job_id']]);
            $job = $stmt->fetch();
            
            if ($job) {
                require_once 'includes/EmailTemplateHelper.php';
                $emailHelper = new EmailTemplateHelper($config);
                $emailHelper->sendPaymentFailed($payment, $job);
            }
        } catch (Exception $e) {
            log_error('Error sending payment failed email', [
                'exception' => $e->getMessage(),
                'payment_id' => $payment['id']
            ]);
        }
    }
    
    // Return success response to ToyyibPay
    http_response_code(200);
    echo "OK";
    
} catch (PDOException $e) {
    log_error('Database error in payment callback', [
        'exception' => $e->getMessage(),
        'order_id' => $order_id,
        'bill_code' => $bill_code
    ]);
    http_response_code(500);
    echo "Database error";
    exit;
} catch (Exception $e) {
    log_error('General error in payment callback', [
        'exception' => $e->getMessage(),
        'order_id' => $order_id,
        'bill_code' => $bill_code
    ]);
    http_response_code(500);
    echo "Error processing callback";
    exit;
}
?>
