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
        if ($body === '') {
            $body = '<div style="font-family:Arial,sans-serif"><h2>Makluman Status Permohonan</h2><p>Kepada ' . htmlspecialchars((string)$name) . ',</p><p>Status permohonan anda telah dikemas kini kepada: <strong>' . htmlspecialchars((string)$statusName) . '</strong>.</p><p>Nota: ' . nl2br(htmlspecialchars($notes)) . '</p><p>Rujukan: ' . htmlspecialchars((string)$ref) . '</p></div>';
        }
        foreach ($map as $k => $v) { $subject = str_replace($k, $v, $subject); $body = str_replace($k, $v, $body); }
        return ['to' => (string)$email, 'subject' => $subject, 'body' => $body];
    }
    public function send(array $application, array $status, string $notes): bool {
        $data = $this->render($application, $status, $notes);
        if ($data['to'] === '') { return false; }
        if (!class_exists('MailSender')) { require_once __DIR__ . '/MailSender.php'; }
        $sender = new \MailSender($this->config);
        return $sender->send($data['to'], $data['subject'], $data['body']);
    }
}