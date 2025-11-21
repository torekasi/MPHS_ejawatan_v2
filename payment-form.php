<?php
// Payment form page for job application
require_once 'includes/ErrorHandler.php';

// Get database connection from config
$result = require 'config.php';
$config = $result;

// Initialize variables
$pdo = null;
$error = '';
$job = null;
$job_id = null;

// Check if payment is enabled
if (!isset($config['payment']['enabled']) || !$config['payment']['enabled']) {
    header('Location: index.php');
    exit;
}

// Validate job_code parameter (primary identifier)
$job_code = $_GET['job_code'] ?? null;
$job_id = null;

if (empty($job_code)) {
    $error = 'Kod jawatan tidak sah.';
    log_warning('Invalid job_code for payment form', ['provided_code' => $job_code ?? 'null', 'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
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
    } catch (PDOException $e) {
        $error = 'Ralat sambungan ke pangkalan data. Sila cuba sebentar lagi.';
        log_error('Database connection error on payment form', ['exception' => $e->getMessage(), 'job_code' => $job_code]);
    }

    // Fetch job details if database connection successful
    if ($pdo && !$error) {
        try {
            // Fetch by job_code (primary method)
            $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE job_code = ? LIMIT 1');
            $stmt->execute([$job_code]);
            $job = $stmt->fetch();
            
            if (!$job) {
                $error = 'Jawatan tidak dijumpai.';
                log_warning('Job not found for payment form', ['job_code' => $job_code]);
            } else {
                // Set job_id from fetched job data
                $job_id = $job['id'];
                
                // Check if job is still open for applications
                $today = new DateTime(date('Y-m-d'));
                $ad_close_date = new DateTime($job['ad_close_date']);
                
                if ($ad_close_date < $today) {
                    $error = 'Permohonan untuk jawatan ini telah ditutup.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Ralat mendapatkan maklumat jawatan.';
            log_error('Error fetching job for payment form', ['exception' => $e->getMessage(), 'job_code' => $job_code]);
        }
    }
}

// Helper function to format job ID for display
function formatJobId($id) {
    return 'JOB-' . str_pad($id, 6, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Permohonan eJawatan- Majlis Perbandaran Hulu Selangor</title>
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
        .required { color: #dc2626; }
        .section-title {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.125rem;
        }
        .uppercase-input {
            text-transform: uppercase;
        }
        .payment-summary {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        }
    </style>
</head>
<body class="min-h-screen body-bg-image">
    <?php include 'header.php'; ?>

    <main class="standard-container px-4 sm:px-6 lg:px-8 py-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="view-job.php?job_code=<?php echo urlencode($job_code ?? ''); ?>" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded shadow transition duration-150 ease-in-out">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Maklumat Jawatan
            </a>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
                <p><?php echo htmlspecialchars($error); ?></p>
                <p class="mt-2">
                    <a href="index.php" class="font-medium underline">Kembali ke halaman utama</a>
                </p>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-8" role="alert">
                <?php 
                $error_type = $_GET['error'];
                $error_messages = [
                    'missing_fields' => 'Sila lengkapkan semua medan yang diperlukan.',
                    'db_error' => 'Ralat sambungan ke pangkalan data. Sila cuba sebentar lagi.',
                    'duplicate_found' => 'Anda telah membuat permohonan untuk jawatan ini. Sila semak status permohonan anda.'
                ];
                echo htmlspecialchars($error_messages[$error_type] ?? 'Ralat tidak diketahui.');
                ?>
            </div>
        <?php elseif ($job): ?>
            <!-- Payment Form Header -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="bg-blue-600 text-white p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold">Fee Permohonan eJawatan</h1>
                            <p class="mt-2"><?php echo htmlspecialchars(strtoupper($job['job_title'])); ?></p>
                            <p class="text-blue-200 text-sm">
                                Kod Gred: <?php echo htmlspecialchars($job['kod_gred']); ?> |
                                Kod Jawatan: <?php echo htmlspecialchars($job['job_code']); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold">RM <?php echo number_format($config['payment']['amount'], 2); ?></div>
                            <div class="text-blue-200 text-sm">Termasuk caj pemprosesan</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <form id="paymentForm" action="check-duplicate-before-payment.php" method="POST" class="space-y-6">
                <input type="hidden" name="job_code" value="<?php echo htmlspecialchars($job_code); ?>">
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($config['payment']['amount']); ?>">
                
                <!-- Payment Details Section -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="section-title">
                        MAKLUMAT PEMBAYAR
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nama Penuh <span class="required">*</span>
                                </label>
                                <input type="text" name="applicant_name" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input" 
                                       placeholder="MASUKKAN NAMA PENUH SEPERTI DALAM KAD PENGENALAN" 
                                       required maxlength="255">
                                <p class="text-xs text-gray-500 mt-1">Nama hendaklah sama seperti dalam kad pengenalan</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    No. Kad Pengenalan <span class="required">*</span>
                                </label>
                                <input type="text" name="applicant_nric" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                       placeholder="000000-00-0000" 
                                       pattern="\d{6}-\d{2}-\d{4}" 
                                       maxlength="14"
                                       required
                                       oninput="this.value = formatNRIC(this.value);">
                                <p class="text-xs text-gray-500 mt-1">Format: 000000-00-0000 (Contoh: 900101-14-5566)</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Alamat Emel <span class="required">*</span>
                                </label>
                                <input type="email" name="applicant_email" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                       placeholder="nama@domain.com" 
                                       required maxlength="255">
                                <p class="text-xs text-gray-500 mt-1">Resit pembayaran akan dihantar ke alamat emel ini</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombor Telefon <span class="required">*</span>
                                </label>
                                <input type="tel" name="applicant_phone" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                       placeholder="0123456789" 
                                       pattern="[0-9]{7,15}" 
                                       minlength="7" 
                                       maxlength="15"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Untuk pengesahan dan makluman pembayaran</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Terms and Conditions -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="section-title">
                        SYARAT DAN TERMA PEMBAYARAN
                    </div>
                    <div class="p-6">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold text-yellow-800 mb-2">⚠️ Penting untuk Dibaca</h4>
                            <ul class="list-disc list-inside space-y-1 text-sm text-yellow-700">
                                <li>Yuran permohonan adalah <strong>tidak boleh dikembalikan</strong> setelah pembayaran dibuat</li>
                                <li>Pembayaran yang berjaya <strong>tidak menjamin</strong> permohonan jawatan akan diterima</li>
                                <li>Yuran ini adalah untuk pemprosesan permohonan sahaja</li>
                                <li>Sila simpan resit pembayaran untuk rujukan masa hadapan</li>
                                <li>Sebarang pertanyaan berkaitan pembayaran, sila hubungi pihak MPHS</li>
                            </ul>
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-start space-x-3">
                                <input type="checkbox" name="agree_terms" value="1" required class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm text-gray-700">
                                    Saya <strong>FAHAM</strong> dan <strong>BERSETUJU</strong> dengan syarat dan terma pembayaran yang dinyatakan di atas <span class="required">*</span>
                                </span>
                            </label>
                            
                            <label class="flex items-start space-x-3">
                                <input type="checkbox" name="confirm_details" value="1" required class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm text-gray-700">
                                    Saya mengesahkan bahawa semua maklumat yang diberikan adalah <strong>BENAR</strong> dan <strong>TEPAT</strong> <span class="required">*</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>


                <!-- Submit Button -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex justify-center">
                            <button type="submit" id="submitBtn" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg transition duration-200 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v2a2 2 0 002 2z"></path>
                                </svg>
                                Teruskan ke Pembayaran (RM <?php echo number_format($config['payment']['amount'], 2); ?>)
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 text-center mt-4">
                            Anda akan diarahkan ke laman pembayaran yang selamat untuk menyelesaikan transaksi
                        </p>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script>
        // Function to format NRIC with dashes (Malaysian IC format)
        function formatNRIC(value) {
            // Remove all non-digits
            const numbers = value.replace(/\D/g, '');
            
            // Limit to 12 digits
            const limitedNumbers = numbers.slice(0, 12);
            
            // Format with dashes (000000-00-0000)
            if (limitedNumbers.length <= 6) {
                return limitedNumbers;
            } else if (limitedNumbers.length <= 8) {
                return limitedNumbers.slice(0, 6) + '-' + limitedNumbers.slice(6);
            } else {
                return limitedNumbers.slice(0, 6) + '-' + limitedNumbers.slice(6, 8) + '-' + limitedNumbers.slice(8);
            }
        }

        // Store form data in session storage
        function storeFormData() {
            const formData = {
                applicant_name: document.querySelector('input[name="applicant_name"]').value,
                applicant_nric: document.querySelector('input[name="applicant_nric"]').value.replace(/\D/g, ''),
                applicant_email: document.querySelector('input[name="applicant_email"]').value,
                applicant_phone: document.querySelector('input[name="applicant_phone"]').value,
                job_code: document.querySelector('input[name="job_code"]').value
            };
            sessionStorage.setItem('paymentFormData', JSON.stringify(formData));
        }

        document.addEventListener('DOMContentLoaded', function() {
            // NRIC input formatting and validation
            const nricInput = document.querySelector('input[name="applicant_nric"]');
            if (nricInput) {
                // Format while typing
                nricInput.addEventListener('input', function(e) {
                    const cursorPosition = this.selectionStart;
                    const oldValue = this.value;
                    const newValue = formatNRIC(this.value);
                    this.value = newValue;

                    // Restore cursor position
                    if (cursorPosition < oldValue.length) {
                        const cursorOffset = newValue.length - oldValue.length;
                        this.setSelectionRange(cursorPosition + cursorOffset, cursorPosition + cursorOffset);
                    }
                });

                // Validate on blur
                nricInput.addEventListener('blur', function() {
                    const nric = this.value.replace(/\D/g, '');
                    if (nric.length === 12) {
                        // Extract date components
                        const year = parseInt(nric.substring(0, 2));
                        const month = parseInt(nric.substring(2, 4));
                        const day = parseInt(nric.substring(4, 6));
                        
                        // Determine century (00-29 → 2000s, 30-99 → 1900s)
                        const fullYear = year + (year < 30 ? 2000 : 1900);
                        
                        // Check if date is valid
                        const date = new Date(fullYear, month - 1, day);
                        const isValidDate = date.getMonth() === month - 1 && 
                                         date.getDate() === day &&
                                         date.getFullYear() === fullYear &&
                                         month >= 1 && month <= 12 &&
                                         day >= 1 && day <= 31;
                        
                        if (!isValidDate) {
                            this.setCustomValidity('Sila masukkan tarikh yang sah dalam format YYMMDD');
                            this.classList.add('border-red-500');
                        } else {
                            this.setCustomValidity('');
                            this.classList.remove('border-red-500');
                        }
                    } else if (nric.length > 0) {
                        this.setCustomValidity('No. Kad Pengenalan mestilah 12 digit');
                        this.classList.add('border-red-500');
                    }
                });
            }

            // Auto-uppercase name input
            const nameInput = document.querySelector('input[name="applicant_name"]');
            if (nameInput) {
                nameInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }

            // Form submission handling
            const form = document.getElementById('paymentForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                    // Basic validation
                    const requiredFields = this.querySelectorAll('[required]');
                    let allValid = true;
                    
                    requiredFields.forEach(field => {
                        if (field.type === 'checkbox') {
                            if (!field.checked) {
                                allValid = false;
                                field.classList.add('border-red-500');
                            } else {
                                field.classList.remove('border-red-500');
                            }
                        } else if (!field.value.trim()) {
                            allValid = false;
                            field.classList.add('border-red-500');
                        } else {
                            field.classList.remove('border-red-500');
                        }
                    });
                    
                    if (!allValid) {
                        e.preventDefault();
                        alert('Sila lengkapkan semua medan yang diperlukan dan tandakan semua kotak pengesahan.');
                        return false;
                    }
                    
                    // Store form data before submission
                    storeFormData();

                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses...
                    `;
                    
                    return true;
                });
            }

            // Email validation
            const emailInput = document.querySelector('input[name="applicant_email"]');
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    const email = this.value.trim();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (email && !emailRegex.test(email)) {
                        this.classList.add('border-red-500');
                        this.setCustomValidity('Sila masukkan alamat emel yang sah');
                    } else {
                        this.classList.remove('border-red-500');
                        this.setCustomValidity('');
                    }
                });
            }

            // Phone validation
            const phoneInput = document.querySelector('input[name="applicant_phone"]');
            if (phoneInput) {
                phoneInput.addEventListener('blur', function() {
                    const phone = this.value.trim();
                    
                    if (phone && (phone.length < 7 || phone.length > 15)) {
                        this.classList.add('border-red-500');
                        this.setCustomValidity('Nombor telefon hendaklah antara 7 hingga 15 digit');
                    } else {
                        this.classList.remove('border-red-500');
                        this.setCustomValidity('');
                    }
                });
            }
        });
    </script>
</body>
</html>
