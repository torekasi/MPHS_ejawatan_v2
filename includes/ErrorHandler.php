<?php
/**
 * Centralized Error Handler for eJawatan System
 * 
 * This file sets up comprehensive error and exception handling that integrates
 * with the ActivityLogger to capture all errors, warnings, and debug information.
 */

// Include ActivityLogger for comprehensive logging (but don't instantiate immediately)
require_once __DIR__ . '/ActivityLogger.php';
require_once __DIR__ . '/LogManager.php';

/**
 * Custom error handler function
 */
function customErrorHandler($severity, $message, $file, $line) {
    // Don't log suppressed errors (with @)
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    // Only log errors and warnings - ignore notices, strict standards, and deprecated
    $should_log = false;
    $log_level = 'ERROR';
    $error_type = 'Unknown Error';
    
    switch ($severity) {
        case E_ERROR:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            $log_level = 'ERROR';
            $error_type = 'Fatal Error';
            $should_log = true;
            break;
        case E_WARNING:
        case E_CORE_WARNING:
        case E_COMPILE_WARNING:
        case E_USER_WARNING:
            $log_level = 'WARNING';
            $error_type = 'Warning';
            $should_log = true;
            break;
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            // Don't log deprecated notices
            $should_log = false;
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
        case E_STRICT:
        default:
            // Don't log notices, strict standards, or other low-level errors
            $should_log = false;
            break;
    }
    
    // Early return if we shouldn't log this error type
    if (!$should_log) {
        return false;
    }
    
    // Format error details
    $error_details = [
        'type' => $error_type,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'severity' => $severity,
        'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
        'referer' => $_SERVER['HTTP_REFERER'] ?? null,
        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
    ];
    
    // Log the error using LogManager (will automatically determine frontend/admin)
    $logManager = LogManager::getInstance();
    $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
    
    if (strpos($script_path, '/admin/') !== false) {
        $logManager->logAdmin($log_level, "PHP Error: $error_type - $message", $error_details);
    } else {
        $logManager->logFrontend($log_level, "PHP Error: $error_type - $message", $error_details);
    }
    
    // Don't execute PHP internal error handler
    return true;
}

/**
 * Custom exception handler function
 */
function customExceptionHandler($exception) {
    // Format exception details
    $exception_details = [
        'type' => get_class($exception),
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'code' => $exception->getCode(),
        'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
        'referer' => $_SERVER['HTTP_REFERER'] ?? null,
        'trace' => $exception->getTraceAsString()
    ];
    
    // Log the exception using LogManager (will automatically determine frontend/admin)
    $logManager = LogManager::getInstance();
    $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
    
    if (strpos($script_path, '/admin/') !== false) {
        $logManager->logAdmin('CRITICAL', "Uncaught Exception: " . get_class($exception) . " - " . $exception->getMessage(), $exception_details);
    } else {
        $logManager->logFrontend('CRITICAL', "Uncaught Exception: " . get_class($exception) . " - " . $exception->getMessage(), $exception_details);
    }
    
    // Only display error message if we haven't already started output
    if (!headers_sent() && !ob_get_level()) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: text/html; charset=UTF-8');
        
        // Show appropriate error page based on environment
        $is_debug = defined('DEBUG_MODE') && DEBUG_MODE;
        $is_admin = strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false;
        
        if ($is_debug) {
            echo "<h1>Exception Details</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
            echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        } else {
            if ($is_admin) {
                echo "<h1>System Error</h1>";
                echo "<p>A system error has occurred. Please check the logs or contact the system administrator.</p>";
            } else {
                echo "<h1>Ralat Sistem</h1>";
                echo "<p>Ralat sistem telah berlaku. Sila cuba lagi kemudian atau hubungi pentadbir sistem.</p>";
            }
        }
    }
    
    exit(1);
}

/**
 * Custom shutdown handler for fatal errors
 */
function customShutdownHandler() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        $error_details = [
            'type' => 'Fatal Error',
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'memory_peak' => memory_get_peak_usage(true),
            'memory_current' => memory_get_usage(true)
        ];
        
        // Log the fatal error using LogManager (will automatically determine frontend/admin)
        $logManager = LogManager::getInstance();
        $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
        
        if (strpos($script_path, '/admin/') !== false) {
            $logManager->logAdmin('CRITICAL', "Fatal Error: " . $error['message'], $error_details);
        } else {
            $logManager->logFrontend('CRITICAL', "Fatal Error: " . $error['message'], $error_details);
        }
    }
}

/**
 * Log page access and basic information
 */
function logPageAccess() {
    // Only log if not already logged in this session for this page
    $current_page = $_SERVER['REQUEST_URI'] ?? 'unknown';
    $session_key = 'logged_pages_' . session_id();
    
    if (!isset($_SESSION[$session_key])) {
        $_SESSION[$session_key] = [];
    }
    
    // Log only once per page per session
    if (!in_array($current_page, $_SESSION[$session_key])) {
        $logger = ActivityLogger::getInstance();
        
        $access_details = [
            'url' => $current_page,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'query_string' => $_SERVER['QUERY_STRING'] ?? null,
            'session_id' => session_id(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $user_type = 'public';
        if (strpos($current_page, '/admin/') !== false) {
            $user_type = 'admin';
        }
        
        $logger->logActivity(
            "Page Access: $current_page",
            ActivityLogger::ACTION_VIEW,
            ActivityLogger::ENTITY_SYSTEM,
            null,
            $access_details,
            ActivityLogger::LOG_LEVEL_INFO,
            $user_type
        );
        
        $_SESSION[$session_key][] = $current_page;
        
        // Limit logged pages per session to prevent memory bloat
        if (count($_SESSION[$session_key]) > 50) {
            $_SESSION[$session_key] = array_slice($_SESSION[$session_key], -25);
        }
    }
}

/**
 * Enhanced error_log function that also logs to database
 */
function enhanced_error_log($message, $details = null) {
    $logger = ActivityLogger::getInstance();
    $logger->error($message, $details);
    
    // Also log to PHP error log as fallback
    error_log($message);
}

// Set up error handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');
register_shutdown_function('customShutdownHandler');

// Override the global error_log function
if (!function_exists('original_error_log')) {
    function original_error_log($message, $message_type = 0, $destination = null, $extra_headers = null) {
        return error_log($message, $message_type, $destination, $extra_headers);
    }
}

    // Disabled page access logging to reduce noise
// if (isset($_SERVER['REQUEST_URI']) && session_status() === PHP_SESSION_ACTIVE) {
//     logPageAccess();
// }