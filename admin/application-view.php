<?php
session_start();
// Use centralized bootstrap (DB, logging, error handling)
require_once '../includes/bootstrap.php';
require_once 'auth.php';
require_once '../modules/preview/DataFetcher.php';

// Get database connection from main config
$config = require '../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID permohonan tidak diberikan.";
    header("Location: applications-list.php");
    exit;
}

$application_id = (int)$_GET['id'];

// Get application details with job information
$sql = "SELECT aa.*, jp.job_title, jp.kod_gred, jp.job_code AS posting_job_id, st.code AS status_code, st.name AS status_name, st.description AS status_description 
        FROM application_application_main aa 
        LEFT JOIN job_postings jp ON aa.job_id = jp.id 
        LEFT JOIN application_statuses st ON st.code = aa.submission_status 
        WHERE aa.id = ?";

// Check if PDO connection exists before preparing statement
if (!$pdo) {
    throw new PDOException("Database connection failed");
}
$stmt = $pdo->prepare($sql);
$stmt->execute([$application_id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    $_SESSION['error'] = "Permohonan tidak ditemui.";
    header("Location: applications-list.php");
    exit;
}

function fieldValue(array $data, array $candidates)
{
    foreach ($candidates as $key) {
        if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
            return $data[$key];
        }
    }

    return null;
}

function resolveColumn(PDO $pdo, string $table, array $candidates): ?string
{
    static $cache = [];

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return null;
    }

    if (!isset($cache[$table])) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
            $cache[$table] = $stmt ? array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field') : [];
        } catch (Throwable $e) {
            $cache[$table] = [];
        }
    }

    foreach ($candidates as $candidate) {
        if (in_array($candidate, $cache[$table], true)) {
            return $candidate;
        }
    }

    return null;
}

// Log admin action
log_admin_info('Viewed job application details', [
    'action' => 'VIEW_APPLICATION',
    'application_id' => $application_id,
    'applicant_name' => $application['nama_penuh'] ?? null,
    'job_title' => $application['job_title'] ?? null
]);

$dataFetcher = new PreviewDataFetcher($pdo, $application);
$allData = $dataFetcher->getAllData();
if (!empty($allData['health'])) { $application = array_merge($application, (array)$allData['health']); }

try { 
    $st_stmt = $pdo->query("SELECT code, name, sort_order FROM application_statuses WHERE is_active = 1 ORDER BY sort_order, id");
    $status_options = $st_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $status_options = []; }

$history_key = resolveColumn($pdo, 'application_status_history', ['application_id','application_reference']);
$history_order = resolveColumn($pdo, 'application_status_history', ['changed_at','updated_at','created_at','timestamp']);
$status_history = [];
if ($history_key) {
    try {
        $val = ($history_key === 'application_id') ? $application_id : ($application['application_reference'] ?? '');
        $order_col = $history_order ?: 'id';
        $hist_sql = "SELECT h.*, s.name AS status_name, s.code AS status_code FROM application_status_history h LEFT JOIN application_statuses s ON s.id = h.status_id WHERE h." . $history_key . " = ? ORDER BY h." . $order_col . " DESC";
        $hist_stmt = $pdo->prepare($hist_sql);
        $hist_stmt->execute([$val]);
        $status_history = $hist_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) { $status_history = []; }
}

// Derive current status from timestamps for consistency with list page
$current_status_code = strtoupper($application['status_code'] ?? ($application['submission_status'] ?? ''));
if ($current_status_code === '') {
    if (!empty($application['approved_at'])) { $current_status_code = 'APPROVED'; }
    elseif (!empty($application['reviewed_at'])) { $current_status_code = 'REVIEWED'; }
    elseif ((int)($application['submission_locked'] ?? 0) === 1) { $current_status_code = 'SUBMITTED'; }
    else { $current_status_code = 'DRAFT'; }
}
$current_status_name = $application['status_name'] ?? $current_status_code;

