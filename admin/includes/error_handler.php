<?php

function logError($error_message, $error_type = 'ERROR', $file = '', $line = '') {
    $log_file = __DIR__ . '/logs/error.log';
    $log_dir = dirname($log_file);

    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    // Format the error message
    $timestamp = date('Y-m-d H:i:s');
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = '';
    $caller_line = '';
    
    // Safely get caller information
    if (isset($backtrace[1]) && isset($backtrace[1]['file'])) {
        $caller = basename($backtrace[1]['file']);
    }
    
    if (isset($backtrace[1]) && isset($backtrace[1]['line'])) {
        $caller_line = $backtrace[1]['line'];
    }

    if (empty($file)) {
        $file = $caller;
    }
    if (empty($line)) {
        $line = $caller_line;
    }

    $log_message = sprintf(
        "[%s] [%s] [File: %s] [Line: %s] %s\n",
        $timestamp,
        $error_type,
        $file,
        $line,
        $error_message
    );

    // Write to log file
    error_log($log_message, 3, $log_file);

    // Never display errors in browser, only log to file
    // This ensures security by not exposing system details to users
}

function handleException($e) {
    logError(
        $e->getMessage(),
        'EXCEPTION',
        $e->getFile(),
        $e->getLine()
    );
}

function handleError($errno, $errstr, $errfile, $errline) {
    $error_type = 'ERROR';
    switch ($errno) {
        case E_USER_ERROR:
            $error_type = 'FATAL ERROR';
            break;
        case E_USER_WARNING:
            $error_type = 'WARNING';
            break;
        case E_USER_NOTICE:
            $error_type = 'NOTICE';
            break;
    }
    
    logError($errstr, $error_type, $errfile, $errline);
    return true;
}

// Set error and exception handlers
set_error_handler('handleError');
set_exception_handler('handleException');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
