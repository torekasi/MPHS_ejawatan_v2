<?php
/**
 * View Uploaded Files
 * 
 * This script allows applicants to view their uploaded files
 * using their application reference.
 */

// Start output buffering for clean output
ob_start();
session_start();

// Include required files
require_once 'config.php';
require_once 'modules/FileUploaderImplementation.php';

// Set up error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get application reference from GET or POST
$application_ref = isset($_REQUEST['ref']) ? trim($_REQUEST['ref']) : '';
$nric = isset($_REQUEST['nric']) ? trim($_REQUEST['nric']) : '';

// Initialize variables
$files = [];
$error_message = '';
$success = false;
$year = date('Y');

// Process form submission
if (!empty($application_ref) && !empty($nric)) {
    // Connect to database
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
        
        // Normalize NRIC
        $clean_nric = preg_replace('/[^0-9]/', '', $nric);
        $formatted_nric = '';
        if (strlen($clean_nric) == 12) {
            $formatted_nric = substr($clean_nric, 0, 6) . '-' . substr($clean_nric, 6, 2) . '-' . substr($clean_nric, 8, 4);
        } else {
            $formatted_nric = $nric; // Use as is if not in expected format
        }
        
        // Verify application ownership
        $stmt = $pdo->prepare("SELECT * FROM application_application_main WHERE application_reference = ? AND nombor_ic = ? LIMIT 1");
        $stmt->execute([$application_ref, $formatted_nric]);
        $application = $stmt->fetch();
        
        if (!$application) {
            // Try legacy table
            $stmt = $pdo->prepare("SELECT * FROM job_applications WHERE application_reference = ? AND nombor_ic = ? LIMIT 1");
            $stmt->execute([$application_ref, $formatted_nric]);
            $application = $stmt->fetch();
        }
        
        if ($application) {
            $success = true;
            
            // Extract year from application date or use current year
            if (!empty($application['created_at'])) {
                $app_date = new DateTime($application['created_at']);
                $year = $app_date->format('Y');
            }
            
            // Get uploaded files
            $upload_dir = 'uploads/applications/' . $year . '/' . $application_ref;
            if (is_dir($upload_dir)) {
                $dir_files = scandir($upload_dir);
                foreach ($dir_files as $file) {
                    if ($file !== '.' && $file !== '..' && is_file($upload_dir . '/' . $file)) {
                        $file_path = $upload_dir . '/' . $file;
                        $file_size = filesize($file_path);
                        $file_type = mime_content_type($file_path);
                        $file_modified = date('Y-m-d H:i:s', filemtime($file_path));
                        
                        // Get a more user-friendly file name
                        $display_name = $file;
                        if (preg_match('/^([a-z_]+)_\d+_[a-f0-9]+\.([a-z]+)$/i', $file, $matches)) {
                            $prefix = str_replace('_', ' ', $matches[1]);
                            $extension = $matches[2];
                            $display_name = ucwords($prefix) . ' (' . strtoupper($extension) . ')';
                        }
                        
                        $files[] = [
                            'name' => $file,
                            'display_name' => $display_name,
                            'path' => $file_path,
                            'size' => $file_size,
                            'formatted_size' => formatBytes($file_size),
                            'type' => $file_type,
                            'modified' => $file_modified,
                            'is_image' => strpos($file_type, 'image/') === 0
                        ];
                    }
                }
            }
        } else {
            $error_message = 'Tiada rekod permohonan dijumpai dengan rujukan dan nombor IC yang diberikan.';
        }
    } catch (PDOException $e) {
        $error_message = 'Ralat sambungan ke pangkalan data. Sila cuba sebentar lagi.';
        error_log('Database error in view-uploads.php: ' . $e->getMessage());
    }
}

// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// HTML output
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Dokumen Dimuat Naik</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Lihat Dokumen Dimuat Naik</h1>
                
                <?php if (!$success): ?>
                <form action="view-uploads.php" method="POST" class="space-y-4">
                    <?php if ($error_message): ?>
                    <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700 mb-4">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <label for="ref" class="block text-sm font-medium text-gray-700 mb-1">Rujukan Permohonan</label>
                        <input type="text" id="ref" name="ref" value="<?php echo htmlspecialchars($application_ref); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                               placeholder="APP-20251029-ABCD1234" required>
                    </div>
                    
                    <div>
                        <label for="nric" class="block text-sm font-medium text-gray-700 mb-1">Nombor Kad Pengenalan</label>
                        <input type="text" id="nric" name="nric" value="<?php echo htmlspecialchars($nric); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                               placeholder="000000-00-0000" required>
                        <p class="text-xs text-gray-500 mt-1">Format: 000000-00-0000 atau 000000000000</p>
                    </div>
                    
                    <div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Lihat Dokumen
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Dokumen untuk Permohonan: <?php echo htmlspecialchars($application_ref); ?></h2>
                        <a href="view-uploads.php" class="text-blue-600 hover:underline">Semak Permohonan Lain</a>
                    </div>
                    
                    <?php if (empty($files)): ?>
                    <div class="p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700">
                        Tiada dokumen dijumpai untuk permohonan ini.
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 border-b text-left">Dokumen</th>
                                    <th class="px-4 py-2 border-b text-left">Saiz</th>
                                    <th class="px-4 py-2 border-b text-left">Jenis</th>
                                    <th class="px-4 py-2 border-b text-left">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $file): ?>
                                <tr>
                                    <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($file['display_name']); ?></td>
                                    <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($file['formatted_size']); ?></td>
                                    <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($file['type']); ?></td>
                                    <td class="px-4 py-2 border-b">
                                        <a href="<?php echo htmlspecialchars($file['path']); ?>" target="_blank" class="text-blue-600 hover:underline">Lihat</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($files as $file): ?>
                        <?php if ($file['is_image']): ?>
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="aspect-w-4 aspect-h-3 mb-2">
                                <img src="<?php echo htmlspecialchars($file['path']); ?>" alt="<?php echo htmlspecialchars($file['display_name']); ?>" class="object-cover rounded">
                            </div>
                            <div class="text-sm font-medium"><?php echo htmlspecialchars($file['display_name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($file['formatted_size']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center text-gray-600 text-sm mt-8">
                <p>&copy; <?php echo date('Y'); ?> Majlis Perbandaran. Hak Cipta Terpelihara.</p>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// End output buffering and send to browser
ob_end_flush();
?>
