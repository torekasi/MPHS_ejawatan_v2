<?php
session_start();
// Centralized bootstrap (logging, DB helper, global handlers)
require_once '../includes/bootstrap.php';
// Keep lightweight admin error logger for logError() helper used below
require_once 'includes/error_handler.php';
require_once 'auth.php';
// Do NOT include admin_logger.php directly; it's auto-loaded by bootstrap in admin context

// Get database connection from main config
$config = require_once '../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

// Log database config to error log
$debug_info = "\nDatabase Host: " . ($config['db_host'] ?? 'Not set');
$debug_info .= "\nDatabase Name: " . ($config['db_name'] ?? 'Not set');
$debug_info .= "\nDatabase User: " . ($config['db_user'] ?? 'Not set');
$debug_info .= "\nPDO Connection: " . ($pdo ? 'Success' : 'Failed');
$debug_info .= "\nConnection Method: " . ($result['connection_method'] ?? 'Unknown');
if (isset($result['error'])) {
    $debug_info .= "\nConnection Error: " . $result['error'];
}
logError($debug_info, 'DEBUG_INFO');

if (!$pdo) {
    logError('Database connection not available', 'DATABASE_ERROR');
    include 'templates/header.php';
    echo '<div class="standard-container mx-auto p-6">';
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">';
    echo '<p class="font-bold">Ralat Sambungan Pangkalan Data</p>';
    echo '<p>Sambungan ke pangkalan data tidak tersedia. Sila cuba lagi kemudian atau hubungi pentadbir sistem.</p>';
    echo '</div>';
    echo '</div>';
    include 'templates/footer.php';
    exit;
}

// Fetch all jobs
// Query and log job_postings table info
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'job_postings'")->fetchAll();
    $debug_info = "\njob_postings table exists: " . (!empty($tables) ? 'Yes' : 'No');
    
    if (!empty($tables)) {
        // Check table structure
        $columns = $pdo->query("SHOW COLUMNS FROM job_postings")->fetchAll(PDO::FETCH_COLUMN);
        $debug_info .= "\nTable columns: " . implode(', ', $columns);
        
        // Count records
        $count = $pdo->query("SELECT COUNT(*) FROM job_postings")->fetchColumn();
        $debug_info .= "\nTotal records: " . $count;
    }
    
    // Execute the main query
    $sql = 'SELECT * FROM job_postings ORDER BY id DESC';
    $debug_info .= "\nSQL Query: " . $sql;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug_info .= "\nRecords fetched: " . count($jobs);
    
    // Log first record if available
    if (!empty($jobs)) {
        $debug_info .= "\nFirst record: " . print_r($jobs[0], true);
    }
    
    logError($debug_info, 'DEBUG_INFO');
} catch (PDOException $e) {
    logError('PDO Error: ' . $e->getMessage(), 'DATABASE_ERROR');
    $jobs = [];
}

// Log if no jobs found
if (empty($jobs)) {
    logError('No jobs found. jobs count = 0', 'DEBUG_INFO');
}

// Debug info - log instead of display
logError("Today's Date: " . date('Y-m-d'), 'DEBUG_INFO');

// Proper categorisation based on dates
$today = new DateTime(date('Y-m-d'));
$published = [];
$upcoming = [];
$recently_closed = [];
$expired = [];

// Process job categorization first, then log the counts
foreach ($jobs as $job) {
    $ad_date = new DateTime($job['ad_date']);
    $ad_close_date = new DateTime($job['ad_close_date']);
    if ($ad_date <= $today && $ad_close_date >= $today) {
        $published[] = $job;
    } elseif ($ad_date > $today) {
        $upcoming[] = $job;
    } elseif ($ad_close_date < $today) {
        // Calculate days since closed
        $interval = $ad_close_date->diff($today);
        $days_closed = (int)$interval->format('%a');
        if ($days_closed <= 45) {
            $recently_closed[] = $job;
        } else {
            $expired[] = $job;
        }
    }
}

// Now log the counts after arrays are populated
log_admin_action('Viewed job listings', 'OTHER', 'job_list', null, [
    'published_count' => count($published),
    'upcoming_count' => count($upcoming),
    'recently_closed_count' => count($recently_closed),
    'expired_count' => count($expired)
]);


include 'templates/header.php';

