<?php
/**
 * @FileID: test-email-comprehensive
 * @Module: Email Testing
 * @Author: Nefi
 * @LastModified: 2025-11-26
 * @SecurityTag: validated
 */

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../includes/bootstrap.php';
require_once '../includes/MailSender.php';
require_once '../includes/NotificationService.php';
require_once '../includes/StatusEmailService.php';
require_once '../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$test_results = [];
$test_email = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $test_type = $_POST['test_type'] ?? '';
    
    if (!$test_email) {
        $test_results[] = [
            'type' => 'error',
            'message' => 'Invalid email address provided'
        ];
    } else {
        // Run the selected test
        switch ($test_type) {
            case 'basic':
                $test_results[] = testBasicEmail($test_email, $config);
                break;
            case 'application':
                $test_results[] = testApplicationNotification($test_email, $config, $pdo);
                break;
            case 'status':
                $test_results[] = testStatusEmail($test_email, $config, $pdo);
                break;
            case 'all':
                $test_results[] = testBasicEmail($test_email, $config);
                $test_results[] = testApplicationNotification($test_email, $config, $pdo);
                $test_results[] = testStatusEmail($test_email, $config, $pdo);
                break;
        }
    }
}

/**
 * Test basic email sending
 */
function testBasicEmail($to, $config) {
    try {
        $mailSender = new MailSender($config);
        $result = $mailSender->sendTest($to);
        
        return [
            'type' => $result ? 'success' : 'error',
            'test' => 'Basic Email Test',
            'message' => $result ? 
                "✓ Basic test email sent successfully to {$to}" : 
                "✗ Failed to send basic test email to {$to}"
        ];
    } catch (Exception $e) {
        return [
            'type' => 'error',
            'test' => 'Basic Email Test',
            'message' => "✗ Exception: " . $e->getMessage()
        ];
    }
}

/**
 * Test application submission notification
 */
function testApplicationNotification($to, $config, $pdo) {
    try {
        // Create a mock application data
        $mockApplication = [
            'id' => 99999,
            'nama_penuh' => 'Test User',
            'email' => $to,
            'application_reference' => 'TEST-' . date('YmdHis'),
            'job_title' => 'Test Position',
            'kod_gred' => 'TEST-01',
            'application_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'nombor_telefon' => '0123456789'
        ];
        
        // Generate email content
        $status_url = $config['base_url'] . '/application-status.php?ref=' . $mockApplication['application_reference'];
        
        $subject = 'Pengesahan Permohonan Jawatan - ' . $mockApplication['application_reference'];
        $message = generateApplicationEmailContent($mockApplication, $config);
        
        // Send email
        $mailSender = new MailSender($config);
        $result = $mailSender->send($to, $subject, $message);
        
        return [
            'type' => $result ? 'success' : 'error',
            'test' => 'Application Notification Test',
            'message' => $result ? 
                "✓ Application notification email sent successfully to {$to}" : 
                "✗ Failed to send application notification email to {$to}"
        ];
    } catch (Exception $e) {
        return [
            'type' => 'error',
            'test' => 'Application Notification Test',
            'message' => "✗ Exception: " . $e->getMessage()
        ];
    }
}

/**
 * Test status update email
 */
