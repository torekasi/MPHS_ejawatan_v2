<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Set execution time limit to prevent indefinite loading
set_time_limit(300); // 5 minutes maximum

require_once '../includes/ErrorHandler.php';
require_once '../includes/LogManager.php';
require_once '../includes/MailSender.php';

// Get database connection
$result = require '../config.php';
$config = $result['config'] ?? $result;

// Ensure email configuration is properly mapped for MailSender
$config['mail_host'] = $config['smtp_host'] ?? 'localhost';
$config['mail_port'] = $config['smtp_port'] ?? 587;
$config['mail_username'] = $config['smtp_username'] ?? '';
$config['mail_password'] = $config['smtp_password'] ?? '';
$config['mail_encryption'] = $config['smtp_secure'] ?? 'tls';
$config['mail_from_address'] = $config['noreply_email'] ?? 'noreply@mphs.gov.my';
$config['mail_from_name'] = $config['email_from_name'] ?? 'eJawatan MPHS';
$config['email_from'] = $config['noreply_email'] ?? 'noreply@mphs.gov.my';
$config['email_reply_to'] = $config['admin_email'] ?? 'admin@mphs.gov.my';

$pdo = null;
$message = '';
$error = '';

try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
} catch (PDOException $e) {
    $error = 'Database connection failed: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Validate CSRF token (if implemented)
    // $csrf_token = $_POST['csrf_token'] ?? '';
    // if (!validate_csrf_token($csrf_token)) {
    //     $error = 'Invalid request. Please refresh the page and try again.';
    // } else

    if ($action === 'test_email') {
        $test_email = trim($_POST['test_email'] ?? '');
        $test_subject = trim($_POST['test_subject'] ?? 'Test Email from eJawatan System');
        $test_message = trim($_POST['test_message'] ?? 'This is a test email from the eJawatan system.');

        // Validate input
        if (empty($test_email)) {
            $error = 'Please enter a test email address.';
        } elseif (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($test_subject) > 255) {
            $error = 'Subject line is too long (maximum 255 characters).';
        } elseif (strlen($test_message) > 5000) {
            $error = 'Message is too long (maximum 5000 characters).';
        } else {
            // Test email sending
            $email_sent = sendTestEmail($test_email, $test_subject, $test_message, $config);

            if ($email_sent) {
                $message = "Test email sent successfully to $test_email";
            } else {
                $error = "Failed to send test email to $test_email. Please check your email configuration and try again.";
            }
        }
    } elseif ($action === 'blast_applications') {
        $blast_type = $_POST['blast_type'] ?? 'all';
        $custom_message = trim($_POST['custom_message'] ?? '');

        // Validate input
        $valid_blast_types = ['all', 'pending', 'recent'];
        if (!in_array($blast_type, $valid_blast_types)) {
            $error = 'Invalid blast type selected.';
        } elseif (strlen($custom_message) > 2000) {
            $error = 'Custom message is too long (maximum 2000 characters).';
        } else {
            // Get applications based on type
            $applications = getApplicationsForBlast($pdo, $blast_type);

            if (empty($applications)) {
                $error = 'No applications found for the selected criteria.';
            } else {
                $sent_count = 0;
                $failed_count = 0;
                $errors = [];

                // Add rate limiting to prevent overwhelming the mail server
                $batch_size = 10; // Send 10 emails at a time
                $delay = 100000; // 100ms delay between batches

                foreach (array_chunk($applications, $batch_size) as $batch) {
                    foreach ($batch as $application) {
                        $email_sent = sendApplicationEmail($application, $custom_message, $config);
                        if ($email_sent) {
                            $sent_count++;
                        } else {
                            $failed_count++;
                            $errors[] = "Failed to send to: " . ($application['email'] ?? 'Unknown');
                        }
                    }

                    // Small delay between batches to prevent overwhelming the mail server
                    if (count($applications) > $batch_size) {
                        usleep($delay);
                    }
                }

                if ($sent_count > 0) {
                    $message = "Email blast completed successfully. Sent: $sent_count, Failed: $failed_count";

                    if ($failed_count > 0) {
                        $message .= "<br><small>Note: " . count($errors) . " emails failed to send. Check the logs for details.</small>";
                    }

                    log_admin_info('Email blast completed', [
                        'blast_type' => $blast_type,
                        'total_applications' => count($applications),
                        'sent_count' => $sent_count,
                        'failed_count' => $failed_count,
                        'custom_message_length' => strlen($custom_message)
                    ]);
                } else {
                    $error = "Failed to send any emails. Please check your email configuration and try again.";
                    if (!empty($errors)) {
                        $error .= "<br><small>Errors: " . implode(", ", array_slice($errors, 0, 3)) . "</small>";
                    }
                }
            }
        }
    }
}

