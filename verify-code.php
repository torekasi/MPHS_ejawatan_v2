<?php
require_once 'includes/ErrorHandler.php';
session_start();

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Validate inputs
$raw_nric = $_POST['nric'] ?? '';
// Remove any non-numeric characters
$clean_nric = preg_replace('/\D/', '', $raw_nric);

if (empty($clean_nric) || strlen($clean_nric) !== 12) {
    $response['message'] = 'No. Kad Pengenalan tidak sah. Sila masukkan 12 digit nombor yang sah.\nFormat: XXXXXX-XX-XXXX';
    echo json_encode($response);
    exit;
}

// Validate format (should have hyphens in correct positions)
if (!preg_match('/^\d{6}-\d{2}-\d{4}$/', $raw_nric)) {
    $response['message'] = 'Format No. Kad Pengenalan tidak betul. Sila gunakan format: XXXXXX-XX-XXXX';
    echo json_encode($response);
    exit;
}

// Use clean NRIC for further processing
$nric = $clean_nric;

// Basic validation for Malaysian IC format
$year = substr($nric, 0, 2);
$month = substr($nric, 2, 2);
$day = substr($nric, 4, 2);
$state = substr($nric, 6, 2);

// Validate month (01-12)
if ($month < 1 || $month > 12) {
    $response['message'] = 'Format No. Kad Pengenalan tidak sah (bulan tidak sah).';
    echo json_encode($response);
    exit;
}

// Validate day (01-31)
if ($day < 1 || $day > 31) {
    $response['message'] = 'Format No. Kad Pengenalan tidak sah (hari tidak sah).';
    echo json_encode($response);
    exit;
}

// Validate state code (01-16 for Malaysian states, 17-99 for non-citizens/others)
$state_int = intval($state);
if ($state_int < 1 || $state_int > 99) {
    $response['message'] = 'Format No. Kad Pengenalan tidak sah (kod negeri tidak sah).';
    echo json_encode($response);
    exit;
}

$verification_code = $_POST['verification_code'] ?? '';

if (empty($verification_code) || !preg_match('/^\d{6}$/', $verification_code)) {
    $response['message'] = 'Kod pengesahan tidak sah.';
    echo json_encode($response);
    exit;
}

// Check if verification data exists in session
if (!isset($_SESSION['verification'])) {
    $response['message'] = 'Sesi pengesahan telah tamat. Sila minta kod baharu.';
    echo json_encode($response);
    exit;
}

$verification = $_SESSION['verification'];

// Validate NRIC matches
if ($verification['nric'] !== $nric) {
    $response['message'] = 'No. Kad Pengenalan tidak sepadan dengan sesi pengesahan.';
    echo json_encode($response);
    exit;
}

// Check if code has expired
if (time() > $verification['expires']) {
    unset($_SESSION['verification']);
    $response['message'] = 'Kod pengesahan telah tamat tempoh. Sila minta kod baharu.';
    echo json_encode($response);
    exit;
}

// Check attempts limit (max 3 attempts)
if ($verification['attempts'] >= 3) {
    unset($_SESSION['verification']);
    $response['message'] = 'Terlalu banyak percubaan. Sila minta kod baharu.';
    echo json_encode($response);
    exit;
}

// Verify code
if ($verification['code'] !== $verification_code) {
    $_SESSION['verification']['attempts']++;
    $remaining_attempts = 3 - $_SESSION['verification']['attempts'];
    $response['message'] = "Kod pengesahan tidak sah. Baki percubaan: {$remaining_attempts}";
    echo json_encode($response);
    exit;
}

// Code verified successfully
$_SESSION['verified_nric'] = $nric;
$_SESSION['verification_time'] = time();
unset($_SESSION['verification']);

$response['success'] = true;
$response['message'] = 'Pengesahan berjaya.';

echo json_encode($response);
