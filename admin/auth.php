<?php
/**
 * Admin Authentication Handler
 * 
 * This file handles authentication for the admin panel.
 * It checks if the user is logged in and redirects to the login page if not.
 * It also redirects logged-in users to the dashboard if they try to access the login page.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current script name
$current_script = basename($_SERVER['SCRIPT_NAME']);

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Not logged in, redirect to login page
    // Skip redirect for login.php, forgot-password.php, reset-password.php and temp_admin_setup.php
    $public_pages = ['login.php', 'forgot-password.php', 'reset-password.php', 'temp_admin_setup.php'];
    
    if (!in_array($current_script, $public_pages)) {
        header('Location: login.php');
        exit;
    }
} else {
    // User is logged in, redirect to dashboard if trying to access login page
    if (in_array($current_script, ['login.php', 'forgot-password.php'])) {
        header('Location: index.php');
        exit;
    }
}

// Load ActivityLogger if not already loaded
if (!class_exists('ActivityLogger')) {
    require_once __DIR__ . '/../includes/ActivityLogger.php';
}

/**
 * Function to check if user has required permission
 * 
 * @param string $required_role Minimum role required (admin, editor, viewer)
 * @return bool True if user has permission, false otherwise
 */
function check_admin_permission($required_role = 'admin') {
    if (!isset($_SESSION['admin_role'])) {
        return false;
    }
    
    $role_hierarchy = [
        'superadmin' => 100,
        'admin' => 80,
        'editor' => 60,
        'viewer' => 40
    ];
    
    $user_role = $_SESSION['admin_role'] ?? 'viewer';
    $user_level = $role_hierarchy[$user_role] ?? 0;
    $required_level = $role_hierarchy[$required_role] ?? 100;
    
    return $user_level >= $required_level;
}

/**
 * Function to require specific permission or die
 * 
 * @param string $required_role Minimum role required
 */
function require_admin_permission($required_role = 'admin') {
    if (!check_admin_permission($required_role)) {
        // Log unauthorized access attempt
        if (function_exists('log_admin_action')) {
            log_admin_action(
                'Unauthorized access attempt', 
                'OTHER', 
                'admin', 
                $_SESSION['admin_id'] ?? null, 
                ['required_role' => $required_role]
            );
        }
        
        // Show error and exit
        http_response_code(403);
        include 'templates/header.php';
        echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">';
        echo '<p class="font-bold">Access Denied</p>';
        echo '<p>You do not have permission to access this page.</p>';
        echo '</div>';
        include 'templates/footer.php';
        exit;
    }
}