/**
 * Send test email
 */
function sendTestEmail($to, $subject, $message, $config) {
    try {
        // Validate email address
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address format');
        }

        // Create MailSender instance
        $mailSender = new MailSender($config);

        // Send test email using MailSender
        $mail_sent = $mailSender->sendTest($to, $subject, $message);

        if ($mail_sent) {
            log_admin_info('Test email sent successfully', [
                'email' => $to,
                'subject' => $subject,
                'config_used' => [
                    'host' => $config['mail_host'] ?? 'N/A',
                    'port' => $config['mail_port'] ?? 'N/A',
                    'encryption' => $config['mail_encryption'] ?? 'N/A'
                ]
            ]);
        }

        return $mail_sent;

    } catch (Exception $e) {
        log_admin_error('Test email failed', [
            'email' => $to,
            'subject' => $subject,
            'error' => $e->getMessage(),
            'config_used' => [
                'host' => $config['mail_host'] ?? 'N/A',
                'port' => $config['mail_port'] ?? 'N/A',
                'encryption' => $config['mail_encryption'] ?? 'N/A'
            ]
        ]);
        return false;
    }
}

/**
 * Get applications for email blast
 */
function getApplicationsForBlast($pdo, $blast_type) {
    try {
        switch ($blast_type) {
            case 'pending':
                $stmt = $pdo->prepare('
                    SELECT a.*, j.job_title, j.kod_gred
                    FROM job_applications a
                    LEFT JOIN job_postings j ON a.job_id = j.id
                    WHERE a.status_id = 1 AND a.submission_locked = 1
                    ORDER BY a.application_date DESC
                ');
                break;
            case 'recent':
                $stmt = $pdo->prepare('
                    SELECT a.*, j.job_title, j.kod_gred
                    FROM job_applications a
                    LEFT JOIN job_postings j ON a.job_id = j.id
                    WHERE a.application_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND a.submission_locked = 1
                    ORDER BY a.application_date DESC
                ');
                break;
            case 'all':
            default:
                $stmt = $pdo->prepare('
                    SELECT a.*, j.job_title, j.kod_gred
                    FROM job_applications a
                    LEFT JOIN job_postings j ON a.job_id = j.id
                    WHERE a.submission_locked = 1
                    ORDER BY a.application_date DESC
                ');
                break;
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        log_admin_error('Failed to get applications for blast', [
            'blast_type' => $blast_type,
            'error' => $e->getMessage()
        ]);
        return [];
    }
}

/**
 * Send application email
 */
function sendApplicationEmail($application, $custom_message, $config) {
    try {
        // Validate application data
        if (empty($application['email'])) {
            throw new Exception('Application email address is missing');
        }

        if (!filter_var($application['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address format in application');
        }

        $to = $application['email'];
        $subject = 'Maklumat Permohonan Jawatan - ' . $application['application_reference'];

        // Generate email content
        $message = generateApplicationEmailContent($application, $custom_message, $config);

        // Create MailSender instance
        $mailSender = new MailSender($config);

        // Send email using MailSender
        $mail_sent = $mailSender->send($to, $subject, $message);

        if ($mail_sent) {
            log_admin_info('Application email sent successfully', [
                'application_id' => $application['id'],
                'application_reference' => $application['application_reference'],
                'email' => $application['email'],
                'recipient_name' => $application['nama_penuh'] ?? 'Unknown'
            ]);
        }

        return $mail_sent;

    } catch (Exception $e) {
        log_admin_error('Application email failed', [
            'application_id' => $application['id'] ?? 'N/A',
            'application_reference' => $application['application_reference'] ?? 'N/A',
            'email' => $application['email'] ?? 'N/A',
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Generate application email content
 */
function generateApplicationEmailContent($application, $custom_message, $config) {
    $status_url = $config['base_url'] . 'application-status.php?ref=' . $application['application_reference'];
    
    return '
    <!DOCTYPE html>
    <html lang="ms">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maklumat Permohonan</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1e3a8a; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; }
            .info-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #3b82f6; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Majlis Perbandaran Hulu Selangor</h1>
                <h2>Maklumat Permohonan Jawatan</h2>
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
                        <li><strong>Status:</strong> ' . htmlspecialchars($application['status']) . '</li>
                    </ul>
                </div>
                
                ' . (!empty($custom_message) ? '<p><strong>Mesej Tambahan:</strong><br>' . nl2br(htmlspecialchars($custom_message)) . '</p>' : '') . '
                
                <p>Permohonan anda telah berjaya diterima dan sedang dalam proses semakan. Anda akan dimaklumkan tentang status permohonan anda melalui email atau telefon.</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $status_url . '" class="button">Semak Status Permohonan</a>
                </div>
                
                <p>Jika anda mempunyai sebarang pertanyaan, sila hubungi kami:</p>
                <ul>
                    <li><strong>Emel:</strong> ' . htmlspecialchars($config['support_email']) . '</li>
                    <li><strong>Telefon:</strong> ' . htmlspecialchars($config['support_phone']) . '</li>
                    <li><strong>Alamat:</strong> ' . htmlspecialchars($config['support_address']) . '</li>
                </ul>
            </div>
            
            <div class="footer">
                <p>Emel ini dihantar secara automatik oleh sistem eJawatan.</p>
                <p>Jangan balas emel ini kerana ia adalah emel sistem.</p>
            </div>
        </div>
    </body>
    </html>';
}

// Get application statistics
$stats = [];
try {
    // Total submitted applications (excluding drafts)
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM job_applications WHERE submission_locked = 1');
    $stmt->execute();
    $stats['total'] = $stmt->fetch()['total'];

    // Pending applications (status_id = 1 for New-Pending Approval, submitted only)
    $stmt = $pdo->prepare('SELECT COUNT(*) as pending FROM job_applications WHERE status_id = 1 AND submission_locked = 1');
    $stmt->execute();
    $stats['pending'] = $stmt->fetch()['pending'];

    // Recent applications (last 24 hours, submitted only)
    $stmt = $pdo->prepare('SELECT COUNT(*) as recent FROM job_applications WHERE application_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND submission_locked = 1');
    $stmt->execute();
    $stats['recent'] = $stmt->fetch()['recent'];

} catch (Exception $e) {
    $error = 'Failed to get statistics: ' . $e->getMessage();
}
?>

<?php include 'templates/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div>
            <div class="standard-container mx-auto">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Email Blaster</h1>
                    <p class="text-gray-600 mt-2">Test and send emails to applicants</p>
                </div>

                <?php if ($message): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                        <p><?php echo htmlspecialchars($message); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Applications</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['total'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['pending'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Last 24 Hours</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['recent'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Test Email -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Test Email</h2>
                            <p class="text-gray-600 text-sm">Send a test email to verify email functionality</p>
                        </div>
                        <div class="p-6">
                            <form method="POST" action="" id="test-email-form">
                                <input type="hidden" name="action" value="test_email">

                                <div class="mb-4">
                                    <label for="test_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                    <input type="email" id="test_email" name="test_email" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Enter email address to test">
                                </div>

                                <div class="mb-4">
                                    <label for="test_subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                                    <input type="text" id="test_subject" name="test_subject" maxlength="255"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           value="Test Email from eJawatan System">
                                </div>

                                <div class="mb-6">
                                    <label for="test_message" class="block text-sm font-medium text-gray-700 mb-2">
                                        Message <span class="text-sm text-gray-500">(max 5000 characters)</span>
                                    </label>
                                    <textarea id="test_message" name="test_message" rows="4" maxlength="5000"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                              placeholder="Enter test message">This is a test email from the eJawatan system.</textarea>
                                </div>

                                <button type="submit" id="test-email-btn"
                                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium py-2 px-4 rounded-md transition duration-200 flex items-center justify-center">
                                    <span class="mr-2">Send Test Email</span>
                                    <svg id="test-email-spinner" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Email Blast -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Email Blast</h2>
                            <p class="text-gray-600 text-sm">Send emails to multiple applicants</p>
                        </div>
                        <div class="p-6">
                            <form method="POST" action="" id="blast-email-form">
                                <input type="hidden" name="action" value="blast_applications">

                                <div class="mb-4">
                                    <label for="blast_type" class="block text-sm font-medium text-gray-700 mb-2">Select Recipients</label>
                                    <select id="blast_type" name="blast_type"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="all">All Applications (<?php echo number_format($stats['total'] ?? 0); ?>)</option>
                                        <option value="pending">Pending Applications (<?php echo number_format($stats['pending'] ?? 0); ?>)</option>
                                        <option value="recent">Recent Applications - Last 24 Hours (<?php echo number_format($stats['recent'] ?? 0); ?>)</option>
                                    </select>
                                </div>

                                <div class="mb-6">
                                    <label for="custom_message" class="block text-sm font-medium text-gray-700 mb-2">
                                        Custom Message (Optional) <span class="text-sm text-gray-500">(max 2000 characters)</span>
                                    </label>
                                    <textarea id="custom_message" name="custom_message" rows="4" maxlength="2000"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                              placeholder="Enter additional message to include in the email"></textarea>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <span id="custom_message_count">0</span>/2000 characters
                                    </div>
                                </div>

                                <button type="submit" id="blast-email-btn"
                                        class="w-full bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white font-medium py-2 px-4 rounded-md transition duration-200 flex items-center justify-center">
                                    <span class="mr-2">Send Email Blast</span>
                                    <svg id="blast-email-spinner" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Email Configuration Info -->
                <div class="mt-8 bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Email Configuration</h2>
                        <p class="text-gray-600 text-sm">Current email settings and status</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-3">SMTP Configuration</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span><strong>Host:</strong></span>
                                        <span class="<?php echo !empty($config['mail_host']) ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo htmlspecialchars($config['mail_host'] ?? 'Not configured'); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span><strong>Port:</strong></span>
                                        <span class="<?php echo !empty($config['mail_port']) ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo htmlspecialchars($config['mail_port'] ?? 'Not configured'); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span><strong>Username:</strong></span>
                                        <span class="<?php echo !empty($config['mail_username']) ? 'text-green-600' : 'text-yellow-600'; ?>">
                                            <?php echo !empty($config['mail_username']) ? 'Configured' : 'Not set (will use PHP mail)'; ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span><strong>Encryption:</strong></span>
                                        <span class="text-blue-600">
                                            <?php echo htmlspecialchars($config['mail_encryption'] ?? 'tls'); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span><strong>Status:</strong></span>
                                        <span class="<?php echo !empty($config['mail_host']) && !empty($config['mail_port']) ? 'text-green-600' : 'text-yellow-600'; ?>">
                                            <?php echo !empty($config['mail_host']) && !empty($config['mail_port']) ? 'SMTP Ready' : 'PHP Mail Fallback'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-3">Email Addresses</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span><strong>From Address:</strong></span>
                                        <span class="<?php echo !empty($config['email_from']) ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo htmlspecialchars($config['email_from'] ?? 'Not configured'); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span><strong>Reply To:</strong></span>
                                        <span class="<?php echo !empty($config['email_reply_to']) ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo htmlspecialchars($config['email_reply_to'] ?? 'Not configured'); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span><strong>Sender Name:</strong></span>
                                        <span class="text-green-600">
                                            <?php echo htmlspecialchars($config['email_from_name'] ?? 'eJawatan MPHS'); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span><strong>Base URL:</strong></span>
                                        <span class="<?php echo !empty($config['base_url']) ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo htmlspecialchars($config['base_url'] ?? 'Not configured'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Email Testing Recommendation -->
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-blue-800">Email Configuration Check</h4>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>Before sending bulk emails, test your configuration using the "Test Email" form above.</p>
                                        <p class="mt-1">Make sure your SMTP settings are correct and the email addresses are valid.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>

    <script>
        // Form submission protection and enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Test email form handling
            const testEmailForm = document.getElementById('test-email-form');
            const testEmailBtn = document.getElementById('test-email-btn');
            const testEmailSpinner = document.getElementById('test-email-spinner');

            if (testEmailForm) {
                testEmailForm.addEventListener('submit', function(e) {
                    // Disable button and show spinner
                    testEmailBtn.disabled = true;
                    testEmailBtn.querySelector('span').textContent = 'Sending...';
                    testEmailSpinner.classList.remove('hidden');

                    // Add visual overlay to prevent multiple submissions
                    const overlay = document.createElement('div');
                    overlay.className = 'fixed inset-0 bg-black bg-opacity-25 z-50 flex items-center justify-center';
                    overlay.innerHTML = '<div class="bg-white p-6 rounded-lg shadow-lg"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div><p class="text-gray-700">Sending test email...</p></div>';
                    document.body.appendChild(overlay);

                    // Re-enable after 60 seconds as fallback (increased from 30)
                    setTimeout(() => {
                        testEmailBtn.disabled = false;
                        testEmailBtn.querySelector('span').textContent = 'Send Test Email';
                        testEmailSpinner.classList.add('hidden');
                        if (overlay.parentNode) {
                            document.body.removeChild(overlay);
                        }
                    }, 60000);
                });
            }

            // Blast email form handling with confirmation
            const blastEmailForm = document.getElementById('blast-email-form');
            const blastEmailBtn = document.getElementById('blast-email-btn');
            const blastEmailSpinner = document.getElementById('blast-email-spinner');

            if (blastEmailForm) {
                blastEmailForm.addEventListener('submit', function(e) {
                    const blastType = document.getElementById('blast_type').value;
                    const recipientCount = document.querySelector(`option[value="${blastType}"]`).textContent.match(/\((\d+)\)/);
                    const count = recipientCount ? recipientCount[1] : '0';

                    if (!confirm(`Are you sure you want to send emails to ${count} applicants? This action cannot be undone.`)) {
                        e.preventDefault();
                        return false;
                    }

                    // Disable button and show spinner
                    blastEmailBtn.disabled = true;
                    blastEmailBtn.querySelector('span').textContent = 'Sending...';
                    blastEmailSpinner.classList.remove('hidden');

                    // Add visual overlay
                    const overlay = document.createElement('div');
                    overlay.className = 'fixed inset-0 bg-black bg-opacity-25 z-50 flex items-center justify-center';
                    overlay.innerHTML = `<div class="bg-white p-6 rounded-lg shadow-lg"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto mb-2"></div><p class="text-gray-700">Sending emails to ${count} recipients...</p><p class="text-sm text-gray-500 mt-2">This may take several minutes.</p></div>`;
                    document.body.appendChild(overlay);

                    // Re-enable after 5 minutes as fallback
                    setTimeout(() => {
                        blastEmailBtn.disabled = false;
                        blastEmailBtn.querySelector('span').textContent = 'Send Email Blast';
                        blastEmailSpinner.classList.add('hidden');
                        if (overlay.parentNode) {
                            document.body.removeChild(overlay);
                        }
                    }, 300000);
                });
            }

            // Character counter for custom message
            const customMessageField = document.getElementById('custom_message');
            const customMessageCount = document.getElementById('custom_message_count');

            if (customMessageField && customMessageCount) {
                customMessageField.addEventListener('input', function() {
                    const length = this.value.length;
                    customMessageCount.textContent = length;

                    if (length > 1800) {
                        customMessageCount.className = 'text-red-500';
                    } else if (length > 1500) {
                        customMessageCount.className = 'text-yellow-500';
                    } else {
                        customMessageCount.className = 'text-gray-500';
                    }
                });

                // Initialize counter
                customMessageField.dispatchEvent(new Event('input'));
            }

            // Auto-refresh statistics every 30 seconds
            setInterval(function() {
                // Only refresh if page is visible
                if (!document.hidden) {
                    location.reload();
                }
            }, 30000);
        });
    </script>
</body>
</html>
