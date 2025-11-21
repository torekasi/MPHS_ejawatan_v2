<?php
session_start();

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
    error_log('Database connection error: ' . $e->getMessage());
    $_SESSION['application_errors'] = ['Database connection error'];
    header('Location: index.php');
    exit();
}

// Validate application ID
$application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : null;

if ($application_id) {
    try {
        // Update application status to PENDING
        $stmt = $pdo->prepare("UPDATE job_applications SET status = 'PENDING', submitted_at = NOW() WHERE id = ?");
        $stmt->execute([$application_id]);
        
        // Get application details
        $stmt = $pdo->prepare("SELECT * FROM job_applications WHERE id = ?");
        $stmt->execute([$application_id]);
        $application = $stmt->fetch();
        
        // Store application reference in session for thank you page
        $_SESSION['application_submitted'] = [
            'reference' => $application['application_reference'],
            'name' => $application['nama_penuh'],
            'email' => $application['email']
        ];
        
        // Redirect to thank you page
        header('Location: application-thank-you.php');
        exit();
    } catch (Exception $e) {
        error_log('Error processing application: ' . $e->getMessage());
        $_SESSION['application_errors'] = ['Error processing application'];
        header('Location: preview-application.php?app_id=' . $application_id);
        exit();
    }
} else {
    $_SESSION['application_errors'] = ['Invalid application ID'];
    header('Location: index.php');
    exit();
}
?>

