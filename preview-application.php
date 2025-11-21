<?php
// Fresh public preview page (read-only) by application reference
// This block renders the entire page and exits, ensuring old code below is not executed
session_start();
require_once __DIR__ . '/includes/bootstrap.php';

try {
    $config = require __DIR__ . '/config.php';
    $db = get_database_connection($config);
    $pdo = $db['pdo'];
} catch (Throwable $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><body><p>Ralat sambungan pangkalan data.</p></body></html>';
    exit;
}

// Ensure we have a valid DB connection (get_database_connection returns ['pdo'=>null] on failure)
if (!$pdo instanceof PDO) {
    if (function_exists('log_error')) {
        log_error('Database connection not available in preview-application', $db);
    }
    http_response_code(500);
    echo '<!DOCTYPE html><html><body><p>Ralat sistem: sambungan pangkalan data tidak tersedia.</p></body></html>';
    exit;
}

function h($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

if (!function_exists('getPostData')) {
    function getPostData($key, $default = '') {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('getPostArray')) {
    function getPostArray($key) {
        return $_POST[$key] ?? [];
    }
}

if (!function_exists('getPostDataUppercase')) {
    function getPostDataUppercase($key, $default = '') {
        $value = $_POST[$key] ?? $default;
        if ($key === 'email' || strpos($key, 'email') !== false || filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $value;
        }
        return is_string($value) ? mb_strtoupper($value) : $value;
    }
}

if (!function_exists('formatComputerSkillsData')) {
    function formatComputerSkillsData($skills) {
        if (!is_array($skills)) return [];
        $formatted = [];
        foreach ($skills as $index => $skill) {
            if (!empty($skill['nama_perisian'])) {
                $formatted[] = [
                    'nama_perisian' => mb_strtoupper($skill['nama_perisian']),
                    'tahap_kemahiran' => mb_strtoupper($skill['tahap_kemahiran'] ?? '')
                ];
            }
        }
        return $formatted;
    }
}

if (!function_exists('formatLanguageSkillsData')) {
    function formatLanguageSkillsData($skills) {
        if (!is_array($skills)) return [];
        $formatted = [];
        foreach ($skills as $index => $skill) {
            if (!empty($skill['bahasa'])) {
                $formatted[] = [
                    'bahasa' => mb_strtoupper($skill['bahasa']),
                    'pertuturan' => mb_strtoupper($skill['pertuturan'] ?? ''),
                    'penulisan' => mb_strtoupper($skill['penulisan'] ?? ''),
                    'gred_spm' => mb_strtoupper($skill['gred_spm'] ?? '')
                ];
            }
        }
        return $formatted;
    }
}

if (!function_exists('formatProfessionalBodyData')) {
    function formatProfessionalBodyData($bodies) {
        if (!is_array($bodies)) return [];
        $formatted = [];
        foreach ($bodies as $index => $body) {
            if (!empty($body['nama_lembaga'])) {
                $certificate_path = '';
                if (isset($_FILES['badan_profesional']['name'][$index]['salinan_sijil']) &&
                    ($_FILES['badan_profesional']['error'][$index]['salinan_sijil'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $file = $_FILES['badan_profesional']['tmp_name'][$index]['salinan_sijil'];
                    $file_name = $_FILES['badan_profesional']['name'][$index]['salinan_sijil'];
                    $file_type = $_FILES['badan_profesional']['type'][$index]['salinan_sijil'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $content = @file_get_contents($file);
                    if ($content !== false) {
                        if (in_array($file_ext, ['jpg','jpeg','png','gif','webp'])) {
                            $certificate_path = 'data:' . $file_type . ';base64,' . base64_encode($content);
                        } elseif ($file_ext === 'pdf') {
                            $temp_dir = sys_get_temp_dir();
                            $temp_file = $temp_dir . '/preview_prof_' . uniqid() . '.pdf';
                            file_put_contents($temp_file, $content);
                            $certificate_path = $temp_file;
                        }
                    }
                }
                $formatted[] = [
                    'nama_lembaga' => mb_strtoupper($body['nama_lembaga']),
                    'no_ahli' => mb_strtoupper($body['no_ahli'] ?? ''),
                    'sijil' => mb_strtoupper($body['sijil'] ?? ''),
                    'tarikh_sijil' => $body['tarikh_sijil'] ?? '',
                    'certificate_path' => $certificate_path,
                    'certificate_name' => $_FILES['badan_profesional']['name'][$index]['salinan_sijil'] ?? ''
                ];
            }
        }
        return $formatted;
    }
}

if (!function_exists('formatEducationData')) {
    function formatEducationData($persekolahan) {
        if (!is_array($persekolahan)) return [];
        $formatted = [];
        foreach ($persekolahan as $index => $edu) {
            if (!empty($edu['institusi'])) {
                $sijil_path = '';
                $sijil_error = '';
                if (isset($_FILES['persekolahan']['name'][$index]['sijil']) &&
                    ($_FILES['persekolahan']['error'][$index]['sijil'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    if (($_FILES['persekolahan']['error'][$index]['sijil'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                        $file = $_FILES['persekolahan']['tmp_name'][$index]['sijil'];
                        $file_name = $_FILES['persekolahan']['name'][$index]['sijil'];
                        $file_type = $_FILES['persekolahan']['type'][$index]['sijil'];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $content = @file_get_contents($file);
                        if ($content !== false) {
                            if (in_array($file_ext, ['jpg','jpeg','png','gif','webp'])) {
                                $sijil_path = 'data:' . $file_type . ';base64,' . base64_encode($content);
                            } elseif ($file_ext === 'pdf') {
                                $temp_dir = sys_get_temp_dir();
                                $temp_file = $temp_dir . '/preview_' . uniqid() . '.pdf';
                                file_put_contents($temp_file, $content);
                                $sijil_path = $temp_file;
                            }
                        }
                    } else {
                        $sijil_error = 'Ralat muat naik fail. Kod: ' . ($_FILES['persekolahan']['error'][$index]['sijil'] ?? '');
                    }
                } elseif (!empty($edu['sijil'])) {
                    $sijil_path = $edu['sijil'];
                }
                $formatted[] = [
                    'institusi' => mb_strtoupper($edu['institusi']),
                    'tempoh' => (function($e){
                        return (isset($e['dari_bulan'],$e['dari_tahun'],$e['hingga_bulan'],$e['hingga_tahun']))
                            ? (['1'=>'Januari','2'=>'Februari','3'=>'Mac','4'=>'April','5'=>'Mei','6'=>'Jun','7'=>'Julai','8'=>'Ogos','9'=>'September','10'=>'Oktober','11'=>'November','12'=>'Disember'][$e['dari_bulan']] ?? '') . ' ' . ($e['dari_tahun'] ?? '') . ' - ' . ((['1'=>'Januari','2'=>'Februari','3'=>'Mac','4'=>'April','5'=>'Mei','6'=>'Jun','7'=>'Julai','8'=>'Ogos','9'=>'September','10'=>'Oktober','11'=>'November','12'=>'Disember'][$e['hingga_bulan']] ?? '') . ' ' . ($e['hingga_tahun'] ?? '')) : '';
                    })($edu),
                    'kelayakan' => mb_strtoupper($edu['kelayakan'] ?? ''),
                    'gred' => mb_strtoupper($edu['gred'] ?? ''),
                    'sijil' => $sijil_path,
                    'sijil_display_name' => mb_strtoupper($edu['sijil_original_name'] ?? basename($sijil_path)),
                    'sijil_type' => $edu['sijil_type'] ?? '',
                    'sijil_ext' => $edu['sijil_ext'] ?? strtolower(pathinfo($sijil_path, PATHINFO_EXTENSION)),
                    'sijil_error' => $sijil_error
                ];
            }
        }
        return $formatted;
    }
}

if (!function_exists('formatWorkExperienceData')) {
    function formatWorkExperienceData($pengalaman_kerja) {
        if (!is_array($pengalaman_kerja)) return [];
        $months = ['1'=>'Januari','2'=>'Februari','3'=>'Mac','4'=>'April','5'=>'Mei','6'=>'Jun','7'=>'Julai','8'=>'Ogos','9'=>'September','10'=>'Oktober','11'=>'November','12'=>'Disember'];
        $formatted = [];
        foreach ($pengalaman_kerja as $work) {
            if (!empty($work['syarikat'])) {
                $from = isset($work['dari_bulan'],$work['dari_tahun']) ? (($months[$work['dari_bulan']] ?? '') . ' ' . ($work['dari_tahun'] ?? '')) : '';
                $to = isset($work['hingga_bulan'],$work['hingga_tahun']) ? (($months[$work['hingga_bulan']] ?? '') . ' ' . ($work['hingga_tahun'] ?? '')) : '';
                $formatted[] = [
                    'syarikat' => mb_strtoupper($work['syarikat']),
                    'jawatan' => mb_strtoupper($work['jawatan'] ?? ''),
                    'tempoh' => $from . ' - ' . $to,
                    'gaji' => mb_strtoupper($work['gaji'] ?? ''),
                    'alasan' => mb_strtoupper($work['alasan'] ?? '')
                ];
            }
        }
        return $formatted;
    }
}

if (!function_exists('formatExtracurricularData')) {
    function formatExtracurricularData($activities) {
        if (!is_array($activities)) return [];
        $formatted = [];
        foreach ($activities as $index => $activity) {
            if (!empty($activity['sukan_persatuan_kelab'])) {
                $certificate_path = '';
                if (isset($_FILES['kegiatan_luar']['name'][$index]['salinan_sijil']) &&
                    ($_FILES['kegiatan_luar']['error'][$index]['salinan_sijil'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $file = $_FILES['kegiatan_luar']['tmp_name'][$index]['salinan_sijil'];
                    $file_name = $_FILES['kegiatan_luar']['name'][$index]['salinan_sijil'];
                    $file_type = $_FILES['kegiatan_luar']['type'][$index]['salinan_sijil'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $content = @file_get_contents($file);
                    if ($content !== false) {
                        if (in_array($file_ext, ['jpg','jpeg','png','gif','webp'])) {
                            $certificate_path = 'data:' . $file_type . ';base64,' . base64_encode($content);
                        } elseif ($file_ext === 'pdf') {
                            $temp_dir = sys_get_temp_dir();
                            $temp_file = $temp_dir . '/preview_extracurricular_' . uniqid() . '.pdf';
                            file_put_contents($temp_file, $content);
                            $certificate_path = $temp_file;
                        }
                    }
                }
                $formatted[] = [
                    'sukan_persatuan_kelab' => mb_strtoupper($activity['sukan_persatuan_kelab']),
                    'jawatan' => mb_strtoupper($activity['jawatan'] ?? ''),
                    'peringkat' => mb_strtoupper($activity['peringkat'] ?? ''),
                    'tarikh_sijil' => $activity['tarikh_sijil'] ?? '',
                    'salinan_sijil' => $certificate_path,
                    'sijil_display_name' => isset($_FILES['kegiatan_luar']['name'][$index]['salinan_sijil']) ? $_FILES['kegiatan_luar']['name'][$index]['salinan_sijil'] : '',
                    'sijil_ext' => isset($_FILES['kegiatan_luar']['name'][$index]['salinan_sijil']) ? strtolower(pathinfo($_FILES['kegiatan_luar']['name'][$index]['salinan_sijil'], PATHINFO_EXTENSION)) : ''
                ];
            }
        }
        return $formatted;
    }
}

function normalizeYesNo($v) {
    $s = strtoupper(trim((string)$v));
    if ($s === '1' || $s === 'YA' || $s === 'Y' || $s === 'YES' || $s === 'TRUE') {
        return 'YA';
    }
    if ($s === '0' || $s === 'TIDAK' || $s === 'T' || $s === 'NO' || $s === 'FALSE') {
        return 'TIDAK';
    }
    return null;
}

function buildFileUrl($filename) {
    if (!$filename) return '';
    // If already a URL or absolute path exposed by web server, return as-is
    if (preg_match('/^https?:\/\//i', $filename)) return $filename;
    // Normalize leading slashes
    $filename = ltrim($filename, '/');
    // Common public uploads directory
    $candidates = [
        'uploads/' . $filename,
        'public/uploads/' . $filename,
        'storage/uploads/' . $filename,
        $filename,
    ];
    foreach ($candidates as $c) {
        // We cannot reliably file_exists in container vs. host mapping for web root; return first candidate
        return '/' . $c;
    }
    return '/' . $filename;
}

// Build URL based on app context: /uploads/applications/<year>/<application_reference>/<file-name>
// Updated to use new standardized path structure without job_id
function buildAppFileUrl($filename, $app) {
    if (!$filename) return '';
    if (preg_match('/^https?:\/\//i', $filename)) return $filename;

    // If the filename already contains a path, use it as-is
    if (strpos($filename, 'uploads/applications/') === 0) {
        return '/' . $filename;
    }

    // Extract components for standardized path
    $year = date('Y'); // Extract from application_reference if needed
    $applicationReference = $app['application_reference'] ?? '';

    $file = basename((string)$filename);
    
    // New standardized path structure (without job_id)
    return '/uploads/applications/' . $year . '/' . rawurlencode((string)$applicationReference) . '/' . rawurlencode($file);
}

function parseSubjectsHtml($subjek_lain, $gred_subjek_lain) {
    // Accept JSON array strings or comma-separated strings
    $subjects = [];
    $grades = [];
    if (is_string($subjek_lain)) {
        $tmp = json_decode($subjek_lain, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
            $subjects = $tmp;
        } else {
            $subjects = array_filter(array_map('trim', explode(',', $subjek_lain)));
        }
    } elseif (is_array($subjek_lain)) {
        $subjects = $subjek_lain;
    }

    if (is_string($gred_subjek_lain)) {
        $tmp = json_decode($gred_subjek_lain, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
            $grades = $tmp;
        } else {
            $grades = array_filter(array_map('trim', explode(',', $gred_subjek_lain)));
        }
    } elseif (is_array($gred_subjek_lain)) {
        $grades = $gred_subjek_lain;
    }

    if (empty($subjects)) return '<span class="text-gray-500">Tiada subjek lain</span>';
    $out = '<ul class="list-disc pl-5">';
    foreach ($subjects as $i => $s) {
        $g = $grades[$i] ?? '';
        $out .= '<li>' . h($s) . ($g !== '' ? ' - <strong>' . h($g) . '</strong>' : '') . '</li>';
    }
    $out .= '</ul>';
    return $out;
}

function parseSubjectsGrid($subjek_lain, $gred_subjek_lain) {
    $subjects = [];
    $grades = [];
    if (is_string($subjek_lain)) {
        $tmp = json_decode($subjek_lain, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
            $subjects = $tmp;
        } else {
            $subjects = array_filter(array_map('trim', explode(',', $subjek_lain)));
        }
    } elseif (is_array($subjek_lain)) {
        $subjects = $subjek_lain;
    }
    if (is_string($gred_subjek_lain)) {
        $tmp = json_decode($gred_subjek_lain, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
            $grades = $tmp;
        } else {
            $grades = array_filter(array_map('trim', explode(',', $gred_subjek_lain)));
        }
    } elseif (is_array($gred_subjek_lain)) {
        $grades = $gred_subjek_lain;
    }
    $items = [];
    foreach ($subjects as $i => $s) {
        $sname = (string)$s;
        if ($sname === '') { continue; }
        $items[] = ['subjek' => $sname, 'gred' => (string)($grades[$i] ?? '')];
    }
    if (empty($items)) {
        return '<span class="text-gray-500">Tiada subjek lain</span>';
    }
    $count = count($items);
    $perCol = (int)ceil($count / 3);
    $col1 = array_slice($items, 0, $perCol);
    $col2 = array_slice($items, $perCol, $perCol);
    $col3 = array_slice($items, $perCol * 2);
    $renderCol = function($rows) {
        $html = '<table class="min-w-full border border-gray-200 text-sm"><thead><tr class="bg-gray-50"><th class="px-2 py-1 text-left border-b border-gray-200">Matapelajaran</th><th class="px-2 py-1 text-left border-b border-gray-200">Keputusan</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td class="px-2 py-1 border-b border-gray-100">' . h($r['subjek']) . '</td><td class="px-2 py-1 border-b border-gray-100">' . h($r['gred']) . '</td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    };
    return '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">' . $renderCol($col1) . $renderCol($col2) . $renderCol($col3) . '</div>';
}

function formatWorkExperienceDate($dari_bulan, $dari_tahun, $hingga_bulan, $hingga_tahun) {
    $dari = '';
    if (!empty($dari_bulan) || !empty($dari_tahun)) {
        $dari = trim($dari_bulan . ' ' . $dari_tahun);
    }
    
    $hingga = '';
    if (!empty($hingga_bulan) || !empty($hingga_tahun)) {
        $hingga = trim($hingga_bulan . ' ' . $hingga_tahun);
    }
    
    return $dari . ($dari && $hingga ? ' - ' : '') . $hingga;
}

function renderTable($data, $emptyMessage = 'Tiada maklumat', $columns = [], $escape = true) {
    // Accepts array rows or JSON string
    if (is_string($data)) {
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $decoded;
        }
    }
    if (!is_array($data) || empty($data)) {
        return '<p class="text-gray-500">' . h($emptyMessage) . '</p>';
    }
    // If no explicit columns provided, infer from first row keys
    if (empty($columns)) {
        $first = $data[0];
        $columns = array_keys($first);
    }
    $out = '<div class="overflow-x-auto"><table class="min-w-full border border-gray-200 text-sm table-fixed"><thead><tr>';
    foreach ($columns as $c) {
        $out .= '<th class="px-3 py-2 text-left bg-gray-50 border-b border-gray-200">' . h(ucwords(str_replace('_',' ', $c))) . '</th>';
    }
    $out .= '</tr></thead><tbody class="divide-y divide-gray-100">';
    foreach ($data as $row) {
        $out .= '<tr>'; 
        foreach ($columns as $c) {
            $cell = $row[$c] ?? '';
            if ($escape) {
                $cell = h($cell);
            }
            $out .= '<td class="px-3 py-2 align-top">' . $cell . '</td>';
        }
        $out .= '</tr>';
    }
    $out .= '</tbody></table></div>';
    return $out;
}

// Read by public reference
$ref = $_GET['ref'] ?? '';
if (!$ref) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><body style="font-family:ui-sans-serif,system-ui;max-width:1050px;margin:40px auto;padding:20px;border:1px solid #eee;border-radius:8px;">'
        . '<h2 style="margin:0 0 12px;">Ralat</h2><p>Parameter ref tidak diberikan.</p></body></html>';
    exit;
}

// Fetch application + job - try application_application_main first, then fallback to job_applications
$stmt = $pdo->prepare("SELECT ja.*, jp.job_title, jp.kod_gred, jp.id as job_posting_id, ja.salinan_lesen_memandu_path as salinan_lesen_memandu FROM application_application_main ja LEFT JOIN job_postings jp ON ja.job_id = jp.id WHERE ja.application_reference = ? LIMIT 1");
$stmt->execute([$ref]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

// If not found in application_application_main, try job_applications (old table)
if (!$app) {
    $stmt = $pdo->prepare("SELECT ja.*, jp.job_title, jp.kod_gred, jp.id as job_posting_id, ja.salinan_lesen_memandu FROM job_applications ja LEFT JOIN job_postings jp ON ja.job_id = jp.id WHERE ja.application_reference = ? LIMIT 1");
    $stmt->execute([$ref]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$app) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body style="font-family:ui-sans-serif,system-ui;max-width:1050px;margin:40px auto;padding:20px;border:1px solid #eee;border-radius:8px;">'
        . '<h2 style="margin:0 0 12px;">Permohonan Tidak Dijumpai</h2><p>Rujukan tidak sah: ' . h($ref) . '</p></body></html>';
    exit;
}

$application_id = (int)$app['id'];

// Fetch normalized related tables safely (avoid fatal on missing tables/columns)
function safeFetchAll(PDO $pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        // Log and continue
        if (function_exists('log_error')) {
            log_error('Query failed in preview-application', ['sql' => $sql, 'params' => $params, 'error' => $e->getMessage()]);
        }
        return [];
    }
}

function resolveColumn(PDO $pdo, string $table, array $candidates): ?string {
    static $cache = [];
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) { return null; }
    if (!isset($cache[$table])) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
            $cache[$table] = $stmt ? array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field') : [];
        } catch (Throwable $e) {
            $cache[$table] = [];
        }
    }
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $cache[$table], true)) { return $candidate; }
    }
    return null;
}

$bodies_col = resolveColumn($pdo, 'application_professional_bodies', ['salinan_sijil_filename','salinan_sijil_path','salinan_sijil']);
$extra_col  = resolveColumn($pdo, 'application_extracurricular', ['salinan_sijil_filename','salinan_sijil_path','salinan_sijil']);
$spm_col    = resolveColumn($pdo, 'application_spm_results', ['salinan_sijil_filename','salinan_sijil_path','salinan_sijil']);
$edu_col    = resolveColumn($pdo, 'application_education', ['sijil_filename','sijil_path']);

// Fetch related data using application_reference as the key
$reference = $app['application_reference'];

// Include modular data fetcher
require_once 'modules/preview/DataFetcher.php';
$dataFetcher = new PreviewDataFetcher($pdo, $app);
$allData = $dataFetcher->getAllData();
$dataFetcher->logDataCounts();

// Log which table the main application data came from
error_log('[Preview] Main application data source: ' . (strpos($app['created_at'] ?? '', '-') !== false ? 'application_application_main' : 'job_applications'));

// Extract data arrays from modular fetcher
// Health data is fetched with all fields: id, application_reference, job_id, darah_tinggi, 
// kencing_manis, penyakit_buah_pinggang, penyakit_jantung, batuk_kering_tibi, kanser, 
// aids, penagih_dadah, perokok, penyakit_lain, penyakit_lain_nyatakan, pemegang_kad_oku, 
// jenis_oku, salinan_kad_oku, memakai_cermin_mata, jenis_rabun, berat_kg, tinggi_cm, 
// created_at, updated_at
$health = $allData['health'];
$language_rows = $allData['language_skills'];
$computer_rows = $allData['computer_skills'];
$bodies_rows = $allData['professional_bodies'];
$edu_rows = $allData['education'];
$spm_rows = $allData['spm_results'];
$spm_additional_rows = $allData['spm_additional_subjects'];
$work_rows = $allData['work_experience'];
$extra_rows = $allData['extracurricular'];
$ref_rows = $allData['references'];
$family_rows = $allData['family_members'];

// Debug logging
if (function_exists('error_log')) {
    error_log("[Preview] Application ID: {$application_id}, Reference: {$app['application_reference']}");
    error_log("[Preview] Language rows: " . count($language_rows));
    error_log("[Preview] Computer rows: " . count($computer_rows));
    error_log("[Preview] Bodies rows: " . count($bodies_rows));
    error_log("[Preview] Extra rows: " . count($extra_rows));
    error_log("[Preview] SPM rows: " . count($spm_rows));
    error_log("[Preview] SPM Additional rows: " . count($spm_additional_rows));
    error_log("[Preview] Education rows: " . count($edu_rows));
    error_log("[Preview] Work rows: " . count($work_rows));
    error_log("[Preview] Reference rows: " . count($ref_rows));
    error_log("[Preview] Family rows: " . count($family_rows));
}

// Transform rows for display needs
// 1) Linkify certificate filenames
foreach ($bodies_rows as &$r) {
    $file = $r['salinan_sijil_filename'] ?? ($r['salinan_sijil_path'] ?? ($r['salinan_sijil'] ?? ''));
    if (!empty($file)) {
        $url = buildAppFileUrl($file, $app);
        $r['sijil'] = '<a class="text-blue-600 hover:underline" target="_blank" href="' . h($url) . '">Papar Sijil</a>';
    } else {
        $r['sijil'] = 'Tiada';
    }
    $tv = $r['tahun'] ?? '';
    if ($tv) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$tv)) {
            $r['tarikh_sijil'] = date('d-M-Y', strtotime($tv));
        } elseif (preg_match('/^\d{4}-\d{2}$/', (string)$tv)) {
            $r['tarikh_sijil'] = date('d-M-Y', strtotime($tv . '-01'));
        } elseif (preg_match('/^\d{4}$/', (string)$tv)) {
            $r['tarikh_sijil'] = date('d-M-Y', strtotime($tv . '-01-01'));
        } else {
            $parsed = strtotime($tv);
            $r['tarikh_sijil'] = $parsed ? date('d-M-Y', $parsed) : (string)$tv;
        }
    } else {
        $r['tarikh_sijil'] = '';
    }
}
unset($r);

foreach ($spm_rows as &$r) {
    // Check for sijil in multiple possible columns
    $sijil = $r['salinan_sijil_filename'] ?? $r['salinan_sijil_path'] ?? $r['salinan_sijil'] ?? null;
    if (!empty($sijil)) {
        $url = buildAppFileUrl($sijil, $app);
        $r['sijil_display'] = '<a class="text-blue-600 hover:underline" target="_blank" href="' . h($url) . '">
            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            Lihat Sijil SPM
        </a>';
        // Keep original for backward compatibility
        $r['salinan_sijil_filename'] = $r['sijil_display'];
    } else {
        $r['sijil_display'] = '<span class="text-gray-400 italic">Tiada sijil</span>';
        $r['salinan_sijil_filename'] = $r['sijil_display'];
    }
}
unset($r);

foreach ($edu_rows as &$r) {
    // Check for sijil in multiple possible columns
    $sijil = $r['sijil_filename'] ?? $r['sijil_path'] ?? $r['sijil'] ?? null;
    if (!empty($sijil)) {
        $url = buildAppFileUrl($sijil, $app);
        $linkText = basename($sijil);
        $r['sijil_display'] = '<a class="text-blue-600 hover:underline" target="_blank" href="' . h($url) . '">Papar Sijil</a>';
        // Keep original for backward compatibility
        $r['sijil_filename'] = $r['sijil_display'];
    } else {
        $r['sijil_display'] = '<span class="text-gray-400 italic">Tiada sijil</span>';
        $r['sijil_filename'] = $r['sijil_display'];
    }
}
unset($r);

// 2) Format gaji with RM prefix
foreach ($work_rows as &$w) {
    if (isset($w['gaji']) && $w['gaji'] !== '') {
        $num = is_numeric($w['gaji']) ? (float)$w['gaji'] : $w['gaji'];
        if (is_numeric($num)) {
            $w['gaji'] = 'RM ' . number_format((float)$num, 2);
        } else {
            // If already text, ensure it has RM prefix once
            $w['gaji'] = (stripos($w['gaji'], 'RM') === 0) ? $w['gaji'] : ('RM ' . $w['gaji']);
        }
    }
}
unset($w);

// UI rendering (public preview)
include __DIR__ . '/header.php';
?>
<div class="standard-container mx-auto">
  <!-- Page heading bar -->
  <div class="bg-blue-50 shadow-sm border border-blue-200 rounded-lg overflow-hidden mb-6">
    <div class="bg-blue-50 px-6 py-4 border-b border-gray-200">
      <h1 class="text-xl font-semibold text-gray-900">Pratonton Permohonan</h1>
      <div class="flex flex-wrap gap-4 mt-1 text-sm text-gray-700">
        <span>Rujukan: <strong><?php echo h($app['application_reference']); ?></strong></span>
        <span>Jawatan: <strong><?php echo h($app['job_title'] ?? $app['jawatan_dipohon'] ?? ''); ?><?php echo !empty($app['kod_gred']) ? ' (' . h($app['kod_gred']) . ')' : ''; ?></strong></span>
      </div>
      </div>
    </div>

    <!-- Maklumat Peribadi -->
  <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
    <div class="bg-blue-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Maklumat Peribadi</h2>
    </div>
    <div class="p-6">
      <?php if (!empty($app['gambar_passport_path']) || !empty($app['gambar_passport'])): ?>
      <div class="mb-6 flex justify-center">
        <div class="w-32 h-40 border border-gray-200 rounded-lg overflow-hidden">
          <?php $passport_url = buildAppFileUrl($app['gambar_passport_path'] ?? $app['gambar_passport'], $app); ?>
          <img src="<?php echo h($passport_url); ?>" alt="Gambar Passport" class="w-full h-full object-cover cursor-pointer" onclick="openImageModal('<?php echo h($passport_url); ?>', 'Gambar Passport')">
        </div>
      </div>
      <?php endif; ?>

      <div class="border border-gray-200 rounded-md p-4 bg-white">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <?php
        // Function to format value (uppercase except email)
        function formatValue($key, $value) {
            if ($key === 'email') {
                return $value; // Keep email as is
            }
            return strtoupper($value);
        }
        ?>
        <div><span class="text-gray-500 text-sm">Nama Penuh</span><p class="text-gray-900 font-medium"><?php echo h(formatValue('nama_penuh', $app['nama_penuh'])); ?></p></div>
        <div>
          <span class="text-gray-500 text-sm">Nombor Kad Pengenalan</span>
          <p class="text-gray-900">
            <?php echo h($app['nombor_ic']); ?>
            <?php if (!empty($app['salinan_ic_path']) || !empty($app['salinan_ic'])): ?>
              <?php $ic_url = buildAppFileUrl($app['salinan_ic_path'] ?? $app['salinan_ic'], $app); ?>
              <a href="<?php echo h($ic_url); ?>" target="_blank" class="text-blue-600 hover:underline text-xs ml-2" onclick="openImageModal('<?php echo h($ic_url); ?>', 'Salinan IC'); return false;">(PAPAR SALINAN)</a>
            <?php endif; ?>
          </p>
        </div>
        <div>
          <span class="text-gray-500 text-sm">Nombor Surat Beranak</span>
          <p class="text-gray-900">
            <?php echo h(formatValue('nombor_surat_beranak', $app['nombor_surat_beranak'] ?? '')); ?>
            <?php if (!empty($app['salinan_surat_beranak_path']) || !empty($app['salinan_surat_beranak'])): ?>
              <?php $beranak_url = buildAppFileUrl($app['salinan_surat_beranak_path'] ?? $app['salinan_surat_beranak'], $app); ?>
              <a href="<?php echo h($beranak_url); ?>" target="_blank" class="text-blue-600 hover:underline text-xs ml-2" onclick="openImageModal('<?php echo h($beranak_url); ?>', 'Salinan Surat Beranak'); return false;">(PAPAR SALINAN)</a>
            <?php endif; ?>
          </p>
        </div>
        <div><span class="text-gray-500 text-sm">Emel</span><p class="text-gray-900"><?php echo h($app['email']); // Email stays as is ?></p></div>
        <div><span class="text-gray-500 text-sm">Agama</span><p class="text-gray-900"><?php echo h(formatValue('agama', $app['agama'] ?? '')); ?></p></div>
        <div><span class="text-gray-500 text-sm">Taraf Perkahwinan</span><p class="text-gray-900"><?php echo h(formatValue('taraf_perkahwinan', $app['taraf_perkahwinan'] ?? '')); ?></p></div>
        <div><span class="text-gray-500 text-sm">Jantina</span><p class="text-gray-900"><?php echo h(formatValue('jantina', $app['jantina'] ?? '')); ?></p></div>
        <div><span class="text-gray-500 text-sm">Tarikh Lahir</span><p class="text-gray-900"><?php $tl = $app['tarikh_lahir'] ?? ''; $ts = $tl ? strtotime($tl) : false; echo h($ts ? date('d-M-Y', $ts) : ($tl ?: '')); ?></p></div>
        <div><span class="text-gray-500 text-sm">Umur</span><p class="text-gray-900"><?php echo h($app['umur'] ?? ''); // Keep numeric ?></p></div>
        <div><span class="text-gray-500 text-sm">Negeri Kelahiran</span><p class="text-gray-900"><?php echo h(formatValue('negeri_kelahiran', $app['negeri_kelahiran'] ?? '')); ?></p></div>
        <div><span class="text-gray-500 text-sm">Bangsa</span><p class="text-gray-900"><?php echo h(formatValue('bangsa', $app['bangsa'] ?? '')); ?></p></div>
        <div><span class="text-gray-500 text-sm">Warganegara</span><p class="text-gray-900"><?php echo h(formatValue('warganegara', $app['warganegara'] ?? '')); ?></p></div>
        <div><span class="text-gray-500 text-sm">Tempoh Bermastautin Di Selangor (tahun)</span><p class="text-gray-900"><?php echo h($app['tempoh_bermastautin_selangor'] ?? ''); // Keep numeric ?></p></div>
        <div><span class="text-gray-500 text-sm">Nombor Telefon Bimbit</span><p class="text-gray-900"><?php echo h($app['nombor_telefon'] ?? ''); // Keep phone number format ?></p></div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-gray-900 text-sm mb-2">Alamat Tetap</h3>
          <p class="text-gray-900 text-sm"><?php echo h(strtoupper($app['alamat_tetap'] ?? '')); ?></p>
          <p class="text-gray-900 text-sm"><?php echo h(strtoupper(($app['poskod_tetap'] ?? '') . ' ' . ($app['bandar_tetap'] ?? ''))); ?></p>
          <p class="text-gray-900 text-sm"><?php echo h(strtoupper($app['negeri_tetap'] ?? '')); ?></p>
        </div>
        <div>
          <h3 class="text-gray-900 text-sm mb-2">Alamat Surat Menyurat</h3>
          <p class="text-gray-900 text-sm"><?php echo h(strtoupper($app['alamat_surat'] ?? '')); ?></p>
          <p class="text-gray-900 text-sm"><?php echo h(strtoupper(($app['poskod_surat'] ?? '') . ' ' . ($app['bandar_surat'] ?? ''))); ?></p>
          <p class="text-gray-900 text-sm"><?php echo h(strtoupper($app['negeri_surat'] ?? '')); ?></p>
        </div>
        </div>
      </div>

      <div class="mt-6">
        <h3 class="font-medium mb-2">Maklumat Lesen Memandu</h3>
        <div class="border border-gray-200 rounded-md p-4 bg-white">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-gray-500">Kelas Lesen</span>
                            <p class="text-gray-900">
                              <?php
                              $lesen_memandu = $app['lesen_memandu_set'] ?? ($app['lesen_memandu'] ?? ($app['kelas_lesen'] ?? ''));
                              $classes = [];
                              $decoded = is_string($lesen_memandu) ? json_decode($lesen_memandu, true) : (is_array($lesen_memandu) ? $lesen_memandu : null);
                              if (is_array($decoded)) {
                                foreach ($decoded as $v) { if ($v !== '') { $classes[] = strtoupper($v); } }
                              } else {
                                $val = strtoupper(trim((string)$lesen_memandu));
                                if ($val !== '') {
                                  $parts = array_map('trim', explode(',', $val));
                                  foreach ($parts as $p) { if ($p !== '') { $classes[] = strtoupper($p); } }
                                }
                              }
                              $has_license = !empty($classes) && !in_array('TIADA LESEN', $classes, true) && !in_array('TIADA', $classes, true);
                              if ($has_license) {
                                echo h(implode(', ', $classes));
                                $lesen_file = $app['salinan_lesen_memandu_path'] ?? ($app['salinan_lesen_memandu'] ?? ($app['salinan_lesen'] ?? ''));
                                if (!empty($lesen_file)) {
                                  $lesen_url = buildAppFileUrl($lesen_file, $app);
                                  echo ' <a href="' . h($lesen_url) . '" target="_blank" class="text-blue-600 hover:underline text-xs ml-2" onclick="openImageModal(\'' . h($lesen_url) . '\', \'Salinan Lesen Memandu\'); return false;">(papar salinan)</a>';
                                }
                              } else {
                                echo h('Tiada Lesen');
                              }
                              ?>
                            </p>
          </div>
          <div>
            <span class="text-gray-500">Tarikh Tamat Lesen</span>
            <p class="text-gray-900">
              <?php
              $tarikh_tamat = $app['tarikh_tamat_lesen'] ?? '';
              if ($tarikh_tamat) {
                echo h(date('d-M-Y', strtotime($tarikh_tamat)));
              } else {
                echo h('Tiada');
              }
              ?>
            </p>
          </div>
          </div>
        </div>
      </div>

      <?php if (strtoupper((string)($app['taraf_perkahwinan'] ?? '')) === 'BERKAHWIN'): ?>
      <div class="mt-6">
        <h3 class="font-medium mb-2">Maklumat Pasangan</h3>
        <div class="border border-gray-200 rounded-md p-4 bg-white">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
           <div><span class="text-gray-500 text-sm">Nama Pasangan</span><p class="text-gray-900 uppercase"><?php echo h($app['nama_pasangan'] ?? ''); ?></p></div>
           <div><span class="text-gray-500 text-sm">Telefon Pasangan</span><p class="text-gray-900"><?php echo h($app['telefon_pasangan'] ?? ''); ?></p></div>
           <?php if (!empty($app['bilangan_anak'])): ?>
           <div><span class="text-gray-500 text-sm">Bilangan Anak</span><p class="text-gray-900"><?php echo h($app['bilangan_anak']); ?></p></div>
           <div><span class="text-gray-500 text-sm">Status Pekerjaan</span><p class="text-gray-900 uppercase"><?php echo h($app['status_pasangan'] ?? ''); ?></p></div>
           <?php endif; ?>
           <div><span class="text-gray-500 text-sm">Pekerjaan Pasangan</span><p class="text-gray-900 uppercase"><?php echo h($app['pekerjaan_pasangan'] ?? ''); ?></p></div>
           <div><span class="text-gray-500 text-sm">Nama Majikan</span><p class="text-gray-900 uppercase"><?php echo h($app['nama_majikan_pasangan'] ?? ''); ?></p></div>
           <div><span class="text-gray-500 text-sm">Telefon Pejabat</span><p class="text-gray-900"><?php echo h($app['telefon_pejabat_pasangan'] ?? ''); ?></p></div>
           <div><span class="text-gray-500 text-sm">Alamat Majikan</span><p class="text-gray-900 uppercase"><?php echo h($app['alamat_majikan_pasangan'] ?? ''); ?></p></div>
           <div><span class="text-gray-500 text-sm">Poskod Majikan</span><p class="text-gray-900"><?php echo h($app['poskod_majikan_pasangan'] ?? ''); ?></p></div>
           <div><span class="text-gray-500 text-sm">Bandar Majikan</span><p class="text-gray-900 uppercase"><?php echo h($app['bandar_majikan_pasangan'] ?? ''); ?></p></div>
           <div><span class="text-gray-500 text-sm">Negeri Majikan</span><p class="text-gray-900 uppercase"><?php echo h($app['negeri_majikan_pasangan'] ?? ''); ?></p></div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($family_rows)): ?>
      <div class="mt-6">
        <h3 class="font-medium mb-2">Maklumat Ahli Keluarga</h3>
        <?php echo renderTable($family_rows, 'Tiada maklumat ahli keluarga', ['hubungan','nama','pekerjaan','telefon','kewarganegaraan']); ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Kesihatan/Fizikal -->
  <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
    <div class="bg-indigo-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Kesihatan/Fizikal</h2>
    </div>
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        <!-- Health Conditions -->
        <div class="space-y-3">
          <h3 class="font-medium text-gray-900 mb-3">Keadaan Kesihatan</h3>
          <?php
          $health_questions = [
            ['label' => 'Darah Tinggi', 'key' => 'darah_tinggi'],
            ['label' => 'Kencing Manis', 'key' => 'kencing_manis'],
            ['label' => 'Penyakit Buah Pinggang', 'key' => 'penyakit_buah_pinggang'],
            ['label' => 'Penyakit Jantung', 'key' => 'penyakit_jantung'],
            ['label' => 'Batuk Kering/Tibi', 'key' => 'batuk_kering_tibi'],
            ['label' => 'Kanser', 'key' => 'kanser'],
            ['label' => 'AIDS', 'key' => 'aids'],
            ['label' => 'Penagih Dadah', 'key' => 'penagih_dadah'],
            ['label' => 'Perokok', 'key' => 'perokok']
          ];
          foreach ($health_questions as $hq):
            $v = (is_array($health) && array_key_exists($hq['key'], $health)) ? $health[$hq['key']] : ($app[$hq['key']] ?? '');
            $yn = normalizeYesNo($v);
            $display_value = ($hq['key'] === 'email') ? $v : ($yn !== null ? strtoupper($yn) : 'TIDAK DINYATAKAN');
          ?>
          <div class="flex justify-between">
            <span class="text-gray-500 text-sm"><?php echo h($hq['label']); ?>:</span>
            <span class="font-medium text-gray-900"><?php 
              if (!empty($hq['is_file']) && !empty($display_value)) {
                $url = buildAppFileUrl($display_value, $app);
                echo '<a href="' . h($url) . '" target="_blank" class="text-blue-600 hover:underline">Papar</a>';
              } else {
                echo h($display_value);
              }
            ?></span>
          </div>
          <?php endforeach; ?>

          <?php 
          $penyakit_lain = (is_array($health) && array_key_exists('penyakit_lain', $health)) ? $health['penyakit_lain'] : ($app['penyakit_lain'] ?? '');
          $penyakit_lain_nyatakan = (is_array($health) && array_key_exists('penyakit_lain_nyatakan', $health)) ? $health['penyakit_lain_nyatakan'] : ($app['penyakit_lain_nyatakan'] ?? '');
          ?>
          <?php $penyakit_lain_norm = normalizeYesNo($penyakit_lain); ?>
          <?php if ($penyakit_lain_norm === 'YA'): ?>
          <div class="mt-3 pt-3 border-t border-gray-200">
            <div class="flex justify-between">
              <span class="text-gray-500">Penyakit Lain:</span>
              <span class="font-medium text-gray-900 uppercase">YA</span>
            </div>
            <?php if (!empty($penyakit_lain_nyatakan)): ?>
            <div class="mt-2 text-sm">
              <span class="text-gray-500">Nyatakan:</span>
              <span class="text-gray-900"><?php echo h($penyakit_lain_nyatakan); ?></span>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Physical Information -->
        <div class="space-y-3">
          <h3 class="font-medium text-gray-900 mb-3">Maklumat Fizikal</h3>

          <!-- OKU Status -->
          <div class="flex justify-between">
            <span class="text-gray-500">Pemegang Kad OKU:</span>
            <span class="font-medium text-gray-900">
              <?php
                $pemegang = (is_array($health) && array_key_exists('pemegang_kad_oku', $health)) ? $health['pemegang_kad_oku'] : ($app['pemegang_kad_oku'] ?? '');
                $pemegang_norm = normalizeYesNo($pemegang) ?? 'TIDAK';
                echo h(strtoupper($pemegang_norm));
              ?>
            </span>
          </div>

          <?php
            $jenisOkuRaw = (is_array($health) && array_key_exists('jenis_oku', $health)) ? $health['jenis_oku'] : ($app['jenis_oku'] ?? '');
            $salinanOku = (is_array($health) && array_key_exists('salinan_kad_oku', $health)) ? $health['salinan_kad_oku'] : ($app['salinan_kad_oku_path'] ?? $app['salinan_kad_oku'] ?? '');
          ?>
          <?php if (($pemegang_norm ?? normalizeYesNo($pemegang)) === 'YA' && !empty($jenisOkuRaw)): ?>
          <div class="mt-2 text-sm">
            <span class="text-gray-500">Jenis OKU:</span>
            <div class="mt-1">
              <?php
                $jenis_oku = is_string($jenisOkuRaw) ? json_decode($jenisOkuRaw, true) : $jenisOkuRaw;
                if (is_array($jenis_oku)) {
                  foreach ($jenis_oku as $oku_type) {
                    echo '<span class="inline-block text-gray-900 text-sm mr-2">' . h(strtoupper($oku_type)) . '</span>';
                  }
                }
              ?>
            </div>
            <?php if (!empty($salinanOku)): ?>
            <div class="mt-2">
              <span class="text-gray-500">Salinan Kad OKU:</span>
              <?php $oku_url = buildAppFileUrl($salinanOku, $app); ?>
              <a href="<?php echo h($oku_url); ?>" target="_blank" class="text-blue-600 hover:underline ml-2" onclick="openImageModal('<?php echo h($oku_url); ?>', 'Salinan Kad OKU'); return false;">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Lihat Salinan Kad
              </a>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Eyesight -->
          <div class="flex justify-between">
            <span class="text-gray-500">Memakai Cermin Mata:</span>
            <span class="font-medium text-gray-900">
              <?php
                $cermin = (is_array($health) && array_key_exists('memakai_cermin_mata', $health)) ? $health['memakai_cermin_mata'] : ($app['memakai_cermin_mata'] ?? '');
                $cermin_norm = normalizeYesNo($cermin) ?? 'TIDAK';
                echo h(strtoupper($cermin_norm));
              ?>
            </span>
          </div>
          <?php if (($cermin_norm ?? normalizeYesNo($cermin)) === 'YA'): ?>
          <div class="mt-2 text-sm">
            <span class="text-gray-500">Jenis Rabun:</span>
            <span class="text-gray-900"><?php echo h((is_array($health) && array_key_exists('jenis_rabun', $health)) ? ($health['jenis_rabun'] ?? '') : ($app['jenis_rabun'] ?? '')); ?></span>
          </div>
          <?php endif; ?>

          <!-- Physical Measurements -->
          <div class="mt-3 pt-3 border-t border-gray-200">
            <?php
              $tinggi = (is_array($health) && array_key_exists('tinggi_cm', $health)) ? $health['tinggi_cm'] : ($app['tinggi_cm'] ?? null);
              $berat = (is_array($health) && array_key_exists('berat_kg', $health)) ? $health['berat_kg'] : ($app['berat_kg'] ?? null);
              $tinggi_display = ($tinggi !== null && $tinggi !== '') ? (is_numeric($tinggi) ? $tinggi . ' cm' : h($tinggi)) : 'Tidak Dinyatakan';
              $berat_display = ($berat !== null && $berat !== '') ? (is_numeric($berat) ? $berat . ' kg' : h($berat)) : 'Tidak Dinyatakan';
            ?>
            <div class="flex justify-between">
              <span class="text-gray-500">Tinggi:</span>
              <span class="font-medium text-gray-900"><?php echo $tinggi_display; ?></span>
            </div>
            <div class="flex justify-between mt-2">
              <span class="text-gray-500">Berat:</span>
              <span class="font-medium text-gray-900"><?php echo $berat_display; ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Pendidikan -->
  <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
    <div class="bg-green-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Pendidikan</h2>
    </div>
    <div class="p-6">
      <div class="mb-6">
        <h3 class="font-medium mb-2">Kelulusan SPM/SPV</h3>
        <?php if (!empty($spm_rows)): ?>
          <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200 text-sm">
              <thead>
                <tr class="bg-gray-50">
                  <th class="px-3 py-2 text-left border-b border-gray-200">Tahun</th>
                  <th class="px-3 py-2 text-left border-b border-gray-200">Gred Keseluruhan</th>
                  <th class="px-3 py-2 text-left border-b border-gray-200">Angka Giliran</th>
                  <th class="px-3 py-2 text-left border-b border-gray-200">Bahasa Malaysia</th>
                  <th class="px-3 py-2 text-left border-b border-gray-200">Bahasa Inggeris</th>
                  <th class="px-3 py-2 text-left border-b border-gray-200">Matematik</th>
                  <th class="px-3 py-2 text-left border-b border-gray-200">Sejarah</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($spm_rows as $spm): ?>
                <tr>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($spm['tahun'] ?? ''); ?></td>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($spm['gred_keseluruhan'] ?? ''); ?></td>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($spm['angka_giliran'] ?? ''); ?></td>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($spm['bahasa_malaysia'] ?? ''); ?></td>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($spm['bahasa_inggeris'] ?? ''); ?></td>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($spm['matematik'] ?? ''); ?></td>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($spm['sejarah'] ?? ''); ?></td>
                </tr>
                <tr>
                  <td class="px-3 py-2 border-b border-gray-100 text-sm text-gray-700" colspan="7">
                    <div class="px-3 py-2 bg-gray-50 border border-gray-200 rounded font-semibold text-gray-900">Subjek Lain</div>
                    <div class="mt-2">
                      <?php echo parseSubjectsGrid($spm['subjek_lain'] ?? '', $spm['gred_subjek_lain'] ?? ''); ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td class="px-3 py-2 border-b border-gray-100 text-sm text-gray-700" colspan="7">
                    <div class="flex items-center gap-2">
                      <span class="text-gray-500">Sijil SPM:</span>
                      <span>
                        <?php echo $spm['sijil_display'] ?? '<span class="text-gray-400 italic">Tiada sijil</span>'; ?>
                      </span>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-gray-500">Tiada maklumat SPM</p>
        <?php endif; ?>
      </div>
      
      <div class="mt-6">
        <h3 class="font-medium mb-2">Maklumat Persekolahan & IPT</h3>
        <?php if (!empty($edu_rows)): ?>
          <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200 text-sm">
              <thead>
                <tr class="bg-gray-50">
                  <th class="px-3 py-2 text-left border-b border-gray-200">Institusi</th>
                  <th class="px-3 py-2 text-left border-b border-gray-200">Dari Tahun</th>
                  <th class="px-3 py-2 text-left border-b border-gray-200">Hingga Tahun</th>
                  <th class="px-3 py-2 text-left border-b border-gray-200">Kelayakan</th>
                  <th class="px-3 py-2 text-left border-b border-gray-200">Gred</th>
                  <th class="px-3 py-2 text-left border-b border-gray-200">Sijil</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($edu_rows as $edu): ?>
                <tr>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($edu['institusi'] ?? $edu['nama_institusi'] ?? ''); ?></td>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($edu['dari_tahun'] ?? ''); ?></td>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($edu['hingga_tahun'] ?? ''); ?></td>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($edu['kelayakan'] ?? ''); ?></td>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo h($edu['gred'] ?? $edu['pangkat_gred_cgpa'] ?? ''); ?></td>
                  <td class="px-3 py-2 border-b border-gray-100"><?php echo $edu['sijil_display'] ?? '<span class="text-gray-400 italic">Tiada sijil</span>'; ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-gray-500">Tiada maklumat pendidikan</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Kemahiran -->
  <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
    <div class="bg-purple-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Kemahiran</h2>
    </div>
    <div class="p-6">
      <div class="mb-6">
        <h3 class="font-medium mb-2">Kemahiran Bahasa</h3>
        <?php echo renderTable($language_rows, 'Tiada maklumat kemahiran bahasa', ['bahasa','tahap_lisan','tahap_penulisan']); ?>
      </div>
      <div class="mb-6">
        <h3 class="font-medium mb-2">Kemahiran Komputer</h3>
        <?php echo renderTable($computer_rows, 'Tiada maklumat kemahiran komputer'); ?>
      </div>
      <div class="mb-6">
        <h3 class="font-medium mb-2">Badan Profesional</h3>
        <?php echo renderTable($bodies_rows, 'Tiada maklumat badan profesional', ['nama_lembaga','no_ahli','sijil_diperoleh','tarikh_sijil','sijil'], false); ?>
      </div>
      <div class="mb-2">
        <h3 class="font-medium mb-2">Kegiatan Luar</h3>
        <?php 
        $extra_display = [];
        foreach ($extra_rows as $row) {
            $file = $row['salinan_sijil_filename'] ?? '';
            if (!empty($file)) {
                $url = buildAppFileUrl($file, $app);
                $row['sijil'] = '<a href="' . h($url) . '" target="_blank" class="text-blue-600 hover:underline">Papar Sijil</a>';
            } else {
                $row['sijil'] = 'Tiada';
            }
            $tv = $row['tarikh_sijil'] ?? ($row['tahun'] ?? '');
            if ($tv) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$tv)) {
                    $row['tarikh_sijil'] = date('d-M-Y', strtotime($tv));
                } elseif (preg_match('/^\d{4}-\d{2}$/', (string)$tv)) {
                    $row['tarikh_sijil'] = date('d-M-Y', strtotime($tv . '-01'));
                } elseif (preg_match('/^\d{4}$/', (string)$tv)) {
                    $row['tarikh_sijil'] = date('d-M-Y', strtotime($tv . '-01-01'));
                } else {
                    $parsed = strtotime($tv);
                    $row['tarikh_sijil'] = $parsed ? date('d-M-Y', $parsed) : (string)$tv;
                }
            } else {
                $row['tarikh_sijil'] = '';
            }
            $extra_display[] = $row;
        }
        echo renderTable($extra_display, 'Tiada maklumat kegiatan luar', ['sukan_persatuan_kelab','jawatan','peringkat','tarikh_sijil','sijil'], false); 
        ?>
      </div>
    </div>
  </div>

  <!-- Pengalaman Bekerja -->
  <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
    <div class="bg-orange-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Pengalaman Bekerja</h2>
    </div>
    <div class="p-6">
      <?php if (!empty($work_rows)): ?>
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr class="bg-gray-50">
              <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Syarikat</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Jawatan</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Tarikh Mula</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Tarikh Tamat</th>
              <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Gaji</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($work_rows as $idx => $work): ?>
              <tr>
                <?php 
                  $startDate = !empty($work['mula_berkhidmat']) ? (string)$work['mula_berkhidmat'] : trim(($work['dari_bulan'] ?? '') . ' ' . ($work['dari_tahun'] ?? ''));
                  $endDate = !empty($work['tamat_berkhidmat']) ? (string)$work['tamat_berkhidmat'] : trim(($work['hingga_bulan'] ?? '') . ' ' . ($work['hingga_tahun'] ?? ''));
                ?>
                <td class="px-4 py-2 text-sm"><?php echo h($work['syarikat'] ?? ''); ?></td>
                <td class="px-4 py-2 text-sm"><?php echo h($work['jawatan'] ?? ''); ?></td>
                <td class="px-4 py-2 text-sm"><?php echo h($startDate); ?></td>
                <td class="px-4 py-2 text-sm"><?php echo h($endDate); ?></td>
                <td class="px-4 py-2 text-sm"><?php echo h($work['gaji'] ?? ''); ?></td>
              </tr>
              <tr>
                <td class="px-4 py-3 text-sm" colspan="5">
                  <?php 
                    $scope = $work['bidang_tugas'] ?? ($work['skop_kerja'] ?? ($work['job_scope'] ?? ($work['tugas'] ?? '')));
                    $alasan = $work['alasan'] ?? '';
                  ?>
                  <div class="mt-1">
                    <div class="px-3 py-2 bg-gray-50 border border-gray-200 rounded font-medium text-gray-900">Skop Kerja</div>
                    <div class="text-gray-900 mt-2"><?php echo nl2br(h($scope)); ?></div>
                  </div>
                  <?php if (!empty($alasan)): ?>
                    <div class="mt-3">
                      <div class="px-3 py-2 bg-gray-50 border border-gray-200 rounded flex items-start gap-2">
                        <span class="text-gray-500">Alasan Berhenti:</span>
                        <span class="text-gray-900"><?php echo nl2br(h($alasan)); ?></span>
                      </div>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
              <?php if ($idx < (count($work_rows) - 1)): ?>
              <tr>
                <td class="px-4 py-2" colspan="5">
                  <div style="border-top:3px solid #93c5fd;"></div>
                </td>
              </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-gray-500 italic">Tiada maklumat pengalaman kerja</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Pengisytiharan Diri -->
  <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
    <div class="bg-red-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Pengisytiharan Diri</h2>
    </div>
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <?php 
          $decls = [
            ['label' => 'Pekerja Perkhidmatan Awam', 'key' => 'pekerja_perkhidmatan_awam', 'nyatakan' => 'pekerja_perkhidmatan_awam_nyatakan'],
            ['label' => 'Pertalian Kakitangan', 'key' => 'pertalian_kakitangan', 'nyatakan' => 'pertalian_kakitangan_nyatakan'],
            ['label' => 'Pernah Bekerja di MPHS', 'key' => 'pernah_bekerja_mphs', 'nyatakan' => 'pernah_bekerja_mphs_nyatakan'],
            ['label' => 'Tindakan Tatatertib', 'key' => 'tindakan_tatatertib', 'nyatakan' => 'tindakan_tatatertib_nyatakan'],
            ['label' => 'Kesalahan Undang-undang', 'key' => 'kesalahan_undangundang', 'nyatakan' => 'kesalahan_undangundang_nyatakan'],
            ['label' => 'Muflis', 'key' => 'muflis', 'nyatakan' => 'muflis_nyatakan'],
          ];
          foreach ($decls as $d): 
            $v = $app[$d['key']] ?? '';
            $cls = ($v === 'YA') ? 'text-red-600 font-semibold' : 'text-gray-900';
          ?>
          <div class="flex flex-col">
            <div class="flex items-center">
              <span class="text-gray-500 w-48 md:w-64"><?php echo h($d['label']); ?>:</span>
              <span class="font-medium text-black"><?php 
                $value = $app[$d['key']] ?? '';
                $display = normalizeYesNo($value) ?? 'TIDAK';
                echo h(strtoupper($display)); 
              ?></span>
            </div>
            <?php 
            $nyatakan = $app[$d['nyatakan']] ?? '';
            $show_nyatakan = (strtoupper($value) === 'YA' || $value === '1') && !empty($nyatakan);
            if ($show_nyatakan): ?>
              <div class="mt-1 flex items-start">
                <span class="text-gray-500 w-48 md:w-64">Nyatakan:</span>
                <span class="text-black"><?php echo h($nyatakan); ?></span>
              </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <!-- removed stray section closing tag -->
    </div>
  </div>

  <!-- Rujukan -->
  <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
    <div class="bg-yellow-50 px-6 py-4 border-b border-gray-100">
      <h2 class="text-lg font-semibold text-gray-900">Rujukan</h2>
    </div>
    <div class="p-6">
      <?php 
      // Split references into two columns
      $half = ceil(count($ref_rows) / 2);
      $first_column = array_slice($ref_rows, 0, $half);
      $second_column = array_slice($ref_rows, $half);
      ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <?php echo renderTable($first_column, 'Tiada maklumat rujukan', ['nama','telefon','tempoh']); ?>
        </div>
        <?php if (!empty($second_column)): ?>
        <div>
          <?php echo renderTable($second_column, '', ['nama','telefon','tempoh']); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Confirmation and Submit Button -->
  <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
    <div class="p-6">
      <form id="confirmSubmitForm" action="finalize-submission.php" method="POST" class="space-y-4">
        <input type="hidden" name="application_id" value="<?php echo (int)$application_id; ?>">
        <input type="hidden" name="application_reference" value="<?php echo h($app['application_reference']); ?>">
        <input type="hidden" name="submission_status" value="Pending">
        
        <div class="bg-blue-50 p-4 rounded-lg">
          <p class="text-gray-700 text-sm">
            Dengan menekan butang "Sahkan dan Hantar Permohonan" di bawah, saya mengesahkan bahawa:
          </p>
          <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
            <li>Semua maklumat yang diberikan adalah benar dan lengkap</li>
            <li>Saya faham bahawa permohonan ini tidak boleh diubah selepas penghantaran</li>
            <li>Saya bersetuju untuk mematuhi semua syarat dan peraturan yang ditetapkan</li>
          </ul>
        </div>

        <div class="flex justify-between items-center">
          <?php if (!empty($config['navigation']['show_status_check'])): ?>
          <a href="semak-status.php?app_ref=<?php echo urlencode($app['application_reference']); ?>&nric=<?php echo urlencode($app['nombor_ic']); ?>" 
             class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition duration-200 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Edit Permohonan
          </a>
          <?php endif; ?>
          
          <!-- Submit Button -->
          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg transition duration-200 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Sahkan dan Hantar Permohonan
          </button>
        </div>
      </form>
    </div>
  </div>
  <div id="processingOverlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.9);z-index:10000;display:none;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#ffffff;border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,0.1);padding:24px 28px;text-align:center;max-width:320px;width:90%;">
      <div style="margin:0 auto 16px;width:56px;height:56px;border:5px solid #e2e8f0;border-top-color:#38a169;border-radius:50%;animation:spin 1s linear infinite;"></div>
      <div style="font-weight:600;font-size:16px;color:#1a202c;">Sedang memproses permohonan</div>
      <div style="font-size:14px;color:#4a5568;margin-top:6px;">Sila tunggu sebentar. Jangan tutup halaman ini.</div>
    </div>
    <style>
      @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var form = document.getElementById('confirmSubmitForm');
      if (!form) return;
      var overlay = document.getElementById('processingOverlay');
      var btn = form.querySelector('button[type="submit"]');
      form.addEventListener('submit', function() {
        if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; btn.style.cursor = 'not-allowed'; }
        if (overlay) { overlay.style.display = 'block'; }
        document.body.setAttribute('aria-busy', 'true');
      });
    });
  </script>
