<?php
session_start();
// Centralized bootstrap (logging, DB helper, global handlers)
require_once '../includes/bootstrap.php';
// Keep page-level auth
require_once 'auth.php';

// Get database connection from main config
$config = require '../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Log admin action
// Initialize filters
$filters = [];
$where_clauses = [];
$params = [];

log_admin_info('Viewed job applications list', [
    'action' => 'VIEW_APPLICATIONS_LIST',
    'filters' => $filters
]);

// Handle search and filters
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(aa.nama_penuh LIKE ? OR aa.nombor_ic LIKE ? OR aa.email LIKE ? OR aa.application_reference LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $filters['search'] = $_GET['search'];
}

$where_clauses[] = "aa.submission_locked = 1";

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $filters['status'] = $_GET['status'];
    $where_clauses[] = "aa.submission_status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['job_id']) && !empty($_GET['job_id'])) {
    $where_clauses[] = "aa.job_id = ?";
    $params[] = $_GET['job_id'];
    $filters['job_id'] = $_GET['job_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
        $_SESSION['error'] = 'CSRF tidak sah.';
        $redir = [];
        if (!empty($_POST['search'])) { $redir['search'] = $_POST['search']; }
        if (!empty($_POST['status'])) { $redir['status'] = $_POST['status']; }
        if (!empty($_POST['job_id'])) { $redir['job_id'] = $_POST['job_id']; }
        header('Location: applications-list.php' . (!empty($redir) ? '?' . http_build_query(array_filter($redir)) : ''));
        exit;
    }
    $ids = $_POST['selected_ids'] ?? [];
    if (!is_array($ids)) { $ids = []; }
    $ids = array_values(array_unique(array_map(function($v){ return (int)$v; }, $ids)));
    $action = strtoupper(trim((string)($_POST['bulk_action'] ?? '')));
    $redir = [];
    if (!empty($_POST['search'])) { $redir['search'] = $_POST['search']; }
    if (!empty($_POST['status'])) { $redir['status'] = $_POST['status']; }
    if (!empty($_POST['job_id'])) { $redir['job_id'] = $_POST['job_id']; }
    if (empty($ids) || empty($action)) {
        $_SESSION['error'] = 'Sila pilih permohonan dan tindakan.';
        header('Location: applications-list.php' . (!empty($redir) ? '?' . http_build_query(array_filter($redir)) : ''));
        exit;
    }
    try {
        $st_stmt = $pdo->prepare('SELECT id, code, name FROM application_statuses WHERE code = ? AND is_active = 1 LIMIT 1');
        $st_stmt->execute([$action]);
        $st_row = $st_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$st_row) {
            $_SESSION['error'] = 'Tindakan tidak sah.';
            header('Location: applications-list.php' . (!empty($redir) ? '?' . http_build_query(array_filter($redir)) : ''));
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $update_sql = "UPDATE application_application_main SET submission_status = ? WHERE id IN ($placeholders)";
        $update_stmt = $pdo->prepare($update_sql);
        $update_params = array_merge([$st_row['code']], $ids);
        $update_stmt->execute($update_params);

        // Optional: update status_notes in main table if column exists
        try {
            $colsn = $pdo->query("SHOW COLUMNS FROM application_application_main LIKE 'status_notes'")->fetch(PDO::FETCH_ASSOC);
            if ($colsn) {
                $update_sql2 = "UPDATE application_application_main SET status_notes = ? WHERE id IN ($placeholders)";
                $update_stmt2 = $pdo->prepare($update_sql2);
                $note = trim((string)($_POST['bulk_note'] ?? ''));
                $update_params2 = array_merge([$note], $ids);
                $update_stmt2->execute($update_params2);
            }
        } catch (Exception $e) {}

        // Insert history entries (adapt to available schema)
        $changed_by = $_SESSION['admin_id'] ?? null;
        $has_app_id = false; $has_app_ref = false;
        try {
            $col = $pdo->query("SHOW COLUMNS FROM application_status_history LIKE 'application_id'")->fetch(PDO::FETCH_ASSOC);
            $has_app_id = (bool)$col;
            $col2 = $pdo->query("SHOW COLUMNS FROM application_status_history LIKE 'application_reference'")->fetch(PDO::FETCH_ASSOC);
            $has_app_ref = (bool)$col2;
        } catch (Exception $e) {}

        $sendFlag = (!empty($config['status_email_enabled']) && isset($_POST['send_status_email']) && $_POST['send_status_email'] == '1');
        if ($has_app_id) {
            $hist_sql = "INSERT INTO application_status_history (application_id, status_id, status_description, changed_by, notes) VALUES (?, ?, ?, ?, ?)";
            $hist_stmt = $pdo->prepare($hist_sql);
            $note = trim((string)($_POST['bulk_note'] ?? ''));
            foreach ($ids as $aid) {
                $hist_stmt->execute([$aid, $st_row['id'], $st_row['name'], $changed_by, $note]);
                if ($sendFlag) {
                    $app_stmt = $pdo->prepare('SELECT * FROM application_application_main WHERE id = ?');
                    $app_stmt->execute([$aid]);
                    $app = $app_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    $extra = '';
                    try {
                        $cols = $pdo->query("SHOW COLUMNS FROM application_statuses")->fetchAll(PDO::FETCH_ASSOC);
                        $names = array_column($cols,'Field');
                        if (in_array('email_template_subject',$names,true)) { $extra .= ', email_template_subject AS email_subject'; }
                        if (in_array('email_template_body',$names,true)) { $extra .= ', email_template_body AS email_body'; }
                    } catch (Exception $e) {}
                    $st2 = $pdo->prepare('SELECT id, code, name' . $extra . ' FROM application_statuses WHERE id = ? LIMIT 1');
                    $st2->execute([$st_row['id']]);
                    $stFull = $st2->fetch(PDO::FETCH_ASSOC) ?: $st_row;
                    require_once __DIR__ . '/../includes/StatusEmailService.php';
                    $svc = new StatusEmailService($config, $pdo);
                    $svc->send($app, $stFull, $note);
                }
            }
        } elseif ($has_app_ref) {
            // Fetch references for selected ids
            $ref_stmt = $pdo->prepare("SELECT id, application_reference FROM application_application_main WHERE id IN (" . $placeholders . ")");
            $ref_stmt->execute($ids);
            $ref_map = $ref_stmt->fetchAll(PDO::FETCH_ASSOC);
            $hist_sql = "INSERT INTO application_status_history (application_reference, status_id, status_description, changed_by, notes) VALUES (?, ?, ?, ?, ?)";
            $hist_stmt = $pdo->prepare($hist_sql);
            $note = trim((string)($_POST['bulk_note'] ?? ''));
            foreach ($ref_map as $row) {
                $hist_stmt->execute([$row['application_reference'], $st_row['id'], $st_row['name'], $changed_by, $note]);
                if ($sendFlag) {
                    $app_stmt = $pdo->prepare('SELECT * FROM application_application_main WHERE id = ?');
                    $app_stmt->execute([$row['id']]);
                    $app = $app_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    $extra = '';
                    try {
                        $cols = $pdo->query("SHOW COLUMNS FROM application_statuses")->fetchAll(PDO::FETCH_ASSOC);
                        $names = array_column($cols,'Field');
                        if (in_array('email_template_subject',$names,true)) { $extra .= ', email_template_subject AS email_subject'; }
                        if (in_array('email_template_body',$names,true)) { $extra .= ', email_template_body AS email_body'; }
                    } catch (Exception $e) {}
                    $st2 = $pdo->prepare('SELECT id, code, name' . $extra . ' FROM application_statuses WHERE id = ? LIMIT 1');
                    $st2->execute([$st_row['id']]);
                    $stFull = $st2->fetch(PDO::FETCH_ASSOC) ?: $st_row;
                    require_once __DIR__ . '/../includes/StatusEmailService.php';
                    $svc = new StatusEmailService($config, $pdo);
                    $svc->send($app, $stFull, $note);
                }
            }
        } // else: skip history insertion if schema unknown

        $_SESSION['success'] = 'Status berjaya dikemaskini.';
    } catch (Exception $e) {
        log_admin_error('Bulk status update failed', [
            'error' => $e->getMessage(),
            'action' => $action,
            'ids' => $ids
        ]);
        $_SESSION['error'] = 'Ralat mengemaskini status.';
    }
    header('Location: applications-list.php' . (!empty($redir) ? '?' . http_build_query(array_filter($redir)) : ''));
    exit;
}

