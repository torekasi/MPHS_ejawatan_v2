<?php
/**
 * PHP Error and Debug Configuration for eJawatan
 * 
 * This file configures PHP to capture all errors, warnings, notices,
 * and debug information to the centralized logging system.
 */

// Load the ActivityLogger system
require_once __DIR__ . '/ActivityLogger.php';

// Configure PHP error reporting
ini_set('display_errors', 0);  // Don't display errors to users
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);      // Enable error logging
ini_set('error_log', __DIR__ . '/../admin/logs/error.log');  // Set log file path

// Set error reporting level to capture everything
error_reporting(E_ALL | E_STRICT);

// Configure additional PHP settings for better error capture
ini_set('log_errors_max_len', 0);  // No limit on error log length
ini_set('ignore_repeated_errors', 0);  // Log repeated errors
ini_set('ignore_repeated_source', 0);   // Log repeated errors from same source
ini_set('html_errors', 0);              // No HTML in error logs

// Configure memory and execution limits with logging
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 60);

// Set up session configuration with logging
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 7200);  // 2 hours
ini_set('session.cookie_httponly', 1);    // Security
ini_set('session.use_strict_mode', 1);    // Security

// Configure file upload settings
ini_set('file_uploads', 1);
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_file_uploads', 20);

/**
 * Enhanced debug logging function
 */
function debug_log($message, $data = null, $category = 'DEBUG') {
    $logger = ActivityLogger::getInstance();
    
    $debug_info = [
        'category' => $category,
        'timestamp' => microtime(true),
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true),
        'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
        'data' => $data
    ];
    
    $logger->debug($message, $debug_info);
}

/**
 * Performance monitoring function
 */
function log_performance_metrics($checkpoint_name = 'default') {
    static $start_time = null;
    static $start_memory = null;
    static $checkpoints = [];
    
    if ($start_time === null) {
        $start_time = $_SERVER['REQUEST_TIME_FLOAT'];
        $start_memory = memory_get_usage(true);
    }
    
    $current_time = microtime(true);
    $current_memory = memory_get_usage(true);
    
    $metrics = [
        'checkpoint' => $checkpoint_name,
        'total_time' => $current_time - $start_time,
        'checkpoint_time' => isset($checkpoints[$checkpoint_name]) ? 
            $current_time - $checkpoints[$checkpoint_name] : 
            $current_time - $start_time,
        'memory_current' => $current_memory,
        'memory_peak' => memory_get_peak_usage(true),
        'memory_diff' => $current_memory - $start_memory,
        'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI'
    ];
    
    $checkpoints[$checkpoint_name] = $current_time;
    
    // Only log slow operations (> 1 second) or high memory usage (> 50MB)
    if ($metrics['total_time'] > 1.0 || $metrics['memory_current'] > 50 * 1024 * 1024) {
        debug_log("Performance checkpoint: $checkpoint_name", $metrics, 'PERFORMANCE');
    }
}

/**
 * Database query logging function
 */
function log_database_query($query, $params = [], $execution_time = 0, $error = null) {
    $query_info = [
        'query' => $query,
        'params' => $params,
        'execution_time' => $execution_time,
        'error' => $error,
        'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI'
    ];
    
    if ($error) {
        log_error('Database query failed', $query_info);
    } elseif ($execution_time > 1.0) {
        log_warning('Slow database query detected', $query_info);
    } else {
        debug_log('Database query executed', $query_info, 'DATABASE');
    }
}

/**
 * Security event logging function
 */
function log_security_event($event_type, $details = null, $severity = 'WARNING') {
    $security_info = [
        'event_type' => $event_type,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        'referer' => $_SERVER['HTTP_REFERER'] ?? null,
        'session_id' => session_id(),
        'timestamp' => date('Y-m-d H:i:s'),
        'details' => $details
    ];
    
    $logger = ActivityLogger::getInstance();
    
    switch (strtoupper($severity)) {
        case 'CRITICAL':
            $logger->critical("Security Event: $event_type", $security_info);
            break;
        case 'ERROR':
            $logger->error("Security Event: $event_type", $security_info);
            break;
        case 'WARNING':
        default:
            $logger->warning("Security Event: $event_type", $security_info);
            break;
    }
}

/**
 * Request logging function
 */
function log_request_info() {
    $request_info = [
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'referer' => $_SERVER['HTTP_REFERER'] ?? null,
        'query_string' => $_SERVER['QUERY_STRING'] ?? null,
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
        'accept' => $_SERVER['HTTP_ACCEPT'] ?? null,
        'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null,
        'session_id' => session_id()
    ];
    
    debug_log('HTTP Request received', $request_info, 'REQUEST');
}

// Log the initial request information
if (isset($_SERVER['REQUEST_URI'])) {
    log_request_info();
    log_performance_metrics('request_start');
}

// Register shutdown function to log final metrics
register_shutdown_function(function() {
    log_performance_metrics('request_end');
    
    // Log any final errors
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        log_error('Fatal error during shutdown', $error);
    }
});

/**
 * Custom assert handler for development debugging
 */
function custom_assert_handler($file, $line, $assertion, $description = null) {
    $assert_info = [
        'file' => $file,
        'line' => $line,
        'assertion' => $assertion,
        'description' => $description,
        'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
    ];
    
    log_error('Assertion failed', $assert_info);
}

// Enable assertions for debugging
ini_set('assert.active', 1);
ini_set('assert.exception', 0);  // Don't throw exceptions
ini_set('assert.warning', 0);    // Don't show warnings
assert_options(ASSERT_CALLBACK, 'custom_assert_handler');

// Set up custom stream wrapper for logging file operations if needed
// This is advanced logging for file system operations
class LoggingStreamWrapper {
    private $resource;
    private static $logger;
    
    public static function register() {
        if (self::$logger === null) {
            self::$logger = ActivityLogger::getInstance();
        }
        stream_wrapper_register('logfile', __CLASS__);
    }
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        $real_path = substr($path, 10); // Remove 'logfile://' prefix
        $this->resource = fopen($real_path, $mode);
        
        if ($this->resource) {
            self::$logger->debug('File opened', [
                'path' => $real_path,
                'mode' => $mode,
                'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ]);
        }
        
        return $this->resource !== false;
    }
    
    public function stream_read($count) {
        return fread($this->resource, $count);
    }
    
    public function stream_write($data) {
        return fwrite($this->resource, $data);
    }
    
    public function stream_close() {
        return fclose($this->resource);
    }
    
    public function stream_eof() {
        return feof($this->resource);
    }
    
    public function stream_tell() {
        return ftell($this->resource);
    }
    
    public function stream_seek($offset, $whence = SEEK_SET) {
        return fseek($this->resource, $offset, $whence) === 0;
    }
    
    public function stream_stat() {
        return fstat($this->resource);
    }
}

// Register the logging stream wrapper
LoggingStreamWrapper::register();