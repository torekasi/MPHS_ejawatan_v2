<?php
/**
 * Upload Management Tool
 * 
 * This script provides an interface to view and manage uploaded files
 * in the standardized directory structure.
 */

// Start output buffering for clean output
ob_start();
session_start();

// Set up error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once '../config.php';
require_once '../includes/auth.php';

// Check if user is logged in and has admin privileges
if (!isLoggedIn() || !hasAdminPrivileges()) {
    header('Location: login.php');
    exit;
}

// Base upload directory
$base_dir = '../uploads/applications';

// Get year parameter
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get application reference parameter
$app_ref = isset($_GET['app_ref']) ? $_GET['app_ref'] : null;

// Get delete file parameter
$delete_file = isset($_GET['delete']) ? $_GET['delete'] : null;

// Handle file deletion
if ($delete_file && isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $file_path = realpath($base_dir . '/' . $year . '/' . $app_ref . '/' . $delete_file);
    $base_path = realpath($base_dir);
    
    // Security check: Make sure the file is within the uploads directory
    if ($file_path && strpos($file_path, $base_path) === 0 && file_exists($file_path)) {
        if (unlink($file_path)) {
            $_SESSION['success_message'] = 'File deleted successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to delete file.';
        }
    } else {
        $_SESSION['error_message'] = 'Invalid file path.';
    }
    
    // Redirect to remove the delete parameter from URL
    header('Location: manage-uploads.php?year=' . urlencode($year) . ($app_ref ? '&app_ref=' . urlencode($app_ref) : ''));
    exit;
}

// HTML header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Uploads</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Manage Uploaded Files</h1>';

// Display success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']);
}

// Display year selection
echo '<div class="mb-6">
    <h2 class="text-xl font-semibold mb-3">Select Year</h2>
    <div class="flex flex-wrap gap-2">';

// Get available years
$years = [];
if (is_dir($base_dir)) {
    $dir_contents = scandir($base_dir);
    foreach ($dir_contents as $item) {
        if ($item !== '.' && $item !== '..' && is_dir($base_dir . '/' . $item) && is_numeric($item)) {
            $years[] = $item;
        }
    }
    rsort($years); // Sort years in descending order
}

// If no years found, add current year
if (empty($years)) {
    $years[] = date('Y');
}

// Display year buttons
foreach ($years as $y) {
    $active_class = ($y == $year) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300';
    echo '<a href="manage-uploads.php?year=' . $y . '" class="px-4 py-2 rounded ' . $active_class . '">' . $y . '</a>';
}

echo '</div></div>';

// Display applications for selected year
$year_dir = $base_dir . '/' . $year;
if (is_dir($year_dir)) {
    if ($app_ref) {
        // Display files for specific application
        $app_dir = $year_dir . '/' . $app_ref;
        if (is_dir($app_dir)) {
            echo '<div class="mb-6">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-xl font-semibold">Files for Application: ' . htmlspecialchars($app_ref) . '</h2>
                    <a href="manage-uploads.php?year=' . $year . '" class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Back to Applications</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 border-b text-left">File Name</th>
                                <th class="px-4 py-2 border-b text-left">Size</th>
                                <th class="px-4 py-2 border-b text-left">Type</th>
                                <th class="px-4 py-2 border-b text-left">Last Modified</th>
                                <th class="px-4 py-2 border-b text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>';
            
            $files = scandir($app_dir);
            $has_files = false;
            
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && is_file($app_dir . '/' . $file)) {
                    $has_files = true;
                    $file_path = $app_dir . '/' . $file;
                    $file_size = filesize($file_path);
                    $file_type = mime_content_type($file_path);
                    $file_modified = date('Y-m-d H:i:s', filemtime($file_path));
                    
                    echo '<tr>
                        <td class="px-4 py-2 border-b">' . htmlspecialchars($file) . '</td>
                        <td class="px-4 py-2 border-b">' . formatBytes($file_size) . '</td>
                        <td class="px-4 py-2 border-b">' . htmlspecialchars($file_type) . '</td>
                        <td class="px-4 py-2 border-b">' . $file_modified . '</td>
                        <td class="px-4 py-2 border-b">
                            <a href="../' . $file_path . '" target="_blank" class="text-blue-600 hover:underline mr-3">View</a>
                            <a href="manage-uploads.php?year=' . $year . '&app_ref=' . $app_ref . '&delete=' . urlencode($file) . '" class="text-red-600 hover:underline" onclick="return confirm(\'Are you sure you want to delete this file?\')">Delete</a>
                        </td>
                    </tr>';
                }
            }
            
            if (!$has_files) {
                echo '<tr><td colspan="5" class="px-4 py-2 text-center">No files found for this application.</td></tr>';
            }
            
            echo '</tbody></table></div></div>';
        } else {
            echo '<div class="mb-6 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700">
                Application directory not found: ' . htmlspecialchars($app_ref) . '
            </div>';
        }
    } else {
        // Display list of applications for selected year
        echo '<div class="mb-6">
            <h2 class="text-xl font-semibold mb-3">Applications for ' . $year . '</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border-b text-left">Application Reference</th>
                            <th class="px-4 py-2 border-b text-left">Number of Files</th>
                            <th class="px-4 py-2 border-b text-left">Total Size</th>
                            <th class="px-4 py-2 border-b text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $applications = scandir($year_dir);
        $has_applications = false;
        
        foreach ($applications as $app) {
            if ($app !== '.' && $app !== '..' && is_dir($year_dir . '/' . $app)) {
                $has_applications = true;
                $app_dir = $year_dir . '/' . $app;
                $file_count = 0;
                $total_size = 0;
                
                $files = scandir($app_dir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_file($app_dir . '/' . $file)) {
                        $file_count++;
                        $total_size += filesize($app_dir . '/' . $file);
                    }
                }
                
                echo '<tr>
                    <td class="px-4 py-2 border-b">' . htmlspecialchars($app) . '</td>
                    <td class="px-4 py-2 border-b">' . $file_count . '</td>
                    <td class="px-4 py-2 border-b">' . formatBytes($total_size) . '</td>
                    <td class="px-4 py-2 border-b">
                        <a href="manage-uploads.php?year=' . $year . '&app_ref=' . urlencode($app) . '" class="text-blue-600 hover:underline">View Files</a>
                    </td>
                </tr>';
            }
        }
        
        if (!$has_applications) {
            echo '<tr><td colspan="4" class="px-4 py-2 text-center">No applications found for ' . $year . '.</td></tr>';
        }
        
        echo '</tbody></table></div></div>';
    }
} else {
    echo '<div class="mb-6 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700">
        No uploads directory found for year: ' . $year . '
    </div>';
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

// HTML footer
echo '
            <div class="mt-8 pt-4 border-t border-gray-200">
                <a href="../admin/index.php" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Back to Admin Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>';

// End output buffering and send to browser
ob_end_flush();
?>
