<?php
/**
 * Bootstrap file for eJawatan Application
 * 
 * This file initializes the comprehensive logging system and should be
 * included at the top of every PHP file that needs logging capabilities.
 */

// Prevent direct access
if (!defined('EJAWATAN_BOOTSTRAP')) {
    define('EJAWATAN_BOOTSTRAP', true);
}

// Load Composer autoloader if available (for external libraries like PHPMailer)
$composerAutoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];
foreach ($composerAutoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        break;
    }
}

// Provide shims for legacy unqualified PHPMailer class references
if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') && !class_exists('PHPMailer')) {
    class_alias('\\PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer');
}
if (class_exists('\\PHPMailer\\PHPMailer\\Exception') && !class_exists('PHPMailerException')) {
    class_alias('\\PHPMailer\\PHPMailer\\Exception', 'PHPMailerException');
}

// Set up PHP configuration for comprehensive error logging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../admin/logs/error.log');
error_reporting(E_ALL | E_STRICT);

// Additional PHP settings
ini_set('log_errors_max_len', 0);
ini_set('ignore_repeated_errors', 0);
ini_set('ignore_repeated_source', 0);
ini_set('html_errors', 0);

// Create logs directory if it doesn't exist
$log_dir = __DIR__ . '/../admin/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Load the error handler and activity logger
require_once __DIR__ . '/ErrorHandler.php';

// Load admin logger functions if in admin context
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false) {
    // Ensure SECURE_ACCESS is defined before loading admin_logger.php
    if (!defined('SECURE_ACCESS')) {
        define('SECURE_ACCESS', true);
    }
    // Load admin logger with absolute path
    $admin_logger_path = realpath(__DIR__ . '/../admin/includes/admin_logger.php');
    if ($admin_logger_path && file_exists($admin_logger_path)) {
        require_once $admin_logger_path;
    } else {
        // Try alternative path for Docker environment
        $docker_path = '/var/www/html/admin/includes/admin_logger.php';
        if (file_exists($docker_path)) {
            require_once $docker_path;
        } else {
            error_log("Admin Logger file not found at: " . $admin_logger_path . " or " . $docker_path);
        }
    }
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Log bootstrap initialization
log_debug('Application bootstrap initialized', [
    'file' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
    'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'timestamp' => date('Y-m-d H:i:s')
]);

/**
 * Global helper functions for logging
 */

if (!function_exists('log_admin_action')) {
    function log_admin_action($action, $action_type, $entity_type, $entity_id = null, $details = null) {
        // If admin logger is not available, use the activity logger directly
        if (function_exists('log_activity')) {
            log_activity($action, $action_type, $entity_type, $entity_id, $details, ActivityLogger::LOG_LEVEL_INFO);
        } else {
            // Fallback to simple error_log
            error_log("[ADMIN] $action - $action_type on $entity_type ID: $entity_id");
        }
    }
}

if (!function_exists('log_user_activity')) {
    function log_user_activity($action, $entity_type, $entity_id = null, $details = null) {
        $user_type = 'public';
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false) {
            $user_type = 'admin';
        }
        
        log_activity($action, ActivityLogger::ACTION_OTHER, $entity_type, $entity_id, $details, ActivityLogger::LOG_LEVEL_INFO, $user_type);
    }
}

if (!function_exists('log_database_activity')) {
    function log_database_activity($action, $table, $record_id = null, $details = null) {
        log_activity($action, ActivityLogger::ACTION_OTHER, "db_$table", $record_id, $details, ActivityLogger::LOG_LEVEL_DEBUG, 'system');
    }
}

if (!function_exists('log_security_activity')) {
    function log_security_activity($event, $details = null, $severity = 'WARNING') {
        $security_details = array_merge([
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'session_id' => session_id()
        ], $details ?? []);
        
        $log_level = ActivityLogger::LOG_LEVEL_WARNING;
        switch (strtoupper($severity)) {
            case 'CRITICAL':
                $log_level = ActivityLogger::LOG_LEVEL_CRITICAL;
                break;
            case 'ERROR':
                $log_level = ActivityLogger::LOG_LEVEL_ERROR;
                break;
        }
        
        log_activity("Security Event: $event", ActivityLogger::ACTION_OTHER, ActivityLogger::ENTITY_SYSTEM, null, $security_details, $log_level, 'system');
    }
}

if (!function_exists('log_performance')) {
    function log_performance($checkpoint, $start_time = null) {
        static $checkpoints = [];
        
        $current_time = microtime(true);
        $memory_usage = memory_get_usage(true);
        $peak_memory = memory_get_peak_usage(true);
        
        if ($start_time === null) {
            $start_time = $_SERVER['REQUEST_TIME_FLOAT'] ?? $current_time;
        }
        
        $performance_data = [
            'checkpoint' => $checkpoint,
            'execution_time' => $current_time - $start_time,
            'memory_current' => $memory_usage,
            'memory_peak' => $peak_memory,
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI'
        ];
        
        // Only log if execution time > 1 second or memory > 50MB
        if ($performance_data['execution_time'] > 1.0 || $memory_usage > 50 * 1024 * 1024) {
            log_activity("Performance checkpoint: $checkpoint", ActivityLogger::ACTION_OTHER, ActivityLogger::ENTITY_SYSTEM, null, $performance_data, ActivityLogger::LOG_LEVEL_WARNING, 'system');
        } else {
            log_debug("Performance checkpoint: $checkpoint", $performance_data);
        }
        
        $checkpoints[$checkpoint] = $current_time;
        return $current_time;
    }
}

/**
 * Database connection helper
 */
if (!function_exists('get_database_connection')) {
    function get_database_connection($config) {
        try {
            // Build DSN (use host, optional port, and dbname)
            $host = $config['db_host'] ?? 'localhost';
            $dbname = $config['db_name'] ?? '';
            $port = $config['db_port'] ?? null;
            $dsn = "mysql:host={$host}" . ($port ? ";port={$port}" : '') . ";dbname={$dbname};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $pdo = new PDO($dsn, $config['db_user'] ?? null, $config['db_pass'] ?? null, $options);

            // Optional: log success at DEBUG level
            if (function_exists('log_debug')) {
                log_debug('Database connection established', ['host' => $host, 'dbname' => $dbname]);
            }

            return ['pdo' => $pdo, 'connection_method' => 'PDO'];
        } catch (PDOException $e) {
            // Log error using centralized logger
            if (function_exists('log_error')) {
                log_error('Database connection error', ['message' => $e->getMessage()]);
            } else {
                error_log('Database connection error: ' . $e->getMessage());
            }
            return ['pdo' => null, 'error' => true, 'error_details' => $e->getMessage()];
        }
    }
}

// Log the current request
if (isset($_SERVER['REQUEST_URI'])) {
    log_performance('request_start');
    
    // Register shutdown function to log request completion
    register_shutdown_function(function() {
        log_performance('request_end');
        
        // Log any fatal errors that occurred
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            log_error('Fatal error occurred', $error);
        }
    });
}