</div>
<?php include __DIR__ . '/footer.php'; ?>
<?php exit; // DO NOT EXECUTE THE OLD CODE BELOW ?>

// Start output buffering to prevent any accidental output
ob_start();

// Preview application form data before final submission
require_once 'includes/ErrorHandler.php';

// Get database connection from config
$result = require 'config.php';
$config = $result['config'] ?? $result;

// Initialize variables
$pdo = null;
$error = '';
$job = null;

// We support both POST (direct preview) and GET (after saved redirect)
// If GET with app_id or ref is provided, we'll fetch saved data and populate $_POST for rendering

// Resolve application and job from POST or GET
$application_id = null;
$application_ref = null;
$job_id = $_POST['job_id'] ?? null;

// Debug information
error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
error_log('GET parameters: ' . print_r($_GET, true));
error_log('POST parameters: ' . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $application_id = isset($_GET['app_id']) && is_numeric($_GET['app_id']) ? (int)$_GET['app_id'] : null;
    $application_ref = $_GET['ref'] ?? null;
    
    // If application_id is not provided but id is, use that instead (for direct URL access)
    if (!$application_id && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $application_id = (int)$_GET['id'];
        error_log('Using id parameter as application_id: ' . $application_id);
    }
}

if (($_SERVER['REQUEST_METHOD'] === 'POST' && (!$job_id || !is_numeric($job_id))) ||
    ($_SERVER['REQUEST_METHOD'] === 'GET' && !$application_id && !$application_ref)) {
    $error = 'Data permohonan tidak lengkap.';
    error_log('Invalid preview request. job_id: ' . ($job_id ?? 'null') . ', app_id: ' . ($application_id ?? 'null') . ', ref: ' . ($application_ref ?? 'null'));
} else {
    // Connect to database to get job details
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
        
        // If GET, fetch application and job; if POST, fetch job only
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Debug information
            error_log('Attempting to fetch application. ID: ' . ($application_id ?? 'null') . ', Ref: ' . ($application_ref ?? 'null'));
            
            // Fetch application by id or reference
            if ($application_id) {
                $stmt = $pdo->prepare('SELECT * FROM job_applications WHERE id = ? LIMIT 1');
                $stmt->execute([$application_id]);
                error_log('Executed query for application_id = ' . $application_id);
            } else {
                $stmt = $pdo->prepare('SELECT * FROM job_applications WHERE application_reference = ? LIMIT 1');
                $stmt->execute([$application_ref]);
                error_log('Executed query for application_reference = ' . $application_ref);
            }
            $application = $stmt->fetch();
            
            // Debug information
            if ($application) {
                error_log('Application found. ID: ' . $application['id'] . ', Job ID: ' . $application['job_id']);
            } else {
                error_log('No application found with the provided ID or reference.');
            }
            if (!$application) {
                $error = 'Permohonan tidak dijumpai.';
            } else {
                // Populate $_POST so the existing view code can render consistently
                $job_id = (int)$application['job_id'];
                $_POST['job_id'] = $job_id;
                $_POST['jawatan_dipohon'] = $application['jawatan_dipohon'] ?? '';
                $_POST['payment_reference'] = $application['application_reference'] ?? ($application['payment_reference'] ?? '');
                $_POST['pengistiharan'] = $application['pengistiharan'] ?? '1';
                // Files (paths)
                $_POST['gambar_passport_path'] = $application['gambar_passport'] ?? '';
                $_POST['salinan_ic_path'] = $application['salinan_ic'] ?? '';
                $_POST['salinan_surat_beranak_path'] = $application['salinan_surat_beranak'] ?? '';
                // Personal
                $_POST['nama_penuh'] = $application['nama_penuh'] ?? '';
                $_POST['nombor_ic'] = $application['nombor_ic'] ?? '';
                $_POST['nombor_surat_beranak'] = $application['nombor_surat_beranak'] ?? '';
                $_POST['email'] = $application['email'] ?? '';
                $_POST['agama'] = $application['agama'] ?? '';
                $_POST['taraf_perkahwinan'] = $application['taraf_perkahwinan'] ?? '';
                $_POST['jantina'] = $application['jantina'] ?? '';
                $_POST['tarikh_lahir'] = $application['tarikh_lahir'] ?? '';
                $_POST['umur'] = $application['umur'] ?? '';
                $_POST['negeri_kelahiran'] = $application['negeri_kelahiran'] ?? '';
                $_POST['bangsa'] = $application['bangsa'] ?? '';
                $_POST['warganegara'] = $application['warganegara'] ?? '';
                $_POST['tempoh_bermastautin_selangor'] = $application['tempoh_bermastautin_selangor'] ?? '';
                $_POST['nombor_telefon'] = $application['nombor_telefon'] ?? '';
                
                // Spouse information
                $_POST['nama_pasangan'] = $application['nama_pasangan'] ?? '';
                $_POST['telefon_pasangan'] = $application['telefon_pasangan'] ?? '';
                $_POST['bilangan_anak'] = $application['bilangan_anak'] ?? '';
                $_POST['status_pasangan'] = $application['status_pasangan'] ?? '';
                $_POST['pekerjaan_pasangan'] = $application['pekerjaan_pasangan'] ?? '';
                $_POST['nama_majikan_pasangan'] = $application['nama_majikan_pasangan'] ?? '';
                $_POST['telefon_pejabat_pasangan'] = $application['telefon_pejabat_pasangan'] ?? '';
                $_POST['alamat_majikan_pasangan'] = $application['alamat_majikan_pasangan'] ?? '';
                $_POST['poskod_majikan_pasangan'] = $application['poskod_majikan_pasangan'] ?? '';
                $_POST['bandar_majikan_pasangan'] = $application['bandar_majikan_pasangan'] ?? '';
                $_POST['negeri_majikan_pasangan'] = $application['negeri_majikan_pasangan'] ?? '';
                // Addresses
                $_POST['alamat_tetap'] = $application['alamat_tetap'] ?? '';
                $_POST['bandar_tetap'] = $application['bandar_tetap'] ?? '';
                $_POST['negeri_tetap'] = $application['negeri_tetap'] ?? '';
                $_POST['poskod_tetap'] = $application['poskod_tetap'] ?? '';
                $_POST['alamat_surat_sama'] = !empty($application['alamat_surat_sama']) ? '1' : '';
                $_POST['alamat_surat'] = $application['alamat_surat'] ?? '';
                $_POST['bandar_surat'] = $application['bandar_surat'] ?? '';
                $_POST['negeri_surat'] = $application['negeri_surat'] ?? '';
                $_POST['poskod_surat'] = $application['poskod_surat'] ?? '';
                // Health
                foreach (['darah_tinggi','kencing_manis','penyakit_buah_pinggang','penyakit_jantung','batuk_kering_tibi','kanser','aids','penagih_dadah','penyakit_lain','perokok','pemegang_kad_oku','memakai_cermin_mata'] as $k) {
                    $_POST[$k] = $application[$k] ?? '';
                }
                $_POST['penyakit_lain_nyatakan'] = $application['penyakit_lain_nyatakan'] ?? '';
                $_POST['jenis_rabun'] = $application['jenis_rabun'] ?? '';
                $_POST['jenis_oku'] = !empty($application['jenis_oku']) ? json_decode($application['jenis_oku'], true) : [];
                $_POST['berat_kg'] = $application['berat_kg'] ?? '';
                $_POST['tinggi_cm'] = $application['tinggi_cm'] ?? '';
                
                // SPM Data
                $_POST['spm_tahun'] = $application['spm_tahun'] ?? '';
                $_POST['spm_gred_keseluruhan'] = $application['spm_gred_keseluruhan'] ?? '';
                $_POST['spm_angka_giliran'] = $application['spm_angka_giliran'] ?? '';
                $_POST['spm_bahasa_malaysia'] = $application['spm_bahasa_malaysia'] ?? '';
                $_POST['spm_bahasa_inggeris'] = $application['spm_bahasa_inggeris'] ?? '';
                $_POST['spm_matematik'] = $application['spm_matematik'] ?? '';
                $_POST['spm_sejarah'] = $application['spm_sejarah'] ?? '';
                $_POST['spm_salinan_sijil'] = $application['spm_salinan_sijil'] ?? '';
                // License
                $_POST['lesen_memandu'] = !empty($application['lesen_memandu']) ? json_decode($application['lesen_memandu'], true) : [];
                $_POST['tarikh_tamat_lesen'] = $application['tarikh_tamat_lesen'] ?? '';

                // Declaration fields
                $_POST['pekerja_perkhidmatan_awam'] = $application['pekerja_perkhidmatan_awam'] ?? '';
                $_POST['pekerja_perkhidmatan_awam_nyatakan'] = $application['pekerja_perkhidmatan_awam_nyatakan'] ?? '';
                $_POST['pertalian_kakitangan'] = $application['pertalian_kakitangan'] ?? '';
                $_POST['pertalian_kakitangan_nyatakan'] = $application['pertalian_kakitangan_nyatakan'] ?? '';
                $_POST['pernah_bekerja_mphs'] = $application['pernah_bekerja_mphs'] ?? '';
                $_POST['pernah_bekerja_mphs_nyatakan'] = $application['pernah_bekerja_mphs_nyatakan'] ?? '';
                $_POST['tindakan_tatatertib'] = $application['tindakan_tatatertib'] ?? '';
                $_POST['tindakan_tatatertib_nyatakan'] = $application['tindakan_tatatertib_nyatakan'] ?? '';
                $_POST['kesalahan_undangundang'] = $application['kesalahan_undangundang'] ?? '';
                $_POST['kesalahan_undangundang_nyatakan'] = $application['kesalahan_undangundang_nyatakan'] ?? '';
                $_POST['muflis'] = $application['muflis'] ?? '';
                $_POST['muflis_nyatakan'] = $application['muflis_nyatakan'] ?? '';
                $_POST['status_id'] = $application['status_id'] ?? '';
                $_POST['status_description'] = ($application['status_id'] == 2) ? 'COMPLETED' : 'PENDING';

                // Fetch related tables to mimic form arrays
                // Language skills
                $_POST['kemahiran_bahasa'] = [];
                try {
                    // Check if table exists
                    $check_table = $pdo->query("SHOW TABLES LIKE 'application_language_skills'");
                    if ($check_table->rowCount() > 0) {
                        // Check column names
                        $pertuturan_column = 'tahap_lisan';
                        $check_column = $pdo->query("SHOW COLUMNS FROM application_language_skills LIKE 'tahap_lisan'");
                        if ($check_column->rowCount() == 0) {
                            $check_column = $pdo->query("SHOW COLUMNS FROM application_language_skills LIKE 'pertuturan'");
                            if ($check_column->rowCount() > 0) {
                                $pertuturan_column = 'pertuturan';
                            }
                        }
                        
                        $penulisan_column = 'tahap_penulisan';
                        $check_column = $pdo->query("SHOW COLUMNS FROM application_language_skills LIKE 'tahap_penulisan'");
                        if ($check_column->rowCount() == 0) {
                            $check_column = $pdo->query("SHOW COLUMNS FROM application_language_skills LIKE 'penulisan'");
                            if ($check_column->rowCount() > 0) {
                                $penulisan_column = 'penulisan';
                            }
                        }
                        
                        $query = "SELECT bahasa, $pertuturan_column, $penulisan_column FROM application_language_skills WHERE application_id = ?";
                        error_log("Language skills query: $query");
                        
                        $stmt = $pdo->prepare($query);
                        $stmt->execute([$application['id']]);
                        $i = 0;
                        while ($row = $stmt->fetch()) {
                            $_POST['kemahiran_bahasa'][$i++] = [
                                'bahasa' => $row['bahasa'],
                                'pertuturan' => $row[$pertuturan_column],
                                'penulisan' => $row[$penulisan_column]
                            ];
                        }
                        error_log('Loaded ' . $i . ' language skills');
                    } else {
                        error_log('application_language_skills table does not exist');
                    }
                } catch (PDOException $e) {
                    error_log('Error loading language skills: ' . $e->getMessage());
                }

                // Computer skills
                $_POST['kemahiran_komputer'] = [];
                try {
                    // Check if table exists
                    $check_table = $pdo->query("SHOW TABLES LIKE 'application_computer_skills'");
                    if ($check_table->rowCount() > 0) {
                        // Check column names
                        $check_column = $pdo->query("SHOW COLUMNS FROM application_computer_skills LIKE 'nama_perisian'");
                        $nama_column = 'nama_perisian';
                        if ($check_column->rowCount() == 0) {
                            $check_column = $pdo->query("SHOW COLUMNS FROM application_computer_skills LIKE 'nama_kemahiran'");
                            if ($check_column->rowCount() > 0) {
                                $nama_column = 'nama_kemahiran';
                            }
                        }

                        $check_column = $pdo->query("SHOW COLUMNS FROM application_computer_skills LIKE 'tahap_kemahiran'");
                        $tahap_column = 'tahap_kemahiran';
                        if ($check_column->rowCount() == 0) {
                            $check_column = $pdo->query("SHOW COLUMNS FROM application_computer_skills LIKE 'tahap'");
                            if ($check_column->rowCount() > 0) {
                                $tahap_column = 'tahap';
                            }
                        }
                        
                        $query = "SELECT $nama_column, $tahap_column FROM application_computer_skills WHERE application_id = ?";
                        error_log("Computer skills query: $query");
                        
                        $stmt = $pdo->prepare($query);
                        $stmt->execute([$application['id']]);
                        $i = 0;
                        while ($row = $stmt->fetch()) {
                            $_POST['kemahiran_komputer'][$i++] = [
                                'nama_perisian' => $row[$nama_column],
                                'tahap_kemahiran' => $row[$tahap_column]
                            ];
                        }
                        error_log('Loaded ' . $i . ' computer skills');
                    } else {
                        error_log('application_computer_skills table does not exist');
                    }
                } catch (PDOException $e) {
                    error_log('Error loading computer skills: ' . $e->getMessage());
                }
                
                // Professional bodies
                $_POST['badan_profesional'] = [];
                $stmt = $pdo->prepare('SELECT nama_lembaga, no_ahli, sijil, tarikh_sijil, salinan_sijil FROM application_professional_bodies WHERE application_id = ?');
                $stmt->execute([$application['id']]);
                $i = 0;
                while ($row = $stmt->fetch()) {
                    $_POST['badan_profesional'][$i++] = [
                        'nama_lembaga' => $row['nama_lembaga'],
                        'no_ahli' => $row['no_ahli'],
                        'sijil' => $row['sijil'],
                        'tarikh_sijil' => $row['tarikh_sijil'],
                        'salinan_sijil' => $row['salinan_sijil']
                    ];
                }
                
                // Extracurricular activities
                $_POST['kegiatan_luar'] = [];
                $stmt = $pdo->prepare('SELECT sukan_persatuan_kelab, jawatan, peringkat, tahun, salinan_sijil FROM application_extracurricular WHERE application_id = ?');
                $stmt->execute([$application['id']]);
                $i = 0;
                while ($row = $stmt->fetch()) {
                    $_POST['kegiatan_luar'][$i++] = [
                        'sukan_persatuan_kelab' => $row['sukan_persatuan_kelab'],
                        'jawatan' => $row['jawatan'],
                        'peringkat' => $row['peringkat'],
                        'tarikh_sijil' => isset($row['tarikh_sijil']) ? $row['tarikh_sijil'] : (!empty($row['tahun']) ? ($row['tahun'] . '-01-01') : ''),
                        'salinan_sijil' => $row['salinan_sijil']
                    ];
                }

                // SPM Subjects
                $_POST['spm_subjek_lain'] = [];
                $stmt = $pdo->prepare('SELECT subjek, gred FROM application_spm_subjects WHERE application_id = ?');
                $stmt->execute([$application['id']]);
                $i = 0;
                while ($row = $stmt->fetch()) {
                    $_POST['spm_subjek_lain'][$i++] = [
                        'subjek' => $row['subjek'],
                        'gred' => $row['gred']
                    ];
                }

                // Education
                $_POST['persekolahan'] = [];
                try {
                    // First check if the sijil_path column exists
                    $check_column = $pdo->query("SHOW COLUMNS FROM application_education LIKE 'sijil_path'");
                    $sijil_column = 'sijil_path';
                    
                    if ($check_column->rowCount() == 0) {
                        // If sijil_path doesn't exist, try sijil_filename
                        $check_column = $pdo->query("SHOW COLUMNS FROM application_education LIKE 'sijil_filename'");
                        if ($check_column->rowCount() > 0) {
                            $sijil_column = 'sijil_filename';
                        } else {
                            // If neither exists, default to sijil_path but log it
                            error_log('Neither sijil_path nor sijil_filename column exists in application_education table');
                        }
                    }
                    
                    // Now construct the query with the correct column name
                    $query = "SELECT nama_institusi, dari_tahun, hingga_tahun, kelayakan, pangkat_gred_cgpa, $sijil_column FROM application_education WHERE application_id = ?";
                    error_log("Education query: $query");
                    
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$application['id']]);
                    $i = 0; 
                    while ($row = $stmt->fetch()) { 
                        $_POST['persekolahan'][$i++] = [ 
                            'institusi' => $row['nama_institusi'] ?? $row['institusi'] ?? '', 
                            'dari_tahun' => $row['dari_tahun'], 
                            'hingga_tahun' => $row['hingga_tahun'], 
                            'kelayakan' => $row['kelayakan'], 
                            'gred' => $row['pangkat_gred_cgpa'] ?? $row['gred'] ?? '', 
                            'sijil' => $row[$sijil_column] ?? '' 
                        ]; 
                    }
                    error_log('Loaded ' . $i . ' education records');
                } catch (PDOException $e) {
                    error_log('Error loading education data: ' . $e->getMessage());
                }

                // Work experience
                $_POST['pengalaman_kerja'] = [];
                $stmt = $pdo->prepare('SELECT nama_syarikat, jawatan, dari_bulan, dari_tahun, hingga_bulan, hingga_tahun, gaji, alasan_berhenti FROM application_work_experience WHERE application_id = ?');
                $stmt->execute([$application['id']]);
                $i = 0; while ($row = $stmt->fetch()) { $_POST['pengalaman_kerja'][$i++] = [ 'syarikat' => $row['nama_syarikat'], 'jawatan' => $row['jawatan'], 'dari_bulan' => $row['dari_bulan'], 'dari_tahun' => $row['dari_tahun'], 'hingga_bulan' => $row['hingga_bulan'], 'hingga_tahun' => $row['hingga_tahun'], 'gaji' => $row['gaji'], 'alasan' => $row['alasan_berhenti'] ]; }

                // Family members - first check if we have data in the application_family_members table
                $_POST['ahli_keluarga'] = [];
                try {
                    // Check if the application_family_members table exists
                    $check_table = $pdo->query("SHOW TABLES LIKE 'application_family_members'");
                    if ($check_table->rowCount() > 0) {
                        $stmt = $pdo->prepare('SELECT hubungan, nama, pekerjaan, telefon, kewarganegaraan FROM application_family_members WHERE application_id = ?');
                        $stmt->execute([$application['id']]);
                        $i = 0;
                        while ($row = $stmt->fetch()) {
                            $_POST['ahli_keluarga'][$i++] = [
                                'hubungan' => $row['hubungan'],
                                'nama' => $row['nama'],
                                'pekerjaan' => $row['pekerjaan'],
                                'telefon' => $row['telefon'],
                                'kewarganegaraan' => $row['kewarganegaraan']
                            ];
                        }
                        error_log('Loaded ' . $i . ' family members from application_family_members table');
                    } else {
                        error_log('application_family_members table does not exist');
                    }
                } catch (PDOException $e) {
                    error_log('Error loading family members: ' . $e->getMessage());
                }
                
                // If no family members were loaded from the table, try to get the data from the main application record
                if (empty($_POST['ahli_keluarga'])) {
                    // Check for ibu/ayah fields in the main application record
                    if (!empty($application['ibu_nama']) || !empty($application['ayah_nama'])) {
                        if (!empty($application['ibu_nama'])) {
                            $_POST['ahli_keluarga'][] = [
                                'hubungan' => 'IBU',
                                'nama' => $application['ibu_nama'] ?? '',
                                'pekerjaan' => $application['ibu_pekerjaan'] ?? '',
                                'telefon' => $application['ibu_telefon'] ?? '',
                                'kewarganegaraan' => $application['ibu_kewarganegaraan'] ?? ''
                            ];
                        }
                        
                        if (!empty($application['ayah_nama'])) {
                            $_POST['ahli_keluarga'][] = [
                                'hubungan' => 'AYAH',
                                'nama' => $application['ayah_nama'] ?? '',
                                'pekerjaan' => $application['ayah_pekerjaan'] ?? '',
                                'telefon' => $application['ayah_telefon'] ?? '',
                                'kewarganegaraan' => $application['ayah_kewarganegaraan'] ?? ''
                            ];
                        }
                        error_log('Loaded family members from main application record');
                    }
                }

                // References - load from application_references table
                $_POST['rujukan'] = [];
                $stmt = $pdo->prepare('SELECT nama, telefon, tempoh FROM application_references WHERE application_id = ? ORDER BY id');
                $stmt->execute([$application['id']]);
                $i = 1;
                $references = [];
                while ($row = $stmt->fetch()) {
                    $references[] = $row;
                }
                
                // Map references to their respective fields
                if (count($references) > 0) {
                    $_POST['rujukan_1_nama'] = $references[0]['nama'];
                    $_POST['rujukan_1_telefon'] = $references[0]['telefon'];
                    $_POST['rujukan_1_tempoh'] = $references[0]['tempoh'];
                }
                
                if (count($references) > 1) {
                    $_POST['rujukan_2_nama'] = $references[1]['nama'];
                    $_POST['rujukan_2_telefon'] = $references[1]['telefon'];
                    $_POST['rujukan_2_tempoh'] = $references[1]['tempoh'];
                }
            }
        }

        // Fetch job details
        try {
            $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
            $stmt->execute([$job_id]);
            $job = $stmt->fetch();
            
            if (!$job) {
                error_log('Job not found for application preview: ' . $job_id);
                
                // Try alternative table name if job_postings doesn't have the record
                $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ? LIMIT 1');
                $stmt->execute([$job_id]);
                $job = $stmt->fetch();
                
                if ($job) {
                    error_log('Job found in jobs table instead of job_postings');
                } else {
                    $error = 'Jawatan tidak dijumpai.';
                    error_log('Job not found in either job_postings or jobs tables: ' . $job_id);
                }
            } else {
                error_log('Job found in job_postings table. Job title: ' . ($job['job_title'] ?? 'unknown'));
            }
        } catch (PDOException $e) {
            $error = 'Ralat mendapatkan maklumat jawatan.';
            error_log('Error fetching job for application preview: ' . $e->getMessage() . ' - Job ID: ' . $job_id);
            
            // Try to recover by checking if the jobs table exists instead
            try {
                $check_table = $pdo->query("SHOW TABLES LIKE 'jobs'");
                if ($check_table->rowCount() > 0) {
                    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ? LIMIT 1');
                    $stmt->execute([$job_id]);
                    $job = $stmt->fetch();
                    
                    if ($job) {
                        error_log('Job found in jobs table after error with job_postings');
                        $error = ''; // Clear the error since we recovered
                    }
                }
            } catch (PDOException $e2) {
                error_log('Error in recovery attempt: ' . $e2->getMessage());
            }
        }
    } catch (PDOException $e) {
        $error = 'Ralat mendapatkan maklumat jawatan.';
        error_log('Error fetching job for application preview: ' . $e->getMessage() . ' - Job ID: ' . $job_id);
    }
}

