<?php
/**
 * AJAX Backup Handler
 * Handles backup creation requests via AJAX
 */

session_start();

// Security check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
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

// Set backup directory
$backupDir = '../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Logging system
$logFile = '../logs/backup_system.log';
function logBackupActivity($message, $level = 'INFO') {
    global $logFile;
    
    // Ensure logs directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
    
    // Add error handling for file writing
    if (file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) === false) {
        error_log("Failed to write to backup log file: {$logFile}");
    }
}

// Database backup function using mysqldump
function createBackup($config, $backupDir) {
    global $pdo; // Access the global PDO connection
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "backup_{$config['db_name']}_{$timestamp}";
    $sqlFile = "{$backupDir}/{$filename}.sql";
    $zipFile = "{$backupDir}/{$filename}.zip";
    
    logBackupActivity("Starting backup creation for database: {$config['db_name']}");
    
    // Try mysqldump first
    $mysqldumpPath = 'mysqldump';
    $command = sprintf(
        '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s 2>&1',
        $mysqldumpPath,
        escapeshellarg($config['db_host']),
        escapeshellarg($config['db_port']),
        escapeshellarg($config['db_user']),
        escapeshellarg($config['db_pass']),
        escapeshellarg($config['db_name']),
        escapeshellarg($sqlFile)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($sqlFile) && filesize($sqlFile) > 0) {
        // Create ZIP file
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($sqlFile, basename($sqlFile));
            $zip->close();
            
            // Remove SQL file, keep only ZIP
            unlink($sqlFile);
            
            $fileSize = filesize($zipFile);
            logBackupActivity("Backup created successfully: {$filename}.zip ({$fileSize} bytes)");
            
            // Log to admin activity logs
            if ($pdo) {
                log_admin_action(
                    "Database backup created successfully: {$filename}.zip", 
                    'CREATE', 
                    'backup', 
                    null, 
                    [
                        'filename' => "{$filename}.zip",
                        'size' => $fileSize,
                        'method' => 'mysqldump',
                        'database' => $config['db_name']
                    ]
                );
            }
            
            return [
                'success' => true,
                'filename' => "{$filename}.zip",
                'size' => $fileSize,
                'method' => 'mysqldump'
            ];
        }
    }
    
    // Fallback to PDO method
    return createBackupPDO($config, $backupDir, $filename);
}

// PDO backup fallback
function createBackupPDO($config, $backupDir, $filename) {
    global $pdo; // Access the global PDO connection for logging
    
    try {
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
        $backupPdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $sqlFile = "{$backupDir}/{$filename}.sql";
        $zipFile = "{$backupDir}/{$filename}.zip";
        
        // Build SQL dump
        $sql = "-- Database Backup: {$config['db_name']}\n";
        $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        // Get all tables
        $tables = $backupPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            // Table structure
            $createTable = $backupPdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createTable['Create Table'] . ";\n\n";
            
            // Table data
            $rows = $backupPdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $values = array_map(function($value) use ($backupPdo) {
                        return $value === null ? 'NULL' : $backupPdo->quote($value);
                    }, array_values($row));
                    $columns = '`' . implode('`, `', array_keys($row)) . '`';
                    $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        // Write SQL file
        file_put_contents($sqlFile, $sql);
        
        // Create ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($sqlFile, basename($sqlFile));
            $zip->close();
            unlink($sqlFile);
            
            $fileSize = filesize($zipFile);
            logBackupActivity("PDO backup created successfully: {$filename}.zip ({$fileSize} bytes)");
            
            // Log to admin activity logs
            if ($pdo) {
                log_admin_action(
                    "Database backup created successfully: {$filename}.zip", 
                    'CREATE', 
                    'backup', 
                    null, 
                    [
                        'filename' => "{$filename}.zip",
                        'size' => $fileSize,
                        'method' => 'PDO',
                        'database' => $config['db_name']
                    ]
                );
            }
            
            return [
                'success' => true,
                'filename' => "{$filename}.zip",
                'size' => $fileSize,
                'method' => 'PDO'
            ];
        }
        
    } catch (Exception $e) {
        logBackupActivity("PDO backup failed: " . $e->getMessage(), 'ERROR');
        
        // Log backup failure to admin activity logs
        if ($pdo) {
            log_admin_action(
                "Database backup failed: " . $e->getMessage(), 
                'CREATE', 
                'backup', 
                null, 
                [
                    'error' => $e->getMessage(),
                    'method' => 'PDO',
                    'database' => $config['db_name']
                ]
            );
        }
        
        return ['success' => false, 'error' => $e->getMessage()];
    }
    
    return ['success' => false, 'error' => 'Unknown error occurred'];
}

// Handle backup creation
if (isset($_POST['action']) && $_POST['action'] === 'create_backup') {
    // Log backup initiation
    if ($pdo) {
        log_admin_action(
            "Database backup initiated", 
            'CREATE', 
            'backup', 
            null, 
            [
                'database' => $config['db_name'],
                'initiated_at' => date('Y-m-d H:i:s')
            ]
        );
    }
    
    $result = createBackup($config, $backupDir);
    
    if ($result['success']) {
        $sizeInMB = $result['size'] / (1024 * 1024);
        $sizeDisplay = $sizeInMB < 0.1 ? 
            number_format($result['size'] / 1024, 2) . ' KB' : 
            number_format($sizeInMB, 2) . ' MB';
        
        echo json_encode([
            'success' => true,
            'message' => "Backup created successfully!",
            'filename' => $result['filename'],
            'size' => $sizeDisplay,
            'method' => $result['method']
        ]);
    } else {
        // Log backup failure
        if ($pdo) {
            log_admin_action(
                "Database backup failed: " . ($result['error'] ?? 'Unknown error'), 
                'CREATE', 
                'backup', 
                null, 
                [
                    'error' => $result['error'] ?? 'Unknown error',
                    'database' => $config['db_name']
                ]
            );
        }
        
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>