// Build the WHERE clause (without submission_locked filter)
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// CSV Export
if (isset($_GET['export']) && strtolower($_GET['export']) === 'csv') {
    try {
        $export_where_sql = $where_sql;
        if (!empty($where_clauses)) {
            $export_where_sql .= " AND (aa.submission_status != 'Draft' OR aa.submission_status IS NULL)";
        } else {
            $export_where_sql = " WHERE (aa.submission_status != 'Draft' OR aa.submission_status IS NULL)";
        }

        $export_sql = "SELECT aa.*, jp.job_title, jp.job_code AS job_code, jp.kod_gred, st.name AS status_name, st.code AS status_code\n            FROM application_application_main aa\n            LEFT JOIN job_postings jp ON aa.job_id = jp.id\n            LEFT JOIN application_statuses st ON st.code = aa.submission_status" .
            $export_where_sql .
            " ORDER BY aa.created_at DESC";

        $export_stmt = $pdo->prepare($export_sql);
        $export_stmt->execute($params);
        $rows = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = 'applications_export_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if (!empty($rows)) {
            $headers = array_keys($rows[0]);
            fputcsv($out, $headers);
            foreach ($rows as $r) {
                $line = [];
                foreach ($headers as $h) {
                    $line[] = isset($r[$h]) ? (is_string($r[$h]) ? $r[$h] : (string)$r[$h]) : '';
                }
                fputcsv($out, $line);
            }
        } else {
            fputcsv($out, ['message']);
            fputcsv($out, ['Tiada data ditemui untuk eksport']);
        }
        fclose($out);
        exit;
    } catch (Exception $e) {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Ralat eksport: ' . $e->getMessage();
        exit;
    }
}