// Check if we have a notification to display
$notification = null;
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    // Clear the notification from session after retrieving it
    unset($_SESSION['notification']);
}
?>
<?php if ($notification): ?>
<div id="notification" class="fixed top-4 right-4 z-50 max-w-md p-4 rounded-lg shadow-lg <?php echo $notification['type'] === 'success' ? 'bg-green-100 border-green-500' : 'bg-red-100 border-red-500'; ?> border-l-4">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <?php if ($notification['type'] === 'success'): ?>
                <!-- Success icon -->
                <svg class="h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            <?php else: ?>
                <!-- Error icon -->
                <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            <?php endif; ?>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium <?php echo $notification['type'] === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                <?php echo htmlspecialchars($notification['message']); ?>
            </p>
        </div>
        <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
                <button onclick="dismissNotificationAndAck();" class="inline-flex rounded-md p-1.5 <?php echo $notification['type'] === 'success' ? 'text-green-500 hover:bg-green-200' : 'text-red-500 hover:bg-red-200'; ?> focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    // Inject deleted job details from server-side notification (if available)
    const deletedNotifJob = <?php echo json_encode($notification['deleted_job'] ?? null); ?>;

    // Dismiss notification and send acknowledgment logging
    function dismissNotificationAndAck() {
        const notificationEl = document.getElementById('notification');
        if (notificationEl) {
            notificationEl.style.display = 'none';
        }
        if (deletedNotifJob && deletedNotifJob.id) {
            const ackData = new FormData();
            ackData.append('action', 'delete_ack');
            ackData.append('id', deletedNotifJob.id);
            if (deletedNotifJob.job_code) ackData.append('job_code', deletedNotifJob.job_code);
            ackData.append('job_title', deletedNotifJob.job_title || '');
            fetch('job-delete.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: ackData
            }).catch(() => { /* ignore ack errors */ });
        }
    }

    // Auto-hide notification after 5 seconds
    setTimeout(function() {
        const notification = document.getElementById('notification');
        if (notification) {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.5s';
            setTimeout(function() {
                notification.style.display = 'none';
            }, 500);
        }
    }, 5000);
</script>
<?php endif; ?>

<div class="standard-container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Senarai Jawatan Kosong</h1>
        <a href="job-create.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
            Tambah Jawatan
        </a>
    </div>
    <?php if (count($published) === 0 && count($upcoming) === 0 && count($recently_closed) === 0 && count($expired) === 0): ?>
    <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-600">
        <svg class="w-10 h-10 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h6" />
        </svg>
        <p class="text-lg">Tiada jawatan untuk dipaparkan buat masa ini.</p>
        <p class="text-sm mt-1">Sila klik "Tambah Jawatan" untuk menambah jawatan baharu.</p>
    </div>
    <?php endif; ?>
    
    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Total Jawatan</p>
                    <p class="text-lg font-semibold text-gray-900"><?php echo count($jobs); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Sedang Diterbitkan</p>
                    <p class="text-lg font-semibold text-gray-900"><?php echo count($published); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Akan Datang</p>
                    <p class="text-lg font-semibold text-gray-900"><?php echo count($upcoming); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Telah Ditutup</p>
                    <p class="text-lg font-semibold text-gray-900"><?php echo count($expired); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (count($published) > 0): ?>
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Sedang Diterbitkan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarikh Iklan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarikh Tutup</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($published as $job): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['job_title'] ?? ''); ?></div>
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($job['job_code'] ?? 'N/A'); ?>
                                <?php if (!empty($job['kod_gred'])): ?>
                                 - Gred: <?php echo htmlspecialchars($job['kod_gred']); ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($job['ad_date'] ?? ''); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($job['ad_close_date'] ?? ''); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="job-view.php?job_code=<?php echo urlencode($job['job_code']); ?>" class="text-green-600 hover:text-green-900">Lihat</a>
                                <a href="job-edit.php?job_code=<?php echo urlencode($job['job_code']); ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <button onclick="showDeleteModal('<?php echo htmlspecialchars($job['job_code']); ?>', '<?php echo htmlspecialchars($job['job_title']); ?>', 'job_code')" class="text-red-600 hover:text-red-900">Padam</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php if (count($upcoming) > 0): ?>
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Akan Datang</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarikh Iklan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarikh Tutup</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($upcoming as $job): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['job_title'] ?? ''); ?></div>
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($job['job_code'] ?? 'N/A'); ?>
                                <?php if (!empty($job['kod_gred'])): ?>
                                 - Gred: <?php echo htmlspecialchars($job['kod_gred']); ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($job['ad_date'] ?? ''); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($job['ad_close_date'] ?? ''); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="job-view.php?job_code=<?php echo urlencode($job['job_code']); ?>" class="text-green-600 hover:text-green-900">Lihat</a>
                                <a href="job-edit.php?job_code=<?php echo urlencode($job['job_code']); ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <button onclick="showDeleteModal('<?php echo htmlspecialchars($job['job_code']); ?>', '<?php echo htmlspecialchars($job['job_title']); ?>', 'job_code')" class="text-red-600 hover:text-red-900">Padam</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php if (count($recently_closed) > 0): ?>
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-8">
        <div class="bg-yellow-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-yellow-700 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                Jawatan Baru Ditutup (≤ 45 hari)
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full border text-sm">
            <thead>
                <tr class="bg-blue-600">
                    <th class="px-3 py-2 w-64 text-white font-medium">NAMA JAWATAN</th>
                    <th class="px-3 py-2 w-32 text-white font-medium">Tarikh Iklan</th>
                    <th class="px-3 py-2 w-32 text-white font-medium">Tarikh Tutup</th>
                    <th class="px-3 py-2 w-32 text-white font-medium">Tindakan</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recently_closed as $job): ?>
                <tr>
                    <td class="px-3 py-2 w-64">
                        <div class="font-medium"><?php echo htmlspecialchars(strtoupper($job['job_title'] ?? '')); ?></div>
                        <div class="text-xs text-gray-600">
                            <?php echo htmlspecialchars($job['job_code'] ?? 'N/A'); ?>
                            <?php if (!empty($job['kod_gred'])): ?>
                             - <?php echo htmlspecialchars(strtoupper($job['kod_gred'])); ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-3 py-2 w-32 whitespace-nowrap"><?php echo htmlspecialchars($job['ad_date'] ?? ''); ?></td>
                    <td class="px-3 py-2 w-32 whitespace-nowrap"><?php echo htmlspecialchars($job['ad_close_date'] ?? ''); ?></td>
                    <td class="px-3 py-2 w-32 whitespace-nowrap">
                        <a href="job-view.php?job_code=<?php echo urlencode($job['job_code']); ?>" class="text-green-700 hover:underline">Lihat</a> |
                        <a href="job-edit.php?job_code=<?php echo urlencode($job['job_code']); ?>" class="text-blue-700 hover:underline">Edit</a> |
                        <button onclick="showDeleteModal('<?php echo htmlspecialchars($job['job_code']); ?>', '<?php echo htmlspecialchars($job['job_title']); ?>', 'job_code')" class="text-red-700 hover:underline">Padam</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (count($expired) > 0): ?>
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-8">
        <div class="bg-red-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-red-700 flex items-center">
                <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Jawatan Telah Ditutup (> 45 hari)
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full border text-sm">
            <thead>
                <tr class="bg-blue-600">
                    <th class="px-3 py-2 text-white font-medium">NAMA JAWATAN</th>
                    <th class="px-3 py-2 text-white font-medium">Tarikh Iklan</th>
                    <th class="px-3 py-2 text-white font-medium">Tarikh Tutup</th>
                    <th class="px-3 py-2 text-white font-medium">Tindakan</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($expired as $job): ?>
                <tr>
                    <td class="px-3 py-2 w-64">
                        <div class="font-medium"><?php echo htmlspecialchars(strtoupper($job['job_title'] ?? '')); ?></div>
                        <div class="text-xs text-gray-600">
                            <?php echo htmlspecialchars('JOB-' . str_pad($job['id'] ?? '', 6, '0', STR_PAD_LEFT)); ?>
                            <?php if (!empty($job['kod_gred'])): ?>
                             - <?php echo htmlspecialchars(strtoupper($job['kod_gred'])); ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-3 py-2 w-32 whitespace-nowrap"><?php echo htmlspecialchars($job['ad_date'] ?? ''); ?></td>
                    <td class="px-3 py-2 w-32 whitespace-nowrap"><?php echo htmlspecialchars($job['ad_close_date'] ?? ''); ?></td>
                    <td class="px-3 py-2 w-32 whitespace-nowrap">
                        <a href="job-view.php?id=<?php echo urlencode($job['id']); ?>" class="text-green-700 hover:underline">Lihat</a> |
                        <a href="job-edit.php?id=<?php echo urlencode($job['id']); ?>" class="text-blue-700 hover:underline">Edit</a> |
                        <button onclick="showDeleteModal('<?php echo htmlspecialchars($job['id']); ?>', '<?php echo htmlspecialchars($job['job_title']); ?>', 'id')" class="text-red-700 hover:underline">Padam</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Padam Jawatan</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Adakah anda pasti untuk memadam jawatan ini?
                </p>
                <p class="text-sm font-semibold text-gray-700 mt-2" id="jobTitle"></p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmDelete" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Padam
                </button>
                <button id="cancelDelete" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-24 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let deleteJobId = '';
