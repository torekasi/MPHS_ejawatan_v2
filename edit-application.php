<?php
// Edit application page using secure token
require_once 'includes/ErrorHandler.php';
require_once 'includes/EditLinkEmailTemplate.php';

// Get database connection from config
$result = require 'config.php';
$config = $result['config'] ?? $result;

// Initialize variables
$pdo = null;
$error = '';
$application = null;
$job = null;
$token_data = null;

// Check if token is provided OR direct access via app_id and ref
$token = $_GET['token'] ?? '';
$app_id = $_GET['app_id'] ?? '';
$app_ref = $_GET['ref'] ?? '';

if (empty($token) && (empty($app_id) || empty($app_ref))) {
    $error = 'Token atau rujukan permohonan tidak sah.';
} else {
    // Connect to database
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
        
        if (!empty($token)) {
            // Token-based access (existing logic)
            $emailTemplate = new EditLinkEmailTemplate($config);
            $token_data = $emailTemplate->verifyEditToken($token);
            
            if (!$token_data) {
                $error = 'Token tidak sah, telah tamat tempoh, atau telah digunakan.';
            } else {
                // Get application data
                $stmt = $pdo->prepare('SELECT * FROM job_applications WHERE id = ? AND email = ? LIMIT 1');
                $stmt->execute([$token_data['id'], $token_data['email']]);
                $application = $stmt->fetch();
                
                if (!$application) {
                    $error = 'Permohonan tidak dijumpai atau maklumat tidak sepadan.';
                } else {
                    // Verify NRIC matches
                    $clean_nric = preg_replace('/[^0-9]/', '', $application['nombor_ic']);
                    if ($clean_nric !== $token_data['nric']) {
                        $error = 'Maklumat pengesahan tidak sepadan.';
                    } else {
                        // Check if application is locked
                        if ($application['submission_locked'] == 1) {
                            $error = 'Permohonan ini telah selesai dan tidak boleh diedit lagi.';
                        } else {
                            // Get job information
                            $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
                            $stmt->execute([$application['job_id']]);
                            $job = $stmt->fetch();
                            
                            if (!$job) {
                                $error = 'Maklumat jawatan tidak dijumpai.';
                            }
                        }
                    }
                }
            }
        } else {
            // Direct access via app_id and ref (new logic)
            session_start();
            
            // Check if user has been verified via NRIC
            if (!isset($_SESSION['verified_application_id']) || 
                !isset($_SESSION['verified_application_ref']) ||
                $_SESSION['verified_application_id'] != $app_id ||
                $_SESSION['verified_application_ref'] != $app_ref) {
                $error = 'Akses tidak sah. Sila sahkan identiti anda terlebih dahulu.';
            } else {
                // Get application data directly
                $stmt = $pdo->prepare('SELECT * FROM job_applications WHERE id = ? AND application_reference = ? LIMIT 1');
                $stmt->execute([$app_id, $app_ref]);
                $application = $stmt->fetch();
                
                if (!$application) {
                    $error = 'Permohonan tidak dijumpai.';
                } else {
                    // Check if application is locked
                    if ($application['submission_locked'] == 1) {
                        $error = 'Permohonan ini telah selesai dan tidak boleh diedit lagi.';
                    } else {
                        // Get job information
                        $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
                        $stmt->execute([$application['job_id']]);
                        $job = $stmt->fetch();
                        
                        if (!$job) {
                            $error = 'Maklumat jawatan tidak dijumpai.';
                        }
                    }
                }
            }
        }
        
    } catch (PDOException $e) {
        $error = 'Ralat sambungan ke pangkalan data. Sila cuba sebentar lagi.';
        log_error('Database connection error on edit application', ['exception' => $e->getMessage(), 'token' => substr($token, 0, 20) . '...']);
    } catch (Exception $e) {
        $error = 'Ralat sistem. Sila cuba lagi atau hubungi pihak pentadbir.';
        log_error('Error verifying edit token', ['exception' => $e->getMessage(), 'token' => substr($token, 0, 20) . '...']);
    }
}

// If everything is valid, redirect to the application form with prefilled data
if ($application && $job && !$error) {
    // Store the application data in session for form population
    if (!session_id()) session_start();
    $_SESSION['edit_application_data'] = $application;
    $_SESSION['edit_application_verified'] = true;
    
    if (!empty($token)) {
        $_SESSION['edit_application_token'] = $token;
        
        // Log the successful token access
        log_info('User accessed edit link via token', [
            'application_id' => $application['id'],
            'email' => $application['email'],
            'job_id' => $application['job_id'],
            'nric_last_4' => substr($token_data['nric'], -4)
        ]);
    } else {
        // Log the successful direct access
        log_info('User accessed edit link via direct NRIC verification', [
            'application_id' => $application['id'],
            'application_ref' => $application['application_reference'],
            'job_id' => $application['job_id']
        ]);
        
        // Clear the verification session variables for security
        unset($_SESSION['verified_application_id']);
        unset($_SESSION['verified_application_ref']);
    }
    
    // Redirect to the application form
    $job_id = $application['job_id'];
    $redirect_url = "job-application-1.php?job_id={$job_id}&edit=1&app_id=" . $application['id'] . "&ref=" . urlencode($application['application_reference']);
    
    // Add payment reference if it exists
    if (!empty($application['payment_reference'])) {
        $redirect_url .= "&payment_ref=" . urlencode($application['payment_reference']);
    }
    
    header("Location: {$redirect_url}");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Permohonan - eJawatan MPHS</title>
    <link href="assets/css/tailwind.min.css" rel="stylesheet">
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
    </style>
</head>
<body class="min-h-screen body-bg-image">
    <?php include 'header.php'; ?>

    <main class="standard-container px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-red-600 text-white p-6">
                <h1 class="text-2xl font-bold">Ralat Edit Permohonan</h1>
                <p class="mt-2 text-red-200">Tidak dapat mengakses permohonan untuk diedit</p>
            </div>
        </div>

        <!-- Error Message -->
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Ralat Akses</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Possible Solutions -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Kemungkinan Penyelesaian:</h3>
                
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-6 w-6 rounded-full bg-blue-100 text-blue-600 text-sm font-medium">1</div>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900">Pautan Tamat Tempoh</h4>
                            <p class="text-sm text-gray-500">Pautan edit hanya sah selama 24 jam. Sila minta pautan baru melalui halaman status permohonan.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-6 w-6 rounded-full bg-blue-100 text-blue-600 text-sm font-medium">2</div>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900">Pautan Tidak Lengkap</h4>
                            <p class="text-sm text-gray-500">Pastikan anda menyalin pautan secara lengkap dari email yang diterima.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-6 w-6 rounded-full bg-blue-100 text-blue-600 text-sm font-medium">3</div>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900">Permohonan Telah Selesai</h4>
                            <p class="text-sm text-gray-500">Permohonan yang telah diserahkan tidak boleh diedit lagi.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-col sm:flex-row gap-4">
                    <a href="index.php" 
                       class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Halaman Utama
                    </a>
                    
                    <a href="javascript:history.back()" 
                       class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
