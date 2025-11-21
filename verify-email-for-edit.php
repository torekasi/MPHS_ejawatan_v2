<?php
/**
 * @FileID: verify-email-for-edit
 * @Module: Application Edit Verification
 * @Author: Nefi
 * @LastModified: 2025-01-14
 * @SecurityTag: validated
 */

session_start();
require_once 'includes/ErrorHandler.php';

// Get database connection from config
$result = require 'config.php';
$config = $result['config'] ?? $result;

$error = '';
$success = '';
$application = null;
$job = null;

// Get parameters from URL (pre-filled from preview page)
$app_ref = $_GET['app_ref'] ?? '';
$nric = $_GET['nric'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_ref = trim($_POST['application_ref'] ?? '');
    $nric = trim($_POST['nric'] ?? '');
    
    if (empty($app_ref) || empty($nric)) {
        $error = 'Sila masukkan semua maklumat yang diperlukan.';
    } else {
        try {
            // Connect to database
            $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
            
            // Try both tables for application lookup
            $stmt = $pdo->prepare("SELECT * FROM application_application_main WHERE application_reference = ? LIMIT 1");
            $stmt->execute([$app_ref]);
            $application = $stmt->fetch();
            
            if (!$application) {
                $stmt = $pdo->prepare("SELECT * FROM job_applications WHERE application_reference = ? LIMIT 1");
                $stmt->execute([$app_ref]);
                $application = $stmt->fetch();
            }
            
            if (!$application) {
                $error = 'Rujukan permohonan tidak dijumpai.';
            } else {
                // Verify NRIC matches
                $clean_nric = preg_replace('/[^0-9]/', '', $application['nombor_ic']);
                $clean_input_nric = preg_replace('/[^0-9]/', '', $nric);
                
                if ($clean_nric !== $clean_input_nric) {
                    $error = 'No. Kad Pengenalan tidak sepadan dengan rekod permohonan.';
                } else {
                    // Check if application is locked
                    if (isset($application['submission_locked']) && $application['submission_locked'] == 1) {
                        $error = 'Permohonan ini telah selesai dan tidak boleh diedit lagi.';
                    } else {
                        // Get job information
                        $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
                        $stmt->execute([$application['job_id']]);
                        $job = $stmt->fetch();
                        
                        if (!$job) {
                            $error = 'Maklumat jawatan tidak dijumpai.';
                        } else {
                            // Store verification in session
                            $_SESSION['verified_application_id'] = $application['id'];
                            $_SESSION['verified_application_ref'] = $application['application_reference'];
                            $_SESSION['verified_nric'] = $clean_nric;
                            
                            // Redirect to edit page
                            $redirect_url = "edit-application.php?app_id=" . $application['id'] . "&ref=" . urlencode($application['application_reference']);
                            header("Location: {$redirect_url}");
                            exit;
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Ralat sambungan ke pangkalan data. Sila cuba sebentar lagi.';
            log_error('Database connection error in verify-email-for-edit', ['exception' => $e->getMessage()]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sahkan Identiti untuk Edit - eJawatan MPHS</title>
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
        .section-title {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.125rem;
        }
        .required { color: #dc2626; }
        .uppercase-input {
            text-transform: uppercase;
        }
    </style>
</head>
<body class="min-h-screen">
    <?php include 'header.php'; ?>

    <main class="standard-container px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Title -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Sahkan Identiti untuk Edit Permohonan</h1>
            <p class="mt-1 text-sm text-gray-600">Sila masukkan maklumat permohonan anda untuk mengesahkan identiti</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?php echo htmlspecialchars($success); ?></p>
        </div>
        <?php endif; ?>

        <!-- Verification Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="section-title">
                SAHKAN IDENTITI ANDA
            </div>
            <div class="p-6">
                <!-- Information Alert -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Untuk keselamatan, sila sahkan identiti anda dengan memasukkan Rujukan Permohonan dan No. Kad Pengenalan.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Verification Form -->
                <div class="space-y-6">
                    <form id="verifyForm" action="" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Application Reference -->
                            <div>
                                <label for="application_ref" class="block text-sm font-medium text-gray-700">
                                    Rujukan Permohonan <span class="required">*</span>
                                </label>
                                <div class="mt-1">
                                    <input type="text" name="application_ref" id="application_ref" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input"
                                        placeholder="Contoh: APP-2025-0003-95B0FF34"
                                        value="<?php echo htmlspecialchars($app_ref); ?>"
                                        pattern="APP-[A-Z0-9-]+"
                                        maxlength="50">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Format: APP-XXXXXXXX (contoh: APP-2025-0003-95B0FF34)</p>
                            </div>

                            <!-- NRIC -->
                            <div>
                                <label for="nric" class="block text-sm font-medium text-gray-700">
                                    No. Kad Pengenalan <span class="required">*</span>
                                </label>
                                <div class="mt-1">
                                    <input type="text" name="nric" id="nric" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                                        placeholder="Contoh: 830412-05-6444"
                                        value="<?php echo htmlspecialchars($nric); ?>"
                                        pattern="\d{6}-\d{2}-\d{4}"
                                        maxlength="14">
                                </div>
                                <div class="mt-1 space-y-1">
                                    <p class="text-xs text-gray-500">Format: XXXXXX-XX-XXXX</p>                          
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center space-x-4">
                            <button type="button" onclick="history.back()" 
                                class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-8 rounded-lg transition duration-200 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Kembali
                            </button>
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-8 rounded-lg transition duration-200 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Sahkan & Edit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const verifyForm = document.getElementById('verifyForm');
        const nricInput = document.getElementById('nric');
        const appRefInput = document.getElementById('application_ref');

        // Format NRIC input with Malaysian IC format (XXXXXX-XX-XXXX)
        function formatNRIC(value) {
            // Remove all non-numeric characters
            const numbers = value.replace(/\D/g, '');

            // Ensure we have exactly 12 digits
            const limitedNumbers = numbers.slice(0, 12);

            // Apply formatting based on length
            if (limitedNumbers.length <= 6) {
                return limitedNumbers;
            } else if (limitedNumbers.length <= 8) {
                return limitedNumbers.slice(0, 6) + '-' + limitedNumbers.slice(6);
            } else {
                return limitedNumbers.slice(0, 6) + '-' + limitedNumbers.slice(6, 8) + '-' + limitedNumbers.slice(8);
            }
        }

        // Handle NRIC input formatting
        nricInput.addEventListener('input', function(e) {
            const cursorPosition = this.selectionStart;
            const previousLength = this.value.length;

            // Format the value
            this.value = formatNRIC(this.value);

            // Adjust cursor position after formatting
            const newLength = this.value.length;
            const cursorOffset = newLength - previousLength;

            // Only adjust cursor if we're not at the end
            if (cursorPosition < previousLength) {
                this.setSelectionRange(cursorPosition + cursorOffset, cursorPosition + cursorOffset);
            }
        });

        // Format Application Reference input (uppercase, flexible format)
        appRefInput.addEventListener('input', function(e) {
            // Convert to uppercase
            this.value = this.value.toUpperCase();

            // Remove any spaces
            this.value = this.value.replace(/\s/g, '');

            // Ensure it starts with APP-
            if (!this.value.startsWith('APP-') && this.value.length > 0) {
                this.value = 'APP-' + this.value.replace('APP-', '');
            }

            // Allow flexible format up to 50 characters
            if (this.value.length > 50) {
                this.value = this.value.slice(0, 50);
            }
        });

        // Form validation before submission
        verifyForm.addEventListener('submit', function(e) {
            const nric = nricInput.value.trim();
            const appRef = appRefInput.value.trim();

            // Basic validation - check if fields are not empty
            if (!nric) {
                e.preventDefault();
                alert('Sila masukkan No. Kad Pengenalan.');
                nricInput.focus();
                return false;
            }

            if (!appRef) {
                e.preventDefault();
                alert('Sila masukkan Rujukan Permohonan.');
                appRefInput.focus();
                return false;
            }

            // Validate NRIC format (must be exactly XXXXXX-XX-XXXX)
            if (!/^\d{6}-\d{2}-\d{4}$/.test(nric)) {
                e.preventDefault();
                alert('Sila pastikan format No. Kad Pengenalan adalah betul.\nFormat: XXXXXX-XX-XXXX');
                nricInput.focus();
                return false;
            }

            // Validate Application Reference format (must match database format)
            if (!/^APP-[A-Z0-9-]+$/.test(appRef) || appRef.length < 8) {
                e.preventDefault();
                alert('Sila pastikan Rujukan Permohonan adalah betul.\nFormat: APP-XXXXXXXX (contoh: APP-2025-0003-95B0FF34)');
                appRefInput.focus();
                return false;
            }

            // Show loading state
            const submitBtn = verifyForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Mengesahkan...';
                submitBtn.disabled = true;
            }

            return true;
        });
    });
    </script>
</body>
</html>