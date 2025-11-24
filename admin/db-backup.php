<?php
/**
 * Database Backup Management System
 * Modern implementation with enhanced security and user experience
 */

session_start();

// Security check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Load configuration
$config = include('../config.php');

// Include admin logger for activity tracking
require_once 'includes/admin_logger.php';

// Initialize PDO connection for admin logging
try {
    $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed for admin logging: " . $e->getMessage());
    $pdo = null;
}

// Initialize variables
$message = '';
$error = '';
$backups = [];

// Set backup directory
$backupDir = '../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Logging system
$logFile = '../logs/backup_system.log';
function logBackupActivity($message, $level = 'INFO') {
    global $logFile;
    
    // Only log ERROR and WARNING levels to backup_system.log
    // Skip INFO level messages (successful operations)
    if ($level !== 'ERROR' && $level !== 'WARNING') {
        return;
    }
    
    // Ensure logs directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
    
    // Add error handling for file writing
    if (file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) === false) {
        error_log("Failed to write to backup log file: {$logFile}");
    }
}

// Database backup function using mysqldump
function createBackup($config, $backupDir) {
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "backup_{$config['db_name']}_{$timestamp}";
    $sqlFile = "{$backupDir}/{$filename}.sql";
    $zipFile = "{$backupDir}/{$filename}.zip";
    
    logBackupActivity("Starting backup creation for database: {$config['db_name']}");
    
    // Try mysqldump first
    $mysqldumpPath = 'mysqldump';
    $command = sprintf(
        '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s 2>&1',
        $mysqldumpPath,
        escapeshellarg($config['db_host']),
        escapeshellarg($config['db_port']),
        escapeshellarg($config['db_user']),
        escapeshellarg($config['db_pass']),
        escapeshellarg($config['db_name']),
        escapeshellarg($sqlFile)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($sqlFile) && filesize($sqlFile) > 0) {
        // Create ZIP file
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($sqlFile, basename($sqlFile));
            $zip->close();
            
            // Remove SQL file, keep only ZIP
            unlink($sqlFile);
            
            $fileSize = filesize($zipFile);
            // Only log to backup_system.log for errors, not success
            // logBackupActivity("Backup created successfully: {$filename}.zip ({$fileSize} bytes)");
            
            // Log to admin activity logs instead
            if ($pdo) {
                log_admin_action(
                    "Database backup created successfully: {$filename}.zip", 
                    'CREATE', 
                    'backup', 
                    null, 
                    [
                        'filename' => "{$filename}.zip",
                        'size' => $fileSize,
                        'method' => 'mysqldump',
                        'database' => $config['db_name']
                    ]
                );
            }
            
            return [
                'success' => true,
                'filename' => "{$filename}.zip",
                'size' => $fileSize,
                'method' => 'mysqldump'
            ];
        }
    }
    
    // Fallback to PDO method
    return createBackupPDO($config, $backupDir, $filename);
}

// PDO backup fallback
function createBackupPDO($config, $backupDir, $filename) {
    try {
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $sqlFile = "{$backupDir}/{$filename}.sql";
        $zipFile = "{$backupDir}/{$filename}.zip";
        
        // Build SQL dump
        $sql = "-- Database Backup: {$config['db_name']}\n";
        $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        // Get all tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            // Table structure
            $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createTable['Create Table'] . ";\n\n";
            
            // Table data
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $values = array_map(function($value) use ($pdo) {
                        return $value === null ? 'NULL' : $pdo->quote($value);
                    }, array_values($row));
                    $columns = '`' . implode('`, `', array_keys($row)) . '`';
                    $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        // Write SQL file
        file_put_contents($sqlFile, $sql);
        
        // Create ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($sqlFile, basename($sqlFile));
            $zip->close();
            unlink($sqlFile);
            
            $fileSize = filesize($zipFile);
            // Only log to backup_system.log for errors, not success
            // logBackupActivity("PDO backup created successfully: {$filename}.zip ({$fileSize} bytes)");
            
            // Log to admin activity logs instead
            if ($pdo) {
                log_admin_action(
                    "Database backup created successfully: {$filename}.zip", 
                    'CREATE', 
                    'backup', 
                    null, 
                    [
                        'filename' => "{$filename}.zip",
                        'size' => $fileSize,
                        'method' => 'PDO',
                        'database' => $config['db_name']
                    ]
                );
            }
            
            return [
                'success' => true,
                'filename' => "{$filename}.zip",
                'size' => $fileSize,
                'method' => 'PDO'
            ];
        }
        
    } catch (Exception $e) {
        logBackupActivity("PDO backup failed: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'error' => $e->getMessage()];
    }
    
    return ['success' => false, 'error' => 'Unknown error occurred'];
}

