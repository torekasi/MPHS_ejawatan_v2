<?php
/**
 * Email Template Helper Class
 * 
 * Helps load and send email templates with proper formatting
 */
class EmailTemplateHelper {
    private $config;
    private $mailSender;
    
    /**
     * Constructor
     * 
     * @param array $config Configuration array from config.php
     */
    public function __construct($config) {
        $this->config = $config;
        
        // Initialize MailSender
        require_once __DIR__ . '/MailSender.php';
        $this->mailSender = new MailSender($config);
    }
    
    /**
     * Send payment confirmation email
     * 
     * @param array $payment Payment transaction data
     * @param array $job Job posting data
     * @return bool Success status
     */
    public function sendPaymentConfirmation($payment, $job) {
        try {
            // Prepare template variables
            $template_vars = [
                'payment_reference' => $payment['payment_reference'],
                'transaction_id' => $payment['transaction_id'] ?? 'N/A',
                'applicant_name' => $payment['applicant_name'],
                'applicant_email' => $payment['applicant_email'],
                'applicant_phone' => $payment['applicant_phone'],
                'amount' => $payment['amount'],
                'payment_date' => $payment['payment_date'] ?? $payment['created_at'],
                'job_title' => $job['job_title'],
                'job_id' => $job['id'],
                'job_code' => $job['job_code'] ?? 'N/A',
                'kod_gred' => $job['kod_gred'] ?? 'N/A',
                'ad_close_date' => $job['ad_close_date'],
                'config' => $this->config
            ];
            
            // Extract variables for template
            extract($template_vars);
            
            // Load template
            $template_path = __DIR__ . '/email-templates/payment-confirmation.php';
            if (!file_exists($template_path)) {
                error_log('Payment confirmation email template not found: ' . $template_path);
                return false;
            }
            
            // Capture template output
            ob_start();
            include $template_path;
            $email_html = ob_get_clean();
            
            // Prepare email
            $to = $payment['applicant_email'];
            $subject = 'Pengesahan Pembayaran Berjaya - Permohonan Jawatan MPHS';
            
            // Send email
            $result = $this->mailSender->send($to, $subject, $email_html);
            
            if ($result) {
                error_log('[EmailTemplateHelper] Payment confirmation email sent successfully to: ' . $to);
            } else {
                error_log('[EmailTemplateHelper] Failed to send payment confirmation email to: ' . $to);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('[EmailTemplateHelper] Error sending payment confirmation email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send payment failed notification email
     * 
     * @param array $payment Payment transaction data
     * @param array $job Job posting data
     * @return bool Success status
     */
    public function sendPaymentFailed($payment, $job) {
        try {
            $to = $payment['applicant_email'];
            $subject = 'Pembayaran Tidak Berjaya - Permohonan Jawatan MPHS';
            
            $email_html = $this->generatePaymentFailedEmail($payment, $job);
            
            return $this->mailSender->send($to, $subject, $email_html);
            
        } catch (Exception $e) {
            error_log('[EmailTemplateHelper] Error sending payment failed email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate payment failed email HTML
     * 
     * @param array $payment Payment data
     * @param array $job Job data
     * @return string HTML email content
     */
    private function generatePaymentFailedEmail($payment, $job) {
        $html = '
        <!DOCTYPE html>
        <html lang="ms">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Pembayaran Tidak Berjaya</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header { background: #dc2626; color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .info-box { background: #fee2e2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; }
                .button { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>❌ Pembayaran Tidak Berjaya</h1>
                </div>
                <div class="content">
                    <p>Assalamualaikum ' . htmlspecialchars($payment['applicant_name']) . ',</p>
                    <p>Kami ingin memaklumkan bahawa pembayaran anda untuk permohonan jawatan <strong>' . htmlspecialchars($job['job_title']) . '</strong> tidak berjaya diproses.</p>
                    
                    <div class="info-box">
                        <strong>Rujukan:</strong> ' . htmlspecialchars($payment['payment_reference']) . '<br>
                        <strong>Jumlah:</strong> RM ' . number_format($payment['amount'], 2) . '<br>
                        <strong>Tarikh:</strong> ' . date('d/m/Y H:i', strtotime($payment['created_at'])) . '
                    </div>
                    
                    <p><strong>Sila cuba lagi:</strong></p>
                    <p>Anda boleh cuba membuat pembayaran semula dengan mengklik butang di bawah.</p>
                    
                    <div style="text-align: center;">
                        <a href="' . $this->config['base_url'] . '/payment-form.php?job_code=' . urlencode($job['job_code'] ?? '') . '" class="button">
                            Cuba Bayar Semula
                        </a>
                    </div>
                    
                    <p>Sekiranya masalah berterusan, sila hubungi kami di:</p>
                    <p>
                        📧 ' . htmlspecialchars($this->config['admin_email'] ?? 'admin@mphs.gov.my') . '<br>
                        📞 ' . htmlspecialchars($this->config['contact_phone'] ?? '03-6064 1111') . '
                    </p>
                </div>
                <div class="footer">
                    <p>Majlis Perbandaran Hulu Selangor<br>Sistem eJawatan</p>
                    <p>&copy; ' . date('Y') . ' MPHS. Hak Cipta Terpelihara.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Get MailSender instance for custom emails
     * 
     * @return MailSender
     */
    public function getMailSender() {
        return $this->mailSender;
    }
}
?>

