<?php
/**
 * @FileID: view_job_public_001
 * @Module: ViewJobPage
 * @Author: Nefi
 * @LastModified: 2025-11-09T00:00:00Z
 * @SecurityTag: validated
 */
// Job details page for public users

// Initialize comprehensive logging
require_once 'includes/ErrorHandler.php';

// Helper function to format job ID for display (deprecated - use job_code directly)
function formatJobId($id) {
    return 'JOB-' . str_pad($id, 6, '0', STR_PAD_LEFT);
}

// Get database connection from config
$result = require 'config.php';

// Initialize variables
$pdo = null;
$error = '';
$job = null;

// Validate job_code parameter
if (!isset($_GET['job_code']) || empty($_GET['job_code'])) {
    $error = 'Parameter jawatan tidak sah.';
    log_warning('Invalid job parameter accessed', ['provided_job_code' => $_GET['job_code'] ?? 'null', 'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
} else {
    $job_code = $_GET['job_code'];
    
    // Log job view attempt with job_code
    $log_data = ['user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown', 'job_code' => $job_code];
    log_public_action('Job detail page accessed', ActivityLogger::ACTION_VIEW, ActivityLogger::ENTITY_JOB, $job_code, $log_data);
    
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
        log_error('Database connection error on job view page', ['exception' => $e->getMessage(), 'job_code' => $job_code, 'file' => __FILE__, 'line' => __LINE__]);
    }

    // Fetch job details if database connection successful
    if ($pdo && !$error) {
        try {
            // Fetch job by job_code
            $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE job_code = ? LIMIT 1');
            $stmt->execute([$job_code]);
            $job = $stmt->fetch();
            
            if (!$job) {
                $error = 'Jawatan tidak dijumpai.';
                log_warning('Job not found', ['job_code' => $job_code, 'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            } else {
                // Log successful job view
                log_public_action('Job details retrieved successfully', ActivityLogger::ACTION_READ, ActivityLogger::ENTITY_JOB, $job_code, [
                    'job_title' => $job['job_title'],
                    'job_code' => $job['job_code'],
                    'status' => 'found'
                ]);
            }
        } catch (PDOException $e) {
            $error = 'Ralat mendapatkan maklumat jawatan. Sila cuba sebentar lagi.';
            // Log error securely without exposing details to users
            log_error('Error fetching job details', ['exception' => $e->getMessage(), 'job_code' => $job_code, 'file' => __FILE__, 'line' => __LINE__]);
        }
    }
}

// Check if job is active or upcoming
$job_status = '';
if ($job) {
    $today = new DateTime(date('Y-m-d'));
    $ad_date = new DateTime($job['ad_date']);
    $ad_close_date = new DateTime($job['ad_close_date']);
    
    if ($ad_date <= $today && $ad_close_date >= $today) {
        $job_status = 'active';
    } elseif ($ad_date > $today) {
        $job_status = 'upcoming';
    } else {
        $job_status = 'closed';
    }
}

// Fetch application instructions from database
$application_instructions = '';
if ($pdo) {
    try {
        // Check if page_content table exists
        $check_table = $pdo->query("SHOW TABLES LIKE 'page_content'");
        if ($check_table->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT content_value FROM page_content WHERE content_key = 'application_instructions'");
            $stmt->execute();
            $app_instructions_row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($app_instructions_row) {
                $application_instructions = $app_instructions_row['content_value'];
            }
        }
    } catch (PDOException $e) {
        log_error('Error fetching application instructions', ['exception' => $e->getMessage(), 'file' => __FILE__, 'line' => __LINE__]);
    }
}

// Use default content if not found in database or if there was an error
if (empty($application_instructions)) {
    $application_instructions = '<p>Permohonan hendaklah dibuat secara dalam talian melalui portal ini dengan mengklik butang "Mohon Sekarang" di atas.</p>
<p>Sila pastikan anda memenuhi semua syarat kelayakan sebelum memohon.</p>
<p>Hanya calon yang disenarai pendek sahaja akan dipanggil untuk temu duga.</p>';
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $job ? htmlspecialchars($job['job_title']) : 'Maklumat Jawatan'; ?> - Majlis Perbandaran Hulu Selangor</title>
    <link href="assets/css/tailwind.min.css" rel="stylesheet">
    <!-- Import Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f7f9fc;
        }
        .standard-container {
            max-width: 1050px;
            margin: 0 auto;
            width: 100%;
        }
        .standard-container > * {
            width: 100%;
        }
        .standard-container .bg-white {
            width: 100%;
        }
        .standard-container .shadow-md {
            width: 100%;
        }
        .banner {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }
        .requirements h4 {
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            font-size: 1.125rem;
        }
        .requirements p {
            margin-bottom: 1rem;
        }
        .requirements ul, .requirements ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        .requirements ul {
            list-style-type: disc;
        }
        .requirements ol {
            list-style-type: decimal;
        }
        .requirements li {
            margin-bottom: 0.5rem;
        }
        /* Force full width on all container elements */
        .grid {
            width: 100%;
        }
        .space-y-6 > * {
            width: 100%;
        }
    </style>
</head>
<body class="min-h-screen body-bg-image">
    <?php include 'header.php'; ?>

    <!-- Main Content -->
    <main class="standard-container px-4 sm:px-6 lg:px-8 py-12">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="index.php" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded shadow transition duration-150 ease-in-out">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Senarai Jawatan
            </a>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
                <p><?php echo htmlspecialchars($error); ?></p>
                <p class="mt-2">
                    <a href="index.php" class="font-medium underline">Kembali ke halaman utama</a>
                </p>
            </div>
        <?php elseif ($job): ?>
            <!-- Job Status Badge -->
            <div class="mb-6">
                <?php if ($job_status === 'active'): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                        Dibuka untuk Permohonan
                    </span>
                <?php elseif ($job_status === 'upcoming'): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                        Akan Dibuka pada <?php echo htmlspecialchars($job['ad_date']); ?>
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                        Tutup
                    </span>
                <?php endif; ?>
            </div>

            <!-- Job Title and Basic Info -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="bg-blue-600 text-white p-6">
                    <h2 class="text-2xl font-bold"><?php echo htmlspecialchars(strtoupper($job['job_title'])); ?></h2>
                    <div class="flex items-center mt-2">
                        <span class="bg-blue-800 text-white text-sm font-medium px-3 py-1 rounded-full">
                            <?php echo htmlspecialchars($job['kod_gred']); ?>
                        </span>
                        <span class="mx-2 text-white">•</span>
                        <span class="text-white text-sm">
                            ID: <?php echo htmlspecialchars($job['job_code'] ?? 'N/A'); ?>
                        </span>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Maklumat Jawatan</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Edaran Iklan:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($job['edaran_iklan']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tarikh Iklan:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($job['ad_date']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tarikh Tutup:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($job['ad_close_date']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Maklumat Gaji</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Gaji Minimum:</span>
                                    <span class="font-medium">RM <?php echo number_format($job['salary_min'], 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Gaji Maksimum:</span>
                                    <span class="font-medium">RM <?php echo number_format($job['salary_max'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($job_status === 'active'): ?>
                        <div class="mt-6 text-center">
                            <?php 
                            // Check if payment gateway is enabled in config
                            $config_data = $GLOBALS['config'] ?? $result ?? [];
                            if (isset($config_data['payment']['enabled']) && $config_data['payment']['enabled']): 
                            ?>
                                <div class="mb-4">
                                    <p class="text-gray-600">Yuran Pemprosesan: RM <?php echo number_format($config_data['payment']['amount'], 2); ?></p>
                                </div>
                                <a href="payment-form.php?job_code=<?php echo urlencode($job['job_code']); ?>" class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium px-6 py-3 rounded-md transition">
                                    Mohon & Bayar Sekarang
                                </a>
                            <?php else: ?>
                                <a href="job-application-full.php?job_code=<?php echo urlencode($job['job_code']); ?>" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-md transition">
                                    Mohon Sekarang
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Job Requirements -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Syarat & Kelayakan</h3>
                    <div class="requirements prose prose-blue max-w-none">
                        <?php echo $job['requirements']; ?>
                    </div>
                </div>
            </div>
            
            <!-- Application Instructions -->
            <div class="bg-blue-50 rounded-lg p-6 mb-8">
                <h3 class="text-xl font-bold text-blue-800 mb-4">Cara Memohon</h3>
                <div class="prose prose-blue max-w-none">
                    <div class="requirements cara-memohon">
                        <?php echo $application_instructions; ?>
                    </div>
                </div>
            </div>
            

        <?php endif; ?>
    </main>

    <!-- Footer - Dynamic -->
    <?php include 'footer.php'; ?>
</body>
</html>