// Function to safely get POST data
function getPostData($key, $default = '') {
    return $_POST[$key] ?? $default;
}

// Function to safely get array POST data
function getPostArray($key) {
    return $_POST[$key] ?? [];
}

// Function to get POST data and convert to uppercase except for email fields
function getPostDataUppercase($key, $default = '') {
    $value = $_POST[$key] ?? $default;
    
    // Check if this is an email field
    if ($key === 'email' || strpos($key, 'email') !== false || filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return $value; // Return email as is, without uppercase conversion
    }
    
    // Convert other fields to uppercase
    return is_string($value) ? mb_strtoupper($value) : $value;
}

// Function to format date range
function formatDateRange($dari_bulan, $dari_tahun, $hingga_bulan, $hingga_tahun) {
    $months = [
        '1' => 'Januari', '2' => 'Februari', '3' => 'Mac', '4' => 'April',
        '5' => 'Mei', '6' => 'Jun', '7' => 'Julai', '8' => 'Ogos',
        '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Disember'
    ];
    
    $dari_text = '';
    if ($dari_bulan && $dari_tahun) {
        $dari_text = $months[$dari_bulan] . ' ' . $dari_tahun;
    }
    
    $hingga_text = '';
    if ($hingga_bulan && $hingga_tahun) {
        $hingga_text = $months[$hingga_bulan] . ' ' . $hingga_tahun;
    }
    
    return $dari_text . ' - ' . $hingga_text;
}

