<?php
/**
 * Secure Backup File Download Handler
 * Provides secure download functionality for database backup files
 */

session_start();

// Security check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Load configuration
$config = include('../config.php');

// Include admin logger for activity tracking
require_once 'includes/admin_logger.php';

// Initialize PDO connection for admin logging
try {
    $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed for admin logging: " . $e->getMessage());
    $pdo = null;
}

// Get the requested file
$filename = $_GET['file'] ?? '';

// Validate filename
if (empty($filename) || !preg_match('/^backup_.*\.zip$/', $filename)) {
    http_response_code(400);
    die('Invalid file requested');
}

// Set backup directory
$backupDir = '../backups';
$filepath = $backupDir . '/' . basename($filename);

// Check if file exists and is within backup directory
if (!file_exists($filepath) || !is_file($filepath)) {
    http_response_code(404);
    die('File not found');
}

// Security check: ensure file is within backup directory
$realBackupDir = realpath($backupDir);
$realFilePath = realpath($filepath);

if (!$realFilePath || strpos($realFilePath, $realBackupDir) !== 0) {
    http_response_code(403);
    die('Access denied');
}

// Log the download activity
$logFile = '../logs/backup_system.log';
$timestamp = date('Y-m-d H:i:s');
$logEntry = "[{$timestamp}] [INFO] Backup downloaded: {$filename} by admin ID: {$_SESSION['admin_id']}\n";
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Log to admin activity logs
if ($pdo) {
    log_admin_action(
        "Database backup downloaded: {$filename}", 
        'READ', 
        'backup', 
        null, 
        [
            'filename' => $filename,
            'filesize' => filesize($filepath),
            'admin_id' => $_SESSION['admin_id'] ?? null
        ]
    );
}

// Set headers for file download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Output file
readfile($filepath);
exit();
?>
