<?php
/**
 * MailSender Class
 * 
 * A wrapper class for sending emails using either PHP mail() function or direct SMTP
 * This helps avoid issues with missing sendmail binary in Docker containers
 */
class MailSender {
    private $config;
    private $logger;
    
    /**
     * Constructor
     * 
     * @param array $config Configuration array from config.php
     */
    public function __construct($config) {
        $this->config = $config;
        
        // Set up logging if available
        if (class_exists('LogManager')) {
            $this->logger = LogManager::getInstance();
        }
    }
    
    /**
     * Log a message if logger is available
     * 
     * @param string $message Message to log
     * @param string $level Log level (info, warning, error)
     */
    private function log($message, $level = 'info') {
        if ($this->logger) {
            $this->logger->logFrontend($level, $message);
        }
    }
    
    /**
     * Send an email using the best available method
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param array $headers Optional additional headers
     * @return bool Success status
     */
    public function send($to, $subject, $message, $headers = []) {
        // Try SMTP first if configured (check both mail_* and smtp_* keys)
        $mail_host = $this->config['mail_host'] ?? $this->config['smtp_host'] ?? null;
        $mail_port = $this->config['mail_port'] ?? $this->config['smtp_port'] ?? null;
        
        if (!empty($mail_host) && !empty($mail_port)) {
            $this->log("Using SMTP configuration: {$mail_host}:{$mail_port}");
            return $this->sendSmtp($to, $subject, $message, $headers);
        }
        
        // Fall back to PHP mail() function
        $this->log("Using PHP mail() function as fallback");
        return $this->sendMail($to, $subject, $message, $headers);
    }
    
    /**
     * Send email using PHP mail() function
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param array $headers Optional additional headers
     * @return bool Success status
     */
    private function sendMail($to, $subject, $message, $headers = []) {
        // Prepare headers
        $defaultHeaders = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . ($this->config['email_from'] ?? 'noreply@mphs.gov.my'),
            'Reply-To: ' . ($this->config['email_reply_to'] ?? 'admin@mphs.gov.my'),
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Merge with custom headers
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        // Send email
        $result = @mail($to, $subject, $message, implode("\r\n", $allHeaders));
        
        // Log result
        if ($result) {
            $this->log("Email sent successfully to {$to} with subject: {$subject}");
        } else {
            $this->log("Failed to send email to {$to} with subject: {$subject}", 'error');
            
            // Get last error if available
            $error = error_get_last();
            if ($error) {
                $this->log("Mail error: " . $error['message'], 'error');
            }
        }
        
        return $result;
    }
    