// Get existing backups
function getBackupList($backupDir) {
    $backups = [];
    if (is_dir($backupDir)) {
        $files = glob($backupDir . '/backup_*.zip');
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'path' => $file
            ];
        }
        // Sort by date (newest first)
        usort($backups, function($a, $b) {
            return filemtime($b['path']) - filemtime($a['path']);
        });
    }
    return $backups;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_backup'])) {
        $result = createBackup($config, $backupDir);
        if ($result['success']) {
            $sizeInMB = $result['size'] / (1024 * 1024);
            $sizeDisplay = $sizeInMB < 0.1 ? 
                number_format($result['size'] / 1024, 2) . ' KB' : 
                number_format($sizeInMB, 2) . ' MB';
            $message = "Backup created successfully: {$result['filename']} ({$sizeDisplay})";
        } else {
            $error = "Backup failed: " . $result['error'];
        }
    } elseif (isset($_POST['delete_backup']) && isset($_POST['filename'])) {
        $filename = basename($_POST['filename']);
        $filepath = $backupDir . '/' . $filename;
        if (file_exists($filepath) && unlink($filepath)) {
            $message = "Backup deleted successfully: {$filename}";
            // Only log to backup_system.log for errors, not success
            // logBackupActivity("Backup deleted: {$filename}");
            
            // Log to admin activity logs instead
            if ($pdo) {
                log_admin_action(
                    "Database backup deleted: {$filename}", 
                    'DELETE', 
                    'backup', 
                    null, 
                    [
                        'filename' => $filename,
                        'filepath' => $filepath
                    ]
                );
            }
        } else {
            $error = "Failed to delete backup file.";
        }
    }
}

// Get backup list
$backups = getBackupList($backupDir);
?>

