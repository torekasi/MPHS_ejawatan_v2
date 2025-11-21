<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store user ID for logging before destroying session
$admin_id = $_SESSION['admin_id'] ?? null;
$username = $_SESSION['admin_username'] ?? 'Unknown';

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Now include the admin logger and log the action
require_once __DIR__ . '/includes/admin_logger.php';

// Log logout activity if we had a user ID
if ($admin_id) {
    try {
        log_admin_action('User logout: ' . $username, 'LOGOUT', 'admin', $admin_id, [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Just continue if logging fails
        error_log("Failed to log logout: " . $e->getMessage());
    }
}

// Redirect to login page
header('Location: login.php');
exit;
