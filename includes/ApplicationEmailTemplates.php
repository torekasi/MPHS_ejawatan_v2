<?php
/**
 * Application Email Templates
 * 
 * Contains functions to generate email templates for job applications
 */

/**
 * Generate a standard email layout wrapper
 * 
 * @param string $title Meta title of the email
 * @param string $subtitle Heading subtitle (e.g. "Draf Permohonan Disimpan")
 * @param string $content HTML content to inject into the body
 * @param array $config Configuration array
 * @return string Full HTML email
 */
function generateStandardEmailLayout($title, $subtitle, $content, $config) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? ($config['base_url_host'] ?? 'localhost');
    $base = rtrim((string)($config['base_url'] ?? ($scheme . $host . '/')), '/');
    $logoUrl = $base . '/' . ltrim((string)($config['logo_url'] ?? ''), '/');
    
    return '<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #374151; margin: 0; padding: 0; background-color: #f3f4f6; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        .header { background-color: #e0f2fe; padding: 40px 20px; text-align: center; border-bottom: 4px solid #3b82f6; }
        .header img { height: 80px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1e3a8a; font-size: 24px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2; }
        .header h2 { margin: 15px 0 0; color: #1d4ed8; font-size: 20px; font-weight: 600; }
        .content { padding: 40px; background-color: #ffffff; }
        .content p { margin-bottom: 16px; line-height: 1.8; font-size: 16px; }
        .info-box { background-color: #ffffff; border: 1px solid #e5e7eb; border-left: 5px solid #3b82f6; padding: 25px; margin: 25px 0; border-radius: 6px; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
        .info-box h3 { margin-top: 0; margin-bottom: 15px; color: #111827; font-size: 18px; font-weight: 700; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }
        .info-box ul { margin: 0; padding-left: 0; list-style-type: none; }
        .info-box li { margin-bottom: 10px; color: #4b5563; font-size: 15px; display: flex; align-items: flex-start; }
        .info-box li strong { color: #1f2937; min-width: 140px; display: inline-block; font-weight: 600; }
        .button { display: inline-block; padding: 14px 28px; background-color: #2563eb; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 600; text-align: center; margin: 10px 0; transition: background-color 0.2s; }
        .button:hover { background-color: #1d4ed8; }
        .footer { background-color: #f9fafb; padding: 30px 20px; text-align: center; font-size: 13px; color: #6b7280; border-top: 1px solid #e5e7eb; }
        .footer p { margin: 5px 0; }
        .footer a { color: #2563eb; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .container { margin: 0; border-radius: 0; }
            .content { padding: 25px; }
            .info-box li { flex-direction: column; }
            .info-box li strong { margin-bottom: 4px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="' . htmlspecialchars($logoUrl) . '" alt="MPHS Logo">
            <h1>Majlis Perbandaran Hulu Selangor</h1>
            <h2>' . htmlspecialchars($subtitle) . '</h2>
        </div>
        <div class="content">
            ' . $content . '
        </div>
        <div class="footer">
            <p>Emel ini dijana secara automatik oleh Sistem eJawatan MPHS.</p>
            <p>Sila jangan balas emel ini.</p>
            <p>&copy; ' . date('Y') . ' Majlis Perbandaran Hulu Selangor</p>
        </div>
    </div>
</body>
</html>';
}

/**
 * Generate application confirmation email template
 * 
 * @param array $application Application data
 * @return string HTML email content
 */
function generateApplicationConfirmationEmail($application) {
    $formatted_date = date('d/m/Y H:i:s', strtotime($application['application_date'] ?? $application['created_at'] ?? date('Y-m-d H:i:s')));
    $job_title = trim((string)($application['job_title'] ?? ''));
    $kod_gred = trim((string)($application['kod_gred'] ?? ''));
    $job_code = trim((string)($application['job_code'] ?? ''));
    
    // Fallback to fetch job details if missing
    if ($job_title === '' || $kod_gred === '' || $job_code === '') {
        try {
            $cfgLoad2 = @require __DIR__ . '/../config.php';
            $cfg2 = is_array($cfgLoad2) && isset($cfgLoad2['config']) ? $cfgLoad2['config'] : (is_array($cfgLoad2) ? $cfgLoad2 : []);
            if (!empty($application['job_id'])) {
                $dsn = "mysql:host=" . ($cfg2['db_host'] ?? '') . ";dbname=" . ($cfg2['db_name'] ?? '') . ";charset=utf8mb4";
                $pdo2 = new \PDO($dsn, ($cfg2['db_user'] ?? ''), ($cfg2['db_pass'] ?? ''), [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $stmtJ = $pdo2->prepare('SELECT job_title, kod_gred, job_code FROM job_postings WHERE id = ? LIMIT 1');
                $stmtJ->execute([ (int)$application['job_id'] ]);
                $jr = $stmtJ->fetch();
                if ($jr) {
                    if ($job_title === '') { $job_title = (string)($jr['job_title'] ?? ''); }
                    if ($kod_gred === '') { $kod_gred = (string)($jr['kod_gred'] ?? ''); }
                    if ($job_code === '') { $job_code = (string)($jr['job_code'] ?? ''); }
                }
            }
        } catch (\Throwable $e) {}
    }
    
    $cfgLoad = @require __DIR__ . '/../config.php';
    $cfg = is_array($cfgLoad) && isset($cfgLoad['config']) ? $cfgLoad['config'] : (is_array($cfgLoad) ? $cfgLoad : []);

    // Build Inner Content
    $name = htmlspecialchars($application['nama_penuh'] ?? 'Pemohon');
    $ref = htmlspecialchars($application['application_reference'] ?? 'N/A');
    
    $content = '<p>Kepada <strong>' . $name . '</strong>,</p>
    <p>Terima kasih kerana memohon jawatan di Majlis Perbandaran Hulu Selangor. Permohonan anda telah diterima dan sedang dalam proses semakan.</p>
    
    <div class="info-box">
        <h3>Butiran Permohonan</h3>
        <ul>
            <li><strong>Nombor Rujukan:</strong> ' . $ref . '</li>
            <li><strong>Jawatan:</strong> ' . htmlspecialchars($job_title) . '</li>
            <li><strong>Kod Gred:</strong> ' . htmlspecialchars($kod_gred) . '</li>
            <li><strong>Kod Jawatan:</strong> ' . htmlspecialchars($job_code) . '</li>
            <li><strong>Tarikh:</strong> ' . $formatted_date . '</li>
        </ul>
    </div>

    <div class="info-box">
        <h3>Langkah Seterusnya</h3>
        <p style="margin-bottom:10px;">Permohonan anda akan melalui proses berikut:</p>
        <ul style="list-style-type: disc; padding-left: 20px;">
            <li>Semakan dokumen dan kelayakan</li>
            <li>Panggilan temu duga (jika layak)</li>
            <li>Keputusan permohonan</li>
        </ul>
    </div>
    
    <p>Anda boleh menyemak status permohonan anda pada bila-bila masa di laman web eJawatan.</p>';

    return generateStandardEmailLayout(
        'Pengesahan Penerimaan Permohonan Jawatan', 
        'Permohonan Diterima', 
        $content, 
        $cfg
    );
}
