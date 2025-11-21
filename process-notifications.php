<?php
/**
 * Background Notification Processor
 * This file handles sending notifications in the background to prevent page hanging
 */

// Start session to access pending notifications
session_start();

// Include required files
require_once 'includes/ErrorHandler.php';
require_once 'includes/NotificationService.php';
require_once 'includes/LogManager.php';

// Get database connection from config
$result = require 'config.php';
$config = $result['config'] ?? $result;

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
    error_log('Database connection error in notification processing: ' . $e->getMessage());
    exit('Database connection failed');
}

// Process pending notifications
$processed_count = 0;
$error_count = 0;

if (isset($_SESSION['pending_notifications']) && is_array($_SESSION['pending_notifications'])) {
    $notificationService = new NotificationService($config, $pdo);
    
    foreach ($_SESSION['pending_notifications'] as $index => $notification_data) {
        try {
            // Set timeout for each notification
            set_time_limit(15);
            
            // Get full application data from database
            $stmt = $pdo->prepare('
                SELECT a.*, j.job_title, j.kod_gred 
                FROM job_applications a 
                LEFT JOIN job_postings j ON a.job_id = j.id 
                WHERE a.id = ?
            ');
            $stmt->execute([$notification_data['application_id']]);
            $application = $stmt->fetch();
            
            if ($application) {
                // Send notifications
                $result = $notificationService->sendApplicationSubmissionNotification($notification_data['application_id']);
                
                if ($result) {
                    log_frontend_info('Background notification sent successfully', [
                        'application_id' => $notification_data['application_id'],
                        'email' => $notification_data['email'],
                        'phone' => $notification_data['phone']
                    ]);
                    $processed_count++;
                } else {
                    log_frontend_warning('Background notification failed', [
                        'application_id' => $notification_data['application_id']
                    ]);
                    $error_count++;
                }
            } else {
                log_frontend_error('Application not found for notification', [
                    'application_id' => $notification_data['application_id']
                ]);
                $error_count++;
            }
            
            // Remove processed notification from session
            unset($_SESSION['pending_notifications'][$index]);
            
        } catch (Exception $e) {
            log_frontend_error('Background notification processing error', [
                'application_id' => $notification_data['application_id'],
                'error' => $e->getMessage()
            ]);
            
            // Remove failed notification from session to prevent infinite retries
            unset($_SESSION['pending_notifications'][$index]);
            $error_count++;
        }
    }
    
    // Clean up empty array
    if (empty($_SESSION['pending_notifications'])) {
        unset($_SESSION['pending_notifications']);
    }
}

// Return success response
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success', 
    'message' => 'Notifications processed',
    'processed' => $processed_count,
    'errors' => $error_count,
    'total' => $processed_count + $error_count
]);
?>