// Count total applications (exclude drafts)
try {
    $count_where_sql = $where_sql;
    if (!empty($where_clauses)) {
        $count_where_sql .= " AND (aa.submission_status != 'Draft' OR aa.submission_status IS NULL)";
    } else {
        $count_where_sql = " WHERE (aa.submission_status != 'Draft' OR aa.submission_status IS NULL)";
    }

    $count_sql = "SELECT COUNT(*) FROM application_application_main aa" . $count_where_sql;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_applications = $count_stmt->fetchColumn();
    $total_pages = max(1, ceil($total_applications / $per_page));
} catch (Exception $e) {
    $total_applications = 0;
    $total_pages = 1;
}

// Get applications with job title (exclude drafts)
try {
    $data_where_sql = $where_sql;
    if (!empty($where_clauses)) {
        $data_where_sql .= " AND (aa.submission_status != 'Draft' OR aa.submission_status IS NULL)";
    } else {
        $data_where_sql = " WHERE (aa.submission_status != 'Draft' OR aa.submission_status IS NULL)";
    }

    $sql = "SELECT aa.*, jp.job_title, jp.job_code AS job_code, jp.kod_gred, st.name AS status_name, st.code AS status_code
            FROM application_application_main aa
            LEFT JOIN job_postings jp ON aa.job_id = jp.id
            LEFT JOIN application_statuses st ON st.code = aa.submission_status" .
            $data_where_sql .
            " ORDER BY aa.created_at DESC LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);
    $all_params = array_merge($params, [$per_page, $offset]);
    $stmt->execute($all_params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error
    log_admin_error('Database error in applications-list.php', [
        'error' => $e->getMessage(),
        'where_sql' => $where_sql,
        'params' => $params
    ]);
    $applications = [];
    $error_message = "Ralat pangkalan data: " . $e->getMessage();
}

// Get all jobs for filter dropdown with categorization
try {
    $jobs_stmt = $pdo->query("SELECT id, job_title, job_code, kod_gred, ad_date, ad_close_date FROM job_postings ORDER BY job_title");
    $jobs = $jobs_stmt->fetchAll(PDO::FETCH_ASSOC);
    $today = new DateTime(date('Y-m-d'));
    $published = [];
    $upcoming = [];
    $recently_closed = [];
    $expired = [];
    $job_labels_by_id = [];
    foreach ($jobs as $job) {
        $label = trim(($job['job_title'] ?? '') . ' (' . ($job['job_code'] ?? '') . ' ' . ($job['kod_gred'] ?? '') . ')');
        $job_labels_by_id[$job['id']] = $label;
        $ad_date = !empty($job['ad_date']) ? new DateTime($job['ad_date']) : null;
        $ad_close_date = !empty($job['ad_close_date']) ? new DateTime($job['ad_close_date']) : null;
        if ($ad_date && $ad_close_date) {
            if ($ad_date <= $today && $ad_close_date >= $today) {
                $published[] = $job;
            } elseif ($ad_date > $today) {
                $upcoming[] = $job;
            } elseif ($ad_close_date < $today) {
                $interval = $ad_close_date->diff($today);
                $days_closed = (int)$interval->format('%a');
                if ($days_closed <= 45) {
                    $recently_closed[] = $job;
                } else {
                    $expired[] = $job;
                }
            }
        } else {
            $expired[] = $job;
        }
    }
} catch (PDOException $e) {
    log_admin_error('Database error getting jobs', [
        'error' => $e->getMessage()
    ]);
    $jobs = [];
    $published = [];
    $upcoming = [];
    $recently_closed = [];
    $expired = [];
    $job_labels_by_id = [];
}

try {
    $st_stmt = $pdo->query("SELECT id, code, name, sort_order FROM application_statuses WHERE is_active = 1 ORDER BY sort_order, id");
    $status_options = $st_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_options = [];
}

// Page title
$page_title = "Senarai Permohonan Jawatan";
include 'templates/header.php';
?>

<div class="standard-container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Senarai Permohonan Dihantar</h1>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500">Total: <?php echo $total_applications; ?> permohonan</span>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($_SESSION['success']); ?>
    </div>
    <?php unset($_SESSION['success']); endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($_SESSION['error']); ?>
    </div>
    <?php unset($_SESSION['error']); endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <strong>Ralat:</strong> <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>
    
    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Applications -->
        <a href="applications-list.php" class="block transform hover:scale-105 transition-transform duration-200">
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 hover:shadow-xl h-24 flex items-center justify-center">
                <div class="text-center">
                    <p class="text-sm font-medium text-gray-500">Total Permohonan</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_applications); ?></p>
                    <p class="text-xs text-blue-600 mt-1">Klik untuk lihat semua</p>
                </div>
            </div>
        </a>
        
        <!-- Pending Applications -->
        <a href="applications-list.php?status=PENDING" class="block transform hover:scale-105 transition-transform duration-200">
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 hover:shadow-xl h-24 flex items-center justify-center">
                <div class="text-center">
                    <p class="text-sm font-medium text-gray-500">Belum Disemak</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php 
                        // Count from database excluding drafts
                        try {
                            $pending_sql = "SELECT COUNT(*) FROM application_application_main aa WHERE aa.submission_locked = 1 AND (aa.submission_status != 'Draft' OR aa.submission_status IS NULL) AND aa.reviewed_at IS NULL AND aa.approved_at IS NULL";
                            $pending_stmt = $pdo->query($pending_sql);
                            $pending_count = $pending_stmt->fetchColumn();
                            echo number_format($pending_count);
                        } catch (Exception $e) {
                            echo '0';
                        }
                        ?>
                    </p>
                    <p class="text-xs text-yellow-600 mt-1">Klik untuk tapis</p>
                </div>
            </div>
        </a>
        
        <!-- Reviewed Applications -->
        <a href="applications-list.php?status=REVIEWED" class="block transform hover:scale-105 transition-transform duration-200">
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 hover:shadow-xl h-24 flex items-center justify-center">
                <div class="text-center">
                    <p class="text-sm font-medium text-gray-500">Telah Disemak</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php 
                        // Count from database excluding drafts
                        try {
                            $reviewed_sql = "SELECT COUNT(*) FROM application_application_main aa WHERE aa.submission_locked = 1 AND (aa.submission_status != 'Draft' OR aa.submission_status IS NULL) AND aa.reviewed_at IS NOT NULL AND aa.approved_at IS NULL";
                            $reviewed_stmt = $pdo->query($reviewed_sql);
                            $reviewed_count = $reviewed_stmt->fetchColumn();
                            echo number_format($reviewed_count);
                        } catch (Exception $e) {
                            echo '0';
                        }
                        ?>
                    </p>
                    <p class="text-xs text-blue-600 mt-1">Klik untuk tapis</p>
                </div>
            </div>
        </a>
        
        <!-- Approved Applications -->
        <a href="applications-list.php?status=APPROVED" class="block transform hover:scale-105 transition-transform duration-200">
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 hover:shadow-xl h-24 flex items-center justify-center">
                <div class="text-center">
                    <p class="text-sm font-medium text-gray-500">Diluluskan</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php 
                        // Count from database excluding drafts
                        try {
                            $approved_sql = "SELECT COUNT(*) FROM application_application_main aa WHERE aa.submission_locked = 1 AND (aa.submission_status != 'Draft' OR aa.submission_status IS NULL) AND aa.approved_at IS NOT NULL";
                            $approved_stmt = $pdo->query($approved_sql);
                            $approved_count = $approved_stmt->fetchColumn();
                            echo number_format($approved_count);
                        } catch (Exception $e) {
                            echo '0';
                        }
                        ?>
                    </p>
                    <p class="text-xs text-green-600 mt-1">Klik untuk tapis</p>
                </div>
            </div>
        </a>
    </div>

    <div class="flex justify-between items-center mb-6">
        <form id="bulk-form" method="post" action="applications-list.php" class="flex items-center space-x-2">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filters['status'] ?? ''); ?>">
            <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($filters['job_id'] ?? ''); ?>">
            <select name="bulk_action" class="px-3 py-2 border rounded">
                <option value="">Tindakan Status</option>
                <?php if (!empty($status_options)): ?>
                    <?php foreach ($status_options as $st): ?>
                        <option value="<?php echo htmlspecialchars($st['code']); ?>"><?php echo htmlspecialchars($st['name']); ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="PENDING">Permohonan Diterima</option>
                    <option value="SCREENING">Sedang Ditapis</option>
                    <option value="TEST_INTERVIEW">Dipanggil Ujian / Temu Duga</option>
                    <option value="AWAITING_RESULT">Menunggu Keputusan</option>
                    <option value="PASSED_INTERVIEW">Lulus Temu Duga</option>
                    <option value="OFFER_APPOINTMENT">Tawaran Pelantikan</option>
                    <option value="APPOINTED">Dilantik</option>
                <?php endif; ?>
            </select>
            <input type="text" name="bulk_note" id="bulk-note" class="px-3 py-2 border rounded w-64" placeholder="Nota (wajib)">
            <label class="inline-flex items-center space-x-2"><input type="checkbox" name="send_status_email" value="1" <?php echo !empty($config['status_email_enabled']) ? 'checked' : ''; ?>> <span>Hantar emel</span></label>
            <button type="button" id="bulk-apply" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">Laksana</button>
        </form>
        <div class="flex items-center space-x-2">
            <a href="draft-applications.php" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">Permohonan Draf</a>
            <a href="applications-list.php?export=csv<?php echo !empty($filters) ? '&' . http_build_query(array_filter($filters)) : ''; ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Export CSV</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Tapis Permohonan</h2>
        <form method="get" class="flex flex-wrap gap-4">
            <div class="w-full md:w-1/3">
                <label class="block text-gray-700 mb-2">Carian</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" 
                       placeholder="Nama / No. IC / Email / No. Rujukan" 
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="w-full md:w-1/4">
                <label class="block text-gray-700 mb-2">Jawatan</label>
                <?php 
                    $selected_job_id = $filters['job_id'] ?? '';
                    $selected_label = '';
                    if ($selected_job_id) {
                        foreach ($published as $j) { if ((string)$j['id'] === (string)$selected_job_id) { $selected_label = 'Aktif: ' . $job_labels_by_id[$j['id']]; break; } }
                        if ($selected_label === '') { foreach ($upcoming as $j) { if ((string)$j['id'] === (string)$selected_job_id) { $selected_label = 'Akan Datang: ' . $job_labels_by_id[$j['id']]; break; } } }
                        if ($selected_label === '') { foreach ($recently_closed as $j) { if ((string)$j['id'] === (string)$selected_job_id) { $selected_label = 'Ditutup ≤45 hari: ' . $job_labels_by_id[$j['id']]; break; } } }
                        if ($selected_label === '') { foreach ($expired as $j) { if ((string)$j['id'] === (string)$selected_job_id) { $selected_label = 'Ditutup >45 hari: ' . $job_labels_by_id[$j['id']]; break; } } }
                    }
                ?>
                <input type="text" id="job_search" name="job_search" list="jobs-datalist" placeholder="Taip untuk cari jawatan" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($selected_label); ?>">
                <input type="hidden" name="job_id" id="job_id_hidden" value="<?php echo htmlspecialchars($selected_job_id); ?>">
                <datalist id="jobs-datalist">
                    <?php foreach ($published as $job): ?>
                        <option value="<?php echo htmlspecialchars('Aktif: ' . $job_labels_by_id[$job['id']]); ?>"></option>
                    <?php endforeach; ?>
                    <?php foreach ($upcoming as $job): ?>
                        <option value="<?php echo htmlspecialchars('Akan Datang: ' . $job_labels_by_id[$job['id']]); ?>"></option>
                    <?php endforeach; ?>
                    <?php foreach ($recently_closed as $job): ?>
                        <option value="<?php echo htmlspecialchars('Ditutup ≤45 hari: ' . $job_labels_by_id[$job['id']]); ?>"></option>
                    <?php endforeach; ?>
                    <?php foreach ($expired as $job): ?>
                        <option value="<?php echo htmlspecialchars('Ditutup >45 hari: ' . $job_labels_by_id[$job['id']]); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="w-full md:w-1/5">
                <label class="block text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-3 py-2 border rounded">
                    <option value="">Semua Status</option>
                    <?php if (!empty($status_options)): ?>
                        <?php foreach ($status_options as $st): ?>
                            <option value="<?php echo htmlspecialchars($st['code']); ?>" <?php echo (isset($filters['status']) && $filters['status'] == $st['code']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st['name']); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="PENDING" <?php echo (isset($filters['status']) && $filters['status'] == 'PENDING') ? 'selected' : ''; ?>>Permohonan Diterima</option>
                        <option value="SCREENING" <?php echo (isset($filters['status']) && $filters['status'] == 'SCREENING') ? 'selected' : ''; ?>>Sedang Ditapis</option>
                        <option value="TEST_INTERVIEW" <?php echo (isset($filters['status']) && $filters['status'] == 'TEST_INTERVIEW') ? 'selected' : ''; ?>>Dipanggil Ujian / Temu Duga</option>
                        <option value="AWAITING_RESULT" <?php echo (isset($filters['status']) && $filters['status'] == 'AWAITING_RESULT') ? 'selected' : ''; ?>>Menunggu Keputusan</option>
                        <option value="PASSED_INTERVIEW" <?php echo (isset($filters['status']) && $filters['status'] == 'PASSED_INTERVIEW') ? 'selected' : ''; ?>>Lulus Temu Duga</option>
                        <option value="OFFER_APPOINTMENT" <?php echo (isset($filters['status']) && $filters['status'] == 'OFFER_APPOINTMENT') ? 'selected' : ''; ?>>Tawaran Pelantikan</option>
                        <option value="APPOINTED" <?php echo (isset($filters['status']) && $filters['status'] == 'APPOINTED') ? 'selected' : ''; ?>>Dilantik</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="w-full md:w-auto flex items-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tapis</button>
                <a href="applications-list.php" class="ml-2 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Applications Table Layout -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
        <?php if (empty($applications)): ?>
        <div class="p-8 text-center text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tiada Permohonan</h3>
            <p class="mt-1 text-sm text-gray-500">Tiada permohonan jawatan ditemui.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><input type="checkbox" id="select-all"></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pemohon</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarikh Mohon</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($applications as $application): 
                        $status_class = '';
                        $status_text = '';
                        if (!empty($application['status_name'])) {
                            $code = strtoupper($application['status_code'] ?? '');
                            if ($code === 'RECEIVED') { $status_class = 'bg-blue-100 text-blue-800 border-blue-200'; }
                            elseif ($code === 'SCREENING') { $status_class = 'bg-yellow-100 text-yellow-800 border-yellow-200'; }
                            elseif ($code === 'TEST_INTERVIEW') { $status_class = 'bg-indigo-100 text-indigo-800 border-indigo-200'; }
                            elseif ($code === 'AWAITING_RESULT') { $status_class = 'bg-blue-100 text-blue-800 border-blue-200'; }
                            elseif ($code === 'PASSED_INTERVIEW') { $status_class = 'bg-green-100 text-green-800 border-green-200'; }
                            elseif ($code === 'OFFER_APPOINTMENT') { $status_class = 'bg-teal-100 text-teal-800 border-teal-200'; }
                            elseif ($code === 'APPOINTED') { $status_class = 'bg-green-100 text-green-800 border-green-200'; }
                            else { $status_class = 'bg-gray-100 text-gray-800 border-gray-200'; }
                            $status_text = $application['status_name'];
                        } else {
                            if (!empty($application['approved_at'])) { $status_class = 'bg-green-100 text-green-800 border-green-200'; $status_text = 'Diluluskan'; }
                            elseif (!empty($application['reviewed_at'])) { $status_class = 'bg-blue-100 text-blue-800 border-blue-200'; $status_text = 'Telah Disemak'; }
                            else { $status_class = 'bg-yellow-100 text-yellow-800 border-yellow-200'; $status_text = 'Belum Disemak'; }
                        }
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 whitespace-nowrap text-sm"><input type="checkbox" class="row-select" value="<?php echo (int)$application['id']; ?>"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($application['id']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <?php if (!empty($application['gambar_passport_path']) && file_exists('../' . $application['gambar_passport_path'])): ?>
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full overflow-hidden">
                                        <img src="../<?php echo htmlspecialchars($application['gambar_passport_path']); ?>" alt="Passport Photo" class="h-full w-full object-cover">
                                    </div>
                                <?php else: ?>
                                    <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 font-semibold">
                                            <?php echo strtoupper(substr($application['nama_penuh'], 0, 1)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($application['nama_penuh']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($application['email']); ?>
                                    </div>
                                    <div class="text-sm text-gray-400">
                                        <small>Ref: <?php echo htmlspecialchars($application['application_reference']); ?></small>
                                    </div>
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($application['job_title'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars(($application['job_code'] ?? '') . ' ' . ($application['kod_gred'] ?? '')); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($application['nombor_ic']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php 
                                $date_str = $application['updated_at'] ?? $application['created_at'] ?? null;
                                echo $date_str ? date('d-M-Y', strtotime($date_str)) : '-';
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="application-view.php?id=<?php echo $application['id']; ?>" class="text-blue-600 hover:text-blue-900">Lihat</a>
                                <button onclick="updateStatus(<?php echo $application['id']; ?>)" class="text-gray-600 hover:text-gray-900 ml-2">Status</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-6 flex justify-center">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($filters) ? '&' . http_build_query(array_filter($filters)) : ''; ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Previous</span>
                &laquo; Sebelumnya
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                    <?php echo $i; ?>
                </span>
                <?php else: ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($filters) ? '&' . http_build_query(array_filter($filters)) : ''; ?>" 
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <?php echo $i; ?>
                </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($filters) ? '&' . http_build_query(array_filter($filters)) : ''; ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Next</span>
                Seterusnya &raquo;
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
function updateStatus(applicationId) {
    // Redirect to application view page with status update focus
    window.location.href = 'application-view.php?id=' + applicationId + '#status';
}

var jobIndex = {};
try {
    var dataEl = document.getElementById('job-index-data');
    if (dataEl) { jobIndex = JSON.parse(dataEl.textContent || '{}'); }
} catch (e) {}

var jobSearchInput = document.getElementById('job_search');
var jobHidden = document.getElementById('job_id_hidden');
if (jobSearchInput && jobHidden) {
    jobSearchInput.addEventListener('input', function() {
        var v = this.value;
        if (jobIndex[v]) {
            jobHidden.value = jobIndex[v];
        } else {
            jobHidden.value = '';
        }
    });
}

// Bulk selection and action handlers
var selectAll = document.getElementById('select-all');
if (selectAll) {
    selectAll.addEventListener('change', function() {
        var rows = document.querySelectorAll('.row-select');
        rows.forEach(function(cb){ cb.checked = selectAll.checked; });
    });
}

var bulkApply = document.getElementById('bulk-apply');
if (bulkApply) {
    bulkApply.addEventListener('click', function() {
        var form = document.getElementById('bulk-form');
        var actionSel = form.querySelector('select[name="bulk_action"]');
        var noteInput = form.querySelector('#bulk-note');
        var actionVal = (actionSel && actionSel.value || '').trim();
        var noteVal = (noteInput && noteInput.value || '').trim();
        if (!actionVal) { alert('Sila pilih tindakan status.'); return; }
        if (!noteVal) { alert('Sila masukkan nota untuk tindakan status.'); return; }
        var existing = form.querySelectorAll('input[name="selected_ids[]"]');
        existing.forEach(function(el){ el.remove(); });
        var ids = Array.from(document.querySelectorAll('.row-select:checked')).map(function(el){ return el.value; });
        if (ids.length === 0) { alert('Sila pilih sekurang-kurangnya satu permohonan.'); return; }
        ids.forEach(function(id){
            var h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'selected_ids[]';
            h.value = id;
            form.appendChild(h);
        });
        form.submit();
    });
}

</script>

<?php 
// Provide data for job index in a non-rendered JSON script block to avoid visible code
$search_map = [];
foreach ($published as $job) { $search_map['Aktif: ' . $job_labels_by_id[$job['id']]] = (string)$job['id']; }
foreach ($upcoming as $job) { $search_map['Akan Datang: ' . $job_labels_by_id[$job['id']]] = (string)$job['id']; }
foreach ($recently_closed as $job) { $search_map['Ditutup ≤45 hari: ' . $job_labels_by_id[$job['id']]] = (string)$job['id']; }
foreach ($expired as $job) { $search_map['Ditutup >45 hari: ' . $job_labels_by_id[$job['id']]] = (string)$job['id']; }
?>
<script type="application/json" id="job-index-data"><?php echo json_encode($search_map, JSON_UNESCAPED_UNICODE); ?></script>

<?php include 'templates/footer.php'; ?>