// Related data using unified fetcher
$language_skills = $allData['language_skills'] ?? [];
$computer_skills = $allData['computer_skills'] ?? [];
$profColumn = resolveColumn($pdo, 'application_professional_bodies', ['salinan_sijil_path', 'salinan_sijil_filename', 'salinan_sijil']);
$professional_bodies = $allData['professional_bodies'] ?? [];
$extColumn = resolveColumn($pdo, 'application_extracurricular', ['salinan_sijil_path', 'salinan_sijil_filename', 'salinan_sijil']);
$extracurricular = $allData['extracurricular'] ?? [];
$spmColumn = resolveColumn($pdo, 'application_spm_results', ['salinan_sijil_path', 'salinan_sijil_filename', 'salinan_sijil']);
$spm_results = $allData['spm_results'] ?? [];
foreach ($spm_results as &$spm) {
    $sijil = $spm['salinan_sijil_filename'] ?? ($spm['salinan_sijil'] ?? null);
    if (!empty($sijil)) { $spm['salinan_sijil'] = $sijil; }
    $list = [];
    if (!empty($spm['subjek_lain'])) {
        if (is_array($spm['subjek_lain'])) {
            $list = $spm['subjek_lain'];
        } else {
            $decoded = json_decode((string)$spm['subjek_lain'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $grades = json_decode((string)($spm['gred_subjek_lain'] ?? ''), true);
                if (!is_array($grades)) { $grades = preg_split('/\s*,\s*/', (string)($spm['gred_subjek_lain'] ?? '')); }
                foreach ($decoded as $i => $s) { $list[] = ['subjek' => (string)$s, 'gred' => is_array($grades) ? ($grades[$i] ?? '') : '']; }
            } else {
                $subs = preg_split('/\s*,\s*/', (string)$spm['subjek_lain']);
                $grades = preg_split('/\s*,\s*/', (string)($spm['gred_subjek_lain'] ?? ''));
                foreach ($subs as $i => $s) { if ($s !== '') { $list[] = ['subjek' => (string)$s, 'gred' => $grades[$i] ?? '']; } }
            }
        }
    }
    $spm['subjek_lain'] = $list;
}
unset($spm);
$spm_additional = $allData['spm_additional_subjects'] ?? [];
$map = [];
foreach ($spm_results as $idx => $row) { $k = (string)($row['tahun'] ?? '') . '_' . (string)($row['angka_giliran'] ?? ''); if (!isset($spm_results[$idx]['subjek_lain']) || !is_array($spm_results[$idx]['subjek_lain'])) { $spm_results[$idx]['subjek_lain'] = []; } $map[$k] =& $spm_results[$idx]['subjek_lain']; }
foreach ($spm_additional as $a) { $k = (string)($a['tahun'] ?? '') . '_' . (string)($a['angka_giliran'] ?? ''); $item = ['subjek' => (string)($a['subjek'] ?? ''), 'gred' => (string)($a['gred'] ?? '')]; if (!empty($item['subjek'])) { if (!isset($map[$k])) { $map[$k] = []; } $map[$k][] = $item; } }
foreach ($spm_results as &$row) {
    if (!empty($row['subjek_lain']) && is_array($row['subjek_lain'])) {
        $seen = [];
        $dedup = [];
        foreach ($row['subjek_lain'] as $it) {
            $sraw = trim((string)($it['subjek'] ?? ''));
            $graw = trim((string)($it['gred'] ?? ''));
            if ($sraw === '') { continue; }
            $key = strtoupper($sraw) . '|' . strtoupper($graw);
            if (!isset($seen[$key])) { $seen[$key] = true; $dedup[] = ['subjek' => $sraw, 'gred' => $graw]; }
        }
        $row['subjek_lain'] = $dedup;
    }
}
unset($row);
$education_db = $allData['education'] ?? [];
$work_experience = $allData['work_experience'] ?? [];
foreach ($work_experience as &$w) { if (isset($w['gaji']) && $w['gaji'] !== '') { $num = is_numeric($w['gaji']) ? (float)$w['gaji'] : $w['gaji']; if (is_numeric($num)) { $w['gaji'] = 'RM ' . number_format((float)$num, 2); } else { $w['gaji'] = (stripos($w['gaji'], 'RM') === 0) ? $w['gaji'] : ('RM ' . $w['gaji']); } } }
unset($w);
$rujukan_db = $allData['references'] ?? [];

// Build job criteria display for top section
$job_req_display = '';
$suitability_score_top = null;
try {
    $jr_stmt = $pdo->prepare('SELECT job_requirements FROM job_postings WHERE id = ?');
    $jr_stmt->execute([ (int)($application['job_id'] ?? 0) ]);
    $req_raw_top = (string)($jr_stmt->fetchColumn() ?: '');
} catch (Throwable $e) { $req_raw_top = ''; }
if ($req_raw_top === '' && !empty($application['posting_job_id'])) {
    try {
        $jr2 = $pdo->prepare('SELECT job_requirements FROM job_postings WHERE job_code = ?');
        $jr2->execute([ (string)$application['posting_job_id'] ]);
        $req_raw_top = (string)($jr2->fetchColumn() ?: '');
    } catch (Throwable $e) {}
}
if ($req_raw_top !== '') {
    $decoded_top = json_decode($req_raw_top, true);
    if (is_array($decoded_top)) {
        $chips = [];
        $criteria_total = 0;
        $criteria_met = 0;
        $lic = $decoded_top['license'] ?? [];
        if (is_string($lic) && strlen($lic) > 0) { $lic = [$lic]; }
        if (is_array($lic) && count($lic) > 0) {
            foreach ($lic as $l) { $chips[] = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">Lesen: ' . htmlspecialchars((string)$l, ENT_QUOTES, 'UTF-8') . '</span>'; }
            $licenses_raw = $application['lesen_memandu_set'] ?? ($application['lesen_memandu'] ?? ($application['kelas_lesen'] ?? ''));
            $app_licenses = [];
            if (is_array($licenses_raw)) { $app_licenses = $licenses_raw; }
            elseif (is_string($licenses_raw)) {
                $raw = trim($licenses_raw);
                if ($raw !== '') {
                    $dec = json_decode($raw, true);
                    if (is_array($dec)) { $app_licenses = $dec; }
                    else {
                        $parts = preg_split('/[\s,;\\\/]+/', $raw);
                        $app_licenses = array_values(array_filter(array_map(function($v){ return trim((string)$v); }, (array)$parts), function($v){ return $v !== ''; }));
                    }
                }
            }
            $app_licenses = array_map(function($v){ return strtoupper((string)$v); }, (array)$app_licenses);
            $required_licenses = array_values(array_filter(array_map(function($v){ return strtoupper((string)$v); }, (array)$lic), function($v){ return $v !== ''; }));
            $required_licenses = array_unique($required_licenses);
            $similar_sum = 0.0;
            foreach ($required_licenses as $req_lic) {
                $best = 0.0;
                foreach ($app_licenses as $al) {
                    if ($req_lic === $al) { $best = 1.0; break; }
                    if (strpos($al, $req_lic) !== false || strpos($req_lic, $al) !== false) { $best = max($best, 0.9); }
                    $pct = 0.0; similar_text($req_lic, $al, $pct); $best = max($best, ($pct/100.0));
                }
                $similar_sum += $best;
            }
            $criteria_total += count($required_licenses);
            $criteria_met += $similar_sum;
        }
        $map = [ 'gender' => 'Jantina', 'nationality' => 'Warganegara', 'bangsa' => 'Bangsa', 'birth_state' => 'Negeri Kelahiran', 'min_education' => 'Min Pendidikan' ];
        foreach ($map as $k => $label) {
            $val = $decoded_top[$k] ?? '';
            if (is_string($val)) { $val = trim($val); }
            if (!empty($val)) { $chips[] = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 border border-indigo-200">' . $label . ': ' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '</span>'; }
        }
        $msy = $decoded_top['min_selangor_years'] ?? '';
        if ($msy !== '' && $msy !== null) { $chips[] = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">Bermastautin Selangor ≥ ' . htmlspecialchars((string)$msy, ENT_QUOTES, 'UTF-8') . ' tahun</span>'; $criteria_total++; $years = (int)($application['tempoh_bermastautin_selangor'] ?? 0); if ($years >= (int)$msy) { $criteria_met++; } }
        if (!empty($decoded_top['gender'])) { $criteria_total++; $match = strtoupper(trim($application['jantina'] ?? '')) === strtoupper(trim($decoded_top['gender'])); if ($match) { $criteria_met++; } }
        if (!empty($decoded_top['nationality'])) { $criteria_total++; $match = strtoupper(trim($application['warganegara'] ?? '')) === strtoupper(trim($decoded_top['nationality'])); if ($match) { $criteria_met++; } }
        if (!empty($decoded_top['bangsa'])) { $criteria_total++; $match = strtoupper(trim($application['bangsa'] ?? '')) === strtoupper(trim($decoded_top['bangsa'])); if ($match) { $criteria_met++; } }
        if (!empty($decoded_top['birth_state'])) { $criteria_total++; $match = strtoupper(trim($application['negeri_kelahiran'] ?? '')) === strtoupper(trim($decoded_top['birth_state'])); if ($match) { $criteria_met++; } }
        if (!empty($decoded_top['min_education'])) {
            $criteria_total++;
            try {
                $edu_stmt = $pdo->prepare("SELECT kelayakan FROM application_education WHERE application_reference = ?");
                $edu_stmt->execute([$application['application_reference']]);
                $edu_rows = $edu_stmt->fetchAll(PDO::FETCH_COLUMN);
                $edu_ranks = [ 'SPM' => 1, 'STPM' => 2, 'SIJIL' => 2, 'DIPLOMA' => 3, 'IJAZAH' => 4, 'IJAZAH SARJANA MUDA' => 4, 'MASTER' => 5, 'SARJANA' => 5, 'PHD' => 5 ];
                $req_rank = $edu_ranks[strtoupper($decoded_top['min_education'])] ?? 0;
                $max_app_rank = 0;
                foreach ($edu_rows as $edu) {
                    $normalized_edu = strtoupper(trim($edu));
                    foreach ($edu_ranks as $key => $rank) { if (strpos($normalized_edu, $key) !== false) { $max_app_rank = max($max_app_rank, $rank); } }
                }
                if ($max_app_rank >= $req_rank) { $criteria_met++; }
            } catch (Throwable $e) {}
        }
        if ($criteria_total > 0) { $suitability_score_top = (int)round(($criteria_met / $criteria_total) * 100); }
        if (count($chips) > 0) { $job_req_display = implode('', $chips); }
        else { $job_req_display = '<span class="text-gray-500 italic">Tiada kriteria khusus</span>'; }
    } else {
        $job_req_display = strlen(trim($req_raw_top)) > 0 ? htmlspecialchars($req_raw_top, ENT_QUOTES, 'UTF-8') : '<span class="text-gray-500 italic">Tiada kriteria khusus</span>';
    }
}

// Handle status update if form submitted (timestamp-based)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrf)) {
        $_SESSION['error'] = "CSRF tidak sah.";
        header("Location: application-view.php?id=" . $application_id . "#status");
        exit;
    }
    $new_code = strtoupper(trim((string)($_POST['status'] ?? '')));
    $status_notes = trim((string)($_POST['status_notes'] ?? ''));
    if ($status_notes === '') {
        $_SESSION['error'] = "Sila masukkan nota status.";
        header("Location: application-view.php?id=" . $application_id . "#status");
        exit;
    }
    try {
        $subCol = resolveColumn($pdo, 'application_statuses', ['email_template_subject','email_subject']);
        $bodyCol = resolveColumn($pdo, 'application_statuses', ['email_template_body','email_body']);
        $sql = 'SELECT id, code, name' . ($subCol ? ', ' . $subCol . ' AS email_subject' : '') . ($bodyCol ? ', ' . $bodyCol . ' AS email_body' : '') . ' FROM application_statuses WHERE code = ? AND is_active = 1 LIMIT 1';
        $row = $pdo->prepare($sql);
        $row->execute([$new_code]);
        $st = $row->fetch(PDO::FETCH_ASSOC);
        if (!$st) { $_SESSION['error'] = "Status tidak sah."; header("Location: application-view.php?id=" . $application_id . "#status"); exit; }
        $pdo->prepare("UPDATE application_application_main SET submission_status = ?, status_notes = ? WHERE id = ?")
            ->execute([$st['code'], $status_notes, $application_id]);

        $has_app_id = false; $has_app_ref = false;
        try {
            $c1 = $pdo->query("SHOW COLUMNS FROM application_status_history LIKE 'application_id'")->fetch(PDO::FETCH_ASSOC);
            $c2 = $pdo->query("SHOW COLUMNS FROM application_status_history LIKE 'application_reference'")->fetch(PDO::FETCH_ASSOC);
            $has_app_id = (bool)$c1; $has_app_ref = (bool)$c2;
        } catch (Throwable $e) {}
        if ($has_app_id) {
            $pdo->prepare("INSERT INTO application_status_history (application_id, status_id, status_description, changed_by, notes) VALUES (?, ?, ?, ?, ?)")
                ->execute([$application_id, $st['id'], $st['name'], $_SESSION['admin_id'] ?? null, $status_notes]);
        } elseif ($has_app_ref) {
            $pdo->prepare("INSERT INTO application_status_history (application_reference, status_id, status_description, changed_by, notes) VALUES (?, ?, ?, ?, ?)")
                ->execute([$application['application_reference'], $st['id'], $st['name'], $_SESSION['admin_id'] ?? null, $status_notes]);
        }
        log_admin_info('Updated job application status', [
            'action' => 'UPDATE_STATUS',
            'application_id' => $application_id,
            'old_status' => $current_status_code,
            'new_status' => $new_code,
            'notes' => $status_notes
        ]);
        $doSend = (!empty($config['status_email_enabled']) && isset($_POST['send_status_email']) && $_POST['send_status_email'] == '1');
        if ($doSend) {
            require_once __DIR__ . '/../includes/StatusEmailService.php';
            $svc = new StatusEmailService($config, $pdo);
            $svc->send($application, $st, $status_notes);
        }
        $_SESSION['success'] = "Status permohonan telah dikemaskini.";
        header("Location: application-view.php?id=" . $application_id . "#status");
        exit;
    } catch (Throwable $e) {
        log_admin_error('Database error updating status', ['error' => $e->getMessage(), 'application_id' => $application_id, 'target_status' => $new_code]);
        $_SESSION['error'] = "Ralat mengemaskini status: " . $e->getMessage();
    }
}


// Helper functions from preview-application.php
function h($v) { 
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); 
}

// Normalize yes/no values where DB may store 1/2 or YA/TIDAK
function normalizeYesNoValue($value) {
    if ($value === null) return '';
    if (is_bool($value)) return $value ? 'YA' : 'TIDAK';
    if (is_numeric($value)) return ((int)$value) === 1 ? 'YA' : 'TIDAK';
    $v = strtoupper(trim((string)$value));
    if ($v === '') return '';
    return in_array($v, ['YA','Y','YES','TRUE','1'], true) ? 'YA' : 'TIDAK';
}

function isYes($value) {
    return normalizeYesNoValue($value) === 'YA';
}

function safeFetchAll(PDO $pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        // Log and continue
        if (function_exists('log_admin_error')) {
            log_admin_error('Query failed in application-view', ['sql' => $sql, 'params' => $params, 'error' => $e->getMessage()]);
        }
        return [];
    }
}

