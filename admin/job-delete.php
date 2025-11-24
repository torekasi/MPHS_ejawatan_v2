<?php
session_start();
require_once '../includes/bootstrap.php';
require_once 'includes/error_handler.php';
require_once 'includes/admin_logger.php'; // Include admin logger

// Get database connection from main config
$config = require '../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

// Handle AJAX deletion and acknowledgment EARLY to avoid HTML redirects breaking JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Ensure admin session for AJAX calls without redirecting to HTML login
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Sesi admin tidak sah. Sila log masuk semula.']);
        exit;
    }

    // Acknowledgment branch: log only after user clicks OK on success
    if ($_POST['action'] === 'delete_ack') {
        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $job_code = $_POST['job_code'] ?? null;
            $job_title = $_POST['job_title'] ?? null;

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Parameter ACK tidak sah']);
                exit;
            }

            $log_details = [
                'job_code' => $job_code,
                'job_title' => $job_title,
                'acknowledged_at' => date('Y-m-d H:i:s')
            ];
            // Use integer entity_id to satisfy admin_logs schema
            log_admin_action('Deleted job posting acknowledged', 'DELETE', 'job', $id, $log_details);

            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            logError($e->getMessage(), 'DATABASE_ERROR');
            echo json_encode(['success' => false, 'message' => 'Ralat ack: ' . $e->getMessage()]);
            exit;
        }
    }

    // Deletion branch: perform deletion but DO NOT log yet
    if ($_POST['action'] === 'delete') {
        try {
            $job_id = $_POST['job_id'] ?? '';
            $job_type = $_POST['job_type'] ?? '';
            
            if (empty($job_id) || empty($job_type)) {
                echo json_encode(['success' => false, 'message' => 'Parameter tidak sah']);
                exit;
            }
            
            // Fetch job details first to return for acknowledgment logging
            if ($job_type === 'job_code') {
                $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE job_code = ? LIMIT 1');
                $stmt->execute([$job_id]);
            } else {
                $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
                $stmt->execute([$job_id]);
            }
            
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$job) {
                echo json_encode(['success' => false, 'message' => 'Jawatan tidak dijumpai']);
                exit;
            }
            
            // Delete the job
            if ($job_type === 'job_code') {
                $stmt = $pdo->prepare('DELETE FROM job_postings WHERE job_code = ?');
                $stmt->execute([$job_id]);
            } else {
                $stmt = $pdo->prepare('DELETE FROM job_postings WHERE id = ?');
                $stmt->execute([$job_id]);
            }
            
            // Prepare details to be used in the acknowledgment step
            $formatted_job_id = $job['job_code'] ?? ('JOB-' . str_pad($job['id'], 6, '0', STR_PAD_LEFT));
            $log_details = [
                'job_id' => $formatted_job_id,
                'job_title' => $job['job_title'],
                'deleted_at' => date('Y-m-d H:i:s')
            ];

            // Return success and the deleted_job details to the frontend
            echo json_encode([
                'success' => true,
                'message' => 'Jawatan berjaya dipadam',
                'deleted_job' => [
                    'id' => $job['id'],
                    'job_code' => $job['job_code'] ?? null,
                    'job_title' => $job['job_title']
                ]
            ]);
            exit;
            
        } catch (Exception $e) {
            logError($e->getMessage(), 'DATABASE_ERROR');
            echo json_encode(['success' => false, 'message' => 'Ralat pangkalan data: ' . $e->getMessage()]);
            exit;
        }
    }
}

// Only load auth after handling AJAX path to preserve JSON responses
require_once 'auth.php';

// Check for job_code parameter first, then fallback to id
if (isset($_GET['job_code']) && !empty($_GET['job_code'])) {
    $job_code = $_GET['job_code'];
    logError('Job Code Parameter in job-delete.php: ' . $job_code, 'DEBUG_INFO');
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    logError('ID Parameter in job-delete.php: ' . $id, 'DEBUG_INFO');
} else {
    logError('No valid job_code or ID parameter provided in job-delete.php', 'DEBUG_INFO');
    
    // Set error notification
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Parameter jawatan tidak sah.'
    ];
    
    header('Location: job-list.php');
    exit;
}