<?php include('templates/header.php'); ?>
        
        <div class="p-6">
                <div class="standard-container mx-auto">
                    <!-- Page Header -->
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900">Database Backup Management</h1>
                        <p class="text-gray-600 mt-2">Create, manage, and download database backups securely</p>
                    </div>

                    <!-- Status Messages -->
                    <?php if ($message): ?>
                        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-green-700"><?php echo htmlspecialchars($message); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Database Info & Backup Creation -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Create New Backup</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="font-medium text-gray-900 mb-2">Database Information</h3>
                                    <div class="space-y-1 text-sm text-gray-600">
                                        <p><span class="font-medium">Database:</span> <?php echo htmlspecialchars($config['db_name']); ?></p>
                                        <p><span class="font-medium">Host:</span> <?php echo htmlspecialchars($config['db_host']); ?></p>
                                        <p><span class="font-medium">Port:</span> <?php echo htmlspecialchars($config['db_port']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <h3 class="font-medium text-gray-900 mb-2">Backup Information</h3>
                                    <div class="space-y-1 text-sm text-gray-600">
                                        <p><span class="font-medium">Format:</span> ZIP compressed SQL dump</p>
                                        <p><span class="font-medium">Location:</span> /backups/</p>
                                        <p><span class="font-medium">Includes:</span> Structure + Data + Triggers</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                <button id="backupBtn" type="button" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg id="backupIcon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                                    </svg>
                                    <div id="loadingSpinner" class="hidden w-5 h-5 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    <span id="backupBtnText">Create Backup Now</span>
                                </button>
                                <p class="text-sm text-gray-500">This will create a complete database backup including all tables and data.</p>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div id="progressContainer" class="hidden mt-4">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                                <p id="progressText" class="text-sm text-gray-600 mt-2">Preparing backup...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Backup History -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Backup History</h2>
                            <p class="text-gray-600 mt-1">Manage your existing database backups</p>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <?php if (empty($backups)): ?>
                                <div class="p-8 text-center">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No backups found</h3>
                                    <p class="text-gray-600">Create your first backup to get started.</p>
                                </div>
                            <?php else: ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filename</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Created</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($backups as $backup): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <svg class="w-5 h-5 text-blue-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($backup['filename']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($backup['date']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    <?php 
                                                    $sizeInMB = $backup['size'] / (1024 * 1024);
                                                    if ($sizeInMB < 0.1) {
                                                        echo number_format($backup['size'] / 1024, 2) . ' KB';
                                                    } else {
                                                        echo number_format($sizeInMB, 2) . ' MB';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                    <a href="download-backup.php?file=<?php echo urlencode($backup['filename']); ?>" 
                                                       class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l4-4m-4 4l-4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        Download
                                                    </a>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this backup?');">
                                                        <input type="hidden" name="filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                        <button type="submit" name="delete_backup" value="1"
                                                                class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-gray-900">Backup Complete!</h3>
                </div>
            </div>
            <div class="mb-4">
                <p id="modalMessage" class="text-sm text-gray-600"></p>
            </div>
            <div class="flex justify-end">
                <button id="closeModal" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                    OK
                </button>
            </div>
        </div>
    </div>

    <script>
        // Backup functionality with AJAX
        document.getElementById('backupBtn').addEventListener('click', function() {
            const btn = this;
            const btnText = document.getElementById('backupBtnText');
            const backupIcon = document.getElementById('backupIcon');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            
            // Disable button and show loading state
            btn.disabled = true;
            btnText.textContent = 'Creating Backup...';
            backupIcon.classList.add('hidden');
            loadingSpinner.classList.remove('hidden');
            
            // Show progress bar
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '10%';
            progressText.textContent = 'Initializing backup process...';
            
            // Simulate progress updates
            let progress = 10;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 20;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
                
                if (progress < 30) {
                    progressText.textContent = 'Connecting to database...';
                } else if (progress < 60) {
                    progressText.textContent = 'Exporting database structure...';
                } else if (progress < 90) {
                    progressText.textContent = 'Exporting data and creating archive...';
                }
            }, 200);
            
            // Make AJAX request
            fetch('backup-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=create_backup'
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                
                // Complete progress bar
                progressBar.style.width = '100%';
                progressText.textContent = 'Backup completed successfully!';
                
                setTimeout(() => {
                    // Hide progress bar
                    progressContainer.classList.add('hidden');
                    
                    // Reset button
                    btn.disabled = false;
                    btnText.textContent = 'Create Backup Now';
                    backupIcon.classList.remove('hidden');
                    loadingSpinner.classList.add('hidden');
                    
                    if (data.success) {
                        // Show success modal
                        document.getElementById('modalMessage').textContent = 
                            `Backup "${data.filename}" created successfully! Size: ${data.size}`;
                        document.getElementById('successModal').classList.remove('hidden');
                        document.getElementById('successModal').classList.add('flex');
                        
                        // Refresh page after modal is closed to show new backup in list
                        document.getElementById('closeModal').addEventListener('click', function() {
                            location.reload();
                        });
                    } else {
                        alert('Backup failed: ' + (data.error || 'Unknown error'));
                    }
                }, 1000);
            })
            .catch(error => {
                clearInterval(progressInterval);
                console.error('Error:', error);
                
                // Hide progress bar
                progressContainer.classList.add('hidden');
                
                // Reset button
                btn.disabled = false;
                btnText.textContent = 'Create Backup Now';
                backupIcon.classList.remove('hidden');
                loadingSpinner.classList.add('hidden');
                
                alert('An error occurred while creating the backup. Please try again.');
            });
        });
        
        // Close modal functionality
        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('successModal').classList.add('hidden');
            document.getElementById('successModal').classList.remove('flex');
        });
        
        // Close modal when clicking outside
        document.getElementById('successModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                this.classList.remove('flex');
            }
        });
    </script>
        // Auto-refresh page after successful backup creation
        <?php if ($message && strpos($message, 'successfully') !== false): ?>
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        <?php endif; ?>
    </script>
<?php include 'templates/footer.php'; ?>