function buildAppFileUrl($filename, $app) {
    if (!$filename) return '';
    if (preg_match('/^https?:\/\//i', $filename)) return $filename;
    if (strpos($filename, 'uploads/applications/') === 0) { return '/' . ltrim($filename, '/'); }
    $year = date('Y');
    $applicationReference = $app['application_reference'] ?? '';
    $file = basename((string)$filename);
    return '/uploads/applications/' . $year . '/' . rawurlencode((string)$applicationReference) . '/' . rawurlencode($file);
}

// Normalize a file href for admin pages: prefer absolute http(s); otherwise
// generate a path relative to admin/ that points to the site root
function adminHrefFromRaw($raw, $app) {
    if (!$raw) return '';
    $path = (string)$raw;
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    // If already under uploads, ensure it is relative to admin root
    if (strpos($path, '/uploads/') === 0 || strpos($path, 'uploads/') === 0) {
        return '../' . ltrim($path, '/');
    }
    // Otherwise build the standardized application path and make it admin-relative
    $built = buildAppFileUrl($path, $app); // returns /uploads/...
    return '../' . ltrim($built, '/');
}

function formatDateDMY($v) {
    $ts = strtotime((string)$v);
    return $ts ? date('d-M-Y', $ts) : (string)$v;
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

// Helper function to display JSON data as a list or table
function displayJsonData($jsonData, $emptyMessage = "Tiada data", $displayType = 'list') {
    if (empty($jsonData)) {
        return "<p class='text-gray-500'>{$emptyMessage}</p>";
    }
    
    $data = json_decode($jsonData, true);
    if (!$data || empty($data)) {
        return "<p class='text-gray-500'>{$emptyMessage}</p>";
    }
    
    // If display type is table, use the table function
    if ($displayType === 'table') {
        return displayJsonDataAsTable($data, $emptyMessage);
    }
    
    // Otherwise use the original list display
    $output = "<ul class='list-disc pl-5'>";
    foreach ($data as $item) {
        if (is_array($item)) {
            $output .= "<li class='mb-2'>";
            foreach ($item as $key => $value) {
                $label = ucfirst(str_replace('_', ' ', $key));
                $output .= "<strong>{$label}:</strong> " . htmlspecialchars($value) . "<br>";
            }
            $output .= "</li>";
        } else {
            $output .= "<li class='mb-1'>" . htmlspecialchars($item) . "</li>";
        }
    }
    $output .= "</ul>";
    
    return $output;
}

// Helper function to display JSON data as a table
function displayJsonDataAsTable($data, $emptyMessage = "Tiada data") {
    // If data is already decoded, use it directly
    if (!is_array($data)) {
        $data = json_decode($data, true);
        if (!$data || empty($data)) {
            return "<p class='text-gray-500'>{$emptyMessage}</p>";
        }
    }
    
    // Check if data is empty
    if (empty($data)) {
        return "<p class='text-gray-500'>{$emptyMessage}</p>";
    }
    
    // Get all possible keys for table headers
    $allKeys = [];
    foreach ($data as $item) {
        if (is_array($item)) {
            foreach ($item as $key => $value) {
                if (!in_array($key, $allKeys)) {
                    $allKeys[] = $key;
                }
            }
        }
    }
    
    // If no array items found or no keys, display as simple list
    if (empty($allKeys)) {
        $output = "<ul class='list-disc pl-5'>";
        foreach ($data as $item) {
            $output .= "<li class='mb-1'>" . htmlspecialchars($item) . "</li>";
        }
        $output .= "</ul>";
        return $output;
    }
    
    // Build the table
    $output = "<div class='overflow-x-auto'>";
    $output .= "<table class='min-w-full divide-y divide-gray-200 table-fixed'>";
    
    // Table header
    $output .= "<thead class='bg-gray-50'>";
    $output .= "<tr>";
    
    foreach ($allKeys as $key) {
        $label = ucfirst(str_replace('_', ' ', $key));
        $output .= "<th scope='col' class='px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>" . htmlspecialchars($label) . "</th>";
    }
    $output .= "</tr>";
    $output .= "</thead>";
    
    // Table body
    $output .= "<tbody class='bg-white divide-y divide-gray-200'>";
    
    foreach ($data as $item) {
        if (is_array($item)) {
            $output .= "<tr class='hover:bg-gray-50'>";
            
            foreach ($allKeys as $key) {
                $value = isset($item[$key]) ? $item[$key] : '';
                $output .= "<td class='px-4 py-3 text-sm text-gray-900'>" . htmlspecialchars($value) . "</td>";
            }
            $output .= "</tr>";
        }
    }
    $output .= "</tbody>";
    $output .= "</table>";
    $output .= "</div>";
    
    return $output;
}

// Page title
$page_title = "Butiran Permohonan: " . $application['nama_penuh'];
include 'templates/header.php';
?>

<div id="mainContent" class="standard-container mx-auto transition-all">

    <style>
    @media print { .print-hidden { display: none !important; } }
    </style>

    <div class="print-hidden pdf-hidden bg-indigo-50 p-4 rounded-lg border border-indigo-100 mb-4">
        <div class="text-xs font-semibold tracking-wide text-indigo-700 mb-2 flex items-center gap-2">Skor Kriteria Jawatan<?php if ($suitability_score_top !== null) { echo ' <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">' . htmlspecialchars((string)$suitability_score_top, ENT_QUOTES, 'UTF-8') . '%</span>'; } ?></div>
        <div class="flex flex-wrap gap-2 items-start bg-white rounded p-3 border border-indigo-100">
            <?php echo $job_req_display !== '' ? $job_req_display : '<span class="text-gray-500 italic">Tiada kriteria khusus</span>'; ?>
        </div>
    </div>

    <!-- Non-sticky header: back link and status chip -->
    <div class="flex justify-between items-center mb-6 px-4 mt-4">
        <a href="applications-list.php" class="flex items-center text-blue-600 hover:text-blue-800 print-hidden pdf-hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Senarai Permohonan
        </a>
        <div class="flex items-center">
            <span class="mr-2">Status:</span>
            <?php 
            $code = strtoupper($current_status_code);
            $status_class = 'bg-gray-100 text-gray-800';
            if ($code === 'PENDING' || $code === 'SUBMITTED') { $status_class = 'bg-blue-100 text-blue-800'; }
            elseif ($code === 'SCREENING') { $status_class = 'bg-yellow-100 text-yellow-800'; }
            elseif ($code === 'TEST_INTERVIEW') { $status_class = 'bg-indigo-100 text-indigo-800'; }
            elseif ($code === 'AWAITING_RESULT') { $status_class = 'bg-blue-100 text-blue-800'; }
            elseif ($code === 'PASSED_INTERVIEW') { $status_class = 'bg-green-100 text-green-800'; }
            elseif ($code === 'OFFER_APPOINTMENT') { $status_class = 'bg-teal-100 text-teal-800'; }
            elseif ($code === 'APPOINTED' || $code === 'APPROVED') { $status_class = 'bg-green-100 text-green-800'; }
            elseif ($code === 'REVIEWED') { $status_class = 'bg-blue-100 text-blue-800'; }
            elseif ($code === 'REJECTED') { $status_class = 'bg-red-100 text-red-800'; }
            $status_text = $current_status_name;
            ?>
            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                <?php echo $status_text; ?>
            </span>
        </div>
    </div>
    
    <button id="sidebarHandle" class="md:flex fixed right-0 top-1/2 -translate-y-1/2 z-40 bg-blue-600 text-white px-3 py-8 text-2xl rounded-l-full shadow-lg hover:bg-blue-700">&laquo;</button>
    <div id="statusSidebar" class="hidden md:block fixed right-0 top-0 w-80 z-30 transform transition-transform translate-x-full bg-gray-100">
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Kemaskini Status</h3>
                    <button id="closeSidebarBtn" type="button" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">X</button>
                </div>
            </div>
            <div class="p-4">
                <form method="post" action="">
                    <div class="mb-3">
                        <label class="block text-gray-700 mb-2">Status Baru</label>
                        <select name="status" class="w-full px-3 py-2 border rounded">
                            <?php foreach (($status_options ?? []) as $opt) { $sel = ((string)$opt['code'] === (string)$current_status_code) ? 'selected' : ''; echo '<option value="' . htmlspecialchars($opt['code']) . '" ' . $sel . '>' . htmlspecialchars($opt['name']) . '</option>'; } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-gray-700 mb-2">Catatan</label>
                        <textarea name="status_notes" class="w-full px-3 py-2 border rounded" rows="3" placeholder="Nota (wajib)" required></textarea>
                        <label class="mt-2 inline-flex items-center space-x-2"><input type="checkbox" name="send_status_email" value="1" <?php echo !empty($config['status_email_enabled']) ? 'checked' : ''; ?>> <span>Hantar emel kepada pemohon</span></label>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <input type="hidden" name="csrf_token" value="<?php if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <button type="submit" name="update_status" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Simpan</button>
                        <button type="button" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700" onclick="printResume()">Cetak</button>
                        <button type="button" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700" onclick="downloadResumePdf()">PDF</button>
                    </div>
                </form>
                <div class="border-t mt-4 pt-4">
                    <div class="bg-gray-200 text-gray-800 font-semibold px-4 py-2 rounded mb-3 text-center uppercase">Rekod Status</div>
                    <div class="max-h-[50vh] overflow-y-auto space-y-3">
                        <?php foreach (($status_history ?? []) as $row): ?>
                            <?php 
                                $code = strtoupper($row['status_code'] ?? '');
                                $name = $row['status_name'] ?? $code;
                                $cls = 'bg-gray-100 text-gray-800 border-gray-200';
                                if ($code === 'PENDING' || $code === 'SUBMITTED') { $cls = 'bg-blue-100 text-blue-800 border-blue-200'; }
                                elseif ($code === 'SCREENING') { $cls = 'bg-yellow-100 text-yellow-800 border-yellow-200'; }
                                elseif ($code === 'TEST_INTERVIEW') { $cls = 'bg-indigo-100 text-indigo-800 border-indigo-200'; }
                                elseif ($code === 'AWAITING_RESULT') { $cls = 'bg-blue-100 text-blue-800 border-blue-200'; }
                                elseif ($code === 'PASSED_INTERVIEW') { $cls = 'bg-green-100 text-green-800 border-green-200'; }
                                elseif ($code === 'OFFER_APPOINTMENT') { $cls = 'bg-teal-100 text-teal-800 border-teal-200'; }
                                elseif ($code === 'APPOINTED' || $code === 'APPROVED') { $cls = 'bg-green-100 text-green-800 border-green-200'; }
                                elseif ($code === 'REVIEWED') { $cls = 'bg-blue-100 text-blue-800 border-blue-200'; }
                                elseif ($code === 'REJECTED') { $cls = 'bg-red-100 text-red-800 border-red-200'; }
                                $dt = $row['changed_at'] ?? ($row['updated_at'] ?? ($row['created_at'] ?? null));
                                $time_text = $dt ? date('h:iA', strtotime($dt)) : '';
                                $date_text = $dt ? date('d-M_Y', strtotime($dt)) : '';
                                $notes = trim((string)($row['notes'] ?? ''));
                            ?>
                            <div class="border rounded p-3">
                                <div class="mb-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border <?php echo $cls; ?>"><?php echo htmlspecialchars($name); ?></span>
                                    <div class="mt-1 text-xs text-gray-500"><?php echo htmlspecialchars(($date_text !== '' ? $date_text : '') . (($time_text !== '' && $date_text !== '') ? '  |  ' : '') . ($time_text !== '' ? $time_text : '')); ?></div>
                                </div>
                                <?php if ($notes !== ''): ?>
                                <p class="text-sm text-gray-700"><?php echo htmlspecialchars($notes); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($status_history)): ?>
                            <p class="text-sm text-gray-500">Tiada sejarah status.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    <!-- Resume Header -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-8 text-white">
            <div class="flex items-center justify-start">
                <div class="flex items-center">
                    <?php $passportPath = $application['gambar_passport_path'] ?? $application['gambar_passport'] ?? null; ?>
                    <?php if (!empty($passportPath) && file_exists('../' . $passportPath)): ?>
                        <div class="w-28 h-28 rounded-full overflow-hidden mr-6 border-2 border-white">
                            <img src="../<?php echo htmlspecialchars($passportPath); ?>" alt="Passport Photo" class="h-full w-full object-cover">
                        </div>
                    <?php else: ?>
                        <div class="w-28 h-28 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-6">
                            <span class="text-white font-bold text-3xl">
                                <?php echo strtoupper(substr($application['nama_penuh'], 0, 1)); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($application['nama_penuh']); ?></h1>
                        <p class="text-blue-100 text-lg"><?php echo htmlspecialchars($application['jawatan_dipohon'] ?? 'Pemohon Jawatan'); ?></p>
                        <p class="text-blue-100">ID Permohonan: <?php echo htmlspecialchars($application['application_reference']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Application Sections -->
    <div class="space-y-6">
        
        <!-- Section 1: Pengesahan -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-blue-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                   Pengesahan
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Jawatan yang Dipohon</label>
                        <p class="text-sm text-gray-900 font-medium">
                            <?php 
                            // Display job title from job_postings table
                            echo htmlspecialchars(($application['job_title'] ?? $application['jawatan_dipohon'] ?? 'N/A') ?? '');
                            
                            // Display job code and grade if available
                            if (!empty($application['job_id']) || !empty($application['kod_gred'])) {
                                echo ' <span class="text-gray-500">(';
                                if (!empty($application['job_id'])) {
                                    echo htmlspecialchars($application['job_id'] ?? '');
                                }
                                if (!empty($application['kod_gred'])) {
                                    echo !empty($application['job_id']) ? ' - ' : '';
                                    echo htmlspecialchars($application['kod_gred'] ?? '');
                                }
                                echo ')</span>';
                            }
                            ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Rujukan Permohonan</label>
                        <p class="text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($application['application_reference'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Rujukan Pembayaran</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['payment_reference'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Tarikh Permohonan</label>
                        <p class="text-sm text-gray-900">
                            <?php 
                            if (!empty($application['created_at'])) {
                                echo htmlspecialchars(formatDateDMY($application['created_at']));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Status Penguncian</label>
                        <p class="text-sm text-gray-900">
                            <?php if ($application['submission_locked'] == 1): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Dikunci (Dihantar)
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"></path>
                                    </svg>
                                    Tidak Dikunci (Draf)
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Pengisytiharan</label>
                        <?php $pengisytiharanVal = normalizeYesNoValue($application['pengistiharan'] ?? ''); // use DB column pengistiharan ?>
                        <p class="text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($pengisytiharanVal === 'YA') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>"><?php echo ($pengisytiharanVal === 'YA') ? 'Ya' : 'Tidak'; ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Maklumat Peribadi -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-green-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Maklumat Peribadi
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-3">
                        <label class="text-sm font-medium text-gray-500">Nama Penuh</label>
                        <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($application['nama_penuh']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Nombor IC</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['nombor_ic']); ?></p>
                        <?php $salinanIcPath = $application['salinan_ic_path'] ?? $application['salinan_ic'] ?? null; ?>
                        <?php if (!empty($salinanIcPath)): ?>
                            <button type="button" onclick="window.open('../<?php echo htmlspecialchars($salinanIcPath); ?>', '_blank')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Lihat Salinan
                            </button>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Nombor Surat Beranak</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars(($application['nombor_surat_beranak'] ?? 'N/A') ?? ''); ?></p>
                        <?php $salinanSuratPath = $application['salinan_surat_beranak_path'] ?? $application['salinan_surat_beranak'] ?? null; ?>
                        <?php if (!empty($salinanSuratPath)): ?>
                            <button type="button" onclick="window.open('../<?php echo htmlspecialchars($salinanSuratPath); ?>', '_blank')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Lihat Salinan
                            </button>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Jantina</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['jantina']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Tarikh Lahir</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars(formatDateDMY($application['tarikh_lahir'])); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Umur</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['umur']); ?> tahun</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Email</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['email']); ?></p>
                        <p class="text-sm text-gray-500"><small>Ref: <?php echo htmlspecialchars($application['application_reference']); ?></small></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Nombor Telefon</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['nombor_telefon']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Agama</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['agama']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Taraf Perkahwinan</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['taraf_perkahwinan']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Bangsa</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['bangsa']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Warganegara</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['warganegara']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Negeri Kelahiran</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars(($application['negeri_kelahiran'] ?? 'N/A') ?? ''); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Tempoh Bermastautin di Selangor</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars(($application['tempoh_bermastautin_selangor'] ?? 'N/A') ?? ''); ?> tahun</p>
                    </div>
                </div>

                <!-- Driving License Information -->
                <?php if (!empty($application['lesen_memandu']) || !empty($application['tarikh_tamat_lesen'])): ?>
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Maklumat Lesen Memandu</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Kelas Lesen</label>
                            <p class="text-sm text-gray-900">
                    <?php 
                                $lesen_memandu = $application['lesen_memandu_set'] ?? ($application['lesen_memandu'] ?? '');
                                if ($lesen_memandu) {
                                    $decoded = json_decode($lesen_memandu, true);
                                    if (is_array($decoded)) {
                                        echo htmlspecialchars(implode(', ', array_map('strtoupper', $decoded)));
                                    } else {
                                        echo htmlspecialchars(strtoupper($lesen_memandu));
                                    }
                                    
                                    // Add license document link if available
                                    $lesenPath = $application['salinan_lesen_memandu_path'] ?? $application['salinan_lesen_memandu'] ?? null;
                                    if (!empty($lesenPath)) {
                                        echo ' <button type="button" onclick="window.open(\'../' . htmlspecialchars($lesenPath) . '\', \'_blank\')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ml-2">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Lihat Salinan
                                        </button>';
                                    }
                                } else {
                                    echo 'Tiada';
                                }
                                ?>
                            </p>
            </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Tarikh Tamat Lesen</label>
                            <p class="text-sm text-gray-900">
                                <?php 
                                $tarikh_tamat = $application['tarikh_tamat_lesen'] ?? '';
                                if ($tarikh_tamat) {
                                    echo htmlspecialchars(formatDateDMY($tarikh_tamat));
                                } else {
                                    echo 'Tiada';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Spouse Information (if married) -->
                <?php if (strtoupper($application['taraf_perkahwinan'] ?? '') === 'BERKAHWIN' && !empty($application['nama_pasangan'])): ?>
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Maklumat Pasangan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Nama Pasangan</label>
                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars(($application['nama_pasangan'] ?? 'N/A') ?? ''); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Telefon Pasangan</label>
                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars(($application['telefon_pasangan'] ?? 'N/A') ?? ''); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Bilangan Anak</label>
                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['bilangan_anak'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Pekerjaan Pasangan</label>
                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['pekerjaan_pasangan'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Nama Majikan</label>
                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['nama_majikan_pasangan'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Telefon Pejabat</label>
                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['telefon_pejabat_pasangan'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section 3: Alamat -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-purple-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Alamat
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Alamat Surat Menyurat</label>
                        <p class="text-sm text-gray-900">
                            <?php echo htmlspecialchars($application['alamat_surat']); ?><br>
                            <?php echo htmlspecialchars($application['bandar_surat']); ?><br>
                            <?php echo htmlspecialchars($application['poskod_surat']); ?> <?php echo htmlspecialchars($application['negeri_surat']); ?>
                        </p>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Alamat Tetap</label>
                        <p class="text-sm text-gray-900">
                            <?php echo htmlspecialchars($application['alamat_tetap']); ?><br>
                            <?php echo htmlspecialchars($application['bandar_tetap']); ?><br>
                            <?php echo htmlspecialchars($application['poskod_tetap']); ?> <?php echo htmlspecialchars($application['negeri_tetap']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Maklumat Kesihatan/Fizikal -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-red-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    Maklumat Kesihatan/Fizikal
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Darah Tinggi</label>
                        <p class="text-sm">
                            <?php if (($application['darah_tinggi'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['darah_tinggi'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Kencing Manis</label>
                        <p class="text-sm">
                            <?php if (($application['kencing_manis'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['kencing_manis'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Penyakit Buah Pinggang</label>
                        <p class="text-sm">
                            <?php if (($application['penyakit_buah_pinggang'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['penyakit_buah_pinggang'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Penyakit Jantung</label>
                        <p class="text-sm">
                            <?php if (($application['penyakit_jantung'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['penyakit_jantung'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Batuk Kering/Tibi</label>
                        <p class="text-sm">
                            <?php if (($application['batuk_kering_tibi'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['batuk_kering_tibi'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Kanser</label>
                        <p class="text-sm">
                            <?php if (($application['kanser'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['kanser'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">AIDS</label>
                        <p class="text-sm">
                            <?php if (($application['aids'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['aids'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Penagih Dadah</label>
                        <p class="text-sm">
                            <?php if (($application['penagih_dadah'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['penagih_dadah'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Penyakit Lain</label>
                        <p class="text-sm">
                            <?php if (($application['penyakit_lain'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                                <?php if (!empty($application['penyakit_lain_nyatakan'])): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2"><?php echo htmlspecialchars($application['penyakit_lain_nyatakan']); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['penyakit_lain'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Perokok</label>
                        <p class="text-sm">
                            <?php if (($application['perokok'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['perokok'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Pemegang Kad OKU</label>
                        <p class="text-sm">
                            <?php if (($application['pemegang_kad_oku'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['pemegang_kad_oku'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                        <?php if (($application['pemegang_kad_oku'] ?? '') === 'YA'): ?>
                            <?php if (!empty($application['salinan_kad_oku'])): ?>
                                <?php 
                                    $okuRaw = (string)$application['salinan_kad_oku'];
                                    if (preg_match('/^https?:\\/\\//i', $okuRaw)) {
                                        $okuHref = $okuRaw; // absolute URL
                                    } else {
                                        // ensure link points to site root from admin/, not /admin/...
                                        $okuHref = '../' . ltrim($okuRaw, '/');
                                    }
                                ?>
                                <div class="mt-2">
                                    <button type="button" 
                                       onclick="window.open('<?php echo htmlspecialchars($okuHref); ?>', '_blank')" 
                                       class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Lihat Salinan Kad OKU
                                    </button>
                                </div>
                            <?php else: ?>
                                <p class="mt-1 text-sm text-red-500">Salinan Kad OKU tidak dimuat naik</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Memakai Cermin Mata</label>
                        <p class="text-sm">
                            <?php if (($application['memakai_cermin_mata'] ?? '') === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">YA</span>
                            <?php else: ?>
                                <span class="text-gray-900"><?php echo htmlspecialchars($application['memakai_cermin_mata'] ?? 'Tiada Rekod'); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Berat (kg)</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['berat_kg'] ?? 'Tiada Rekod'); ?> <?php echo isset($application['berat_kg']) ? 'kg' : ''; ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Tinggi (cm)</label>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($application['tinggi_cm'] ?? 'Tiada Rekod'); ?> <?php echo isset($application['tinggi_cm']) ? 'cm' : ''; ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">BMI</label>
                        <?php 
                        $berat = floatval($application['berat_kg'] ?? 0);
                        $tinggi = floatval($application['tinggi_cm'] ?? 0) / 100; // Convert cm to meters
                        $bmi = ($tinggi > 0) ? $berat / ($tinggi * $tinggi) : 0;
                        $bmi_formatted = number_format($bmi, 2);
                        
                        // Determine BMI category and chip color
                        if ($bmi < 18.5) {
                            $bmi_bg_class = 'bg-blue-100 text-blue-800';
                            $bmi_category = 'Kurang Berat Badan';
                        } elseif ($bmi >= 18.5 && $bmi < 25) {
                            $bmi_bg_class = 'bg-green-100 text-green-800';
                            $bmi_category = 'Normal';
                        } elseif ($bmi >= 25 && $bmi < 30) {
                            $bmi_bg_class = 'bg-yellow-100 text-yellow-800';
                            $bmi_category = 'Berat Badan Berlebihan';
                        } else {
                            $bmi_bg_class = 'bg-red-100 text-red-800';
                            $bmi_category = 'Obesiti';
                        }
                        ?>
                        <p class="text-sm font-bold">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $bmi_bg_class; ?>"><?php echo $bmi_formatted . ' (' . $bmi_category . ')'; ?></span>
                        </p>
                    </div>
                </div>
                
                <?php if (($application['pemegang_kad_oku'] ?? '') === 'YA' && !empty($application['jenis_kanta'])): ?>
                <div class="mt-4">
                    <label class="text-sm font-medium text-gray-500">Jenis OKU</label>
                    <?php echo displayJsonData($application['jenis_kanta'], "Tiada maklumat jenis OKU", 'table'); ?>
                </div>
                <?php endif; ?>
                
                <?php if (($application['memakai_cermin_mata'] ?? '') === 'YA' && !empty($application['jenis_kanta'])): ?>
                <div class="mt-4">
                    <label class="text-sm font-medium text-gray-500">Jenis Kanta</label>
                    <?php echo displayJsonData($application['jenis_kanta'], "Tiada maklumat jenis kanta", 'table'); ?>
                </div>
                <?php endif; ?>

                
            </div>
        </div>

        <!-- Section 5: Pendidikan -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-indigo-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Pendidikan
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6">
                    
                    <!-- Kelulusan SPM/SPV -->
                    <div class="bg-white shadow-sm border border-gray-100 rounded-lg overflow-hidden">
                        <div class="bg-yellow-50 px-4 py-3 border-b border-gray-100">
                            <h3 class="text-base font-medium text-gray-900">Kelulusan SPM/SPV</h3>
                        </div>
                        <div class="p-4">
                            <?php if (!empty($spm_results)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Angka Giliran</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gred Keseluruhan</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bahasa Malaysia</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bahasa Inggeris</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matematik</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sejarah</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sijil</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($spm_results as $spm): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($spm['tahun'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($spm['angka_giliran'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($spm['gred_keseluruhan'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($spm['bahasa_malaysia'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($spm['bahasa_inggeris'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($spm['matematik'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($spm['sejarah'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm">
                                                <?php if (!empty($spm['salinan_sijil'])): ?>
                                                <?php $file_path = buildAppFileUrl($spm['salinan_sijil'], $application); ?>
                                                <a href="<?php echo htmlspecialchars(adminHrefFromRaw($spm['salinan_sijil'], $application)); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    Lihat
                                                </a>
                                                <?php else: ?>
                                                <span class="text-gray-400 text-xs italic">Tiada</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if (!empty($spm['subjek_lain']) && is_array($spm['subjek_lain'])): ?>
                                        <tr class="bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-500" colspan="8">
                                                <div class="mt-2">
                                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Subjek Lain:</h4>
                                                    <div class="overflow-x-auto">
                                                        <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded">
                                                            <thead class="bg-gray-100">
                                                                <tr>
                                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subjek</th>
                                                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gred</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="bg-white divide-y divide-gray-200">
                                                                <?php foreach ($spm['subjek_lain'] as $subjek): ?>
                                                                <?php if (!empty($subjek['subjek'])): ?>
                                                                <tr class="hover:bg-gray-50">
                                                                    <td class="px-3 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($subjek['subjek']); ?></td>
                                                                    <td class="px-3 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($subjek['gred'] ?? 'N/A'); ?></td>
                                                                </tr>
                                                                <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                    </div>
                            <?php elseif (!empty($application['kelulusan_spm'])): ?>
                                <?php echo displayJsonData($application['kelulusan_spm'], "Tiada maklumat SPM", 'table'); ?>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada maklumat SPM</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Application Education Data -->
                    <div class="bg-white shadow-sm border border-gray-100 rounded-lg overflow-hidden">
                        <div class="bg-indigo-50 px-4 py-3 border-b border-gray-100">
                            <h3 class="text-base font-medium text-gray-900">Maklumat Pendidikan (Education Records)</h3>
                        </div>
                        <div class="p-4">
                            <?php if (!empty($education_db)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Institusi</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tempoh</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelayakan</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gred</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sijil</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($education_db as $edu): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($edu['nama_institusi'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                            <?php 
                                                $dari = '';
                                                $hingga = '';
                                                
                                                // Format dari_tahun and dari_tahun
                                                if (!empty($edu['dari_tahun']) && !empty($edu['hingga_tahun'])) {
                                                    $dari = htmlspecialchars($edu['dari_tahun']);
                                                    $hingga = htmlspecialchars($edu['hingga_tahun']);
                                                } elseif (!empty($edu['dari_tahun'])) {
                                                    $dari = htmlspecialchars($edu['dari_tahun']);
                                                } elseif (!empty($edu['hingga_tahun'])) {
                                                    $hingga = htmlspecialchars($edu['hingga_tahun']);
                                                }
                                                
                                                // Display the formatted dates
                                                if (!empty($dari) && !empty($hingga)) {
                                                    echo $dari . ' - ' . $hingga;
                                                } elseif (!empty($dari)) {
                                                    echo 'Dari: ' . $dari;
                                                } elseif (!empty($hingga)) {
                                                    echo 'Hingga: ' . $hingga;
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($edu['kelayakan'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($edu['pangkat_gred_cgpa'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm">
                                                <?php if (!empty($edu['sijil_filename'])): ?>
                                                <?php $file_href = adminHrefFromRaw($edu['sijil_filename'], $application); ?>
                                                <a href="<?php echo htmlspecialchars($file_href); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    Lihat
                                                </a>
                                                <?php else: ?>
                                                <span class="text-gray-400 text-xs italic">Tiada</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada maklumat pendidikan</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    
                    
                    <?php if (!empty($application['kelulusan_stpm'])): ?>
                    <div class="bg-white shadow-sm border border-gray-100 rounded-lg overflow-hidden">
                        <div class="bg-purple-50 px-4 py-3 border-b border-gray-100">
                            <h3 class="text-base font-medium text-gray-900">Kelulusan STPM</h3>
                        </div>
                        <div class="p-4">
                        <?php echo displayJsonData($application['kelulusan_stpm'], "Tiada maklumat STPM", 'table'); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($application['kelulusan_ipt_1']) || !empty($application['kelulusan_ipt_2'])): ?>
                    <div class="bg-white shadow-sm border border-gray-100 rounded-lg overflow-hidden">
                        <div class="bg-indigo-50 px-4 py-3 border-b border-gray-100">
                            <h3 class="text-base font-medium text-gray-900">Kelulusan IPT</h3>
                        </div>
                        <div class="p-4">
                            <?php if (!empty($application['kelulusan_ipt_1'])): ?>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">IPT 1</h4>
                                <?php echo displayJsonData($application['kelulusan_ipt_1'], "Tiada maklumat IPT 1", 'table'); ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($application['kelulusan_ipt_2'])): ?>
                                <h4 class="text-sm font-medium text-gray-700 mt-4 mb-2">IPT 2</h4>
                                <?php echo displayJsonData($application['kelulusan_ipt_2'], "Tiada maklumat IPT 2", 'table'); ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($application['salinan_sijil_ipt'])): ?>
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Sijil IPT</h4>
                        <?php 
                                    $file = $application['salinan_sijil_ipt'];
                            $file_path = '../' . ltrim($file, '/');
                            $ext = pathinfo($file_path, PATHINFO_EXTENSION);
                            $is_image = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
                                    ?>
                                    <a href="<?php echo htmlspecialchars($file_path); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        Lihat Sijil IPT
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="bg-white shadow-sm border border-gray-100 rounded-lg overflow-hidden">
                        <div class="bg-blue-50 px-4 py-3 border-b border-gray-100">
                            <h3 class="text-base font-medium text-gray-900">Kemahiran Bahasa</h3>
                        </div>
                        <div class="p-4">
                             <?php if (!empty($language_skills)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bahasa</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pertuturan</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penulisan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($language_skills as $lang): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($lang['bahasa'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <?php 
                                                $pertuturan = strtoupper($lang['pertuturan'] ?? ($lang['tahap_lisan'] ?? ''));
                                                $color_class = ''; $bg_class = '';
                                                if ($pertuturan === 'SANGAT BAIK') { $color_class = 'text-green-700 font-medium'; $bg_class = 'bg-green-100'; } elseif ($pertuturan === 'BAIK') { $color_class = 'text-blue-700'; $bg_class = 'bg-blue-100'; } elseif ($pertuturan === 'SEDERHANA') { $color_class = 'text-yellow-700'; $bg_class = 'bg-yellow-100'; } elseif ($pertuturan === 'LEMAH') { $color_class = 'text-red-700'; $bg_class = 'bg-red-100'; }
                                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $bg_class . ' ' . $color_class . '">' . htmlspecialchars($pertuturan) . '</span>';
                                                ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <?php 
                                                $penulisan = strtoupper($lang['penulisan'] ?? ($lang['tahap_penulisan'] ?? ''));
                                                $color_class = ''; $bg_class = '';
                                                if ($penulisan === 'SANGAT BAIK') { $color_class = 'text-green-700 font-medium'; $bg_class = 'bg-green-100'; } elseif ($penulisan === 'BAIK') { $color_class = 'text-blue-700'; $bg_class = 'bg-blue-100'; } elseif ($penulisan === 'SEDERHANA') { $color_class = 'text-yellow-700'; $bg_class = 'bg-yellow-100'; } elseif ($penulisan === 'LEMAH') { $color_class = 'text-red-700'; $bg_class = 'bg-red-100'; }
                                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $bg_class . ' ' . $color_class . '">' . htmlspecialchars($penulisan) . '</span>';
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada maklumat kemahiran bahasa</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="bg-white shadow-sm border border-gray-100 rounded-lg overflow-hidden">
                        <div class="bg-green-50 px-4 py-3 border-b border-gray-100">
                            <h3 class="text-base font-medium text-gray-900">Kemahiran Komputer</h3>
                        </div>
                        <div class="p-4">
                            <?php if (!empty($computer_skills)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Perisian</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahap Kemahiran</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($computer_skills as $comp): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($comp['nama_perisian'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <?php 
                                                $tahap = strtoupper($comp['tahap_kemahiran'] ?? '');
                                                $color_class = ''; $bg_class = '';
                                                if ($tahap === 'SANGAT BAIK' || $tahap === 'MAHIR') { $color_class = 'text-green-700 font-medium'; $bg_class = 'bg-green-100'; } elseif ($tahap === 'BAIK') { $color_class = 'text-blue-700'; $bg_class = 'bg-blue-100'; } elseif ($tahap === 'SEDERHANA') { $color_class = 'text-yellow-700'; $bg_class = 'bg-yellow-100'; } elseif ($tahap === 'LEMAH' || $tahap === 'ASAS') { $color_class = 'text-red-700'; $bg_class = 'bg-red-100'; }
                                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $bg_class . ' ' . $color_class . '">' . htmlspecialchars($tahap) . '</span>';
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada maklumat kemahiran komputer</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="bg-white shadow-sm border border-gray-100 rounded-lg overflow-hidden">
                        <div class="bg-purple-50 px-4 py-3 border-b border-gray-100">
                            <h3 class="text-base font-medium text-gray-900">Maklumat Badan Profesional</h3>
                        </div>
                        <div class="p-4">
                            <?php if (!empty($professional_bodies)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lembaga</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Ahli</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sijil</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarikh Sijil</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salinan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($professional_bodies as $body): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($body['nama_lembaga'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($body['no_ahli'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($body['sijil_diperoleh'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <?php $y = $body['tarikh_sijil'] ?? ($body['tahun'] ?? ''); echo !empty($y) ? htmlspecialchars(formatDateDMY($y)) : 'N/A'; ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <?php if (!empty($body['salinan_sijil'])): ?>
                                                <?php $file_href = adminHrefFromRaw($body['salinan_sijil'], $application); ?>
                                                <a href="<?php echo htmlspecialchars($file_href); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    Lihat
                                                </a>
                                                <?php else: ?>
                                                <span class="text-gray-400 text-xs italic">Tiada</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada maklumat badan profesional</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="bg-white shadow-sm border border-gray-100 rounded-lg overflow-hidden">
                        <div class="bg-orange-50 px-4 py-3 border-b border-gray-100">
                            <h3 class="text-base font-medium text-gray-900">Maklumat Kegiatan Luar</h3>
                    </div>
                        <div class="p-4">
                            <?php if (!empty($extracurricular)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sukan/Persatuan/Kelab</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawatan</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peringkat</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salinan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($extracurricular as $extra): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($extra['sukan_persatuan_kelab'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($extra['jawatan'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($extra['peringkat'] ?? ''); ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900"><?php $y = $extra['tarikh_sijil'] ?? ($extra['tahun'] ?? ''); echo htmlspecialchars(formatDateDMY($y)); ?></td>
                                            <td class="px-4 py-3 text-sm">
                                                <?php if (!empty($extra['salinan_sijil'])): ?>
                                                <?php $file_href = adminHrefFromRaw($extra['salinan_sijil'], $application); ?>
                                                <a href="<?php echo htmlspecialchars($file_href); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    Lihat
                                                </a>
                                                <?php else: ?>
                                                <span class="text-gray-400 text-xs italic">Tiada</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Tiada maklumat kegiatan luar</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 6: Pengalaman Bekerja -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
            <div class="bg-yellow-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6" />
                    </svg>
                    Pengalaman Bekerja
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6">
                    <div class="bg-white shadow-sm border border-gray-100 rounded-lg overflow-hidden">
                        <div class="bg-blue-50 px-4 py-3 border-b border-gray-100">
                            <h3 class="text-base font-medium text-gray-900">Maklumat Pengalaman Kerja</h3>
                        </div>
                        <div class="p-4">
                            <div class="mb-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo (!empty($work_experience)) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo (!empty($work_experience)) ? 'Ada Pengalaman Kerja' : 'Tiada Pengalaman Kerja'; ?>
                                </span>
                </div>
                
                                <?php if (!empty($work_experience)): ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Syarikat</th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawatan</th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tempoh</th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gaji</th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan Berhenti</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($work_experience as $work): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($work['syarikat'] ?? ''); ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($work['jawatan'] ?? ''); ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                    <?php 
                                                    $dari = '';
                                                    $hingga = '';
                                                    
                                                    if (!empty($work['mula_berkhidmat'])) {
                                                        $dari = htmlspecialchars(formatDateDMY($work['mula_berkhidmat']));
                                                    } else {
                                                        if (!empty($work['dari_bulan']) && !empty($work['dari_tahun'])) {
                                                            $dari = htmlspecialchars($work['dari_bulan']) . ' ' . htmlspecialchars($work['dari_tahun']);
                                                        } elseif (!empty($work['dari_tahun'])) {
                                                            $dari = htmlspecialchars($work['dari_tahun']);
                                                        }
                                                    }
                                                    if (!empty($work['tamat_berkhidmat'])) {
                                                        $hingga = htmlspecialchars(formatDateDMY($work['tamat_berkhidmat']));
                                                    } else {
                                                        if (!empty($work['hingga_bulan']) && !empty($work['hingga_tahun'])) {
                                                            $hingga = htmlspecialchars($work['hingga_bulan']) . ' ' . htmlspecialchars($work['hingga_tahun']);
                                                        } elseif (!empty($work['hingga_tahun'])) {
                                                            $hingga = htmlspecialchars($work['hingga_tahun']);
                                                        }
                                                    }
                                                    
                                                    // Display the formatted dates
                                                    if (!empty($dari) && !empty($hingga)) {
                                                        echo $dari . ' - ' . $hingga;
                                                    } elseif (!empty($dari)) {
                                                        echo 'Dari: ' . $dari;
                                                    } elseif (!empty($hingga)) {
                                                        echo 'Hingga: ' . $hingga;
                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    <?php 
                                                    if (!empty($work['gaji'])) {
                                                        // Format as currency if it's a number
                                                        if (is_numeric($work['gaji'])) {
                                                            echo '<span class="font-medium">RM ' . number_format((float)$work['gaji'], 2) . '</span>';
                                                        } else {
                                                            echo htmlspecialchars($work['gaji']);
                                                        }
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-900"><?php $alasan = $work['alasan'] ?? ($work['alasan_berhenti'] ?? ''); echo htmlspecialchars($alasan ?: 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="px-4 pb-4">
                                                    <?php $scope = $work['bidang_tugas'] ?? ($work['skop_kerja'] ?? ''); if ($scope) { ?>
                                                        <div class="w-full bg-gray-50 border border-gray-200 rounded px-4 py-3">
                                                            <div class="text-xs font-medium text-gray-600 mb-1">Skop Kerja</div>
                                                            <div class="text-sm text-gray-900 leading-relaxed whitespace-pre-line"><?php echo htmlspecialchars($scope); ?></div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="text-sm text-gray-400">Skop Kerja: N/A</div>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                </div>
                                <?php else: ?>
                                    <p class="text-gray-500 italic">Tiada maklumat pengalaman kerja</p>
                <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 7: Pengisytiharan Diri -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
            <div class="bg-orange-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Pengisytiharan Diri
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php 
                    // Map of field => [question label, extra key for details (optional)]
                    $questions = [
                        'pekerja_perkhidmatan_awam' => ['Adakah anda pekerja perkhidmatan awam?', 'pekerja_perkhidmatan_awam_nyatakan'],
                        'pertalian_kakitangan' => ['Adakah anda mempunyai pertalian dengan kakitangan MPHS?', 'pertalian_kakitangan_nyatakan'],
                        'pernah_bekerja_mphs' => ['Pernah bekerja di MPHS?', 'pernah_bekerja_mphs_nyatakan'],
                        'tindakan_tatatertib' => ['Pernah dikenakan tindakan tatatertib?', 'tindakan_tatatertib_nyatakan'],
                        'kesalahan_undangundang' => ['Pernah disabit kesalahan undang-undang?', 'kesalahan_undangundang_nyatakan'],
                        'muflis' => ['Adakah anda muflis?', 'muflis_nyatakan'],
                    ];
                    foreach ($questions as $key => [$questionLabel, $detailKey]):
                        $val = normalizeYesNoValue($application[$key] ?? '');
                    ?>
                    <div>
                        <p class="text-sm font-medium text-gray-900 mb-1"><?php echo htmlspecialchars($questionLabel); ?></p>
                        <div class="text-sm">
                            <?php if ($val === 'YA'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Ya</span>
                                <?php if ($key === 'pertalian_kakitangan' && !empty($application['nama_kakitangan_pertalian'])): ?>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><?php echo htmlspecialchars($application['nama_kakitangan_pertalian']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($detailKey) && !empty($application[$detailKey] ?? '')): ?>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><?php echo htmlspecialchars($application[$detailKey]); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-900">Tidak</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Rujukan</h3>
                    <?php if (!empty($rujukan_db) && is_array($rujukan_db)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($rujukan_db as $index => $ref): ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h4 class="text-base font-semibold text-gray-900 mb-3">Rujukan <?php echo $index + 1; ?></h4>
                            <div class="space-y-2">
                                <?php if (!empty($ref['nama'])): ?>
                                <div>
                                    <label class="text-xs font-medium text-gray-500">Nama</label>
                                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($ref['nama']); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($ref['telefon']) || !empty($ref['tempoh'])): ?>
                                <div class="grid grid-cols-2 gap-4">
                                    <?php if (!empty($ref['telefon'])): ?>
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Telefon</label>
                                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($ref['telefon']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($ref['tempoh'])): ?>
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Tempoh kenal</label>
                                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($ref['tempoh']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-sm text-gray-500 italic">Tiada maklumat rujukan</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section 8: Dokumen -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
            <div class="bg-teal-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                Dokumen
                </h2>
            </div>
            <div class="p-6 text-sm">
                <?php
$documents = [];
                // Common personal uploads - similar to preview-application.php approach
                foreach ([
                    ['label' => 'Gambar Passport', 'fields' => ['gambar_passport_path', 'gambar_passport']],
                    ['label' => 'Salinan IC', 'fields' => ['salinan_ic_path', 'salinan_ic']],
                    ['label' => 'Salinan Surat Beranak', 'fields' => ['salinan_surat_beranak_path', 'salinan_surat_beranak']],
                    ['label' => 'Salinan Kad OKU', 'fields' => ['salinan_kad_oku_path', 'salinan_kad_oku'], 'condition' => strtoupper($application['pemegang_kad_oku'] ?? '') === 'YA'],
                    ['label' => 'Salinan Lesen Memandu', 'fields' => ['salinan_lesen_memandu_path', 'salinan_lesen_memandu'], 'condition' => !empty($application['lesen_memandu_set'])],
                ] as $d) {
                    // Skip if there's a condition and it's not met
                    if (isset($d['condition']) && !$d['condition']) {
                        continue;
                    }
                    $rawPath = fieldValue($application, $d['fields']);
                    if ($rawPath) {
                        $url = buildAppFileUrl($rawPath, $application);
                        $file_name = basename($url);
                        $documents[] = [
                            'Dokumen' => $d['label'],
                            'Fail' => '<div class="flex items-center gap-2">
                                <button type="button" onclick="window.open(\'' . h($url) . '\', \'_blank\')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Lihat
                                </button>
                                <button type="button" onclick="printDocument(\'' . h($url) . '\')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Cetak
                                </button>
                            </div>'
                        ];
                    }
                }

                // Education certificates
                foreach ($education_db as $e) {
                    if (!empty($e['sijil_filename'])) {
                        $url = buildAppFileUrl($e['sijil_filename'], $application);
                        $file_name = basename($url);
                        $documents[] = [
                            'Dokumen' => 'Sijil Pendidikan - ' . ($e['nama_institusi'] ?? ''),
                            'Fail' => '<div class="flex items-center gap-2">
                                <button type="button" onclick="window.open(\'' . h($url) . '\', \'_blank\')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Lihat
                                </button>
                                <button type="button" onclick="printDocument(\'' . h($url) . '\')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Cetak
                                </button>
                            </div>'
                        ];
                    }
                }

                // SPM certificates - Get unique sijil per year/angka_giliran
                $spm_document_results = safeFetchAll($pdo, "SELECT DISTINCT tahun, angka_giliran, $spmColumn AS salinan_sijil FROM application_spm_results WHERE application_reference = ? AND $spmColumn IS NOT NULL AND $spmColumn != ''", [$application['application_reference']]);
                foreach ($spm_document_results as $s) {
                    if (!empty($s['salinan_sijil'])) {
                        $url = buildAppFileUrl($s['salinan_sijil'], $application);
                        $file_name = basename($url);
                        $documents[] = [
                            'Dokumen' => 'Sijil SPM/SPV',
                            'Fail' => '<div class="flex items-center gap-2">
                                <button type="button" onclick="window.open(\'' . h($url) . '\', \'_blank\')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Lihat
                                </button>
                                <button type="button" onclick="printDocument(\'' . h($url) . '\')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Cetak
                                </button>
                            </div>'
                        ];
                    }
                }

                // Professional body certificates
                $profesional_results = safeFetchAll($pdo, "SELECT nama_lembaga, $profColumn AS salinan_sijil FROM application_professional_bodies WHERE application_reference = ?", [$application['application_reference']]);
                foreach ($profesional_results as $prof) {
                    if (!empty($prof['salinan_sijil'])) {
                        $url = buildAppFileUrl($prof['salinan_sijil'], $application);
                        $file_name = basename($url);
                        $documents[] = [
                            'Dokumen' => 'Sijil Badan Profesional - ' . ($prof['nama_lembaga'] ?? ''),
                            'Fail' => '<div class="flex items-center gap-2">
                                <button type="button" onclick="window.open(\'' . h($url) . '\', \'_blank\')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Lihat
                                </button>
                                <button type="button" onclick="printDocument(\'' . h($url) . '\')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Cetak
                                </button>
                            </div>'
                        ];
                    }
                }

                // Extracurricular certificates
                $kegiatan_results = safeFetchAll($pdo, "SELECT sukan_persatuan_kelab, $extColumn AS salinan_sijil FROM application_extracurricular WHERE application_reference = ?", [$application['application_reference']]);
                foreach ($kegiatan_results as $kegiatan) {
                    if (!empty($kegiatan['salinan_sijil'])) {
                        $url = buildAppFileUrl($kegiatan['salinan_sijil'], $application);
                        $file_name = basename($url);
                        $documents[] = [
                            'Dokumen' => 'Sijil Kegiatan Luar - ' . ($kegiatan['sukan_persatuan_kelab'] ?? ''),
                            'Fail' => '<div class="flex items-center gap-2">
                                <button type="button" onclick="window.open(\'' . h($url) . '\', \'_blank\')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Lihat
                                        </button>
                                <button type="button" onclick="printDocument(\'' . h($url) . '\')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            Cetak
                                        </button>
                            </div>'
                        ];
                    }
                }

                echo renderTable($documents, 'Tiada dokumen dimuat naik', ['Dokumen','Fail'], false);
                ?>
                                    </div>
        </div>
    </div>
</div>

<style>
@media print {
  header, footer, #statusSidebar, #sidebarHandle { display: none !important; }
  main { max-width: 100% !important; width: 100% !important; padding: 0 !important; }
  #mainContent { margin: 0 !important; padding: 0 !important; }
}
@page { size: A4; margin: 10mm; }
</style>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 z-50 hidden overflow-auto bg-black bg-opacity-75 flex items-center justify-center p-4">
    <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium">Lihat Dokumen</h3>
            <button type="button" onclick="closeImageModal()" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-4 flex items-center justify-center">
            <img id="modalImage" src="" class="max-w-full max-h-[70vh] object-contain" alt="Document Preview">
        </div>
    </div>
</div>

<script>
    
    function printDocument(documentPath) {
        // Open the document in a new window
        const printWindow = window.open(documentPath, '_blank');
        
        // Wait for the document to load, then trigger print
        if (printWindow) {
            printWindow.onload = function() {
                printWindow.focus();
                printWindow.print();
            };
        } else {
            // Fallback if popup is blocked
            alert('Sila benarkan popup untuk mencetak dokumen.');
        }
    }
</script>

<script>
const pdfFileName = <?php echo json_encode(($application['nama_penuh'] ?? 'resume') . '-' . ($application['application_reference'] ?? (string)$application_id) . '.pdf'); ?>;
function printResume() {
  var el = document.getElementById('mainContent');
  if (!el) return;
  var sidebar = document.getElementById('statusSidebar');
  var handle = document.getElementById('sidebarHandle');
  var prevSidebarDisplay = sidebar ? sidebar.style.display : null;
  var prevHandleDisplay = handle ? handle.style.display : null;
  if (sidebar) sidebar.style.display = 'none';
  if (handle) handle.style.display = 'none';
  var pdfHiddenEls = Array.prototype.slice.call(document.querySelectorAll('.pdf-hidden'));
  var prevPdfDisplays = pdfHiddenEls.map(function(x){ return x.style.display; });
  pdfHiddenEls.forEach(function(x){ x.style.display = 'none'; });
  var opt = { margin: 0.3, filename: pdfFileName, image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2, useCORS: true }, jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' } };
  ensureHtml2PdfLoaded(function(){
    try {
      var chain = window.html2pdf().set(opt).from(el).toPdf().get('pdf').then(function(pdf){
        var url = pdf.output('bloburl');
        var w = window.open(url, '_blank');
        if (w) { w.onload = function(){ w.focus(); w.print(); }; }
        if (sidebar) sidebar.style.display = (prevSidebarDisplay !== null ? prevSidebarDisplay : '');
        if (handle) handle.style.display = (prevHandleDisplay !== null ? prevHandleDisplay : '');
        pdfHiddenEls.forEach(function(x, i){ x.style.display = prevPdfDisplays[i] || ''; });
      });
      if (chain && typeof chain.catch === 'function') {
        chain.catch(function(){
          if (sidebar) sidebar.style.display = (prevSidebarDisplay !== null ? prevSidebarDisplay : '');
          if (handle) handle.style.display = (prevHandleDisplay !== null ? prevHandleDisplay : '');
          pdfHiddenEls.forEach(function(x, i){ x.style.display = prevPdfDisplays[i] || ''; });
        });
      }
    } catch (e) {
      if (sidebar) sidebar.style.display = (prevSidebarDisplay !== null ? prevSidebarDisplay : '');
      if (handle) handle.style.display = (prevHandleDisplay !== null ? prevHandleDisplay : '');
      pdfHiddenEls.forEach(function(x, i){ x.style.display = prevPdfDisplays[i] || ''; });
      throw e;
    }
  });
}
function ensureHtml2PdfLoaded(cb) {
  if (window.html2pdf) { cb(); return; }
  var s = document.createElement('script');
  s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js';
  s.onload = cb;
  document.head.appendChild(s);
}
function downloadResumePdf() {
  var el = document.getElementById('mainContent');
  if (!el) return;
  var sidebar = document.getElementById('statusSidebar');
  var handle = document.getElementById('sidebarHandle');
  var prevSidebarDisplay = sidebar ? sidebar.style.display : null;
  var prevHandleDisplay = handle ? handle.style.display : null;
  if (sidebar) sidebar.style.display = 'none';
  if (handle) handle.style.display = 'none';
  var pdfHiddenEls = Array.prototype.slice.call(document.querySelectorAll('.pdf-hidden'));
  var prevPdfDisplays = pdfHiddenEls.map(function(x){ return x.style.display; });
  pdfHiddenEls.forEach(function(x){ x.style.display = 'none'; });
  var opt = { margin: 0.3, filename: pdfFileName, image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2, useCORS: true }, jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' } };
  ensureHtml2PdfLoaded(function(){
    try {
      var task = window.html2pdf().set(opt).from(el).save();
      if (task && typeof task.then === 'function') {
        task.then(function(){
          if (sidebar) sidebar.style.display = (prevSidebarDisplay !== null ? prevSidebarDisplay : '');
          if (handle) handle.style.display = (prevHandleDisplay !== null ? prevHandleDisplay : '');
          pdfHiddenEls.forEach(function(x, i){ x.style.display = prevPdfDisplays[i] || ''; });
        });
      } else {
        setTimeout(function(){
          if (sidebar) sidebar.style.display = (prevSidebarDisplay !== null ? prevSidebarDisplay : '');
          if (handle) handle.style.display = (prevHandleDisplay !== null ? prevHandleDisplay : '');
          pdfHiddenEls.forEach(function(x, i){ x.style.display = prevPdfDisplays[i] || ''; });
        }, 1000);
      }
    } catch (e) {
      if (sidebar) sidebar.style.display = (prevSidebarDisplay !== null ? prevSidebarDisplay : '');
      if (handle) handle.style.display = (prevHandleDisplay !== null ? prevHandleDisplay : '');
      pdfHiddenEls.forEach(function(x, i){ x.style.display = prevPdfDisplays[i] || ''; });
      throw e;
    }
  });
}
</script>

<script>
const updateStatusBtn = null;
const statusSidebar = document.getElementById('statusSidebar');
const closeSidebarBtn = document.getElementById('closeSidebarBtn');
const mainContent = document.getElementById('mainContent');
const sidebarHandle = document.getElementById('sidebarHandle');

function openSidebar() {
    if (!statusSidebar) return;
    statusSidebar.classList.remove('translate-x-full');
    if (sidebarHandle) { sidebarHandle.style.display = 'none'; }
    if (mainContent && window.matchMedia('(min-width: 768px)').matches) {
        mainContent.style.paddingRight = '0px';
    }
}
function closeSidebar() {
    if (!statusSidebar) return;
    statusSidebar.classList.add('translate-x-full');
    if (mainContent) { mainContent.style.paddingRight = ''; }
    if (sidebarHandle) { sidebarHandle.style.display = 'flex'; }
}

if (sidebarHandle) { sidebarHandle.addEventListener('click', openSidebar); }
if (closeSidebarBtn) { closeSidebarBtn.addEventListener('click', closeSidebar); }
</script>

<!-- Image Modal -->
<div id="imageModal" class="modal fixed inset-0 z-50 overflow-auto bg-black bg-opacity-75 flex items-center justify-center hidden">
    <div class="modal-content bg-white rounded-lg max-w-4xl w-full mx-4">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Lihat Dokumen</h3>
            <button type="button" class="close-modal text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-4 flex justify-center">
            <img id="modalImage" src="" alt="Document Preview" class="max-h-[80vh] max-w-full">
        </div>
    </div>
</div>

<script>
// Image modal functionality
function openImageModal(imageSrc, title = 'Lihat Dokumen') {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    
    modal.classList.remove('hidden');
    modalImg.src = imageSrc;
    modalTitle.textContent = title;
    
    document.body.style.overflow = 'hidden'; // Prevent scrolling
}

document.querySelectorAll('.close-modal').forEach(button => {
    button.addEventListener('click', () => {
        document.getElementById('imageModal').classList.add('hidden');
        document.body.style.overflow = '';
    });
});

// Close modal when clicking outside the content
document.getElementById('imageModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('imageModal')) {
        document.getElementById('imageModal').classList.add('hidden');
        document.body.style.overflow = '';
    }
});

// Close modal with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !document.getElementById('imageModal').classList.contains('hidden')) {
        document.getElementById('imageModal').classList.add('hidden');
        document.body.style.overflow = '';
    }
});
</script>

<?php include 'templates/footer.php'; ?>
