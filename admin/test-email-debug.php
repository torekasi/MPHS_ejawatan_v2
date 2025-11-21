<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../includes/bootstrap.php';
require_once '../includes/MailSender.php';
require_once '../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Email Configuration Debug</h2>";

// Display current email configuration
echo "<h3>Current Email Configuration:</h3>";
echo "<pre>";
echo "SMTP Host: " . ($config['smtp_host'] ?? 'Not set') . "\n";
echo "SMTP Port: " . ($config['smtp_port'] ?? 'Not set') . "\n";
echo "SMTP Secure: " . ($config['smtp_secure'] ?? 'Not set') . "\n";
echo "SMTP Username: " . ($config['smtp_username'] ?? 'Not set') . "\n";
echo "SMTP Password: " . (isset($config['smtp_password']) ? '[SET]' : 'Not set') . "\n";
echo "Email From: " . ($config['email_from'] ?? 'Not set') . "\n";
echo "Email Reply To: " . ($config['email_reply_to'] ?? 'Not set') . "\n";
echo "</pre>";

// Test email sending
if ($_POST['test_email'] ?? false) {
    $test_email = $_POST['email'] ?? '';
    
    if (!empty($test_email) && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        echo "<h3>Testing Email Send...</h3>";
        
        try {
            $mailSender = new MailSender($config);
            
            $subject = "Test Email from MPHS System";
            $message = "This is a test email to verify the email configuration is working properly.";
            
            echo "Attempting to send email to: " . htmlspecialchars($test_email) . "<br>";
            
            $result = $mailSender->sendTest($test_email, $subject, $message);
            
            if ($result) {
                echo "<div style='color: green; font-weight: bold;'>✓ Email sent successfully!</div>";
            } else {
                echo "<div style='color: red; font-weight: bold;'>✗ Email failed to send</div>";
            }
            
        } catch (Exception $e) {
            echo "<div style='color: red; font-weight: bold;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div style='color: red;'>Please provide a valid email address</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { margin: 20px 0; padding: 20px; border: 1px solid #ccc; }
        input[type="email"] { width: 300px; padding: 8px; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
        button:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>Email System Debug Tool</h1>
    
    <form method="POST">
        <h3>Test Email Sending</h3>
        <label for="email">Test Email Address:</label><br>
        <input type="email" id="email" name="email" required placeholder="Enter email to test"><br><br>
        <button type="submit" name="test_email" value="1">Send Test Email</button>
    </form>
    
    <p><a href="email-blast.php">← Back to Email Blast</a></p>
</body>
</html>