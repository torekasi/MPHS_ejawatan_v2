<?php
/**
 * AJAX Endpoint for Real-Time Duplicate Checking
 * 
 * Works with client-side DuplicateValidator.js
 * Uses server-side DuplicateValidator module
 * 
 * @version 1.0
 * @date 2025-10-28
 */

// Set JSON response header
header('Content-Type: application/json');

// Start output buffering to catch any errors
ob_start();

try {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Include required files
    require_once 'config.php';
    require_once 'modules/DuplicateValidator.php';
    
    // Get database connection
    $result = require 'config.php';
    $config_data = $result['config'] ?? $result;
    
    // Connect to database
    $dsn = "mysql:host={$config_data['db_host']};dbname={$config_data['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $config_data['db_user'], $config_data['db_pass'], $options);
    
    // Validate required parameters
    $nric = $_POST['nric'] ?? null;
    $job_id = $_POST['job_id'] ?? null;
    $job_code = $_POST['job_code'] ?? null;
    
    if (empty($nric)) {
        throw new Exception('NRIC diperlukan');
    }
    
    if (empty($job_id) && empty($job_code)) {
        throw new Exception('Job ID atau Job Code diperlukan');
    }
    
    // Resolve job_id from job_code if needed
    if (empty($job_id) && !empty($job_code)) {
        $stmt = $pdo->prepare('SELECT id FROM job_postings WHERE job_code = ? LIMIT 1');
        $stmt->execute([$job_code]);
        $job_row = $stmt->fetch();
        if ($job_row) {
            $job_id = (int)$job_row['id'];
        } else {
            throw new Exception('Job tidak dijumpai');
        }
    }
    
    // Initialize validator
    $validator = new DuplicateValidator($pdo);
    
    // Check for duplicate
    $result = $validator->checkDuplicateAjax($nric, $job_id);
    
    // Clean output buffer
    ob_end_clean();
    
    // Return JSON response
    echo json_encode($result);
    exit;
    
} catch (Exception $e) {
    // Clean output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Log error
    error_log('[AJAX Duplicate Check] Error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'duplicate' => false
    ]);
    exit;
}


