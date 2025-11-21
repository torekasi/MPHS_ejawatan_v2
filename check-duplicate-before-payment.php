<?php
// Check for duplicate applications before proceeding to payment
require_once 'includes/ErrorHandler.php';
require_once 'includes/DuplicateApplicationChecker.php';

// Start session for storing data
session_start();

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Get database connection from config
$config = require 'config.php';

// Initialize variables
$pdo = null;
$error = '';

// Validate required fields
$required_fields = ['applicant_nric', 'applicant_email', 'job_code'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        header('Location: payment-form.php?job_code=' . urlencode($_POST['job_code'] ?? '') . '&error=missing_fields');
        exit;
    }
}

// Extract and validate data
$nric = trim($_POST['applicant_nric']);
$email = trim($_POST['applicant_email']);
$job_code = trim($_POST['job_code']);
$applicant_name = trim($_POST['applicant_name'] ?? '');
$applicant_phone = trim($_POST['applicant_phone'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);

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
    log_error('Database connection error in duplicate check', ['exception' => $e->getMessage(), 'job_code' => $job_code]);
    header('Location: payment-form.php?job_code=' . urlencode($job_code) . '&error=db_error');
    exit;
}

// Get job_id from job_code for database operations
try {
    $stmt = $pdo->prepare('SELECT id FROM job_postings WHERE job_code = ? LIMIT 1');
    $stmt->execute([$job_code]);
    $job_result = $stmt->fetch();

    if (!$job_result) {
        log_error('Job not found for job_code', ['job_code' => $job_code]);
        header('Location: payment-form.php?job_code=' . urlencode($job_code) . '&error=job_not_found');
        exit;
    }

    $job_id = $job_result['id'];
} catch (PDOException $e) {
    log_error('Error fetching job details', ['exception' => $e->getMessage(), 'job_code' => $job_code]);
    header('Location: payment-form.php?job_code=' . urlencode($job_code) . '&error=db_error');
    exit;
}

// Check for duplicate application
$checker = new DuplicateApplicationChecker($pdo);
$duplicate_check = $checker->checkBeforePayment($nric, $email, $job_id);

// Log the duplicate check result for debugging
log_info('Duplicate check result', [
    'nric_last_4' => substr($nric, -4),
    'job_code' => $job_code,
    'job_id' => $job_id,
    'status' => $duplicate_check['status'],
    'message' => $duplicate_check['message'] ?? 'No message'
]);

if ($duplicate_check['status'] === 'payment_exists') {
    // Application already exists with payment - redirect to status page
    $nric_param = urlencode($nric);
    $job_id_param = urlencode($job_id);
    header("Location: application-status.php?nric={$nric_param}&job_id={$job_id_param}");
    exit;
} elseif ($duplicate_check['status'] === 'duplicate_found') {
    // Application exists but no payment - allow user to continue to payment
    // This is the case where a user started an application but didn't complete payment
    // We should continue to payment-process.php
} elseif ($duplicate_check['status'] === 'allow_payment') {
    // Application exists without payment - allow user to continue to payment
    // We should continue to payment-process.php
} elseif ($duplicate_check['status'] === 'error') {
    // Database error or system error - redirect back to payment form with error
    header('Location: payment-form.php?job_code=' . urlencode($job_code) . '&error=system_error');
    exit;
}

// No duplicate found - store payment data in session and proceed to payment gateway
$_SESSION['payment_data'] = [
    'applicant_name' => strtoupper($applicant_name),
    'applicant_nric' => $nric,
    'applicant_email' => $email,
    'applicant_phone' => $applicant_phone,
    'job_id' => $job_id,
    'job_code' => $job_code,
    'amount' => $amount,
    'timestamp' => time()
];

// Redirect to actual payment processing
header('Location: payment-process.php');
exit;
?>
