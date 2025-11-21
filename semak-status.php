<?php
require_once 'includes/ErrorHandler.php';
require_once __DIR__ . '/config.php';
session_start();

// Check if status check feature is enabled
if (!isset($config['navigation']['show_status_check']) || !$config['navigation']['show_status_check']) {
    http_response_code(404);
    include '404.php';
    exit;
}

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

// Get URL parameters for auto-fill
$prefill_app_ref = $_GET['app_ref'] ?? '';
$prefill_nric = $_GET['nric'] ?? '';

// Format NRIC if provided (ensure it has proper dashes)
if ($prefill_nric && !preg_match('/^\d{6}-\d{2}-\d{4}$/', $prefill_nric)) {
    // Remove all non-numeric characters and reformat
    $clean_nric = preg_replace('/[^0-9]/', '', $prefill_nric);
    if (strlen($clean_nric) === 12) {
        $prefill_nric = substr($clean_nric, 0, 6) . '-' . substr($clean_nric, 6, 2) . '-' . substr($clean_nric, 8, 4);
    }
}

// Clear flash messages
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semak Status Permohonan - eJawatan MPHS</title>
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
        .auto-filled {
            background-color: #f0f9ff;
            border-color: #0ea5e9;
        }
        .auto-submit-info {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="min-h-screen">
    <?php include 'header.php'; ?>

    <main class="standard-container px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Title -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Semak Status Permohonan</h1>
            <p class="mt-1 text-sm text-gray-600">Sila masukkan maklumat permohonan anda untuk menyemak status</p>
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

        <!-- Status Check Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="section-title">
                SEMAK STATUS PERMOHONAN
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
                            <p class="text-sm text-blue-700<?php echo ($prefill_app_ref && $prefill_nric) ? ' auto-submit-info' : ''; ?>" id="info-message">
                                <?php if ($prefill_app_ref && $prefill_nric): ?>
                                    ✅ Maklumat telah diisi secara automatik. Sistem akan menyemak status dalam sebentar...
                                <?php else: ?>
                                    Sila masukkan No. Kad Pengenalan dan Rujukan Permohonan anda untuk menyemak status.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Status Check Form -->
                <div class="space-y-6">
                    <form id="statusForm" action="application-status.php" method="GET" class="space-y-6" onsubmit="console.log('Form submitting with:', new FormData(this))">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Application Reference -->
                            <div>
                                <label for="application_ref" class="block text-sm font-medium text-gray-700">
                                    Rujukan Permohonan <span class="required">*</span>
                                </label>
                                <div class="mt-1">
                                    <input type="text" name="application_ref" id="application_ref" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase-input<?php echo $prefill_app_ref ? ' auto-filled' : ''; ?>"
                                        placeholder="Contoh: APP-2025-0003-95B0FF34"
                                        pattern="APP-[A-Z0-9-]+"
                                        maxlength="50"
                                        value="<?php echo htmlspecialchars($prefill_app_ref); ?>"
                                        <?php echo $prefill_app_ref ? 'readonly' : ''; ?>>
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
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono<?php echo $prefill_nric ? ' auto-filled' : ''; ?>"
                                        placeholder="Contoh: 098765-01-1234"
                                        pattern="\d{6}-\d{2}-\d{4}"
                                        maxlength="14"
                                        value="<?php echo htmlspecialchars($prefill_nric); ?>"
                                        <?php echo $prefill_nric ? 'readonly' : ''; ?>>
                                </div>
                                <div class="mt-1 space-y-1">
                                    <p class="text-xs text-gray-500">Format: XXXXXX-XX-XXXX</p>                          
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center">
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-8 rounded-lg transition duration-200 flex items-center"
                                onclick="console.log('Button clicked, submitting form...')">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <?php echo ($prefill_app_ref && $prefill_nric) ? 'Menyemak Status...' : 'Semak Status'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-8 bg-white rounded-lg shadow-md overflow-hidden">
            <div class="section-title">
                BANTUAN
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700">Bagaimana untuk menyemak status?</h3>
                        <div class="mt-2 text-sm text-gray-500">
                            <ol class="list-decimal list-inside space-y-2">
                                <li>Masukkan Rujukan Permohonan (APP-XXXXXXX)</li>
                                <li>Masukkan No. Kad Pengenalan anda</li>
                                <li>Klik butang "Semak Status"</li>
                                <li>Status permohonan anda akan dipaparkan dengan segera</li>
                            </ol>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-700">Di mana boleh mendapatkan maklumat?</h3>
                        <div class="mt-2 text-sm text-gray-500">
                            <ul class="list-disc list-inside space-y-2">
                                <li><strong>Rujukan Permohonan:</strong> Boleh didapati dalam email pengesahan (contoh: APP-2025-0003-95B0FF34)</li>
                                <li><strong>No. Kad Pengenalan:</strong> Nombor kad pengenalan yang digunakan semasa memohon (contoh: 830412-05-6444)</li>
                                <li>Pastikan kedua-dua maklumat adalah betul dan sepadan</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Debug Section (for testing) -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">🔧 Debug/Test</h4>
                        <p class="text-xs text-gray-600 mb-3">Untuk tujuan debugging, klik link di bawah untuk test secara manual:</p>
                        <div class="space-y-2">
                            <button type="button" onclick="testBackend()" class="text-xs bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-1 rounded">
                                Test Backend Connection
                            </button>
                            <button type="button" onclick="testAutoFill()" class="text-xs bg-green-100 hover:bg-green-200 text-green-800 px-3 py-1 rounded">
                                Test Auto-Fill
                            </button>
                            <div id="debug-output" class="text-xs text-gray-600 mt-2 hidden"></div>
                        </div>
                        <?php if ($prefill_app_ref || $prefill_nric): ?>
                        <div class="mt-3 p-2 bg-blue-50 rounded text-xs">
                            <strong>URL Parameters Received:</strong><br>
                            App Ref: <?php echo htmlspecialchars($prefill_app_ref ?: 'Not provided'); ?><br>
                            NRIC: <?php echo htmlspecialchars($prefill_nric ?: 'Not provided'); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusForm = document.getElementById('statusForm');
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
        statusForm.addEventListener('submit', function(e) {
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

            console.log('Form validation passed:', { nric, appRef });

            // Show loading state
            const submitBtn = statusForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Menyemak...';
                submitBtn.disabled = true;
            }

            // Log the final URL for debugging
            const finalUrl = `application-status.php?nric=${encodeURIComponent(nric)}&application_ref=${encodeURIComponent(appRef)}`;
            console.log('Final URL:', finalUrl);

            return true;
        });

        // Auto-submit form if both fields are pre-filled and valid
        if (appRefInput.value && nricInput.value) {
            // Validate the pre-filled values
            const nric = nricInput.value.trim();
            const appRef = appRefInput.value.trim();
            
            console.log('Checking auto-submit conditions:', { nric, appRef });
            
            if (/^\d{6}-\d{2}-\d{4}$/.test(nric) && /^APP-[A-Z0-9-]+$/.test(appRef)) {
                console.log('Auto-submit conditions met, preparing to submit...');
                
                // Show loading state immediately
                const submitBtn = statusForm.querySelector('button[type="submit"]');
                const infoMessage = document.getElementById('info-message');
                
                if (submitBtn) {
                    submitBtn.innerHTML = '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Menyemak Status...';
                    submitBtn.disabled = true;
                }
                
                if (infoMessage) {
                    infoMessage.textContent = 'Sedang menyemak status permohonan anda...';
                }
                
                // Add a small delay to show the form is populated, then auto-submit
                setTimeout(function() {
                    console.log('Auto-submitting form with pre-filled values:', { nric, appRef });
                    statusForm.submit();
                }, 1500);
            } else {
                console.log('Auto-submit validation failed:', {
                    nricValid: /^\d{6}-\d{2}-\d{4}$/.test(nric),
                    appRefValid: /^APP-[A-Z0-9-]+$/.test(appRef)
                });
            }
        } else {
            console.log('No auto-submit: fields not pre-filled');
        }

        // Test backend connection function
        window.testBackend = async function() {
            const debugOutput = document.getElementById('debug-output');
            debugOutput.classList.remove('hidden');
            debugOutput.innerHTML = '<div class="text-blue-600">Testing connection...</div>';

            try {
                // Test database connection
                const response = await fetch('application-status.php?test=1', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    debugOutput.innerHTML = '<div class="text-green-600">✅ Backend connection successful</div>';
                } else {
                    debugOutput.innerHTML = `<div class="text-red-600">❌ Backend connection failed: ${response.status}</div>`;
                }
            } catch (error) {
                debugOutput.innerHTML = `<div class="text-red-600">❌ Connection error: ${error.message}</div>`;
            }
        };
        
        // Test auto-fill function
        window.testAutoFill = function() {
            const testUrl = 'semak-status.php?app_ref=APP-2025-0001-TEST123&nric=830412056444';
            window.location.href = testUrl;
        };
    });
    </script>
</body>
</html>