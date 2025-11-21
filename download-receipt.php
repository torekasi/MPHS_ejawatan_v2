<?php
session_start();
require_once __DIR__ . '/includes/ErrorHandler.php';

$result = require __DIR__ . '/config.php';
$config = $result['config'] ?? $result;

$ref = $_GET['ref'] ?? '';
if (!$ref || !preg_match('/^APP-[A-Z0-9-]+$/', strtoupper($ref))) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><body style="font-family:Inter,Arial,sans-serif;padding:24px">Rujukan permohonan tidak sah.</body></html>';
    exit;
}

$pdo = null;
$application = null;
$job = null;

try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    
    $stmt = $pdo->prepare('SELECT a.*, j.job_title, j.kod_gred, j.job_code FROM application_application_main a LEFT JOIN job_postings j ON a.job_id = j.id WHERE a.application_reference = ? LIMIT 1');
    $stmt->execute([$ref]);
    $application = $stmt->fetch();

    if (!$application) {
        $stmt = $pdo->prepare('SELECT a.*, j.job_title, j.kod_gred, j.job_code FROM job_applications a LEFT JOIN job_postings j ON a.job_id = j.id WHERE a.application_reference = ? LIMIT 1');
        $stmt->execute([$ref]);
        $application = $stmt->fetch();
    }
    
    if ($application) {
        $job = [
            'job_title' => $application['job_title'] ?? '',
            'kod_gred' => $application['kod_gred'] ?? '',
            'job_code' => $application['job_code'] ?? ''
        ];
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><body style="font-family:Inter,Arial,sans-serif;padding:24px">Ralat sambungan ke pangkalan data.</body></html>';
    exit;
}

if (!$application) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body style="font-family:Inter,Arial,sans-serif;padding:24px">Rekod permohonan tidak dijumpai.</body></html>';
    exit;
}

$date_field = $application['created_at'] ?? $application['application_date'] ?? $application['submission_date'] ?? $application['submitted_at'] ?? null;
$display_date = 'N/A';
if ($date_field && !empty($date_field)) {
    $ts = strtotime($date_field);
    $display_date = ($ts !== false) ? date('d/m/Y H:i', $ts) : 'N/A';
}

$filename = 'Resit-Penerimaan-' . preg_replace('/[^A-Z0-9-]/', '', strtoupper($application['application_reference'])) . '.pdf';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muat Turun Resit Penerimaan</title>
    <style>
        body { font-family: 'Inter', Arial, sans-serif; background: #f7f9fc; }
        .container { max-width: 900px; margin: 24px auto; }
        .print-receipt { background: #fff; border: 1px solid #e5e7eb; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
        .print-header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #fff; padding: 20px; text-align: center; }
        .print-title { font-size: 20px; font-weight: 700; }
        .print-subtitle { font-size: 13px; opacity: 0.9; }
        .print-body { padding: 20px; }
        .print-row { display: grid; grid-template-columns: 220px 1fr; gap: 12px; padding: 8px 0; border-bottom: 1px dashed #e5e7eb; }
        .print-label { color: #6b7280; font-size: 13px; }
        .print-value { color: #111827; font-weight: 600; font-size: 14px; }
        .actions { margin-top: 16px; text-align: center; }
        .btn { display: inline-block; background: #1f2937; color: #fff; padding: 10px 16px; border-radius: 8px; text-decoration: none; margin: 0 6px; }
        .btn:hover { background: #111827; }
    </style>
</head>
<body>
    <div class="container">
        <div class="print-receipt" id="receipt">
            <div class="print-header">
                <div class="print-title">Penerimaan Permohonan</div>
                <div class="print-subtitle">Sistem eJawatan • Majlis Perbandaran Hulu Selangor</div>
            </div>
            <div class="print-body">
                <div class="print-row"><div class="print-label">Rujukan Permohonan</div><div class="print-value"><?php echo htmlspecialchars($application['application_reference']); ?></div></div>
                <div class="print-row"><div class="print-label">Tarikh Permohonan</div><div class="print-value"><?php echo htmlspecialchars($display_date); ?></div></div>
                <div class="print-row"><div class="print-label">Nama Pemohon</div><div class="print-value"><?php echo htmlspecialchars($application['nama_penuh'] ?? ''); ?></div></div>
                <div class="print-row"><div class="print-label">Emel Pemohon</div><div class="print-value"><?php echo htmlspecialchars($application['email'] ?? ''); ?></div></div>
                <div class="print-row"><div class="print-label">Jawatan</div><div class="print-value"><?php echo htmlspecialchars($job['job_title'] ?? ''); ?></div></div>
                <div class="print-row"><div class="print-label">Kod Gred</div><div class="print-value"><?php echo htmlspecialchars($job['kod_gred'] ?? ''); ?></div></div>
                <div class="print-row"><div class="print-label">Kod Jawatan</div><div class="print-value"><?php echo htmlspecialchars($job['job_code'] ?? ''); ?></div></div>
                <div class="print-row"><div class="print-label">Status</div><div class="print-value">Permohonan Diterima</div></div>
            </div>
        </div>
        <div class="actions">
            <a href="#" onclick="window.print(); return false;" class="btn">Cetak</a>
            <a href="semak-status.php?ref=<?php echo urlencode($application['application_reference']); ?>" class="btn" style="background:#2563eb">Semak Status</a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
    <script>
        (function(){
            const el = document.getElementById('receipt');
            if (!el) return;
            const opt = {
                margin: 0.3,
                filename: <?php echo json_encode($filename); ?>,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(el).save();
        })();
    </script>
</body>
</html>