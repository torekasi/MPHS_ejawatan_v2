<?php
/**
 * Notification Service for Job Applications
 * Handles email and SMS notifications
 */

class NotificationService {
    private $config;
    private $pdo;
    
    public function __construct($config, $pdo) {
        $this->config = $config;
        $this->pdo = $pdo;
    }
    
    /**
     * Send application submission notification
     */
    public function sendApplicationSubmissionNotification($application_id) {
        try {
            // Set timeout for the entire notification process
            set_time_limit(25); // 25 seconds timeout
            
            // Get application details
            $stmt = $this->pdo->prepare('
                SELECT a.*, j.job_title, j.kod_gred 
                FROM job_applications a 
                LEFT JOIN job_postings j ON a.job_id = j.id 
                WHERE a.id = ?
            ');
            $stmt->execute([$application_id]);
            $application = $stmt->fetch();
            
            if (!$application) {
                throw new Exception('Application not found');
            }
            
            // Send email notification with timeout
            $email_sent = false;
            try {
                $this->sendEmailNotification($application);
                $email_sent = true;
            } catch (Exception $e) {
                // Log email error but continue with SMS
                $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
                if (strpos($script_path, '/admin/') !== false) {
                    log_admin_error('Email notification failed', [
                        'application_id' => $application_id,
                        'error' => $e->getMessage()
                    ]);
                } else {
                    log_frontend_error('Email notification failed', [
                        'application_id' => $application_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Send SMS notification with timeout
            $sms_sent = false;
            try {
                $this->sendSMSNotification($application);
                $sms_sent = true;
            } catch (Exception $e) {
                // Log SMS error but continue
                $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
                if (strpos($script_path, '/admin/') !== false) {
                    log_admin_error('SMS notification failed', [
                        'application_id' => $application_id,
                        'error' => $e->getMessage()
                    ]);
                } else {
                    log_frontend_error('SMS notification failed', [
                        'application_id' => $application_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Log notification sent (even if partial)
            $this->logNotificationSent($application_id, 'SUBMISSION_CONFIRMATION');
            
            // Return true if at least one notification was sent
            return ($email_sent || $sms_sent);
            
        } catch (Exception $e) {
            // Determine if this is admin or frontend context
            $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($script_path, '/admin/') !== false) {
                log_admin_error('Notification sending failed', [
                    'application_id' => $application_id,
                    'error' => $e->getMessage()
                ]);
            } else {
                log_frontend_error('Notification sending failed', [
                    'application_id' => $application_id,
                    'error' => $e->getMessage()
                ]);
            }
            return false;
        }
    }
    
    /**
     * Send email notification
     */
    private function sendEmailNotification($application) {
        try {
            // Set timeout for email sending
            set_time_limit(10); // 10 seconds timeout for email
            
            $to = $application['email'];
            $subject = 'Pengesahan Permohonan Jawatan - ' . $application['application_reference'];
            
            $message = $this->generateEmailContent($application);
            
            // Try to send email using configured SMTP settings
            $mail_sent = $this->sendEmailViaSMTP($to, $subject, $message);
            
            if ($mail_sent) {
                // Determine context and log appropriately
                $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
                if (strpos($script_path, '/admin/') !== false) {
                    log_admin_info('Email notification sent', [
                        'application_id' => $application['id'],
                        'email' => $to,
                        'reference' => $application['application_reference']
                    ]);
                } else {
                    log_frontend_info('Email notification sent', [
                        'application_id' => $application['id'],
                        'email' => $to,
                        'reference' => $application['application_reference']
                    ]);
                }
            } else {
                // Log the failure but don't throw exception
                $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
                if (strpos($script_path, '/admin/') !== false) {
                    log_admin_error('Email notification failed', [
                        'application_id' => $application['id'],
                        'email' => $to,
                        'subject' => $subject,
                        'note' => 'SMTP email sending failed'
                    ]);
                } else {
                    log_frontend_error('Email notification failed', [
                        'application_id' => $application['id'],
                        'email' => $to,
                        'subject' => $subject,
                        'note' => 'SMTP email sending failed'
                    ]);
                }
            }
            
            // Always return true to allow the application to continue
            return true;
            
        } catch (Exception $e) {
            // Determine context and log appropriately
            $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($script_path, '/admin/') !== false) {
                log_admin_error('Email notification failed', [
                    'application_id' => $application['id'],
                    'email' => $application['email'],
                    'error' => $e->getMessage()
                ]);
            } else {
                log_frontend_error('Email notification failed', [
                    'application_id' => $application['id'],
                    'email' => $application['email'],
                    'error' => $e->getMessage()
                ]);
            }
            
            // Return true anyway to not block the application flow
            return true;
        }
    }
    
    /**
     * Send email via SMTP
     */
    private function sendEmailViaSMTP($to, $subject, $message) {
        try {
            // Use centralized MailSender (PHPMailer via Composer if available)
            if (!class_exists('MailSender')) {
                require_once __DIR__ . '/MailSender.php';
            }
            $sender = new MailSender($this->config);
            return $sender->send($to, $subject, $message);
        } catch (Exception $e) {
            // Log SMTP error via existing channels
            $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
            $context = [
                'email' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'smtp_host' => $this->config['mail_host'] ?? 'not set',
                'smtp_port' => $this->config['mail_port'] ?? 'not set'
            ];
            if (strpos($script_path, '/admin/') !== false) {
                log_admin_error('SMTP email sending failed', $context);
            } else {
                log_frontend_error('SMTP email sending failed', $context);
            }
            return false;
        }
    }
    
    /**
     * Send SMS notification
     */
    private function sendSMSNotification($application) {
        try {
            // Set timeout for SMS sending
            set_time_limit(10); // 10 seconds timeout for SMS
            
            $phone = $this->formatPhoneNumber($application['nombor_telefon'] ?? '');
            $message = $this->generateSMSContent($application);
            
            // Use configured SMS gateway
            $sms_gateway = $this->config['sms_gateway'] ?? 'default';
            
            switch ($sms_gateway) {
                case 'twilio':
                    $this->sendSMSTwilio($phone, $message);
                    break;
                case 'nexmo':
                    $this->sendSMSNexmo($phone, $message);
                    break;
                default:
                    $this->sendSMSDefault($phone, $message);
                    break;
            }
            
        } catch (Exception $e) {
            // Determine context and log appropriately
            $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($script_path, '/admin/') !== false) {
                log_admin_error('SMS notification failed', [
                    'application_id' => $application['id'],
                    'phone' => $application['nombor_telefon'] ?? '',
                    'error' => $e->getMessage()
                ]);
            } else {
                log_frontend_error('SMS notification failed', [
                    'application_id' => $application['id'],
                    'phone' => $application['nombor_telefon'] ?? '',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Generate email content
     */
    private function generateEmailContent($application) {
        $status_url = $this->config['base_url'] . 'application-status.php?ref=' . $application['application_reference'];
        
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
            </style>
        </head>
        <body>
            <div class="container">
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
                            <li><strong>Tarikh Permohonan:</strong> ' . 
                                (function($date_field) {
                                    if ($date_field && !empty($date_field)) {
                                        $timestamp = strtotime($date_field);
                                        return ($timestamp !== false) ? date('d/m/Y H:i', $timestamp) : 'N/A';
                                    }
                                    return 'N/A';
                                })($application['application_date'] ?? $application['created_at'] ?? null) . '</li>
                        </ul>
                    </div>
                    
                    <p>Permohonan anda telah berjaya diterima dan sedang dalam proses semakan. Anda akan dimaklumkan tentang status permohonan anda melalui email atau telefon.</p>
                    
                    <p><strong>Status Semasa:</strong> Menunggu Semakan</p>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $status_url . '" class="button">Semak Status Permohonan</a>
                    </div>
                    
                    <div class="info-box">
                        <h3>Maklumat Penting:</h3>
                        <ul>
                            <li>Simpan rujukan permohonan anda untuk rujukan akan datang</li>
                            <li>Proses semakan mungkin mengambil masa 2-4 minggu</li>
                            <li>Pastikan maklumat hubungan anda adalah tepat dan terkini</li>
                        </ul>
                    </div>
                    
                    <p>Jika anda mempunyai sebarang pertanyaan, sila hubungi kami:</p>
                    <ul>
                        <li><strong>Telefon:</strong> 03-6064 1124</li>
                        <li><strong>Email:</strong> admin@mphs.gov.my</li>
                        <li><strong>Alamat:</strong> Majlis Perbandaran Hulu Selangor, Kuala Kubu Bharu, Selangor</li>
                    </ul>
                </div>
                
                <div class="footer">
                    <p>Email ini dihantar secara automatik. Sila jangan balas email ini.</p>
                    <p>&copy; ' . date('Y') . ' Majlis Perbandaran Hulu Selangor. Hak cipta terpelihara.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Generate SMS content
     */
    private function generateSMSContent($application) {
        return 'MPHS: Permohonan anda (' . $application['application_reference'] . ') telah diterima. Status: Menunggu Semakan. Semak di: ' . $this->config['base_url'] . 'application-status.php?ref=' . $application['application_reference'];
    }
    
    /**
     * Format phone number for SMS
     */
    private function formatPhoneNumber($phone) {
        // Handle null or empty phone numbers
        if (empty($phone)) {
            return '';
        }
        
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present
        if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            $phone = '60' . substr($phone, 1);
        }
        
        return $phone;
    }
    
    /**
     * Send SMS using Twilio
     */
    private function sendSMSTwilio($phone, $message) {
        $account_sid = $this->config['twilio_account_sid'] ?? '';
        $auth_token = $this->config['twilio_auth_token'] ?? '';
        $from_number = $this->config['twilio_from_number'] ?? '';
        
        if (empty($account_sid) || empty($auth_token) || empty($from_number)) {
            throw new Exception('Twilio configuration missing');
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        $data = [
            'From' => $from_number,
            'To' => '+' . $phone,
            'Body' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$account_sid}:{$auth_token}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8); // 8 seconds timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 seconds connection timeout
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            throw new Exception('Twilio SMS curl error: ' . $curl_error);
        }
        
        if ($http_code !== 201) {
            throw new Exception('Twilio SMS failed: ' . $response);
        }
        
        // Determine context and log appropriately
        $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($script_path, '/admin/') !== false) {
            log_admin_info('SMS sent via Twilio', [
                'phone' => $phone,
                'response' => $response
            ]);
        } else {
            log_frontend_info('SMS sent via Twilio', [
                'phone' => $phone,
                'response' => $response
            ]);
        }
    }
    
    /**
     * Send SMS using Nexmo
     */
    private function sendSMSNexmo($phone, $message) {
        $api_key = $this->config['nexmo_api_key'] ?? '';
        $api_secret = $this->config['nexmo_api_secret'] ?? '';
        $from = $this->config['nexmo_from'] ?? 'MPHS';
        
        if (empty($api_key) || empty($api_secret)) {
            throw new Exception('Nexmo configuration missing');
        }
        
        $url = 'https://rest.nexmo.com/sms/json';
        $data = [
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'to' => $phone,
            'from' => $from,
            'text' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8); // 8 seconds timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 seconds connection timeout
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            throw new Exception('Nexmo SMS curl error: ' . $curl_error);
        }
        
        if ($http_code !== 200) {
            throw new Exception('Nexmo SMS failed: ' . $response);
        }
        
        // Determine context and log appropriately
        $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($script_path, '/admin/') !== false) {
            log_admin_info('SMS sent via Nexmo', [
                'phone' => $phone,
                'response' => $response
            ]);
        } else {
            log_frontend_info('SMS sent via Nexmo', [
                'phone' => $phone,
                'response' => $response
            ]);
        }
    }
    
    /**
     * Send SMS using default method (log only for development)
     */
    private function sendSMSDefault($phone, $message) {
        // For development/testing, just log the SMS
        // Determine context and log appropriately
        $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($script_path, '/admin/') !== false) {
            log_admin_info('SMS would be sent (development mode)', [
                'phone' => $phone,
                'message' => $message
            ]);
        } else {
            log_frontend_info('SMS would be sent (development mode)', [
                'phone' => $phone,
                'message' => $message
            ]);
        }
        
        // In production, you would integrate with your SMS provider here
        // For now, we'll simulate success
        return true;
    }
    
    /**
     * Log notification sent
     */
    private function logNotificationSent($application_id, $type) {
        try {
            // First check if the table exists
            $tableExists = false;
            try {
                $checkTable = $this->pdo->query("SHOW TABLES LIKE 'application_notifications'");
                $tableExists = ($checkTable->rowCount() > 0);
            } catch (Exception $e) {
                // Table doesn't exist, we'll create it
            }
            
            // Create the table if it doesn't exist
            if (!$tableExists) {
                $this->pdo->exec("
                    CREATE TABLE IF NOT EXISTS `application_notifications` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `application_id` int(11) NOT NULL,
                      `notification_type` varchar(50) NOT NULL,
                      `title` varchar(255) NOT NULL,
                      `message` text NOT NULL,
                      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `sent_at` datetime DEFAULT NULL,
                      `status` varchar(20) NOT NULL DEFAULT 'PENDING',
                      PRIMARY KEY (`id`),
                      KEY `application_id` (`application_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
            }
            
            // Now insert the notification record
            $stmt = $this->pdo->prepare('
                INSERT INTO application_notifications (application_id, notification_type, title, message, created_at, sent_at, status) 
                VALUES (?, ?, ?, ?, NOW(), NOW(), "SENT")
            ');
            
            $title = 'Application Submission Confirmation';
            $message = 'Email and SMS notifications sent for application ID: ' . $application_id;
            
            $stmt->execute([$application_id, $type, $title, $message]);
            
            // Determine context and log appropriately
            $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($script_path, '/admin/') !== false) {
                log_admin_info('Notification logged successfully', [
                    'application_id' => $application_id,
                    'type' => $type
                ]);
            } else {
                log_frontend_info('Notification logged successfully', [
                    'application_id' => $application_id,
                    'type' => $type
                ]);
            }
            
        } catch (Exception $e) {
            // Determine context and log appropriately
            $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($script_path, '/admin/') !== false) {
                log_admin_error('Failed to log notification', [
                    'application_id' => $application_id,
                    'error' => $e->getMessage()
                ]);
            } else {
                log_frontend_error('Failed to log notification', [
                    'application_id' => $application_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
?>


