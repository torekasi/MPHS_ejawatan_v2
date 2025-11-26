<?php
/**
 * @FileID: controller_application_001
 * @Module: ApplicationController
 * @Author: Nefi
 * @LastModified: 2025-11-10T00:00:00Z
 * @SecurityTag: validated
 */
declare(strict_types=1);

// Prevent direct access
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) { http_response_code(403); exit; }

require_once __DIR__ . '/../includes/LogManager.php';

class ApplicationController
{
    /**
     * Handle full job application saving.
     * Performs basic CSRF validation and delegates to existing processor for now.
     * Returns: void (handles redirects internally)
     */
    public static function handleSaveFull(): void
    {
        // Security headers
        header('X-Frame-Options: SAMEORIGIN');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline'; font-src 'self' https://fonts.gstatic.com; connect-src 'self';");
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit();
        }

        // Session for CSRF
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // CSRF validation
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
            http_response_code(400);
            echo 'CSRF validation failed.';
            exit();
        }

        $cfgResult = require __DIR__ . '/../config.php';
        $cfg = $cfgResult['config'] ?? $cfgResult;
        $recaptchaToken = $_POST['recaptcha_token'] ?? '';
        $recaptchaAction = $cfg['recaptcha_v3_action'] ?? 'job_application';
        $recaptchaSecret = $cfg['recaptcha_v3_secret_key'] ?? (getenv('RECAPTCHA_V3_SECRET_KEY') ?: '');
        if (!empty($recaptchaSecret)) {
            $ok = self::verifyRecaptchaV3($recaptchaToken, $recaptchaSecret, $recaptchaAction, 0.5);
            if (!$ok) {
                $_SESSION['application_errors'] = ['Pengesahan keselamatan gagal. Sila cuba lagi.'];
                $_SESSION['application_data'] = $_POST;
                $redirect_url = '../job-application-full.php?job_id=' . ($_POST['job_id'] ?? '');
                if (!empty($_POST['application_id'])) { $redirect_url .= '&app_id=' . $_POST['application_id']; }
                if (!empty($_POST['application_reference'])) { $redirect_url .= '&ref=' . urlencode($_POST['application_reference']); }
                if (!empty($_POST['edit'])) { $redirect_url .= '&edit=1'; }
                header('Location: ' . $redirect_url);
                exit();
            }
        } else {
            if (($cfg['app_env'] ?? 'development') === 'production') {
                $_SESSION['application_errors'] = ['Sistem keselamatan tidak dikonfigurasi.'];
                $_SESSION['application_data'] = $_POST;
                $redirect_url = '../job-application-full.php?job_id=' . ($_POST['job_id'] ?? '');
                if (!empty($_POST['application_id'])) { $redirect_url .= '&app_id=' . $_POST['application_id']; }
                if (!empty($_POST['application_reference'])) { $redirect_url .= '&ref=' . urlencode($_POST['application_reference']); }
                if (!empty($_POST['edit'])) { $redirect_url .= '&edit=1'; }
                header('Location: ' . $redirect_url);
                exit();
            }
        }

        // Server-side timeout enforcement
        $startTs = isset($_SESSION['form_start_time']) ? (int)$_SESSION['form_start_time'] : time();
        $timeoutSeconds = isset($_SESSION['form_timeout_seconds']) ? (int)$_SESSION['form_timeout_seconds'] : 1800; // default 30m
        if ((time() - $startTs) > $timeoutSeconds) {
            $_SESSION['error'] = 'Sesi borang telah tamat. Sila mulakan semula dari halaman utama.';
            header('Location: index.php');
            exit();
        }

        // Edit verification enforcement (if edit mode)
        $isEdit = isset($_POST['edit']) && (string)$_POST['edit'] === '1';
        if ($isEdit) {
            $applicationId = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
            $applicationRef = isset($_POST['application_reference']) ? (string)$_POST['application_reference'] : '';
            $verified = false;
            if (isset($_SESSION['verified_application_id'], $_SESSION['verified_application_ref'])) {
                if (($applicationId && (int)$_SESSION['verified_application_id'] === $applicationId) ||
                    ($applicationRef && (string)$_SESSION['verified_application_ref'] === $applicationRef)) {
                    $verified = true;
                }
            }
            if (isset($_SESSION['edit_application_verified']) && $_SESSION['edit_application_verified'] === true) {
                $verified = true;
            }
            if (!$verified) {
                $_SESSION['error'] = 'Akses edit memerlukan pengesahan. Sila sahkan identiti melalui halaman Semak Status.';
                header('Location: semak-status.php');
                exit();
            }
        }

        // Sanitize a few critical inputs defensively
        $_POST['job_id'] = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
        $_POST['nama_penuh'] = isset($_POST['nama_penuh']) ? trim($_POST['nama_penuh']) : '';
        $_POST['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';

        // Use new ApplicationSaveController for proper table structure
        try {
            require_once __DIR__ . '/ApplicationSaveController.php';
            
            // Get database connection
            $result = require __DIR__ . '/../config.php';
            $config = $result['config'] ?? $result;
            
            $pdo = new PDO(
                "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
                $config['db_user'],
                $config['db_pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            
            // Ensure all tables exist
            require_once __DIR__ . '/../includes/schema.php';
            require_once __DIR__ . '/../modules/FileUploaderImplementation.php';
            create_tables($pdo);
            
            $saveController = new ApplicationSaveController($pdo, $config);
            $result = $saveController->saveApplication($_POST, $_FILES);
            
            if ($result['success']) {
                // Log successful save
                error_log('Application saved successfully: ID=' . $result['application_id'] . ', Ref=' . $result['application_reference']);
                
                // Clear any error session data
                unset($_SESSION['application_errors'], $_SESSION['application_data']);
                
                // Redirect to preview or thank you page
                if (!empty($_POST['redirect_to_preview'])) {
                    try {
                        self::sendDraftCreatedEmail($pdo, $cfg, (string)$result['application_reference']);
                    } catch (Throwable $e) {}
                    header('Location: ../preview-application.php?ref=' . urlencode($result['application_reference']));
                } else {
                    header('Location: ../application-thank-you.php?ref=' . urlencode($result['application_reference']));
                }
                exit();
            } else {
                throw new Exception('Failed to save application');
            }
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $errorTrace = $e->getTraceAsString();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();

            $logDetails = [
                'error_message' => $errorMessage,
                'error_file' => $errorFile,
                'error_line' => $errorLine,
                'post_keys' => array_keys($_POST),
                'files_keys' => array_keys($_FILES),
                'job_id' => $_POST['job_id'] ?? null,
                'application_id' => $_POST['application_id'] ?? null,
                'application_reference' => $_POST['application_reference'] ?? null,
                'edit_mode' => isset($_POST['edit']) ? 'true' : 'false',
                'trace' => $errorTrace
            ];

            log_frontend_error('Application save failed', $logDetails);

            $_SESSION['application_errors'] = ['Permohonan tidak dapat diproses buat masa ini. Sila cuba lagi.'];
            $_SESSION['application_data'] = $_POST; // Preserve form data
            unset($_SESSION['debug_info'], $_SESSION['error_trace']);

            // Redirect back to form with error
            $redirect_url = '../job-application-full.php?job_id=' . ($_POST['job_id'] ?? '');
            if (!empty($_POST['application_id'])) {
                $redirect_url .= '&app_id=' . $_POST['application_id'];
            }
            if (!empty($_POST['application_reference'])) {
                $redirect_url .= '&ref=' . urlencode($_POST['application_reference']);
            }
            if (!empty($_POST['edit'])) {
                $redirect_url .= '&edit=1';
            }
            
            header('Location: ' . $redirect_url);
            exit();
        }
    }

    private static function verifyRecaptchaV3(string $token, string $secret, string $expectedAction, float $minScore = 0.5): bool
    {
        if ($token === '' || $secret === '') { return false; }
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data),
                'timeout' => 5
            ]
        ];
        $resp = @file_get_contents($url, false, stream_context_create($opts));
        if ($resp === false) { return false; }
        $json = json_decode($resp, true);
        if (!is_array($json)) { return false; }
        if (empty($json['success'])) { return false; }
        if (isset($json['action']) && $json['action'] !== $expectedAction) { return false; }
        if (isset($json['score']) && (float)$json['score'] < $minScore) { return false; }
        return true;
    }

    public static function checkNricDuplicate(): void
    {
        header('Content-Type: application/json');
        header('X-Frame-Options: SAMEORIGIN');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline'; font-src 'self' https://fonts.gstatic.com; connect-src 'self';");
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'METHOD_NOT_ALLOWED']);
            exit();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
            http_response_code(400);
            echo json_encode(['error' => 'CSRF_FAILED']);
            exit();
        }

        $jobId = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
        $jobCode = isset($_POST['job_code']) ? trim((string)$_POST['job_code']) : '';
        $nric = isset($_POST['nombor_ic']) ? trim((string)$_POST['nombor_ic']) : '';
        $excludeId = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;

        try {
            $cfgResult = require __DIR__ . '/../config.php';
            $cfg = $cfgResult['config'] ?? $cfgResult;
            $dsn = "mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            if (!$jobId && $jobCode !== '') {
                $s = $pdo->prepare('SELECT id FROM job_postings WHERE job_code = ? LIMIT 1');
                $s->execute([$jobCode]);
                $row = $s->fetch();
                if ($row && isset($row['id'])) { $jobId = (int)$row['id']; }
            }

            require_once __DIR__ . '/../includes/DuplicateApplicationChecker.php';
            $checker = new DuplicateApplicationChecker($pdo);
            $result = $checker->checkDuplicateApplication($nric, $jobId);
            if ($result['status'] === 'duplicate_found') {
                $ref = $result['application']['application_reference'] ?? null;
                echo json_encode([
                    'exists' => true,
                    'duplicate' => true,
                    'message' => $result['message'],
                    'application_reference' => $ref,
                    'semak_status_url' => 'semak-status.php'
                ]);
            } else {
                echo json_encode(['exists' => false, 'duplicate' => false]);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'SERVER_ERROR']);
        }
        exit();
    }

    private static function sendDraftCreatedEmail(PDO $pdo, array $cfg, string $applicationRef): void
    {
        $stmt = $pdo->prepare('SELECT aa.*, jp.job_title, jp.kod_gred FROM application_application_main aa LEFT JOIN job_postings jp ON aa.job_id = jp.id WHERE aa.application_reference = ? LIMIT 1');
        $stmt->execute([$applicationRef]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$app) { return; }
        $to = trim((string)($app['email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) { return; }
        $subject = 'Draf Permohonan Dicipta - ' . $applicationRef;
        $base = rtrim((string)($cfg['base_url'] ?? ''), '/');
        $logoUrl = $base . '/' . ltrim((string)($cfg['logo_url'] ?? ''), '/');
        $prefillNric = (string)($app['nombor_ic'] ?? '');
        $statusUrl = $base . '/semak-status.php?app_ref=' . urlencode($applicationRef) . ($prefillNric !== '' ? '&nric=' . urlencode($prefillNric) : '');
        $previewUrl = $base . '/preview-application.php?ref=' . urlencode($applicationRef);
        $name = (string)($app['nama_penuh'] ?? 'Pemohon');
        $jobTitle = (string)($app['job_title'] ?? '');
        $kodGred = (string)($app['kod_gred'] ?? '');
        $createdAt = (string)($app['created_at'] ?? date('Y-m-d H:i:s'));
        $createdDisplay = date('d/m/Y H:i', strtotime($createdAt));
        $updatedAt = (string)($app['updated_at'] ?? $createdAt);
        $updatedDisplay = date('d/m/Y h:i A', strtotime($updatedAt));
        $html = '<!DOCTYPE html><html lang="ms"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Draf Permohonan</title><style>body{font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#333}.container{max-width:600px;margin:0 auto;padding:20px}.header{background:#e0f2fe;color:#1e3a8a;padding:20px;text-align:center}.content{padding:20px;background:#f9f9f9}.footer{padding:20px;text-align:center;font-size:12px;color:#666}.button{display:inline-block;padding:12px 24px;background:#ffffff;color:#2563eb;text-decoration:none;border-radius:6px;border:1px solid #2563eb}.info-box{background:#fff;padding:15px;margin:15px 0;border-left:4px solid #3b82f6}</style></head><body><div class="container"><div class="header"><img src="' . htmlspecialchars($logoUrl) . '" alt="Logo" style="height:48px;margin-bottom:0"><h1>Majlis Perbandaran Hulu Selangor</h1><h2>Draf Permohonan Disimpan</h2></div><div class="content"><p>Kepada <strong>' . htmlspecialchars($name) . '</strong>,</p><p>Permohonan anda telah <strong>disimpan sebagai draf</strong>. Sila simpan email ini sebagai rujukan.</p><div class="info-box"><h3>Maklumat Permohonan</h3><ul><li><strong>Rujukan:</strong> ' . htmlspecialchars($applicationRef) . '</li><li><strong>Jawatan:</strong> ' . htmlspecialchars($jobTitle) . '</li><li><strong>Kod Gred:</strong> ' . htmlspecialchars($kodGred) . '</li><li><strong>Tarikh Simpan:</strong> ' . htmlspecialchars($createdDisplay) . '</li><li><strong>Tarikh Kemaskini:</strong> ' . htmlspecialchars($updatedDisplay) . '</li></ul></div><p>Anda boleh menyemak dan melengkapkan permohonan anda melalui pautan berikut:</p><div style="text-align:center;margin:24px 0"><a class="button" href="' . htmlspecialchars($previewUrl) . '">Teruskan Pratonton / Lengkapkan Permohonan</a></div><div style="text-align:center;margin:12px 0"><a class="button" href="' . htmlspecialchars($statusUrl) . '">Semak Status Permohonan</a></div><div class="info-box"><h3>Peringatan</h3><ul><li>Simpan nombor rujukan ini dengan selamat</li><li>Permohonan draf belum dihantar untuk semakan</li><li>Lengkapkan semua bahagian dan klik Hantar untuk memuktamadkan</li></ul></div></div><div class="footer"><p>Email ini dijana secara automatik. Jangan balas email ini.</p><p>&copy; ' . date('Y') . ' Majlis Perbandaran Hulu Selangor</p></div></div></body></html>';
        if (!class_exists('MailSender')) { require_once __DIR__ . '/../includes/MailSender.php'; }
        $sender = new \MailSender($cfg);
        try { $sender->send($to, $subject, $html); } catch (Throwable $e) {}
    }
}

?>
