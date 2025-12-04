<?php
class StatusEmailService {
    private $config;
    private $pdo;
    public function __construct(array $config, \PDO $pdo) { $this->config = $config; $this->pdo = $pdo; }
    private function value($data, $keys) { foreach ($keys as $k) { if (isset($data[$k]) && $data[$k] !== '') { return $data[$k]; } } return null; }
    private function render(array $application, array $status, string $notes): array {
        $subject = $status['email_subject'] ?? '';
        $body = $status['email_body'] ?? '';
        $name = $this->value($application, ['nama_penuh','name']);
        $ref = $this->value($application, ['application_reference','rujukan_permohonan']);
        $email = $this->value($application, ['email','emel']);
        $job = $this->value($application, ['job_title','jawatan_dipohon']);
        $kod = $this->value($application, ['kod_gred','grade_code']);
        $statusName = $status['name'] ?? ($status['code'] ?? '');
        
        $map = [
            '{APPLICANT_NAME}' => (string)$name,
            '{APPLICATION_REFERENCE}' => (string)$ref,
            '{STATUS_NAME}' => (string)$statusName,
            '{STATUS_CODE}' => (string)($status['code'] ?? ''),
            '{JOB_TITLE}' => (string)$job,
            '{KOD_GRED}' => (string)$kod,
            '{NOTES}' => (string)$notes,
            '{BASE_URL}' => (string)($this->config['base_url'] ?? ''),
        ];
        
        if ($subject === '') { $subject = 'Makluman Status Permohonan ' . ($ref ?? ''); }
        
        // Process placeholders in subject
        foreach ($map as $k => $v) { $subject = str_replace($k, $v, $subject); }

        // Prepare content for the standard layout
        if ($body === '') {
            // Default content structure
            $content = '<p>Kepada <strong>' . htmlspecialchars((string)$name) . '</strong>,</p>
            <p>Status permohonan anda telah dikemas kini.</p>
            
            <div class="info-box">
                <h3>Butiran Status</h3>
                <ul>
                    <li><strong>Status Terkini:</strong> <span style="color:#2563eb;font-weight:bold">' . htmlspecialchars((string)$statusName) . '</span></li>
                    <li><strong>Rujukan:</strong> ' . htmlspecialchars((string)$ref) . '</li>
                    <li><strong>Jawatan:</strong> ' . htmlspecialchars((string)$job) . '</li>
                </ul>
            </div>';
            
            if (!empty($notes)) {
                $content .= '<div class="info-box" style="border-left-color:#f59e0b;">
                    <h3 style="color:#92400e;">Catatan</h3>
                    <p>' . nl2br(htmlspecialchars($notes)) . '</p>
                </div>';
            }
            
            $content .= '<p>Sila log masuk ke sistem eJawatan untuk maklumat lanjut.</p>';
            $content .= '<div style="text-align:center;margin:20px 0;"><a href="' . htmlspecialchars((string)($this->config['base_url'] ?? '')) . '/semak-status.php" class="button">Semak Status</a></div>';
        } else {
            // Use provided body template and replace placeholders
            foreach ($map as $k => $v) { $body = str_replace($k, $v, $body); }
            $content = $body;
        }

        // Use standard layout
        if (!function_exists('generateStandardEmailLayout')) {
            require_once __DIR__ . '/ApplicationEmailTemplates.php';
        }
        
        $fullHtml = generateStandardEmailLayout(
            'Makluman Status Permohonan',
            'Status Permohonan Dikemaskini',
            $content,
            $this->config
        );

        return ['to' => (string)$email, 'subject' => $subject, 'body' => $fullHtml];
    }
    public function send(array $application, array $status, string $notes): bool {
        $data = $this->render($application, $status, $notes);
        if ($data['to'] === '') { return false; }
        if (!class_exists('MailSender')) { require_once __DIR__ . '/MailSender.php'; }
        $sender = new \MailSender($this->config);
        return $sender->send($data['to'], $data['subject'], $data['body']);
    }
}