let deleteJobType = '';

function showDeleteModal(jobId, jobTitle, jobType) {
    deleteJobId = jobId;
    deleteJobType = jobType;
    document.getElementById('jobTitle').textContent = jobTitle;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    deleteJobId = '';
    deleteJobType = '';
}

document.getElementById('confirmDelete').addEventListener('click', function() {
    if (deleteJobId && deleteJobType) {
        // Show loading state
        this.textContent = 'Memproses...';
        this.disabled = true;
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('job_id', deleteJobId);
        formData.append('job_type', deleteJobType);
        
        // Send AJAX request
        fetch('job-delete.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message and, after OK, send acknowledgment log then reload
                const deleted = data.deleted_job || null;
                alert('Jawatan berjaya dipadam!');
                if (deleted && deleted.id) {
                    const ackData = new FormData();
                    ackData.append('action', 'delete_ack');
                    ackData.append('id', deleted.id);
                    if (deleted.job_code) ackData.append('job_code', deleted.job_code);
                    ackData.append('job_title', deleted.job_title || '');
                    fetch('job-delete.php', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: ackData
                    }).catch(() => { /* ignore ack errors */ });
                }
                location.reload();
            } else {
                // Show error message
                alert('Ralat: ' + (data.message || 'Tidak dapat memadam jawatan'));
                this.textContent = 'Padam';
                this.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ralat sistem berlaku');
            this.textContent = 'Padam';
            this.disabled = false;
        });
    }
});

document.getElementById('cancelDelete').addEventListener('click', hideDeleteModal);

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideDeleteModal();
    }
});
</script>

<?php include 'templates/footer.php'; ?>