// Function to format computer skills data
function formatComputerSkillsData($skills) {
    if (!is_array($skills)) return [];
    
    $formatted = [];
    foreach ($skills as $index => $skill) {
        if (!empty($skill['nama_perisian'])) {
            $formatted[] = [
                'nama_perisian' => mb_strtoupper($skill['nama_perisian']),
                'tahap_kemahiran' => mb_strtoupper($skill['tahap_kemahiran'] ?? '')
            ];
        }
    }
    
    return $formatted;
}

// Function to format language skills data
function formatLanguageSkillsData($skills) {
    if (!is_array($skills)) return [];
    
    $formatted = [];
    foreach ($skills as $index => $skill) {
        if (!empty($skill['bahasa'])) {
            $formatted[] = [
                'bahasa' => mb_strtoupper($skill['bahasa']),
                'pertuturan' => mb_strtoupper($skill['pertuturan'] ?? ''),
                'penulisan' => mb_strtoupper($skill['penulisan'] ?? ''),
                'gred_spm' => mb_strtoupper($skill['gred_spm'] ?? '')
            ];
        }
    }
    
    return $formatted;
}

// Function to format professional body data
function formatProfessionalBodyData($bodies) {
    if (!is_array($bodies)) return [];
    
    $formatted = [];
    foreach ($bodies as $index => $body) {
        if (!empty($body['nama_lembaga'])) {
            $certificate_path = '';
            
            // Check if there's a certificate file upload
            if (isset($_FILES['badan_profesional']['name'][$index]['salinan_sijil']) && 
                $_FILES['badan_profesional']['error'][$index]['salinan_sijil'] === UPLOAD_ERR_OK) {
                
                $file = $_FILES['badan_profesional']['tmp_name'][$index]['salinan_sijil'];
                $file_name = $_FILES['badan_profesional']['name'][$index]['salinan_sijil'];
                $file_type = $_FILES['badan_profesional']['type'][$index]['salinan_sijil'];
                
                // Create a temporary base64 data URL for preview
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $content = file_get_contents($file);
                
                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $certificate_path = 'data:' . $file_type . ';base64,' . base64_encode($content);
                } elseif ($file_ext === 'pdf') {
                    // For PDF, we'll create a temporary file and link to it
                    $temp_dir = sys_get_temp_dir();
                    $temp_file = $temp_dir . '/preview_prof_' . uniqid() . '.pdf';
                    file_put_contents($temp_file, $content);
                    $certificate_path = $temp_file;
                }
            }
            
            $formatted[] = [
                'nama_lembaga' => mb_strtoupper($body['nama_lembaga']),
                'no_ahli' => mb_strtoupper($body['no_ahli'] ?? ''),
                'sijil' => mb_strtoupper($body['sijil'] ?? ''),
                'tarikh_sijil' => $body['tarikh_sijil'] ?? '',
                'certificate_path' => $certificate_path,
                'certificate_name' => $_FILES['badan_profesional']['name'][$index]['salinan_sijil'] ?? ''
            ];
        }
    }
    
    return $formatted;
}

