<?php
require_once '../includes/bootstrap.php';
require_once 'auth.php';

// Get database connection
$config = require '../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

if (!$pdo) {
    log_error('Database connection failed in admin/index.php', ['error_type' => 'DATABASE_ERROR', 'error_details' => $result['error_details'] ?? 'unknown']);
    die("System Error: Could not connect to the database. Please check the logs for more information.");
}

// Log admin dashboard access AFTER PDO is available
log_admin_action('Admin dashboard accessed', 'OTHER', 'admin', $_SESSION['admin_id'] ?? null, ['page' => 'dashboard']);

// Connect to database to fetch job listings and applications data
try {
    // Get total submitted applications (excluding drafts)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM application_application_main WHERE submission_locked = 1");
    $total_applications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get total draft applications
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM application_application_main WHERE submission_locked = 0 OR submission_locked IS NULL");
    $total_drafts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get applications by status (only submitted applications)
    $stmt = $pdo->query("SELECT 
        SUM(CASE WHEN status IN ('PENDING','pending') THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status IN ('REVIEWED','SHORTLISTED','shortlisted') THEN 1 ELSE 0 END) as reviewed_count,
        SUM(CASE WHEN status IN ('APPROVED','ACCEPTED','accepted') THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status IN ('REJECTED','rejected') THEN 1 ELSE 0 END) as rejected_count
        FROM application_application_main WHERE submission_locked = 1");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $pending_count = $stats['pending_count'] ?? 0;
    $reviewed_count = $stats['reviewed_count'] ?? 0;
    $approved_count = $stats['approved_count'] ?? 0;
    $rejected_count = $stats['rejected_count'] ?? 0;
    
} catch (PDOException $e) {
    // Log error but don't display to user
    log_error('Error fetching dashboard data: ' . $e->getMessage(), ['error_type' => 'DATABASE_ERROR']);
    
    // Initialize empty variables to prevent errors
    $total_applications = 0;
    $total_drafts = 0;
    $pending_count = 0;
    $reviewed_count = 0;
    $approved_count = 0;
    $rejected_count = 0;
}

include 'templates/header.php';
?>

<!-- Welcome Section -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Selamat Datang, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></h1>
            <p class="text-gray-600 mt-1">Panel Pentadbiran Sistem eJawatan MPHS</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500"><?php echo date('l, d F Y'); ?></p>
            <p class="text-sm text-gray-500" id="live-time">00:00:00</p>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<div class="mb-10">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 w-full">
        <!-- Total Submitted Applications -->
        <a href="applications-list.php" class="block transform hover:scale-105 transition-all duration-300 hover:shadow-2xl">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl shadow-xl p-6 border border-blue-200 hover:border-blue-300">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mr-4 shadow-lg flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-blue-700 mb-1">Permohonan Dihantar</p>
                        <p class="text-2xl font-semibold text-blue-900 mb-1"><?php echo number_format($total_applications); ?></p>
                        <p class="text-xs text-blue-600">Klik untuk lihat semua</p>
                    </div>
                </div>
            </div>
        </a>
        
        <!-- Draft Applications -->
        <a href="draft-applications.php" class="block transform hover:scale-105 transition-all duration-300 hover:shadow-2xl">
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl shadow-xl p-6 border border-orange-200 hover:border-orange-300">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center mr-4 shadow-lg flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-orange-700 mb-1">Permohonan Draf</p>
                        <p class="text-2xl font-semibold text-orange-900 mb-1"><?php echo number_format($total_drafts); ?></p>
                        <p class="text-xs text-orange-600">Klik untuk lihat draf</p>
                    </div>
                </div>
            </div>
        </a>
       
        <!-- Pending Applications -->
        <a href="applications-list.php?status=PENDING" class="block transform hover:scale-105 transition-all duration-300 hover:shadow-2xl">
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-2xl shadow-xl p-6 border border-yellow-200 hover:border-yellow-300">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center mr-4 shadow-lg flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-yellow-700 mb-1">Belum Disemak</p>
                        <p class="text-2xl font-semibold text-yellow-900 mb-1"><?php echo number_format($pending_count); ?></p>
                        <p class="text-xs text-yellow-600">Klik untuk tapis</p>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Job Listings Section -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Senarai Jawatan Kosong</h2>
    
    <?php
    try {
        // Fetch all jobs
        $stmt = $pdo->query('SELECT * FROM job_postings ORDER BY id DESC');
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Proper categorisation based on dates
        $today = new DateTime(date('Y-m-d'));
        $published = [];
        $upcoming = [];
        $recently_closed = [];
        $expired = [];

        // Process job categorization
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
    } catch (PDOException $e) {
        // Log error but don't display to user
        log_error('Error fetching job data: ' . $e->getMessage(), ['error_type' => 'DATABASE_ERROR']);
        
        // Initialize empty arrays to prevent errors
        $jobs = [];
        $published = [];
        $upcoming = [];
        $recently_closed = [];
        $expired = [];
    }
    ?>
    
    <?php
    // Function to get application statistics for a job (submitted applications only)
    function getJobApplicationStats($pdo, $job_id) {
        try {
            $stmt = $pdo->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('PENDING','pending') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status IN ('REVIEWED','SHORTLISTED','shortlisted') THEN 1 ELSE 0 END) as reviewed
                FROM application_application_main WHERE job_id = ? AND submission_locked = 1");
            $stmt->execute([$job_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'total' => $stats['total'] ?? 0,
                'pending' => $stats['pending'] ?? 0,
                'reviewed' => $stats['reviewed'] ?? 0
            ];
        } catch (PDOException $e) {
            log_error('Error fetching job application stats: ' . $e->getMessage(), ['error_type' => 'DATABASE_ERROR']);
            return ['total' => 0, 'pending' => 0, 'reviewed' => 0];
        }
    }
    ?>
    
    <!-- Published Jobs Section -->
    <?php if (count($published) > 0): ?>
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Sedang Diterbitkan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarikh Iklan</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Permohonan
                            <div class="mt-1 grid grid-cols-3 gap-2 text-xs text-gray-500 font-normal">
                                <span class="block text-center">Total</span>
                                <span class="block text-center">Disemak</span>
                                <span class="block text-center">Belum</span>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($published as $job): 
                        $stats = getJobApplicationStats($pdo, $job['id']);
                    ?>
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
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars(!empty($job['ad_date']) ? date('d-M-Y', strtotime($job['ad_date'])) : ''); ?></div>
                            <div class="text-sm text-gray-500">Tutup: <?php echo htmlspecialchars(!empty($job['ad_close_date']) ? date('d-M-Y', strtotime($job['ad_close_date'])) : ''); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="grid grid-cols-3 gap-2">
                                <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>" class="block text-center px-3 py-2 rounded-md text-sm font-semibold bg-blue-100 text-blue-800 hover:bg-blue-200">
                                    <?php echo $stats['total']; ?>
                                </a>
                                <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>&status=REVIEWED" class="block text-center px-3 py-2 rounded-md text-sm font-semibold bg-green-100 text-green-800 hover:bg-green-200">
                                    <?php echo $stats['reviewed']; ?>
                                </a>
                                <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>&status=PENDING" class="block text-center px-3 py-2 rounded-md text-sm font-semibold bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                                    <?php echo $stats['pending']; ?>
                                </a>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="job-view.php?job_code=<?php echo urlencode($job['job_code']); ?>" class="text-green-600 hover:text-green-900">Lihat</a>
                                <a href="job-edit.php?id=<?php echo urlencode($job['id']); ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recently Closed Jobs Section -->
    <?php if (count($recently_closed) > 0): ?>
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Jawatan Baru Ditutup (≤ 45 hari)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarikh Iklan</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Permohonan
                            <div class="mt-1 grid grid-cols-3 gap-2 text-xs text-gray-500 font-normal">
                                <span class="block text-center">Total</span>
                                <span class="block text-center">Disemak</span>
                                <span class="block text-center">Belum</span>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recently_closed as $job): 
                        $stats = getJobApplicationStats($pdo, $job['id']);
                    ?>
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
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars(!empty($job['ad_date']) ? date('d-M-Y', strtotime($job['ad_date'])) : ''); ?></div>
                            <div class="text-sm text-gray-500">Tutup: <?php echo htmlspecialchars(!empty($job['ad_close_date']) ? date('d-M-Y', strtotime($job['ad_close_date'])) : ''); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="grid grid-cols-3 gap-2">
                                <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>" class="block text-center px-3 py-2 rounded-md text-sm font-semibold bg-blue-100 text-blue-800 hover:bg-blue-200">
                                    <?php echo $stats['total']; ?>
                                </a>
                                <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>&status=REVIEWED" class="block text-center px-3 py-2 rounded-md text-sm font-semibold bg-green-100 text-green-800 hover:bg-green-200">
                                    <?php echo $stats['reviewed']; ?>
                                </a>
                                <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>&status=PENDING" class="block text-center px-3 py-2 rounded-md text-sm font-semibold bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                                    <?php echo $stats['pending']; ?>
                                </a>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="job-view.php?job_code=<?php echo urlencode($job['job_code']); ?>" class="text-green-600 hover:text-green-900">Lihat</a>
                                <a href="job-edit.php?id=<?php echo urlencode($job['id']); ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Expired Jobs Section -->
    <?php if (count($expired) > 0): ?>
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 cursor-pointer" onclick="toggleExpiredJobs()">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Jawatan Telah Ditutup (> 45 hari)</h3>
                <svg id="expired-toggle-icon" class="w-5 h-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>
        <div id="expired-jobs-content" class="hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawatan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarikh Iklan</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Permohonan
                                <div class="mt-1 grid grid-cols-3 gap-2 text-xs text-gray-500 font-normal">
                                    <span class="block text-center">Total</span>
                                    <span class="block text-center">Disemak</span>
                                    <span class="block text-center">Belum</span>
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($expired as $job): 
                            $stats = getJobApplicationStats($pdo, $job['id']);
                        ?>
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
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars(!empty($job['ad_date']) ? date('d-M-Y', strtotime($job['ad_date'])) : ''); ?></div>
                                <div class="text-sm text-gray-500">Tutup: <?php echo htmlspecialchars(!empty($job['ad_close_date']) ? date('d-M-Y', strtotime($job['ad_close_date'])) : ''); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="grid grid-cols-3 gap-2">
                                    <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>" class="block text-center px-3 py-2 rounded-md text-sm font-semibold bg-blue-100 text-blue-800 hover:bg-blue-200">
                                        <?php echo $stats['total']; ?>
                                    </a>
                                    <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>&status=REVIEWED" class="block text-center px-3 py-2 rounded-md text-sm font-semibold bg-green-100 text-green-800 hover:bg-green-200">
                                        <?php echo $stats['reviewed']; ?>
                                    </a>
                                    <a href="applications-list.php?job_id=<?php echo urlencode($job['id']); ?>&status=PENDING" class="block text-center px-3 py-2 rounded-md text-sm font-semibold bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                                        <?php echo $stats['pending']; ?>
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="job-view.php?job_code=<?php echo urlencode($job['job_code']); ?>" class="text-green-600 hover:text-green-900">Lihat</a>
                                    <a href="job-edit.php?id=<?php echo urlencode($job['id']); ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (count($jobs) == 0): ?>
    <div class="bg-gray-50 p-6 text-center text-gray-500 rounded-lg border border-gray-200">
        <p>Tiada jawatan kosong dijumpai</p>
    </div>
    <?php endif; ?>
    
    <div class="mt-4 text-right">
        <a href="job-list.php" class="text-blue-600 hover:underline">Lihat Semua Jawatan →</a>
    </div>
</div>

<script>
// Toggle expired jobs section
function toggleExpiredJobs() {
    const content = document.getElementById('expired-jobs-content');
    const icon = document.getElementById('expired-toggle-icon');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}
</script>

<script>
// Live clock function
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('live-time').textContent = `${hours}:${minutes}:${seconds}`;
}

// Update clock every second
setInterval(updateClock, 1000);
updateClock(); // Initial call
</script>

<?php include 'templates/footer.php'; ?>