function testStatusEmail($to, $config, $pdo) {
    try {
        // Create mock application and status data
        $mockApplication = [
            'id' => 99999,
            'nama_penuh' => 'Test User',
            'name' => 'Test User',
            'email' => $to,
            'emel' => $to,
            'application_reference' => 'TEST-' . date('YmdHis'),
            'rujukan_permohonan' => 'TEST-' . date('YmdHis'),
            'job_title' => 'Test Position',
            'jawatan_dipohon' => 'Test Position',
            'kod_gred' => 'TEST-01',
            'grade_code' => 'TEST-01'
        ];
        
        $mockStatus = [
            'code' => 'SHORTLISTED',
            'name' => 'Di Senarai Pendek',
            'email_subject' => 'Tahniah! Anda Telah Dipilih untuk Temuduga',
            'email_body' => '<div style="font-family:Arial,sans-serif"><h2>Tahniah!</h2><p>Kepada {APPLICANT_NAME},</p><p>Kami dengan sukacitanya memaklumkan bahawa permohonan anda untuk jawatan <strong>{JOB_TITLE}</strong> telah dipilih untuk ke peringkat temuduga.</p><p><strong>Rujukan:</strong> {APPLICATION_REFERENCE}</p><p><strong>Status:</strong> {STATUS_NAME}</p><p>Nota: {NOTES}</p></div>'
        ];
        
        $notes = 'Ini adalah ujian email status. Sila abaikan email ini.';
        
        // Send status email
        $statusEmailService = new StatusEmailService($config, $pdo);
        $result = $statusEmailService->send($mockApplication, $mockStatus, $notes);
        
        return [
            'type' => $result ? 'success' : 'error',
            'test' => 'Status Update Email Test',
            'message' => $result ? 
                "✓ Status update email sent successfully to {$to}" : 
                "✗ Failed to send status update email to {$to}"
        ];
    } catch (Exception $e) {
        return [
            'type' => 'error',
            'test' => 'Status Update Email Test',
            'message' => "✗ Exception: " . $e->getMessage()
        ];
    }
}

/**
 * Generate application email content
 */
function generateApplicationEmailContent($application, $config) {
    $status_url = $config['base_url'] . '/application-status.php?ref=' . $application['application_reference'];
    
    return '
    <!DOCTYPE html>
    <html lang="ms">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pengesahan Permohonan</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1e3a8a; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; }
            .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #3b82f6; }
            .test-notice { background: #fef3c7; border: 2px solid #f59e0b; padding: 15px; margin: 15px 0; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="test-notice">
                <strong>⚠️ EMAIL UJIAN</strong><br>
                Ini adalah email ujian untuk mengesahkan sistem email berfungsi dengan baik. Sila abaikan email ini.
            </div>
            
            <div class="header">
                <h1>Majlis Perbandaran Hulu Selangor</h1>
                <h2>Pengesahan Permohonan Jawatan</h2>
            </div>
            
            <div class="content">
                <p>Kepada <strong>' . htmlspecialchars($application['nama_penuh']) . '</strong>,</p>
                
                <p>Terima kasih kerana telah menghantar permohonan jawatan kepada Majlis Perbandaran Hulu Selangor.</p>
                
                <div class="info-box">
                    <h3>Maklumat Permohonan:</h3>
                    <ul>
                        <li><strong>Rujukan Permohonan:</strong> ' . htmlspecialchars($application['application_reference']) . '</li>
                        <li><strong>Jawatan:</strong> ' . htmlspecialchars($application['job_title']) . '</li>
                        <li><strong>Kod Gred:</strong> ' . htmlspecialchars($application['kod_gred']) . '</li>
                        <li><strong>Tarikh Permohonan:</strong> ' . date('d/m/Y H:i', strtotime($application['application_date'])) . '</li>
                    </ul>
                </div>
                
                <p>Permohonan anda telah berjaya diterima dan sedang dalam proses semakan.</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $status_url . '" class="button">Semak Status Permohonan</a>
                </div>
            </div>
            
            <div class="footer">
                <p>Email ini dihantar secara automatik. Sila jangan balas email ini.</p>
                <p>&copy; ' . date('Y') . ' Majlis Perbandaran Hulu Selangor. Hak cipta terpelihara.</p>
            </div>
        </div>
    </body>
    </html>';
}