    /**
     * Send email using direct SMTP connection
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param array $headers Optional additional headers
     * @return bool Success status
     */
    private function sendSmtp($to, $subject, $message, $headers = []) {
        // Use PHPMailer if available via Composer; otherwise attempt to load it, then fall back
        // Support both mail_* and smtp_* config keys for compatibility
        $host = $this->config['mail_host'] ?? $this->config['smtp_host'] ?? 'localhost';
        $port = (int)($this->config['mail_port'] ?? $this->config['smtp_port'] ?? 25);
        $username = $this->config['mail_username'] ?? $this->config['smtp_username'] ?? '';
        $password = $this->config['mail_password'] ?? $this->config['smtp_password'] ?? '';
        $from = $this->config['mail_from_address'] ?? $this->config['email_from'] ?? $this->config['noreply_email'] ?? 'noreply@mphs.gov.my';
        $from_name = $this->config['mail_from_name'] ?? $this->config['email_from_name'] ?? 'MPHS eJawatan';
        $reply_to = $this->config['email_reply_to'] ?? $this->config['admin_email'] ?? 'admin@mphs.gov.my';
        $encryption = strtolower($this->config['mail_encryption'] ?? $this->config['smtp_secure'] ?? ''); // '', 'ssl', 'tls'
        $timeout = (int)($this->config['mail_timeout'] ?? 30); // Increased timeout to 30 seconds

        $this->log("Attempting to send email via PHPMailer SMTP ({$host}:{$port}, enc={$encryption})");

        $hasPHPMailer = class_exists('\\PHPMailer\\PHPMailer\\PHPMailer');
        if (!$hasPHPMailer) {
            $autoloadPaths = [
                __DIR__ . '/../vendor/autoload.php',
                __DIR__ . '/../../vendor/autoload.php'
            ];
            foreach ($autoloadPaths as $autoloadPath) {
                if (file_exists($autoloadPath)) {
                    require_once $autoloadPath;
                    break;
                }
            }
            $hasPHPMailer = class_exists('\\PHPMailer\\PHPMailer\\PHPMailer');
            if ($hasPHPMailer) {
                if (!class_exists('PHPMailer')) {
                    class_alias('\\PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer');
                }
                if (class_exists('\\PHPMailer\\PHPMailer\\Exception') && !class_exists('PHPMailerException')) {
                    class_alias('\\PHPMailer\\PHPMailer\\Exception', 'PHPMailerException');
                }
            }
        }

        if (!$hasPHPMailer) {
            $this->log('PHPMailer not available. Falling back to PHP mail() SMTP headers route.', 'warning');
            return $this->sendMail($to, $subject, $message, $headers);
        }

        try {
            $mail = new PHPMailer(true);

            call_user_func([$mail, 'isSMTP']);
            $mail->{'Host'} = $host;
            $mail->{'Port'} = $port;
            $mail->{'SMTPAuth'} = !empty($username);
            if ($mail->{'SMTPAuth'}) {
                $mail->{'Username'} = $username;
                $mail->{'Password'} = $password;
            }
            if (in_array($encryption, ['ssl', 'tls'], true)) {
                $mail->{'SMTPSecure'} = $encryption;
            }
            $mail->{'Timeout'} = max(10, $timeout);
            $mail->{'CharSet'} = 'UTF-8';

            // Allow insecure connections for local/docker environments to bypass SSL certificate verification issues
            $mail->{'SMTPOptions'} = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            call_user_func([$mail, 'setFrom'], $from, $from_name);
            if (!empty($reply_to)) {
                call_user_func([$mail, 'addReplyTo'], $reply_to);
            }
            call_user_func([$mail, 'addAddress'], $to);

            call_user_func([$mail, 'isHTML'], true);
            $scheme2 = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host2 = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base2 = rtrim((string)($this->config['base_url'] ?? ($scheme2 . $host2 . '/')), '/');
            $logoRel = (string)($this->config['logo_url'] ?? '');
            if ($logoRel !== '') {
                $candidate1 = (isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') : '') . '/' . ltrim($logoRel, '/');
                $candidate2 = __DIR__ . '/../public/' . ltrim($logoRel, '/');
                $embedPath = null;
                if ($candidate1 && file_exists($candidate1)) { $embedPath = $candidate1; }
                elseif (file_exists($candidate2)) { $embedPath = $candidate2; }
                if ($embedPath) {
                    $cid = 'logo_' . uniqid();
                    $ext = strtolower(pathinfo($embedPath, PATHINFO_EXTENSION));
                    $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : (($ext === 'png') ? 'image/png' : 'image/' . $ext);
                    call_user_func([$mail, 'addEmbeddedImage'], $embedPath, $cid, basename($embedPath), 'base64', $mime);
                    $logoAbs = $base2 . '/' . ltrim($logoRel, '/');
                    $message = str_replace('src="' . $logoAbs . '"', 'src="cid:' . $cid . '"', $message);
                    $message = str_replace('src=\'' . $logoAbs . '\'', 'src=\'cid:' . $cid . '\'', $message);
                    $message = str_replace('src="/' . ltrim($logoRel, '/') . '"', 'src="cid:' . $cid . '"', $message);
                    $message = str_replace('src=\'/' . ltrim($logoRel, '/') . '\'', 'src=\'cid:' . $cid . '\'', $message);
                }
            }
            $mail->{'Subject'} = $subject;
            $mail->{'Body'} = $message;
            $mail->{'AltBody'} = strip_tags($message);

            foreach ($headers as $h) {
                $parts = explode(':', $h, 2);
                if (count($parts) === 2) {
                    call_user_func([$mail, 'addCustomHeader'], trim($parts[0]), trim($parts[1]));
                }
            }

            call_user_func([$mail, 'send']);
            $this->log("Email sent successfully to {$to} with subject: {$subject}");
            return true;
        } catch (\Throwable $e) {
            $this->log('SMTP exception: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Send a test email
     * 
     * @param string $to Recipient email
     * @param string $subject Optional subject
     * @param string $message Optional message
     * @return bool Success status
     */
    public function sendTest($to, $subject = null, $message = null) {
        $subject = $subject ?? 'Test Email from eJawatan System';
        $message = $message ?? 'This is a test email from the eJawatan system. Time: ' . date('Y-m-d H:i:s');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim((string)($this->config['base_url'] ?? ($scheme . $host . '/')), '/');
        $logo = $base . '/' . ltrim((string)($this->config['logo_url'] ?? ''), '/');
        
        // Create HTML message
        $html_message = '
        <!DOCTYPE html>
        <html lang="ms">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Test Email</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #e0f2fe; color: #1e3a8a; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="' . htmlspecialchars($logo) . '" alt="Logo" style="height:48px;margin-bottom:0">
                    <h1>Majlis Perbandaran Hulu Selangor</h1>
                    <h2>Test Email</h2>
                </div>
                
                <div class="content">
                    <p>This is a test email from the eJawatan system.</p>
                    <p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>
                    <p><strong>Sent at:</strong> ' . date('d/m/Y H:i:s') . '</p>
                    <p><strong>SMTP Host:</strong> ' . htmlspecialchars($this->config['mail_host'] ?? 'Not configured') . '</p>
                    <p><strong>SMTP Port:</strong> ' . htmlspecialchars($this->config['mail_port'] ?? 'Not configured') . '</p>
                </div>
                
                <div class="footer">
                    <p>This is a test email from the eJawatan system.</p>
                    <p>If you received this email, the email system is working correctly.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $this->send($to, $subject, $html_message);
    }
    
    /**
     * Alias for send() method to maintain compatibility
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $html_body Email body (HTML)
     * @param string $text_body Email body (Plain text - optional, will be ignored for now)
     * @param array $headers Optional additional headers
     * @return bool Success status
     */
    public function sendEmail($to, $subject, $html_body, $text_body = null, $headers = []) {
        return $this->send($to, $subject, $html_body, $headers);
    }
}