// Function to format education data
function formatEducationData($persekolahan) {
    if (!is_array($persekolahan)) return [];
    
    $formatted = [];
    foreach ($persekolahan as $index => $edu) {
        if (!empty($edu['institusi'])) {
            $sijil_path = '';
            $sijil_error = '';
            
            // Check if there's a certificate file upload
            if (isset($_FILES['persekolahan']['name'][$index]['sijil']) && $_FILES['persekolahan']['error'][$index]['sijil'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['persekolahan']['error'][$index]['sijil'] === UPLOAD_ERR_OK) {
                
                    $file = $_FILES['persekolahan']['tmp_name'][$index]['sijil'];
                    $file_name = $_FILES['persekolahan']['name'][$index]['sijil'];
                    $file_type = $_FILES['persekolahan']['type'][$index]['sijil'];
                    
                    // Create a temporary base64 data URL for preview
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $content = file_get_contents($file);
                    
                    if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $sijil_path = 'data:' . $file_type . ';base64,' . base64_encode($content);
                    } elseif ($file_ext === 'pdf') {
                        // For PDF, we'll create a temporary file and link to it
                        $temp_dir = sys_get_temp_dir();
                        $temp_file = $temp_dir . '/preview_' . uniqid() . '.pdf';
                        file_put_contents($temp_file, $content);
                        $sijil_path = $temp_file;
                    }
                    
                    // Store original filename and file type for final submission
                    $edu['sijil_original_name'] = $file_name;
                    $edu['sijil_original_type'] = $file_type;
                    $edu['sijil_display_name'] = $file_name; // Add display name for the UI
                } else {
                    $sijil_error = 'Ralat muat naik fail. Kod: ' . $_FILES['persekolahan']['error'][$index]['sijil'];
                    error_log('Education certificate upload error for index ' . $index . ': ' . $sijil_error);
                }
            } else if (!empty($edu['sijil'])) {
                // If sijil path is already provided (from previous upload)
                $sijil_path = $edu['sijil'];
            }
            
            $formatted[] = [
                'institusi' => mb_strtoupper($edu['institusi']),
                'tempoh' => formatDateRange(
                    $edu['dari_bulan'] ?? '', 
                    $edu['dari_tahun'] ?? '', 
                    $edu['hingga_bulan'] ?? '', 
                    $edu['hingga_tahun'] ?? ''
                ),
                'kelayakan' => mb_strtoupper($edu['kelayakan'] ?? ''),
                'gred' => mb_strtoupper($edu['gred'] ?? ''),
                'sijil' => $sijil_path,
                'sijil_display_name' => mb_strtoupper($edu['sijil_original_name'] ?? basename($sijil_path)),
                'sijil_type' => $edu['sijil_type'] ?? '',
                'sijil_ext' => $edu['sijil_ext'] ?? strtolower(pathinfo($sijil_path, PATHINFO_EXTENSION)),
                'sijil_error' => $sijil_error
            ];
        }
    }
    return $formatted;
}

// Function to format work experience data
function formatWorkExperienceData($pengalaman_kerja) {
    if (!is_array($pengalaman_kerja)) return [];
    
    $formatted = [];
    foreach ($pengalaman_kerja as $work) {
        if (!empty($work['syarikat'])) {
            $formatted[] = [
                'syarikat' => mb_strtoupper($work['syarikat']),
                'jawatan' => mb_strtoupper($work['jawatan'] ?? ''),
                'tempoh' => formatDateRange(
                    $work['dari_bulan'] ?? '', 
                    $work['dari_tahun'] ?? '', 
                    $work['hingga_bulan'] ?? '', 
                    $work['hingga_tahun'] ?? ''
                ),
                'gaji' => mb_strtoupper($work['gaji'] ?? ''),
                'alasan' => mb_strtoupper($work['alasan'] ?? '')
            ];
        }
    }
    return $formatted;
}

// Function to format extracurricular data
function formatExtracurricularData($activities) {
    if (!is_array($activities)) return [];
    
    $formatted = [];
    foreach ($activities as $index => $activity) {
        if (!empty($activity['sukan_persatuan_kelab'])) {
            $certificate_path = '';
            
            // Check if there's a certificate file upload
            if (isset($_FILES['kegiatan_luar']['name'][$index]['salinan_sijil']) && 
                $_FILES['kegiatan_luar']['error'][$index]['salinan_sijil'] === UPLOAD_ERR_OK) {
                
                $file = $_FILES['kegiatan_luar']['tmp_name'][$index]['salinan_sijil'];
                $file_name = $_FILES['kegiatan_luar']['name'][$index]['salinan_sijil'];
                $file_type = $_FILES['kegiatan_luar']['type'][$index]['salinan_sijil'];
                
                // Create a temporary base64 data URL for preview
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $content = file_get_contents($file);
                
                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $certificate_path = 'data:' . $file_type . ';base64,' . base64_encode($content);
                } elseif ($file_ext === 'pdf') {
                    // For PDF, we'll create a temporary file and link to it
                    $temp_dir = sys_get_temp_dir();
                    $temp_file = $temp_dir . '/preview_extracurricular_' . uniqid() . '.pdf';
                    file_put_contents($temp_file, $content);
                    $certificate_path = $temp_file;
                }
            }
            
            $formatted[] = [
                'sukan_persatuan_kelab' => mb_strtoupper($activity['sukan_persatuan_kelab']),
                'jawatan' => mb_strtoupper($activity['jawatan'] ?? ''),
                'peringkat' => mb_strtoupper($activity['peringkat'] ?? ''),
                'tahun' => $activity['tahun'] ?? '',
                'salinan_sijil' => $certificate_path,
                'sijil_display_name' => isset($_FILES['kegiatan_luar']['name'][$index]['salinan_sijil']) ? 
                    $_FILES['kegiatan_luar']['name'][$index]['salinan_sijil'] : '',
                'sijil_ext' => isset($_FILES['kegiatan_luar']['name'][$index]['salinan_sijil']) ? 
                    strtolower(pathinfo($_FILES['kegiatan_luar']['name'][$index]['salinan_sijil'], PATHINFO_EXTENSION)) : ''
            ];
        }
    }
    return $formatted;
}