?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive Email Testing Tool - eJawatan MPHS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .test-result {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">📧 Comprehensive Email Testing Tool</h1>
                <p class="text-gray-600">Test all email functionality in the eJawatan system</p>
            </div>

            <!-- Email Configuration Display -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-6 mb-6 rounded-r-lg">
                <h2 class="text-xl font-bold text-blue-800 mb-4">📋 Current Email Configuration</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">SMTP Host</p>
                        <p class="font-mono font-semibold"><?php echo htmlspecialchars($config['smtp_host'] ?? 'Not set'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">SMTP Port</p>
                        <p class="font-mono font-semibold"><?php echo htmlspecialchars($config['smtp_port'] ?? 'Not set'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">SMTP Security</p>
                        <p class="font-mono font-semibold"><?php echo strtoupper(htmlspecialchars($config['smtp_secure'] ?? 'Not set')); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">SMTP Username</p>
                        <p class="font-mono font-semibold"><?php echo htmlspecialchars($config['smtp_username'] ?? 'Not set'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">SMTP Password</p>
                        <p class="font-mono font-semibold"><?php echo isset($config['smtp_password']) && !empty($config['smtp_password']) ? '✓ Set (Hidden)' : '✗ Not set'; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">From Email</p>
                        <p class="font-mono font-semibold"><?php echo htmlspecialchars($config['noreply_email'] ?? 'Not set'); ?></p>
                    </div>
                </div>
                
                <!-- PHPMailer Status -->
                <div class="mt-4 pt-4 border-t border-blue-200">
                    <p class="text-sm text-gray-600">PHPMailer Status</p>
                    <p class="font-mono font-semibold">
                        <?php 
                        if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
                            echo '✓ PHPMailer Loaded (via Composer)';
                        } elseif (class_exists('PHPMailer')) {
                            echo '✓ PHPMailer Loaded (Legacy)';
                        } else {
                            echo '✗ PHPMailer Not Loaded';
                        }
                        ?>
                    </p>
                </div>
            </div>

            <!-- Test Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">🧪 Run Email Tests</h2>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Test Email Address *
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            value="<?php echo htmlspecialchars($test_email); ?>"
                            placeholder="your.email@example.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        <p class="text-xs text-gray-500 mt-1">Enter the email address where test emails should be sent</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Test Type *
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="test_type" value="basic" required class="mr-3">
                                <div>
                                    <p class="font-semibold">Basic Email Test</p>
                                    <p class="text-xs text-gray-600">Send a simple test email to verify SMTP configuration</p>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="test_type" value="application" class="mr-3">
                                <div>
                                    <p class="font-semibold">Application Notification Test</p>
                                    <p class="text-xs text-gray-600">Test the email sent when applicants submit their application</p>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="test_type" value="status" class="mr-3">
                                <div>
                                    <p class="font-semibold">Status Update Email Test</p>
                                    <p class="text-xs text-gray-600">Test the email sent when application status is updated</p>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="test_type" value="all" class="mr-3">
                                <div>
                                    <p class="font-semibold">Run All Tests</p>
                                    <p class="text-xs text-gray-600">Execute all email tests sequentially</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200"
                    >
                        🚀 Run Email Test
                    </button>
                </form>
            </div>

            <!-- Test Results -->
            <?php if (!empty($test_results)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">📊 Test Results</h2>
                
                <div class="space-y-3">
                    <?php foreach ($test_results as $result): ?>
                    <div class="test-result p-4 rounded-lg border-l-4 <?php echo $result['type'] === 'success' ? 'bg-green-50 border-green-500' : 'bg-red-50 border-red-500'; ?>">
                        <?php if (isset($result['test'])): ?>
                        <p class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($result['test']); ?></p>
                        <?php endif; ?>
                        <p class="<?php echo $result['type'] === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
                            <?php echo htmlspecialchars($result['message']); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Documentation -->
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-r-lg mb-6">
                <h3 class="text-lg font-bold text-yellow-800 mb-3">📖 Important Notes</h3>
                <ul class="list-disc list-inside space-y-2 text-yellow-800">
                    <li>Make sure your SMTP credentials in <code class="bg-yellow-100 px-2 py-1 rounded">config.php</code> are correct</li>
                    <li>For Gmail SMTP, you may need to enable "Less secure app access" or use App Passwords</li>
                    <li>Check your spam/junk folder if you don't receive the test emails</li>
                    <li>All test emails are clearly marked as "TEST" to avoid confusion</li>
                    <li>Check <code class="bg-yellow-100 px-2 py-1 rounded">admin/logs/error.log</code> for detailed error messages</li>
                </ul>
            </div>

            <!-- Back Link -->
            <div class="text-center">
                <a href="index.php" class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
