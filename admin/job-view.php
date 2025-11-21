<?php
session_start();
// Centralized bootstrap (logging, DB helper, global handlers)
require_once '../includes/bootstrap.php';
require_once 'includes/error_handler.php';
require_once 'auth.php';
// Get database connection from main config
$config = require_once '../config.php';

// Check for job_code parameter first, then fallback to id
if (isset($_GET['job_code']) && !empty($_GET['job_code'])) {
    $job_code = $_GET['job_code'];
    logError('Job Code Parameter in job-view.php: ' . $job_code, 'DEBUG_INFO');
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    logError('ID Parameter in job-view.php: ' . $id, 'DEBUG_INFO');
} else {
    logError('No valid job_code or ID parameter provided in job-view.php', 'DEBUG_INFO');
    header('Location: job-list.php');
    exit;
}

try {
    // Get database connection using the merged function
    $result = get_database_connection($config);
    $pdo = $result['pdo'];
    
    if (!$pdo) {
        logError('Database connection not available in job-view.php', 'DATABASE_ERROR');
        include 'templates/header.php';
        echo '<div class="max-w-7xl mx-auto p-6">';
        echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">';
        echo '<p class="font-bold">Ralat Sambungan Pangkalan Data</p>';
        echo '<p>Sambungan ke pangkalan data tidak tersedia. Sila cuba lagi kemudian atau hubungi pentadbir sistem.</p>';
        echo '</div>';
        echo '</div>';
        include 'templates/footer.php';
        exit;
    }
    
    // Query based on available parameter
    if (isset($job_code)) {
        $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE job_code = ? LIMIT 1');
        $stmt->execute([$job_code]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
    }
    
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$job) {
        header('Location: job-list.php');
        exit;
    }
    
    // Format job ID for display and logging
    // Use job_code if available, otherwise use formatted job_id
    $formatted_job_id = !empty($job['job_code']) ? $job['job_code'] : 'JOB-' . str_pad($job['id'], 6, '0', STR_PAD_LEFT);
    
    // Log the job view action (use integer entity_id, keep formatted code in details)
    log_admin_action('Viewed job details', 'OTHER', 'job', $job['id'], ['job_id' => $formatted_job_id, 'job_title' => $job['job_title']]);

    // Calculate application stats for this job
    $application_stats = [];
    try {
        $stats_stmt = $pdo->prepare("SELECT 
            SUM(CASE WHEN submission_locked = 1 THEN 1 ELSE 0 END) as total_submitted,
            SUM(CASE WHEN submission_locked = 0 OR submission_locked IS NULL THEN 1 ELSE 0 END) as draft_total,
            SUM(CASE WHEN submission_locked = 1 AND status IN ('PENDING','pending') THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN submission_locked = 1 AND status IN ('REVIEWED','SHORTLISTED','shortlisted') THEN 1 ELSE 0 END) as reviewed,
            SUM(CASE WHEN submission_locked = 1 AND status IN ('APPROVED','approved') THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN submission_locked = 1 AND status IN ('REJECTED','rejected') THEN 1 ELSE 0 END) as rejected
            FROM application_application_main WHERE job_id = ?");
        $stats_stmt->execute([$job['id']]);
        $application_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError('Error fetching application counts in job-view.php: ' . $e->getMessage(), 'DATABASE_ERROR');
        $application_stats = [];
    }
    $total_submitted = (int)($application_stats['total_submitted'] ?? 0);
    $pending = (int)($application_stats['pending'] ?? 0);
    $reviewed = (int)($application_stats['reviewed'] ?? 0);
    $approved = (int)($application_stats['approved'] ?? 0);
    $rejected = (int)($application_stats['rejected'] ?? 0);
    $draft_total = (int)($application_stats['draft_total'] ?? 0);
} catch (Exception $e) {
    die('Ralat pangkalan data: ' . htmlspecialchars($e->getMessage()));
}

include 'templates/header.php';
?>
<div class="standard-container mx-auto bg-white rounded-xl shadow-lg p-8 mt-10 border border-blue-100">
    <div class="flex items-center gap-4 mb-6">
        <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
            <svg class="w-8 h-8 text-blue-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 01-8 0m8 0a4 4 0 00-8 0m8 0V5a4 4 0 00-8 0v2m8 0v2a4 4 0 01-8 0V7m8 0h2a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V9a2 2 0 012-2h2"></path></svg>
        </div>
        <div>
            <h1 class="text-3xl font-bold text-blue-900 mb-1"><?php echo htmlspecialchars($job['job_title']); ?></h1>
            <div class="text-sm text-gray-600 font-mono">Kod Jawatan: <span class="font-semibold text-blue-700"><?php echo htmlspecialchars($job['job_code']); ?></span></div>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <div class="text-xs text-gray-500 mb-1">Tarikh Iklan</div>
            <div class="font-semibold text-blue-800"><?php echo htmlspecialchars($job['ad_date']); ?></div>
        </div>
        <div>
            <div class="text-xs text-gray-500 mb-1">Tarikh Tutup</div>
            <div class="font-semibold text-red-700"><?php echo htmlspecialchars($job['ad_close_date']); ?></div>
        </div>
        <div>
            <div class="text-xs text-gray-500 mb-1">Edaran Iklan</div>
            <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($job['edaran_iklan'] ?? ''); ?></div>
        </div>
        <div>
            <div class="text-xs text-gray-500 mb-1">Kod Jawatan & Gred</div>
            <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($job['kod_gred'] ?? ''); ?></div>
        </div>
        <div>
            <div class="text-xs text-gray-500 mb-1">Gaji Minimum</div>
            <div class="font-semibold text-green-700">RM <?php echo number_format($job['salary_min'], 2); ?></div>
        </div>
        <div>
            <div class="text-xs text-gray-500 mb-1">Gaji Maksimum</div>
            <div class="font-semibold text-green-700">RM <?php echo number_format($job['salary_max'], 2); ?></div>
        </div>
    </div>
    <div class="mb-8">
        <div class="text-xs text-gray-500 mb-1">Syarat & Kelayakan Lantikan</div>
        <div class="prose prose-blue prose-sm max-w-none bg-gray-50 rounded p-4 border border-gray-200" style="min-height:80px">
            <?php
            // Display the requirements with proper HTML rendering
            echo $job['requirements'];
            ?>
        </div>
        <style>
        /* Enhanced styling for lists and typography */
        .prose ul { list-style-type: disc !important; margin: 0.8em 0 !important; padding-left: 1.7em !important; }
        .prose ol { list-style-type: decimal !important; margin: 0.8em 0 !important; padding-left: 1.7em !important; }
        .prose li { display: list-item !important; margin-bottom: 0.4em !important; }
        .prose li::marker { color: #1e40af !important; }
        .prose ol li::marker { font-weight: bold !important; }
        .prose p { margin-bottom: 0.8em !important; }
        .prose h3 { font-size: 1.3em !important; font-weight: bold !important; margin-top: 1.4em !important; margin-bottom: 0.7em !important; color: #1e3a8a !important; }
        .prose h4 { font-size: 1.1em !important; font-weight: bold !important; margin-top: 1.2em !important; margin-bottom: 0.6em !important; color: #1e40af !important; }
        .prose br { display: block !important; margin-bottom: 0.4em !important; }
        </style>
    </div>

    <!-- Application Stats Cards placed above CTAs -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Statistik Permohonan</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Total Submitted -->
            <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>" class="block bg-white rounded-lg shadow-md border border-gray-200 p-4 hover:shadow-lg hover:border-blue-300 transition-all duration-200 transform hover:scale-105">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $total_submitted; ?></div>
                    <div class="text-sm text-gray-600 mt-1">Jumlah Dihantar</div>
                </div>
            </a>
            
            <!-- Pending -->
            <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>&status=PENDING" class="block bg-white rounded-lg shadow-md border border-gray-200 p-4 hover:shadow-lg hover:border-yellow-300 transition-all duration-200 transform hover:scale-105">
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600"><?php echo $pending; ?></div>
                    <div class="text-sm text-gray-600 mt-1">Belum Disemak</div>
                </div>
            </a>
            
            <!-- Reviewed -->
            <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>&status=REVIEWED" class="block bg-white rounded-lg shadow-md border border-gray-200 p-4 hover:shadow-lg hover:border-green-300 transition-all duration-200 transform hover:scale-105">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $reviewed; ?></div>
                    <div class="text-sm text-gray-600 mt-1">Telah Disemak</div>
                </div>
            </a>
            
            <!-- Approved -->
            <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>&status=APPROVED" class="block bg-white rounded-lg shadow-md border border-gray-200 p-4 hover:shadow-lg hover:border-emerald-300 transition-all duration-200 transform hover:scale-105">
                <div class="text-center">
                    <div class="text-2xl font-bold text-emerald-600"><?php echo $approved; ?></div>
                    <div class="text-sm text-gray-600 mt-1">Diluluskan</div>
                </div>
            </a>
            
            <!-- Rejected -->
            <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>&status=REJECTED" class="block bg-white rounded-lg shadow-md border border-gray-200 p-4 hover:shadow-lg hover:border-red-300 transition-all duration-200 transform hover:scale-105">
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600"><?php echo $rejected; ?></div>
                    <div class="text-sm text-gray-600 mt-1">Ditolak</div>
                </div>
            </a>
            
            <!-- Draft -->
            <a href="draft-applications.php?job_id=<?php echo urlencode($job['id']); ?>" class="block bg-white rounded-lg shadow-md border border-gray-200 p-4 hover:shadow-lg hover:border-gray-400 transition-all duration-200 transform hover:scale-105">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-600"><?php echo $draft_total; ?></div>
                    <div class="text-sm text-gray-600 mt-1">Draf</div>
                </div>
            </a>
        </div>
    </div>

    <div class="flex justify-end gap-4">
        <a href="job-edit.php?id=<?php echo urlencode($job['id']); ?>" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition shadow flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Edit Jawatan
        </a>
        <a href="job-list.php" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition shadow flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Senarai
        </a>
    </div>
</div>
<?php include 'templates/footer.php'; ?>