// Function to display JSON data as a table
function renderTable($data, $emptyMessage = 'Tiada maklumat', $columns = [], $escape = true) {
    if (empty($data)) {
        return "<div class='text-gray-500 text-sm font-sans'>$emptyMessage</div>";
    }
    
    // If columns not specified, use all unique keys from data
    $allKeys = [];
    foreach ($data as $item) {
        if (is_array($item)) {
            $allKeys = array_unique(array_merge($allKeys, array_keys($item)));
        }
    }
    
    if (empty($columns)) {
        $columns = $allKeys;
    } else {
        // Filter out any columns that don't exist in the data
        $columns = array_intersect($columns, $allKeys);
    }
    
    // If still no columns, return empty message
    if (empty($columns)) {
        return "<div class='text-gray-500 text-sm font-sans'>$emptyMessage</div>";
    }
    
    // Build the table
    $output = "<div class='overflow-x-auto font-sans text-sm'>";
    $output .= "<table class='min-w-full divide-y divide-gray-200 table-fixed'>";
    
    // Table header
    $output .= "<thead class='bg-gray-50'>";
    $output .= "<tr>";
    
    foreach ($columns as $key) {
        $label = strtoupper(str_replace('_', ' ', $key));
        $output .= "<th scope='col' class='px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>" . htmlspecialchars($label) . "</th>";
    }
    $output .= "</tr>";
    $output .= "</thead>";
    
    // Table body
    $output .= "<tbody class='bg-white divide-y divide-gray-200'>";
    
    foreach ($data as $item) {
        if (is_array($item)) {
            $output .= "<tr class='hover:bg-gray-50'>";
            
            foreach ($columns as $key) {
                $value = isset($item[$key]) ? $item[$key] : '';
                // Don't uppercase email addresses
                $display_value = (filter_var($value, FILTER_VALIDATE_EMAIL)) 
                    ? $value 
                    : strtoupper($value);
                $output .= "<td class='px-4 py-2 text-gray-900 font-medium'>" . ($escape ? htmlspecialchars($display_value) : $display_value) . "</td>";
            }
            $output .= "</tr>";
        }
    }
    $output .= "</tbody>";
    $output .= "</table>";
    $output .= "</div>";
    
    return $output;
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pratonton Permohonan - Majlis Perbandaran Hulu Selangor</title>
    <link href="assets/css/tailwind.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f7f9fc;
        }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
        }
        .modal-content {
            position: relative;
            margin: auto;
            padding: 0;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .modal-content img {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
        }
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            z-index: 1001;
        }
        .close:hover,
        .close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }
        .modal-caption {
            position: absolute;
            bottom: 0;
            width: 100%;
            text-align: center;
            color: white;
            padding: 10px;
            background-color: rgba(0,0,0,0.7);
        }
        .standard-container {
            max-width: 1050px;
            margin: 0 auto;
            width: 100%;
        }
        .section-title {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.125rem;
        }
        .data-row {
            border-bottom: 1px solid #e5e7eb;
            padding: 0.75rem 0;
        }
        .data-row:last-child {
            border-bottom: none;
        }
        .data-label {
            font-weight: 600;
            color: #374151;
            display: inline-block;
            min-width: 200px;
        }
        .data-value {
            color: #111827;
        }
        @media print {
            body { 
                background-color: white; 
                font-size: 12pt;
                color: #000;
            }
            .no-print { display: none; }
            .standard-container { 
                width: 100%; 
                max-width: 100%; 
                margin: 0;
                padding: 0;
            }
            .section-title {
                background: #f0f0f0 !important;
                color: #000 !important;
                border-bottom: 1px solid #000;
                break-inside: avoid;
            }
            .bg-white {
                box-shadow: none !important;
                border: 1px solid #ddd;
                margin-bottom: 15px;
                break-inside: avoid;
            }
            h1, h2, h3 { break-after: avoid; }
            table { break-inside: auto; }
            tr { break-inside: avoid; break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            .data-row { break-inside: avoid; }
            @page {
                margin: 1.5cm;
                size: A4;
            }
        }
    </style>
</head>
<body class="min-h-screen">

    <main class="standard-container px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
                <p><?php echo htmlspecialchars($error); ?></p>
                <p class="mt-2">
                    <a href="index.php" class="font-medium underline">Kembali ke halaman utama</a>
                </p>
            </div>
        <?php elseif ($job): ?>
            <!-- Header Section -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="bg-blue-600 text-white p-6">
                    <h1 class="text-2xl font-bold">Pratonton Permohonan Jawatan</h1>
                    <?php if ($job): ?>
                        <p class="mt-2"><?php echo htmlspecialchars(strtoupper($job['job_title'] ?? $job['title'] ?? 'Unknown Job Title')); ?></p>
                        <p class="text-blue-200 text-sm">Kod Gred: <?php echo htmlspecialchars($job['kod_gred'] ?? $job['gred'] ?? 'N/A'); ?></p>
                    <?php else: ?>
                        <p class="mt-2">Jawatan Tidak Dijumpai</p>
                        <p class="text-blue-200 text-sm">ID Jawatan: <?php echo htmlspecialchars($job_id ?? 'N/A'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section 1: Pengesahan -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="section-title">
                    1. PENGESAHAN
                </div>
                <div class="p-6">
                    <div class="space-y-2">
                        <div class="data-row">
                            <span class="data-label">Jawatan yang Dipohon:</span>
                            <span class="data-value">
                                <?php echo htmlspecialchars(getPostDataUppercase('jawatan_dipohon')); ?>
                                <span class="ml-4 text-gray-700">Pengakuan = <?php echo (normalizeYesNo(getPostData('pengistiharan')) === 'YA') ? 'Ya' : 'No'; ?></span>
                            </span>
                        </div>
                        <?php if (getPostDataUppercase('payment_reference')): ?>
                        <div class="data-row">
                            <span class="data-label">Rujukan Pembayaran:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('payment_reference')); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="data-row">
                            <span class="data-label">Pengistiharan:</span>
                            <span class="data-value"><?php echo getPostDataUppercase('pengistiharan') ? 'YA' : 'TIDAK'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Dokumen -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="section-title">
                    2. DOKUMEN
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Gambar Passport -->
                        <div>
                            <h3 class="font-semibold text-lg mb-2">Gambar Passport</h3>
                            <?php if (!empty($_FILES['gambar_passport']['tmp_name']) && $_FILES['gambar_passport']['error'] === UPLOAD_ERR_OK): ?>
                                <div class="border border-gray-200 rounded-lg p-2">
                                    <img src="<?php echo 'data:image/jpeg;base64,' . base64_encode(file_get_contents($_FILES['gambar_passport']['tmp_name'])); ?>" 
                                         alt="Gambar Passport" class="w-full h-auto max-h-48 object-contain mx-auto">
                                </div>
                            <?php elseif (!empty($_POST['gambar_passport_path'])): ?>
                                <div class="border border-gray-200 rounded-lg p-2">
                                    <img src="<?php echo htmlspecialchars($_POST['gambar_passport_path']); ?>" alt="Gambar Passport" class="w-full h-auto max-h-48 object-contain mx-auto">
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada gambar passport dimuat naik.</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Salinan IC -->
                        <div>
                            <h3 class="font-semibold text-lg mb-2">Salinan Kad Pengenalan</h3>
                            <?php if (!empty($_FILES['salinan_ic']['tmp_name']) && $_FILES['salinan_ic']['error'] === UPLOAD_ERR_OK): ?>
                                <div class="border border-gray-200 rounded-lg p-2">
                                    <img src="<?php echo 'data:image/jpeg;base64,' . base64_encode(file_get_contents($_FILES['salinan_ic']['tmp_name'])); ?>" 
                                         alt="Salinan IC" class="w-full h-auto max-h-48 object-contain mx-auto">
                                </div>
                            <?php elseif (!empty($_POST['salinan_ic_path'])): ?>
                                <div class="border border-gray-200 rounded-lg p-2">
                                    <img src="<?php echo htmlspecialchars($_POST['salinan_ic_path']); ?>" alt="Salinan IC" class="w-full h-auto max-h-48 object-contain mx-auto">
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada salinan kad pengenalan dimuat naik.</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Salinan Surat Beranak -->
                        <div>
                            <h3 class="font-semibold text-lg mb-2">Salinan Surat Beranak</h3>
                            <?php if (!empty($_FILES['salinan_surat_beranak']['tmp_name']) && $_FILES['salinan_surat_beranak']['error'] === UPLOAD_ERR_OK): ?>
                                <div class="border border-gray-200 rounded-lg p-2">
                                    <img src="<?php echo 'data:image/jpeg;base64,' . base64_encode(file_get_contents($_FILES['salinan_surat_beranak']['tmp_name'])); ?>" 
                                         alt="Salinan Surat Beranak" class="w-full h-auto max-h-48 object-contain mx-auto">
                                </div>
                            <?php elseif (!empty($_POST['salinan_surat_beranak_path'])): ?>
                                <div class="border border-gray-200 rounded-lg p-2">
                                    <img src="<?php echo htmlspecialchars($_POST['salinan_surat_beranak_path']); ?>" alt="Salinan Surat Beranak" class="w-full h-auto max-h-48 object-contain mx-auto">
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada salinan surat beranak dimuat naik.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="section-title">PENGISYTIHARAN</div>
                <div class="p-6">
                    <div class="bg-gray-50 border border-gray-200 rounded p-3">
                        <div class="text-gray-800">Saya mengesahkan semua maklumat yang diberikan adalah benar.<span class="text-red-600">*</span></div>
                        <div class="mt-2 font-semibold text-gray-900">Pengakuan: <?php echo (normalizeYesNo($_POST['pengistiharan'] ?? '0') === 'YA') ? 'Ya' : 'No'; ?></div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Maklumat Peribadi -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="section-title">
                    3. MAKLUMAT PERIBADI
                </div>
                <div class="p-6">
                    <div class="data-row">
                        <span class="data-value">Saya mengesahkan semua maklumat yang diberikan adalah benar.<span class="text-red-600">*</span></span>
                    </div>
                    <div class="data-row">
                        <span class="data-value font-semibold">Pengakuan = <?php echo (normalizeYesNo(getPostData('pengistiharan')) === 'YA') ? 'Ya' : 'No'; ?></span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
                        <div class="space-y-2">
                            <div class="data-row">
                                <span class="data-label">Nama Penuh:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('nama_penuh')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Nombor Kad Pengenalan:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('nombor_ic')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Nombor Surat Beranak:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('nombor_surat_beranak')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Emel:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('email')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Agama:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('agama')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Taraf Perkahwinan:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('taraf_perkahwinan')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Jantina:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('jantina')); ?></span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="data-row">
                                <span class="data-label">Tarikh Lahir:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('tarikh_lahir')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Umur:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('umur')); ?> tahun</span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Negeri Kelahiran:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('negeri_kelahiran')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Bangsa:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('bangsa')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Warganegara:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('warganegara')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Tempoh Bermastautin Di Selangor:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('tempoh_bermastautin_selangor')); ?> tahun</span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Nombor Telefon:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('nombor_telefon')); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section 3: Maklumat Pasangan -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 w-full">
                <div class="section-title">
                    3. MAKLUMAT PASANGAN
                </div>
                <div class="p-6">
                    <?php if (!empty(getPostData('nama_pasangan'))): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
                            <div class="space-y-2">
                                <div class="data-row">
                                    <span class="data-label">Nama Pasangan:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('nama_pasangan')); ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Telefon Pasangan:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('telefon_pasangan')); ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Bilangan Anak:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('bilangan_anak')); ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Status Pasangan:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('status_pasangan')); ?></span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="data-row">
                                    <span class="data-label">Pekerjaan Pasangan:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('pekerjaan_pasangan')); ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Nama Majikan:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('nama_majikan_pasangan')); ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Telefon Pejabat:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('telefon_pejabat_pasangan')); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <h3 class="font-semibold text-lg mb-2">Alamat Majikan Pasangan</h3>
                            <div class="data-row">
                                <span class="data-label">Alamat:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('alamat_majikan_pasangan')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Poskod:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('poskod_majikan_pasangan')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Bandar:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('bandar_majikan_pasangan')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Negeri:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('negeri_majikan_pasangan')); ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Tiada maklumat pasangan dimasukkan.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Section 4: Maklumat Ahli Keluarga -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 w-full">
                <div class="section-title">
                    4. MAKLUMAT AHLI KELUARGA
                </div>
                <div class="p-6">
                    <?php
                    if (!empty($_POST['ahli_keluarga'])) {
                        // Debug information
                        error_log('Family members data: ' . print_r($_POST['ahli_keluarga'], true));
                        
                        foreach ($_POST['ahli_keluarga'] as $index => $ahli) {
                            if (!empty($ahli['hubungan']) || !empty($ahli['nama'])) {
                                // Each family member in a full-width container with unique class
                                echo '<div class="preview-family-member-' . $index . ' mb-6 p-4 border border-gray-200 rounded-lg bg-green-50 w-full">';
                                echo '<div class="preview-family-relation font-semibold text-lg mb-3">' . htmlspecialchars(mb_strtoupper($ahli['hubungan'] ?? '')) . '</div>';
                                
                                // Display all fields in a single row
                                echo '<div class="preview-family-grid grid grid-cols-1 md:grid-cols-4 gap-4 w-full">';
                                
                                // Nama
                                echo '<div class="preview-family-name col-span-1">';
                                echo '<div class="font-medium text-gray-700">Nama:</div>';
                                echo '<div class="text-gray-900">' . htmlspecialchars(mb_strtoupper($ahli['nama'] ?? '')) . '</div>';
                                echo '</div>';
                                
                                // Pekerjaan
                                echo '<div class="preview-family-job col-span-1">';
                                echo '<div class="font-medium text-gray-700">Pekerjaan:</div>';
                                echo '<div class="text-gray-900">' . htmlspecialchars(mb_strtoupper($ahli['pekerjaan'] ?? '')) . '</div>';
                                echo '</div>';
                                
                                // No. Telefon
                                echo '<div class="preview-family-phone col-span-1">';
                                echo '<div class="font-medium text-gray-700">No. Telefon:</div>';
                                echo '<div class="text-gray-900">' . htmlspecialchars($ahli['telefon'] ?? '') . '</div>';
                                echo '</div>';
                                
                                // Kewarganegaraan
                                echo '<div class="preview-family-citizenship col-span-1">';
                                echo '<div class="font-medium text-gray-700">Kewarganegaraan:</div>';
                                echo '<div class="text-gray-900">' . htmlspecialchars(mb_strtoupper($ahli['kewarganegaraan'] ?? '')) . '</div>';
                                echo '</div>';
                                
                                echo '</div>'; // End of grid
                                echo '</div>'; // End of family member container
                                
                                // Add hidden fields to preserve the data for final submission
                                echo '<input type="hidden" name="ahli_keluarga[' . $index . '][hubungan]" value="' . htmlspecialchars($ahli['hubungan'] ?? '') . '">';
                                echo '<input type="hidden" name="ahli_keluarga[' . $index . '][nama]" value="' . htmlspecialchars($ahli['nama'] ?? '') . '">';
                                echo '<input type="hidden" name="ahli_keluarga[' . $index . '][pekerjaan]" value="' . htmlspecialchars($ahli['pekerjaan'] ?? '') . '">';
                                echo '<input type="hidden" name="ahli_keluarga[' . $index . '][telefon]" value="' . htmlspecialchars($ahli['telefon'] ?? '') . '">';
                                echo '<input type="hidden" name="ahli_keluarga[' . $index . '][kewarganegaraan]" value="' . htmlspecialchars($ahli['kewarganegaraan'] ?? '') . '">';
                            }
                        }
                    } else {
                        echo '<div class="preview-family-empty p-4 text-center text-gray-600">Tiada maklumat ahli keluarga</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Section 5: Alamat -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="section-title">
                    5. ALAMAT
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
                        <div class="space-y-2">
                            <h3 class="text-sm mb-2">Alamat Tetap</h3>
                            <div class="data-row">
                                <span class="data-label">Alamat:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('alamat_tetap')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Bandar:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('bandar_tetap')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Poskod:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('poskod_tetap')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Negeri:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('negeri_tetap')); ?></span>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <h3 class="text-sm mb-2">Alamat Surat Menyurat</h3>
                            <?php if (getPostDataUppercase('alamat_surat_sama')): ?>
                                <div class="data-row">
                                    <span class="data-label">Alamat:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('alamat_tetap')); ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Bandar:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('bandar_tetap')); ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Poskod:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('poskod_tetap')); ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Negeri:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('negeri_tetap')); ?></span>
                                </div>
                                <div class="data-row text-sm text-gray-500 italic">
                                    <span>(Sama seperti alamat tetap)</span>
                                </div>
                            <?php else: ?>
                                <div class="data-row">
                                    <span class="data-label">Alamat:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('alamat_surat')); ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Bandar:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('bandar_surat')); ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Poskod:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('poskod_surat')); ?></span>
                                </div>
                                <div class="data-row">
                                    <span class="data-label">Negeri:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('negeri_surat')); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section 6: Maklumat Lesen Memandu -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="section-title">
                    6. MAKLUMAT LESEN MEMANDU
                </div>
                <div class="p-6">
                    <div class="space-y-2">
                        <div class="data-row">
                            <span class="data-label">Lesen Memandu:</span>
                            <span class="data-value">
                                <?php 
                                $lesen_raw = $app['lesen_memandu_set'] ?? ($app['lesen_memandu'] ?? ($app['kelas_lesen'] ?? ''));
                                $classes = [];
                                $decoded = is_string($lesen_raw) ? json_decode($lesen_raw, true) : (is_array($lesen_raw) ? $lesen_raw : null);
                                if (is_array($decoded)) {
                                    foreach ($decoded as $v) { if ($v !== '') { $classes[] = mb_strtoupper($v); } }
                                } else {
                                    $val = mb_strtoupper(trim((string)$lesen_raw));
                                    if ($val !== '') {
                                        $parts = array_map('trim', explode(',', $val));
                                        foreach ($parts as $p) { if ($p !== '') { $classes[] = mb_strtoupper($p); } }
                                    }
                                }
                                $has_license = !empty($classes) && !in_array('TIADA LESEN', $classes, true) && !in_array('TIADA', $classes, true);
                                if ($has_license) {
                                    echo htmlspecialchars(implode(', ', $classes));
                                    $lesen_file = $app['salinan_lesen_memandu_path'] ?? ($app['salinan_lesen_memandu'] ?? ($app['salinan_lesen'] ?? ''));
                                    if (!empty($lesen_file)) {
                                        try {
                                            $lesen_url = buildAppFileUrl($lesen_file, $app);
                                            echo ' <a href="' . h($lesen_url) . '" target="_blank" class="text-blue-600 hover:underline text-xs ml-2">(papar salinan)</a>';
                                        } catch (Exception $e) {
                                            error_log('Error building license file URL: ' . $e->getMessage());
                                        }
                                    }
                                } else {
                                    echo 'TIADA LESEN';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="data-row">
                            <span class="data-label">Tarikh Tamat Lesen:</span>
                            <span class="data-value"><?php 
                              $expireRaw = $app['tarikh_tamat_lesen'] ?? getPostDataUppercase('tarikh_tamat_lesen');
                              echo htmlspecialchars($expireRaw ? date('d-M-Y', strtotime($expireRaw)) : 'TIADA'); 
                            ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section 7: Maklumat Kesihatan -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="section-title">
                    7. MAKLUMAT KESIHATAN / FIZIKAL
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
                        <div class="space-y-2">
                            <div class="data-row">
                                <span class="data-label">Darah Tinggi:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('darah_tinggi')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Kencing Manis:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('kencing_manis')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Penyakit Jantung:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('penyakit_jantung')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Penyakit Buah Pinggang:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('penyakit_buah_pinggang')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Batuk Kering/Tibi:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('batuk_kering_tibi')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Kanser:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('kanser')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">AIDS:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('aids')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Penagih Dadah:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('penagih_dadah')); ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Penyakit Lain:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('penyakit_lain')); ?></span>
                            </div>
                            <?php if (getPostDataUppercase('penyakit_lain') === 'YA'): ?>
                            <div class="data-row">
                                <span class="data-label">Penyakit Lain (Nyatakan):</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('penyakit_lain_nyatakan')); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="data-row">
                                <span class="data-label">Perokok:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('perokok')); ?></span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="data-row preview-oku-status">
                                <span class="data-label">Pemegang Kad OKU:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('pemegang_kad_oku')); ?></span>
                            </div>
                            <?php if (getPostDataUppercase('pemegang_kad_oku') === 'YA'): ?>
                                <?php 
                                $jenis_oku = getPostArray('jenis_oku');
                                if (!empty($jenis_oku)): 
                                    // Format jenis OKU data for display
                                    $formatted_oku = [];
                                    foreach ($jenis_oku as $oku) {
                                        $formatted_oku[] = mb_strtoupper($oku);
                                    }
                                ?>
                                <div class="data-row preview-oku-types">
                                    <span class="data-label">Jenis OKU:</span>
                                    <div class="data-value">
                                        <ul class="list-disc pl-5 space-y-1">
                                            <?php foreach ($formatted_oku as $oku_type): ?>
                                                <li><?php echo htmlspecialchars($oku_type); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="data-row preview-oku-types-empty">
                                    <span class="data-label">Jenis OKU:</span>
                                    <span class="data-value text-gray-500">Tiada maklumat jenis OKU dimasukkan.</span>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="data-row preview-eyesight-status">
                                <span class="data-label">Memakai Cermin Mata:</span>
                                <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('memakai_cermin_mata')); ?></span>
                            </div>
                            <?php if (getPostDataUppercase('memakai_cermin_mata') === 'YA'): ?>
                                <?php if (!empty(getPostData('jenis_rabun'))): ?>
                                <div class="data-row preview-eyesight-type">
                                    <span class="data-label">Jenis Rabun:</span>
                                    <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('jenis_rabun')); ?></span>
                                </div>
                                <?php else: ?>
                                <div class="data-row preview-eyesight-type-empty">
                                    <span class="data-label">Jenis Rabun:</span>
                                    <span class="data-value text-gray-500">Tiada maklumat jenis rabun dimasukkan.</span>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <!-- Penyakit Lain field moved to left column -->
                        </div>
                    </div>
                    
                    <!-- Physical measurements in the same row -->
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <?php
                          $tinggi = $health['tinggi_cm'] ?? $app['tinggi_cm'] ?? null;
                          $berat = $health['berat_kg'] ?? $app['berat_kg'] ?? null;
                          $tinggi_display = ($tinggi !== null && $tinggi !== '') ? (is_numeric($tinggi) ? $tinggi . ' cm' : h($tinggi)) : 'Tidak Dinyatakan';
                          $berat_display = ($berat !== null && $berat !== '') ? (is_numeric($berat) ? $berat . ' kg' : h($berat)) : 'Tidak Dinyatakan';
                        ?>
                        <div class="flex justify-between">
                          <span class="text-gray-500">Tinggi:</span>
                          <span class="font-medium text-gray-900"><?php echo $tinggi_display; ?></span>
                        </div>
                        <div class="flex justify-between mt-2">
                          <span class="text-gray-500">Berat:</span>
                          <span class="font-medium text-gray-900"><?php echo $berat_display; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section 8: Pendidikan -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="section-title">
                    8. PENDIDIKAN
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- Language Skills (Moved to first position) -->
                        <div>
                            <h3 class="font-semibold text-lg mb-3">Kemahiran Bahasa</h3>
                            <?php 
                            $language_skills = formatLanguageSkillsData(getPostArray('kemahiran_bahasa'));
                            if (!empty($language_skills)): 
                            ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Bahasa</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Pertuturan</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Penulisan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($language_skills as $skill): ?>
                                            <tr>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($skill['bahasa']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($skill['pertuturan']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($skill['penulisan']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada kemahiran bahasa dimasukkan.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Computer Skills (Moved to second position) -->
                        <div>
                            <h3 class="font-semibold text-lg mb-3">Kemahiran Komputer</h3>
                            <?php 
                            $computer_skills = formatComputerSkillsData(getPostArray('kemahiran_komputer'));
                            if (!empty($computer_skills)): 
                            ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Nama Perisian</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Tahap Kemahiran</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($computer_skills as $skill): ?>
                                            <tr>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($skill['nama_perisian']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($skill['tahap_kemahiran']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada kemahiran komputer dimasukkan.</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Professional Body Information (Moved to third position) -->
                        <div>
                            <h3 class="font-semibold text-lg mb-3">Maklumat Badan Professional</h3>
                            <?php 
                            $professional_bodies = formatProfessionalBodyData(getPostArray('badan_profesional'));
                            if (!empty($professional_bodies)): 
                            ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Nama Lembaga/Badan</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">No. Ahli / Sijil</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Sijil Yang Diperoleh</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Tarikh Sijil</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Salinan Sijil</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($professional_bodies as $body): ?>
                                            <tr>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($body['nama_lembaga']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($body['no_ahli'] ?? ''); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($body['sijil']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo !empty($body['tarikh_sijil']) ? date('d/m/Y', strtotime($body['tarikh_sijil'])) : ''; ?></td>
                                                <td class="px-4 py-2 text-sm">
                                                    <?php if (!empty($body['certificate_path'])): ?>
                                                        <?php if (strpos($body['certificate_path'], 'data:image') === 0): ?>
                                                            <img src="<?php echo $body['certificate_path']; ?>" alt="Certificate" class="max-w-32 max-h-32 object-contain cursor-pointer" onclick="openImageModal(this.src)">
                                                        <?php elseif (strpos($body['certificate_name'], '.pdf') !== false): ?>
                                                            <span class="text-blue-600 font-medium">📄 <?php echo htmlspecialchars($body['certificate_name']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-green-600 font-medium">📎 <?php echo htmlspecialchars($body['certificate_name']); ?></span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-gray-500 italic">Tiada sijil</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada maklumat badan professional dimasukkan.</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- SPM Results -->
                        <div>
                            <h3 class="font-semibold text-lg mb-3">Keputusan SPM</h3>
                            <?php if (!empty(getPostData('spm_tahun'))): ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Maklumat</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-medium">Tahun</td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(getPostDataUppercase('spm_tahun')); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-medium">Gred Keseluruhan</td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(getPostDataUppercase('spm_gred_keseluruhan')); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-medium">Angka Giliran</td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(getPostDataUppercase('spm_angka_giliran')); ?></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <h4 class="font-semibold text-md mt-4 mb-2">Mata Pelajaran Wajib</h4>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Mata Pelajaran</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Gred</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-medium">Bahasa Malaysia</td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(getPostDataUppercase('spm_bahasa_malaysia')); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-medium">Bahasa Inggeris</td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(getPostDataUppercase('spm_bahasa_inggeris')); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-medium">Matematik</td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(getPostDataUppercase('spm_matematik')); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-medium">Sejarah</td>
                                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(getPostDataUppercase('spm_sejarah')); ?></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <?php 
                                $spm_subjects = getPostArray('spm_subjek_lain');
                                if (!empty($spm_subjects)): 
                                ?>
                                <h4 class="font-semibold text-md mt-4 mb-2">Mata Pelajaran Tambahan</h4>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Mata Pelajaran</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Gred</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($spm_subjects as $subject): ?>
                                            <?php if (!empty($subject['subjek'])): ?>
                                            <tr>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(mb_strtoupper($subject['subjek'])); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars(mb_strtoupper($subject['gred'])); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>

                                <?php if (!empty($_POST['spm_salinan_sijil'])): ?>
                                <div class="mt-4">
                                    <h4 class="font-semibold text-md mb-2">Salinan Sijil SPM</h4>
                                    <div class="border border-gray-200 rounded-lg p-2">
                                        <img src="<?php echo htmlspecialchars($_POST['spm_salinan_sijil']); ?>" 
                                             alt="Sijil SPM" class="w-full h-auto max-h-48 object-contain mx-auto">
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada maklumat SPM dimasukkan.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Education History -->
                        <div class="mt-8">
                            <h3 class="font-semibold text-lg mb-3">Maklumat Persekolahan & IPT</h3>
                            
                            <?php 
                            $education_data = formatEducationData(getPostArray('persekolahan'));
                            if (!empty($education_data)): 
                            ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Institusi/Sekolah</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Tempoh</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Kelayakan</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Gred/CGPA</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Sijil</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($education_data as $edu): ?>
                                            <tr>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($edu['institusi']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($edu['tempoh']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($edu['kelayakan']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($edu['gred']); ?></td>
                                                <td class="px-4 py-2 text-sm">
                                                    <?php if (!empty($edu['sijil_error'])): ?>
                                                        <div class="text-red-600 mb-2">
                                                            <?php echo htmlspecialchars($edu['sijil_error']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($edu['sijil'])): ?>
                                                        <?php 
                                                        $file_ext = isset($edu['sijil_ext']) ? $edu['sijil_ext'] : strtolower(pathinfo($edu['sijil'], PATHINFO_EXTENSION));
                                                        $file_name = isset($edu['sijil_display_name']) ? $edu['sijil_display_name'] : basename($edu['sijil']);
                                                        ?>
                                                        <div class="border border-gray-200 rounded-lg p-2 mb-2">
                                                            <?php if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                                                <a href="#" class="sijil-preview text-blue-600 hover:underline" 
                                                                   data-file="<?php echo htmlspecialchars($edu['sijil']); ?>"
                                                                   data-filename="<?php echo htmlspecialchars($file_name); ?>">
                                                                    <div class="flex items-center">
                                                                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                        <?php echo htmlspecialchars($file_name); ?>
                                                                    </div>
                                                                </a>
                                                                <img src="<?php echo htmlspecialchars($edu['sijil']); ?>" 
                                                                     alt="Sijil" class="w-full h-auto max-h-32 object-contain mx-auto mt-2">
                                                            <?php elseif ($file_ext === 'pdf'): ?>
                                                                <a href="<?php echo htmlspecialchars($edu['sijil']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                                                    <div class="flex items-center">
                                                                        <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                        <?php echo htmlspecialchars($file_name); ?>
                                                                    </div>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-gray-500 italic">Tiada sijil</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada maklumat pendidikan dimasukkan.</p>
                            <?php endif; ?>
                        </div>
                        

                        
                        <!-- Extracurricular Activities -->
                        <div>
                            <h3 class="font-semibold text-lg mb-3">Maklumat Kegiatan Luar</h3>
                            <?php 
                            $extracurricular_data = formatExtracurricularData(getPostArray('kegiatan_luar'));
                            if (!empty($extracurricular_data)): 
                            ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Sukan/Persatuan/Kelab</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Jawatan</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Peringkat</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Tarikh Sijil</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Salinan Sijil</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($extracurricular_data as $activity): ?>
                                            <tr>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($activity['sukan_persatuan_kelab']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($activity['jawatan']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($activity['peringkat']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($activity['tarikh_sijil']); ?></td>
                                                <td class="px-4 py-2 text-sm">
                                                    <?php if (!empty($activity['salinan_sijil'])): ?>
                                                        <?php 
                                                        $file_ext = isset($activity['sijil_ext']) ? $activity['sijil_ext'] : strtolower(pathinfo($activity['salinan_sijil'], PATHINFO_EXTENSION));
                                                        $file_name = isset($activity['sijil_display_name']) ? $activity['sijil_display_name'] : basename($activity['salinan_sijil']);
                                                        ?>
                                                        <div class="border border-gray-200 rounded-lg p-2 mb-2">
                                                            <?php if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                                                <a href="#" class="sijil-preview text-blue-600 hover:underline" 
                                                                   data-file="<?php echo htmlspecialchars($activity['salinan_sijil']); ?>"
                                                                   data-filename="<?php echo htmlspecialchars($file_name); ?>">
                                                                    <div class="flex items-center">
                                                                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                        <?php echo htmlspecialchars($file_name); ?>
                                                                    </div>
                                                                </a>
                                                                <img src="<?php echo htmlspecialchars($activity['salinan_sijil']); ?>" 
                                                                     alt="Salinan Sijil" class="w-full h-auto max-h-32 object-contain mx-auto mt-2">
                                                            <?php elseif ($file_ext === 'pdf'): ?>
                                                                <a href="<?php echo htmlspecialchars($activity['salinan_sijil']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                                                    <div class="flex items-center">
                                                                        <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                                                        </svg>
                                                                        <?php echo htmlspecialchars($file_name); ?>
                                                                    </div>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-gray-500 italic">Tiada sijil</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada maklumat kegiatan luar dimasukkan.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section 9: Pengalaman Kerja -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="section-title">
                    9. PENGALAMAN KERJA
                </div>
                <div class="p-6">
                    <div class="data-row">
                        <span class="data-label">Ada Pengalaman Kerja:</span>
                        <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('ada_pengalaman_kerja', 'Tidak')); ?></span>
                    </div>
                    
                    <?php 
                    $formatted_work_data = [];
                    if (!empty($work_rows)) {
                        foreach ($work_rows as $row) {
                            $db_dari_bulan = $row['dari_bulan'] ?? '';
                            $db_dari_tahun = $row['dari_tahun'] ?? '';
                            $db_hingga_bulan = $row['hingga_bulan'] ?? '';
                            $db_hingga_tahun = $row['hingga_tahun'] ?? '';
                            if ((empty($db_dari_bulan) || empty($db_dari_tahun)) && !empty($row['mula_berkhidmat'])) {
                                $parts = explode('-', (string)$row['mula_berkhidmat']);
                                $db_dari_tahun = $parts[0] ?? '';
                                $db_dari_bulan = ltrim((string)($parts[1] ?? ''), '0');
                            }
                            if ((empty($db_hingga_bulan) || empty($db_hingga_tahun)) && !empty($row['tamat_berkhidmat'])) {
                                $partsH = explode('-', (string)$row['tamat_berkhidmat']);
                                $db_hingga_tahun = $partsH[0] ?? '';
                                $db_hingga_bulan = ltrim((string)($partsH[1] ?? ''), '0');
                            }
                            $gajiRaw = $row['gaji'] ?? '';
                            $gajiFmt = '';
                            if ($gajiRaw !== '') {
                                $num = str_replace(',', '', (string)$gajiRaw);
                                $gajiFmt = is_numeric($num) ? ('RM ' . number_format((float)$num, 2)) : ('RM ' . (string)$gajiRaw);
                            }
                            $formatted_work_data[] = [
                                'syarikat' => mb_strtoupper($row['syarikat'] ?? ($row['nama_syarikat'] ?? '')),
                                'jawatan' => mb_strtoupper($row['jawatan'] ?? ''),
                                'tempoh' => formatWorkExperienceDate($db_dari_bulan, $db_dari_tahun, $db_hingga_bulan, $db_hingga_tahun),
                                'gaji_terakhir' => $gajiFmt,
                                'Skop Kerja' => '<div class="border border-gray-200 rounded-md bg-gray-50 p-3 text-sm text-gray-800 whitespace-pre-line">' . h(mb_strtoupper($row['bidang_tugas'] ?? '')) . '</div>',
                                'alasan_berhenti' => mb_strtoupper($row['alasan_berhenti'] ?? ($row['alasan'] ?? ''))
                            ];
                        }
                        echo '<div class="mt-4">';
                        echo renderTable($formatted_work_data, "Tiada maklumat pengalaman kerja dimasukkan.", ['syarikat','jawatan','tempoh','gaji_terakhir','Skop Kerja','alasan_berhenti'], false);
                        echo '</div>';
                    } else {
                        $posted = getPostArray('pengalaman_kerja');
                        foreach ($posted as $work) {
                            $tempoh = formatWorkExperienceDate(
                                $work['dari_bulan'] ?? '',
                                $work['dari_tahun'] ?? '',
                                $work['hingga_bulan'] ?? '',
                                $work['hingga_tahun'] ?? ''
                            );
                            $gajiRaw = $work['gaji'] ?? '';
                            $gajiFmt = '';
                            if ($gajiRaw !== '') {
                                $num = str_replace(',', '', (string)$gajiRaw);
                                $gajiFmt = is_numeric($num) ? ('RM ' . number_format((float)$num, 2)) : ('RM ' . (string)$gajiRaw);
                            }
                            $formatted_work_data[] = [
                                'syarikat' => mb_strtoupper($work['syarikat'] ?? ''),
                                'jawatan' => mb_strtoupper($work['jawatan'] ?? ''),
                                'tempoh' => $tempoh,
                                'gaji_terakhir' => $gajiFmt,
                                'Skop Kerja' => '<div class="border border-gray-200 rounded-md bg-gray-50 p-3 text-sm text-gray-800 whitespace-pre-line">' . h(mb_strtoupper($work['bidang_tugas'] ?? '')) . '</div>',
                                'alasan_berhenti' => mb_strtoupper($work['alasan'] ?? '')
                            ];
                        }
                        if (!empty($formatted_work_data)) {
                            echo '<div class="mt-4">';
                            echo renderTable($formatted_work_data, "Tiada maklumat pengalaman kerja dimasukkan.", ['syarikat','jawatan','tempoh','gaji_terakhir','Skop Kerja','alasan_berhenti'], false);
                            echo '</div>';
                        } else {
                            echo '<p class="text-gray-500 italic mt-4">Tiada maklumat pengalaman kerja dimasukkan.</p>';
                        }
                    }
                    ?>
                </div>
            </div>
            

            <!-- Section 10: Pengisytiharan Diri -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="section-title">
                    10. PENGISYTIHARAN DIRI
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="data-row">
                            <span class="data-label">Pekerja Perkhidmatan Awam:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('pekerja_perkhidmatan_awam')); ?></span>
                        </div>
                        <?php if (getPostDataUppercase('pekerja_perkhidmatan_awam') === 'Ya' && getPostDataUppercase('pekerja_perkhidmatan_awam_nyatakan')): ?>
                        <div class="data-row">
                            <span class="data-label">Nyatakan:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('pekerja_perkhidmatan_awam_nyatakan')); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="data-row">
                            <span class="data-label">Pertalian Kakitangan:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('pertalian_kakitangan')); ?></span>
                        </div>
                        <?php if (getPostDataUppercase('pertalian_kakitangan') === 'Ya' && getPostDataUppercase('pertalian_kakitangan_nyatakan')): ?>
                        <div class="data-row">
                            <span class="data-label">Nyatakan:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('pertalian_kakitangan_nyatakan')); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="data-row">
                            <span class="data-label">Pernah Bekerja MPHS:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('pernah_bekerja_mphs')); ?></span>
                        </div>
                        <?php if (getPostDataUppercase('pernah_bekerja_mphs') === 'Ya' && getPostDataUppercase('pernah_bekerja_mphs_nyatakan')): ?>
                        <div class="data-row">
                            <span class="data-label">Nyatakan:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('pernah_bekerja_mphs_nyatakan')); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="data-row">
                            <span class="data-label">Tindakan Tatatertib:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('tindakan_tatatertib')); ?></span>
                        </div>
                        <?php if (getPostDataUppercase('tindakan_tatatertib') === 'Ya' && getPostDataUppercase('tindakan_tatatertib_nyatakan')): ?>
                        <div class="data-row">
                            <span class="data-label">Nyatakan:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('tindakan_tatatertib_nyatakan')); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="data-row">
                            <span class="data-label">Kesalahan Undang-undang:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('kesalahan_undangundang')); ?></span>
                        </div>
                        <?php if (getPostDataUppercase('kesalahan_undangundang') === 'Ya' && getPostDataUppercase('kesalahan_undangundang_nyatakan')): ?>
                        <div class="data-row">
                            <span class="data-label">Nyatakan:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('kesalahan_undangundang_nyatakan')); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="data-row">
                            <span class="data-label">Muflis:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('muflis')); ?></span>
                        </div>
                        <?php if (getPostDataUppercase('muflis') === 'Ya' && getPostDataUppercase('muflis_nyatakan')): ?>
                        <div class="data-row">
                            <span class="data-label">Nyatakan:</span>
                            <span class="data-value"><?php echo htmlspecialchars(getPostDataUppercase('muflis_nyatakan')); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- References -->
                    <div class="mt-6">
                        <h3 class="font-semibold text-lg mb-3">Rujukan</h3>
                        <?php
                        // Get references from the form data (populated from database)
                        $formatted_rujukan = [];
                        if (!empty(getPostData('rujukan_1_nama'))) {
                            $formatted_rujukan[] = [
                                'nama' => mb_strtoupper(getPostData('rujukan_1_nama')),
                                'telefon' => getPostData('rujukan_1_telefon'),
                                'tempoh_kenal' => getPostData('rujukan_1_tempoh') . ' TAHUN'
                            ];
                        }
                        if (!empty(getPostData('rujukan_2_nama'))) {
                            $formatted_rujukan[] = [
                                'nama' => mb_strtoupper(getPostData('rujukan_2_nama')),
                                'telefon' => getPostData('rujukan_2_telefon'),
                                'tempoh_kenal' => getPostData('rujukan_2_tempoh') . ' TAHUN'
                            ];
                        }

                        if (!empty($formatted_rujukan)):
                            ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Nama</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">No. Telefon</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Tempoh Kenal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($formatted_rujukan as $rujukan): ?>
                                            <tr>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($rujukan['nama']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($rujukan['telefon']); ?></td>
                                                <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($rujukan['tempoh_kenal']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                            <p class="text-gray-500 italic">Tiada maklumat rujukan dimasukkan.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Section 11: Pengakuan -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="section-title">
                    11. PENGAKUAN
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                            <p class="text-gray-800">
                                Saya mengaku bahawa segala maklumat yang diberikan di atas adalah benar dan lengkap. 
                                Saya faham bahawa sekiranya terdapat maklumat yang tidak benar atau palsu, permohonan 
                                saya boleh dibatalkan atau tawaran yang telah dibuat akan ditarik balik atau perkhidmatan 
                                saya akan ditamatkan pada bila-bila masa tanpa notis.
                            </p>
                            <div class="mt-4 flex items-center">
                                <div class="data-row">
                                    <span class="data-label">Persetujuan:</span>
                                    <span class="data-value font-semibold"><?php echo getPostDataUppercase('pengistiharan') ? 'YA, SAYA BERSETUJU' : 'TIDAK BERSETUJU'; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (getPostDataUppercase('pengistiharan')): ?>
                        <div class="mt-4">
                            <div class="data-row">
                                <span class="data-label">Tarikh:</span>
                                <span class="data-value"><?php echo date('d/m/Y'); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 no-print">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button onclick="goBack()" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-8 rounded-lg transition duration-200">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Kembali & Edit
                        </button>
                        <?php if (isset($application_id) && $application_id): ?>
                        <form action="finalize-application.php" method="POST" style="display:inline;">
                            <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg transition duration-200">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Hantar Permohonan
                            </button>
                        </form>
                        <?php else: ?>
                        <button onclick="submitApplication()" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg transition duration-200">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Hantar Permohonan
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Hidden Form for Final Submission -->
            <form action="finalize-application.php" method="post" id="finalSubmissionForm" class="no-print" enctype="multipart/form-data">
                <?php
                // Function to recursively create hidden inputs for nested arrays
                function createHiddenInputs($data, $namePrefix = '') {
                    foreach ($data as $key => $value) {
                        $inputName = $namePrefix !== '' ? $namePrefix . '[' . $key . ']' : $key;
                        
                        if (is_array($value)) {
                            createHiddenInputs($value, $inputName);
                        } else {
                            echo '<input type="hidden" name="' . htmlspecialchars($inputName) . '" value="' . htmlspecialchars($value) . '">'."\n";
                        }
                    }
                }
                
                // Process all POST data
                createHiddenInputs($_POST);
                
                // Handle file uploads by creating hidden inputs with file data
                if (!empty($_FILES)) {
                    foreach ($_FILES as $fileKey => $fileData) {
                        if (is_array($fileData) && isset($fileData['error'])) {
                            // Single file upload
                            if ($fileData['error'] === UPLOAD_ERR_OK && !empty($fileData['tmp_name'])) {
                                // Read file content and encode as base64
                                $fileContent = file_get_contents($fileData['tmp_name']);
                                if ($fileContent !== false) {
                                    echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_content" value="' . htmlspecialchars(base64_encode($fileContent)) . '">'."\n";
                                    echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_name" value="' . htmlspecialchars($fileData['name']) . '">'."\n";
                                    echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_type" value="' . htmlspecialchars($fileData['type']) . '">'."\n";
                                    $file_ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
                                    echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_ext" value="' . htmlspecialchars($file_ext) . '">'."\n";
                                }
                            }
                        } elseif (is_array($fileData) && isset($fileData['name']) && is_array($fileData['name'])) {
                            // Multiple file upload (like education certificates)
                            foreach ($fileData['name'] as $index => $subData) {
                                if (is_array($subData)) {
                                    // Handle nested arrays like persekolahan[0][sijil]
                                    foreach ($subData as $fieldName => $fileName) {
                                        if ($fileData['error'][$index][$fieldName] === UPLOAD_ERR_OK && !empty($fileData['tmp_name'][$index][$fieldName])) {
                                            // Read file content and encode as base64
                                            $fileContent = file_get_contents($fileData['tmp_name'][$index][$fieldName]);
                                            if ($fileContent !== false) {
                                                echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_' . $index . '_' . $fieldName . '_content" value="' . htmlspecialchars(base64_encode($fileContent)) . '">'."\n";
                                                echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_' . $index . '_' . $fieldName . '_name" value="' . htmlspecialchars($fileName) . '">'."\n";
                                                echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_' . $index . '_' . $fieldName . '_type" value="' . htmlspecialchars($fileData['type'][$index][$fieldName]) . '">'."\n";
                                                $file_ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                                echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_' . $index . '_' . $fieldName . '_ext" value="' . htmlspecialchars($file_ext) . '">'."\n";
                                            }
                                        }
                                    }
                                } else {
                                    // Handle simple array structure
                                    if ($fileData['error'][$index] === UPLOAD_ERR_OK && !empty($fileData['tmp_name'][$index])) {
                                        // Read file content and encode as base64
                                        $fileContent = file_get_contents($fileData['tmp_name'][$index]);
                                        if ($fileContent !== false) {
                                            echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_' . $index . '_content" value="' . htmlspecialchars(base64_encode($fileContent)) . '">'."\n";
                                            echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_' . $index . '_name" value="' . htmlspecialchars($fileName) . '">'."\n";
                                            echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_' . $index . '_type" value="' . htmlspecialchars($fileData['type'][$index]) . '">'."\n";
                                            $file_ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                            echo '<input type="hidden" name="' . htmlspecialchars($fileKey) . '_' . $index . '_ext" value="' . htmlspecialchars($file_ext) . '">'."\n";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Add debugging information
                echo '<!-- Form data debug -->'."\n";
                echo '<!-- Total POST keys: ' . count($_POST) . ' -->'."\n";
                echo '<!-- POST keys: ' . implode(', ', array_keys($_POST)) . ' -->'."\n";
                echo '<!-- Total FILES keys: ' . count($_FILES) . ' -->'."\n";
                echo '<!-- FILES keys: ' . implode(', ', array_keys($_FILES)) . ' -->'."\n";
                ?>
            </form>

        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>
    

    <script>
    // Function to open image modal
    function openImageModal(imageSrc, caption) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        const modalCaption = document.getElementById('modalCaption');
        
        if (modal && modalImg) {
            modal.style.display = 'block';
            modalImg.src = imageSrc;
            if (modalCaption && caption) {
                modalCaption.textContent = caption;
            }
        }
    }
    
    // Function to close image modal
    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Get the modal
        var modal = document.getElementById('imageModal');
        var modalImg = document.getElementById('modalImage');
        var captionText = document.getElementById('modalCaption');
        var closeBtn = document.getElementsByClassName('close')[0];
        
        // Get all sijil preview links
        var sijilPreviews = document.getElementsByClassName('sijil-preview');
        
        // Add click event to all sijil preview links
        for (var i = 0; i < sijilPreviews.length; i++) {
            sijilPreviews[i].addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = "block";
                modalImg.src = this.getAttribute('data-file');
                captionText.innerHTML = this.getAttribute('data-filename');
                
                // Log for debugging
                console.log('Opening modal for file:', this.getAttribute('data-file'));
                console.log('File name:', this.getAttribute('data-filename'));
            });
        
        // Modal functions are now defined globally above
        }
        
        // Close modal when clicking the × button
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
        
        // Close modal when clicking outside the image
        modal.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }
        
        // Close modal when pressing Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape" && modal.style.display === "block") {
                modal.style.display = "none";
            }
        });
    });
    </script>
    
    <script>
        function goBack() {
            if (confirm('Adakah anda pasti ingin kembali untuk mengedit? Semua data yang telah diisi akan dikekalkan.')) {
                // Store form data in sessionStorage to restore it
                const formData = {};
                <?php foreach ($_POST as $key => $value): ?>
                    <?php if (is_array($value)): ?>
                        formData['<?php echo $key; ?>'] = <?php echo json_encode($value); ?>;
                    <?php else: ?>
                        formData['<?php echo $key; ?>'] = '<?php echo addslashes($value); ?>';
                    <?php endif; ?>
                <?php endforeach; ?>
                
                // Store special flag to indicate we're coming back from preview
                formData['_from_preview'] = 'true';
                
                // Store marital status explicitly to ensure it's properly restored
                <?php if (isset($_POST['taraf_perkahwinan'])): ?>
                    formData['_explicit_taraf_perkahwinan'] = '<?php echo addslashes($_POST['taraf_perkahwinan']); ?>';
                <?php endif; ?>
                
                sessionStorage.setItem('applicationFormData', JSON.stringify(formData));
                history.back();
            }
        }
        
        function submitApplication() {
            try {
                console.log('🚀 Starting application submission...');
                
                // Show loading overlay
                const loadingOverlay = document.createElement('div');
                loadingOverlay.id = 'loadingOverlay';
                loadingOverlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.9);z-index:9999;display:flex;flex-direction:column;justify-content:center;align-items:center;';
                loadingOverlay.innerHTML = `
                    <div style="text-align:center;padding:20px;background:white;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                        <div style="margin-bottom:20px;">
                            <svg class="animate-spin" style="width:50px;height:50px;" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="#edf2f7" stroke-width="4" fill="none"></circle>
                                <path fill="none" stroke="#4299e1" stroke-width="4" d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                            </svg>
                        </div>
                        <h2 style="font-size:18px;font-weight:600;margin-bottom:10px;">Menghantar Permohonan</h2>
                        <p style="color:#4a5568;">Sila tunggu sebentar. Jangan tutup halaman ini.</p>
                    </div>
                `;
                document.body.appendChild(loadingOverlay);
                
                // Get the final submission form
                const finalForm = document.getElementById('finalSubmissionForm');
                
                if (!finalForm) {
                    console.error('❌ Final submission form not found!');
                    alert('Ralat: Borang tidak dijumpai. Sila cuba sebentar lagi.');
                    document.body.removeChild(loadingOverlay);
                    return;
                }
                
                // Set the correct attributes on the final form
                finalForm.action = 'process-application.php';
                finalForm.method = 'POST';
                finalForm.enctype = 'multipart/form-data';
                
                console.log('✅ Form found, preparing submission...');
                
                // Verify family information is included
                const familyInputs = document.querySelectorAll('input[name^="ahli_keluarga"]');
                console.log('Family inputs found:', familyInputs.length);
                
                // Add a debug timestamp to track form submission
                const debugInput = document.createElement('input');
                debugInput.type = 'hidden';
                debugInput.name = 'submission_timestamp';
                debugInput.value = new Date().toISOString();
                finalForm.appendChild(debugInput);
                
                // Add a submission token to prevent duplicate submissions
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'submission_token';
                tokenInput.value = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
                finalForm.appendChild(tokenInput);
                
                // Add a flag to indicate this is a final submission
                const finalSubmitFlag = document.createElement('input');
                finalSubmitFlag.type = 'hidden';
                finalSubmitFlag.name = 'final_submission';
                finalSubmitFlag.value = 'true';
                finalForm.appendChild(finalSubmitFlag);
                
                // Add a redirect flag to ensure proper redirection
                const redirectFlag = document.createElement('input');
                redirectFlag.type = 'hidden';
                redirectFlag.name = 'redirect_to_thank_you';
                redirectFlag.value = 'true';
                finalForm.appendChild(redirectFlag);
                
                // Show loading state
                const submitButton = document.querySelector('button[onclick="submitApplication()"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<svg class="w-5 h-5 inline mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Menghantar...';
                }
                
                // Disable all other buttons to prevent multiple submissions
                const allButtons = document.querySelectorAll('button:not([disabled])');
                allButtons.forEach(button => {
                    if (button !== submitButton) {
                        button.disabled = true;
                    }
                });
                
                console.log('📤 Submitting form to:', finalForm.action);
                
                // Create a backup of form data in localStorage
                try {
                    const formData = new FormData(finalForm);
                    const formDataObject = {};
                    formData.forEach((value, key) => {
                        // Skip large file data
                        if (!key.includes('_content')) {
                            formDataObject[key] = value;
                        }
                    });
                    localStorage.setItem('application_backup', JSON.stringify(formDataObject));
                    console.log('📦 Form data backup created in localStorage');
                } catch (e) {
                    console.warn('Could not backup form data:', e);
                }
                
                // Submit the form after a short delay to ensure all DOM updates are complete
                setTimeout(() => {
                    finalForm.submit();
                    console.log('✅ Form submitted successfully');
                    
                    // Set a timeout to check if redirect happened
                    setTimeout(() => {
                        console.log('Checking if redirect occurred...');
                        // If we're still here after 5 seconds, try manual redirect
                        const redirectUrl = 'application-thank-you.php';
                        console.log('⚠️ Redirect timeout reached, manually redirecting to:', redirectUrl);
                        window.location.href = redirectUrl;
                    }, 5000);
                    
                }, 500);
            } catch (error) {
                console.error('❌ Form submission error:', error);
                alert('Ralat menghantar borang: ' + error.message);
                
                // Remove loading overlay if it exists
                const loadingOverlay = document.getElementById('loadingOverlay');
                if (loadingOverlay) {
                    document.body.removeChild(loadingOverlay);
                }
                
                // Restore button
                const submitButton = document.querySelector('button[onclick="submitApplication()"]');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Hantar Permohonan';
                }
                
                // Add client debug info
                const finalForm = document.getElementById('finalSubmissionForm');
                if (finalForm) {
                    const debugInput = document.createElement('input');
                    debugInput.type = 'hidden';
                    debugInput.name = 'client_debug';
                    debugInput.value = JSON.stringify({
                        userAgent: navigator.userAgent,
                        referrer: document.referrer,
                        url: window.location.href,
                        timestamp: new Date().getTime(),
                        error: error.message
                    });
                    finalForm.appendChild(debugInput);
                }
            }
        }
        
        // Add page load debugging
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📄 Preview page loaded');
            console.log('📊 POST data keys: <?php echo implode(", ", array_keys($_POST)); ?>');
            console.log('📈 POST data count: <?php echo count($_POST); ?>');
            console.log('📁 FILES data keys: <?php echo implode(", ", array_keys($_FILES)); ?>');
            console.log('📁 FILES data count: <?php echo count($_FILES); ?>');
            
            const form = document.getElementById('finalSubmissionForm');
            if (form) {
                const inputs = form.querySelectorAll('input[type="hidden"]');
                console.log('🔍 Form ready with', inputs.length, 'hidden fields');
                
                // Log some key hidden inputs for debugging
                const fileInputs = form.querySelectorAll('input[name*="_content"]');
                console.log('📄 File content inputs found:', fileInputs.length);
                fileInputs.forEach((input, index) => {
                    console.log(`📄 File ${index + 1}:`, input.name, 'Length:', input.value.length);
                });
            } else {
                console.error('❌ Final submission form not found on page load!');
            }
        });
    </script>
    
    <!-- Image Modal -->
    <div id="imageModal" style="display:none;position:fixed;z-index:10000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.9);">
        <span onclick="closeImageModal()" style="position:absolute;top:15px;right:35px;color:#f1f1f1;font-size:40px;font-weight:bold;cursor:pointer;">&times;</span>
        <img id="modalImage" style="margin:auto;display:block;width:80%;max-width:700px;margin-top:5%;">
        <div id="modalCaption" style="position:absolute;bottom:0;width:100%;text-align:center;color:white;padding:10px;background-color:rgba(0,0,0,0.7);"></div>
    </div>
</body>
</html>
<?php
// Clean up output buffer if we reach here
if (ob_get_level()) {
    ob_end_flush();
}
?>
