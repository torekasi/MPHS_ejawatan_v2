<?php
// AJAX endpoint to check payment status
require_once 'includes/ErrorHandler.php';

// Set JSON header
header('Content-Type: application/json');

// Get database connection from config
$result = require 'config.php';
$config = $result;

// Check if payment reference is provided
if (!isset($_GET['ref']) || empty($_GET['ref'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Payment reference required']);
    exit;
}

$payment_reference = $_GET['ref'];

try {
    // Connect to database
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    
    // Fetch payment status
    $stmt = $pdo->prepare("SELECT payment_status, updated_at FROM payment_transactions WHERE payment_reference = ? LIMIT 1");
    $stmt->execute([$payment_reference]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['error' => 'Payment not found']);
        exit;
    }
    
    echo json_encode([
        'status' => $payment['payment_status'],
        'updated_at' => $payment['updated_at']
    ]);
    
} catch (PDOException $e) {
    log_error('Database error in payment status check', ['exception' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