try {
    // Fetch job details first for logging
    if (isset($job_code)) {
        $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE job_code = ? LIMIT 1');
        $stmt->execute([$job_code]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
    }
    
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        // Job not found
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Jawatan tidak dijumpai.'
        ];
        
        header('Location: job-list.php');
        exit;
    }
    
    // Confirm deletion if not already confirmed
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
        // Include header
        include 'templates/header.php';
        ?>
        <!-- Add CSS for truncating content -->
        <style>
            .requirements-truncated {
                max-height: 150px;
                overflow: hidden;
                position: relative;
            }
            .requirements-expanded {
                max-height: none;
                overflow: visible;
            }
            .gradient-overlay {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 40px;
                background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(255,255,255,1));
            }
        </style>
        <div class="standard-container mx-auto py-6">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">
                            Adakah anda pasti ingin memadam jawatan ini? Tindakan ini tidak boleh diundurkan.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Job details card -->
            <div class="p-6 bg-white">
                <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2"><?php echo htmlspecialchars(strtoupper($job['job_title'])); ?></h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="flex items-center">
                        <div class="bg-blue-100 rounded-full p-2 mr-3">
                            <svg class="h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 4h3a3 3 0 006 0h3a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm2.5 7a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm2.45 4a2.5 2.5 0 10-4.9 0h4.9zM12 9a1 1 0 100 2h3a1 1 0 100-2h-3zm-1 4a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">ID Jawatan</p>
                            <p class="font-mono font-medium"><?php echo 'JOB-' . str_pad($job['id'], 6, '0', STR_PAD_LEFT); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="bg-purple-100 rounded-full p-2 mr-3">
                            <svg class="h-5 w-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Kod & Gred</p>
                            <p class="font-medium"><?php echo htmlspecialchars($job['kod_gred']); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <p class="text-sm text-gray-500 mb-2">Tarikh Iklan</p>
                    <p class="font-medium"><?php echo htmlspecialchars(date('d/m/Y', strtotime($job['ad_date']))); ?></p>
                </div>
                
                <div class="mb-6">
                    <p class="text-sm text-gray-500 mb-2">Tarikh Tutup</p>
                    <p class="font-medium"><?php echo htmlspecialchars(date('d/m/Y', strtotime($job['ad_close_date']))); ?></p>
                </div>
                
                <div class="mb-6">
                    <p class="text-sm text-gray-500 mb-2">Maklumat Jawatan</p>
                    <div id="requirements-content" class="requirements-truncated">
                        <?php echo $job['requirements']; ?>
                        <div class="gradient-overlay"></div>
                    </div>
                    <button id="read-more-btn" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition flex items-center">
                        <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        <span>Baca Lagi</span>
                    </button>
                </div>
                
                <div class="flex items-center justify-end space-x-3 border-t pt-4">
                    <a href="<?php echo isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'job-list.php'; ?>" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-200 transition flex items-center">
                        <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.793 2.232a.75.75 0 01-.025 1.06L3.622 7.25h10.003a5.375 5.375 0 010 10.75H10.75a.75.75 0 010-1.5h2.875a3.875 3.875 0 000-7.75H3.622l4.146 3.957a.75.75 0 01-1.036 1.085l-5.5-5.25a.75.75 0 010-1.085l5.5-5.25a.75.75 0 011.06.025z" clip-rule="evenodd" />
                        </svg>
                        Batal
                    </a>
                    <a href="job-delete.php?<?php echo isset($job_code) ? 'job_code=' . urlencode($job_code) : 'id=' . urlencode($id); ?>&confirm=yes" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 transition flex items-center">
                        <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011 .999H8.75z" clip-rule="evenodd" />
                        </svg>
                        Ya, Padam Jawatan
                    </a>
                </div>
            </div>
        </div>
        <?php
        // Add JavaScript for Read More functionality
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const requirementsContent = document.getElementById('requirements-content');
                const readMoreBtn = document.getElementById('read-more-btn');
                
                if (requirementsContent && readMoreBtn) {
                    readMoreBtn.addEventListener('click', function() {
                        if (requirementsContent.classList.contains('requirements-truncated')) {
                            // Expand content
                            requirementsContent.classList.remove('requirements-truncated');
                            requirementsContent.classList.add('requirements-expanded');
                            
                            // Change button text and icon
                            readMoreBtn.querySelector('span').textContent = 'Tutup';
                            readMoreBtn.querySelector('svg').innerHTML = '<path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />';
                        } else {
                            // Collapse content
                            requirementsContent.classList.add('requirements-truncated');
                            requirementsContent.classList.remove('requirements-expanded');
                            
                            // Change button text and icon back
                            readMoreBtn.querySelector('span').textContent = 'Baca Lagi';
                            readMoreBtn.querySelector('svg').innerHTML = '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />';
                            
                            // Scroll back to the requirements section
                            requirementsContent.scrollIntoView({ behavior: 'smooth' });
                        }
                    });
                    
                    // Check if content is short enough to not need truncation
                    if (requirementsContent.scrollHeight <= 150) {
                        readMoreBtn.style.display = 'none';
                        requirementsContent.classList.remove('requirements-truncated');
                        requirementsContent.style.maxHeight = 'none';
                    }
                }
            });
        </script>
        <?php
        include 'templates/footer.php';
        exit;
    }
    
    // Delete the job based on available parameter
    if (isset($job_code)) {
        $stmt = $pdo->prepare('DELETE FROM job_postings WHERE job_code = ?');
        $stmt->execute([$job_code]);
    } else {
        $stmt = $pdo->prepare('DELETE FROM job_postings WHERE id = ?');
        $stmt->execute([$id]);
    }
    
    // Prepare deleted job details for acknowledgment logging later
    $formatted_job_id = $job['job_code'] ?? ('JOB-' . str_pad($job['id'], 6, '0', STR_PAD_LEFT));
    
    // Set success notification with deleted job details for ack
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Jawatan berjaya dipadam!',
        'deleted_job' => [
            'id' => $job['id'],
            'job_code' => $job['job_code'] ?? null,
            'job_title' => $job['job_title']
        ]
    ];
    
    header('Location: job-list.php');
    exit;
    
} catch (Exception $e) {
    logError($e->getMessage(), 'DATABASE_ERROR');
    
    // Set error notification
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Ralat pangkalan data: ' . htmlspecialchars($e->getMessage())
    ];
    
    header('Location: job-list.php');
    exit;
}
?>
