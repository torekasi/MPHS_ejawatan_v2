<?php
// Root page for ejawatan - Public Job Listings

// Initialize comprehensive logging
require_once 'includes/ErrorHandler.php';

// Include header with favicon
require_once 'header.php';

// Helper function to format job code for display
function formatJobCode($code) {
    if (!empty($code)) {
        return $code;
    }
    // Fallback to old format if code is empty
    return 'JOB-' . str_pad($id ?? 0, 6, '0', STR_PAD_LEFT);
}

// Get database connection from config
$result = require 'config.php';

// Log page access
log_public_action('Job listings page accessed', ActivityLogger::ACTION_VIEW, ActivityLogger::ENTITY_JOB, null, ['page' => 'index']);

// Initialize variables
$pdo = null;
$error = '';
$jobs = [];
$published = [];
$upcoming = [];

// Connect to database
try {
    $dsn = "mysql:host={$result['db_host']};dbname={$result['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $result['db_user'], $result['db_pass'], $options);
} catch (PDOException $e) {
    $error = 'Ralat sambungan ke pangkalan data. Sila cuba sebentar lagi.';
    // Log error securely without exposing details to users
    log_error('Database connection error on public index page', ['exception' => $e->getMessage(), 'file' => __FILE__, 'line' => __LINE__]);
}

// Fetch job listings if database connection successful
if ($pdo) {
    try {
        // Log job data fetch activity
        log_public_action('Fetching job listings', ActivityLogger::ACTION_READ, ActivityLogger::ENTITY_JOB, null, ['query' => 'all_active_jobs']);
        
        // Fetch all active jobs
        $sql = 'SELECT * FROM job_postings ORDER BY ad_date DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $jobs = $stmt->fetchAll();
        
        // Log successful data retrieval
        log_debug('Job listings retrieved successfully', ['job_count' => count($jobs)]);
        
        // Categorize jobs
        $today = new DateTime(date('Y-m-d'));
        
        foreach ($jobs as $job) {
            $ad_date = new DateTime($job['ad_date']);
            $ad_close_date = new DateTime($job['ad_close_date']);
            
            if ($ad_date <= $today && $ad_close_date >= $today) {
                $published[] = $job;
            } elseif ($ad_date > $today) {
                $upcoming[] = $job;
            }
        }
        
        // Log categorization results
        log_debug('Job categorization completed', [
            'total_jobs' => count($jobs),
            'published_jobs' => count($published),
            'upcoming_jobs' => count($upcoming)
        ]);
        
    } catch (PDOException $e) {
        $error = 'Ralat mendapatkan senarai jawatan. Sila cuba sebentar lagi.';
        // Log error securely without exposing details to users
        log_error('Error fetching job listings', ['exception' => $e->getMessage(), 'file' => __FILE__, 'line' => __LINE__]);
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Portal Jawatan Kosong - Majlis Perbandaran Hulu Selangor</title>
    <link href="assets/css/tailwind.min.css" rel="stylesheet">
    <!-- Import Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Centralized CSS files -->
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/background.css" rel="stylesheet">
</head>
<body class="min-h-screen body-bg-image">

    <!-- Main Content -->
    <main class="standard-container px-2 sm:px-4 md:px-6 lg:px-8 py-6 sm:py-8 md:py-10 lg:py-12 mx-auto max-w-full">
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
                <p><?php echo htmlspecialchars($error ?? ''); ?></p>
            </div>
        <?php endif; ?>

        <!-- Active Jobs Table -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-4 sm:mb-6 md:mb-8 max-w-full mx-auto">
            <!-- Title -->
            <div class="text-center py-3 sm:py-4 bg-blue-600">
                <h2 class="text-xl sm:text-2xl font-bold text-white">Senarai Jawatan Kosong</h2>
            </div>
            <?php if (empty($published)): ?>
                <div class="p-8 text-center">
                    <p class="text-gray-500">Tiada jawatan kosong aktif pada masa ini.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-600">
                        <tr>
                            <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-white uppercase tracking-wider w-full sm:w-1/4">Jawatan</th>
                            <th scope="col" class="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-white uppercase tracking-wider sm:w-1/6">Tarikh Iklan</th>
                            <th scope="col" class="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-white uppercase tracking-wider sm:w-1/6">Tarikh Tutup</th>
                            <th scope="col" class="hidden sm:table-cell px-3 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-white uppercase tracking-wider sm:w-1/6">Gaji (RM)</th>
                            <th scope="col" class="hidden sm:table-cell px-3 sm:px-6 py-3 text-center text-xs sm:text-sm font-medium text-white uppercase tracking-wider w-auto sm:w-1/6">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($published as $index => $job): ?>
                            <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                                <td class="px-3 sm:px-6 py-4 break-words">
                                    <div class="text-sm font-medium text-gray-900"><a href="view-job.php?job_code=<?php echo urlencode($job['job_code'] ?? ''); ?>" class="hover:text-blue-600 hover:underline"><?php echo htmlspecialchars(strtoupper($job['job_title'] ?? '')); ?></a></div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($job['job_code'] ?? ''); ?> |  
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($job['kod_gred'] ?? ''); ?>
                                        </span>
                                    </div>
                                    <!-- Mobile-only info -->
                                    <div class="sm:hidden mt-2 space-y-1">
                                        <div class="text-xs font-medium">Tarikh Iklan: <span class="text-gray-500"><?php echo htmlspecialchars($job['ad_date'] ?? ''); ?></span></div>
                                        <div class="text-xs font-medium">Tarikh Tutup: <span class="text-red-600"><?php echo htmlspecialchars($job['ad_close_date'] ?? ''); ?></span></div>
                                        <div class="text-xs font-medium">Gaji (RM): <span class="text-gray-500"><?php echo number_format($job['salary_min'] ?? 0, 2); ?> - <?php echo number_format($job['salary_max'] ?? 0, 2); ?></span></div>
                                        <div class="text-xs font-medium mt-3">
                                            <a href="view-job.php?job_code=<?php echo urlencode($job['job_code'] ?? ''); ?>" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-2 py-1 text-xs rounded-md transition inline-block w-full text-center">Lihat Butiran</a>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden sm:table-cell px-3 sm:px-6 py-4 text-sm text-gray-500 sm:w-1/6 whitespace-nowrap">
                                    <?php echo htmlspecialchars($job['ad_date'] ?? ''); ?>
                                </td>
                                <td class="hidden sm:table-cell px-3 sm:px-6 py-4 sm:w-1/6 whitespace-nowrap">
                                    <span class="text-sm text-red-600 font-medium">
                                        <?php echo htmlspecialchars($job['ad_close_date'] ?? ''); ?>
                                    </span>
                                </td>
                                <td class="hidden sm:table-cell px-3 sm:px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    <?php echo number_format($job['salary_min'] ?? 0, 2); ?> - <?php echo number_format($job['salary_max'] ?? 0, 2); ?>
                                </td>
                                <td class="hidden sm:table-cell px-3 sm:px-6 py-4 text-center text-sm font-medium">
                                    <a href="view-job.php?job_code=<?php echo urlencode($job['job_code'] ?? ''); ?>" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-2 sm:px-3 py-1 text-xs sm:text-sm rounded-md transition inline-block w-full sm:w-auto">Lihat Butiran</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- No Admin Link -->
    </main>

    <!-- Footer - Dynamic -->
    <?php include 'footer.php'; ?>
</body>
</html>
