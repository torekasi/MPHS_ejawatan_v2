<?php
/**
 * Admin Logger - Utility for logging admin actions
 * 
 * This file provides functions to log admin activities in the system
 * for security, auditing, and tracking purposes.
 */

// Ensure this file is included securely
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

/**
 * Log an admin action to the database
 *
 * @param string $action Description of the action performed
 * @param string $action_type Type of action (CREATE, READ, UPDATE, DELETE, LOGIN, LOGOUT, OTHER)
 * @param string $entity_type Type of entity affected (e.g., job, user, setting)
 * @param string|int $entity_id ID of the affected entity (if applicable)
 * @param array|string $details Additional details about the action
 * @return bool True if logging was successful, false otherwise
 */
function log_admin_action($action, $action_type, $entity_type, $entity_id = null, $details = null) {
    global $pdo;
    
    // Only log actions that match the database schema
    $allowed_actions = ['CREATE', 'READ', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'OTHER'];
    
    // Ensure action_type is uppercase and valid
    $action_type = strtoupper($action_type);
    
    // If action_type is empty or invalid, default to 'OTHER'
    if (empty($action_type) || !in_array($action_type, $allowed_actions)) {
        error_log("Admin Logger: Invalid action_type: {$action_type}, defaulting to OTHER");
        $action_type = 'OTHER';
    }
    
    // If PDO connection is not available, log an error and return.
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        error_log("Admin Logger: Database connection not available.");
        return false;
    }
    
    // Get user information
    $user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
    // Remove username variable as it's causing issues
    
    // Get client information
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Convert details to JSON if it's an array
    if (is_array($details)) {
        $details = json_encode($details, JSON_UNESCAPED_UNICODE);
    }
    
    try {
        // First check if the table exists, create it if not
        $table_exists = false;
        try {
            $check_table = $pdo->query("SHOW TABLES LIKE 'admin_logs'");
            $table_exists = ($check_table->rowCount() > 0);
        } catch (PDOException $e) {
            error_log("Admin Logger: Error checking for admin_logs table: " . $e->getMessage());
        }
        
        if (!$table_exists) {
            // Table doesn't exist, create it
            $sql_file = file_get_contents(__DIR__ . '/create_admin_logs_table.sql');
            if ($sql_file) {
                try {
                    $pdo->exec($sql_file);
                    error_log("Admin Logger: Created admin_logs table");
                } catch (PDOException $e) {
                    error_log("Admin Logger: Failed to create admin_logs table: " . $e->getMessage());
                    return false;
                }
            } else {
                error_log("Admin Logger: Could not read SQL file to create admin_logs table");
                return false;
            }
        }
        
        // Always use a version without username to avoid errors
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs 
            (user_id, action, action_type, entity_type, entity_id, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            $action_type,
            $entity_type,
            $entity_id,
            $details,
            $ip_address,
            $user_agent
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Admin Logger: Error logging action: " . $e->getMessage());
        return false;
    }
}

/**
 * Get admin logs with optional filtering
 *
 * @param array $filters Associative array of filters (user_id, action_type, entity_type, date_from, date_to)
 * @param int $limit Maximum number of logs to retrieve
 * @param int $offset Offset for pagination
 * @return array Array of log entries
 */
function get_admin_logs($filters = [], $limit = 100, $offset = 0) {
    global $pdo;
    
    // If PDO connection is not available, log an error and return.
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        error_log("Admin Logger: Database connection not available.");
        return [];
    }
    
    try {
        // Check if table exists
        $check_table = $pdo->query("SHOW TABLES LIKE 'admin_logs'");
        if ($check_table->rowCount() === 0) {
            return []; // Table doesn't exist yet
        }
        
        // Build query
        $query = "SELECT * FROM admin_logs WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['user_id'])) {
            $query .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        } elseif (!empty($filters['user_ids']) && is_array($filters['user_ids'])) {
            $placeholders = str_repeat('?,', count($filters['user_ids']) - 1) . '?';
            $query .= " AND user_id IN ($placeholders)";
            $params = array_merge($params, $filters['user_ids']);
        }
        
        // Remove username filter as it's causing issues
        // if (!empty($filters['username'])) {
        //     $query .= " AND username LIKE ?";
        //     $params[] = '%' . $filters['username'] . '%';
        // }
        
        if (!empty($filters['action_type'])) {
            $query .= " AND action_type = ?";
            $params[] = $filters['action_type'];
        }
        
        if (!empty($filters['entity_type'])) {
            $query .= " AND entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['entity_id'])) {
            $query .= " AND entity_id = ?";
            $params[] = $filters['entity_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (action LIKE ? OR details LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            // Removed username from search
        }
        
        // Add order by and limit
        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        
        // Execute query
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Admin Logger: Error retrieving logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Count total admin logs with optional filtering
 *
 * @param array $filters Associative array of filters (user_id, action_type, entity_type, date_from, date_to)
 * @return int Total count of matching logs
 */
function count_admin_logs($filters = []) {
    global $pdo;
    
    // If PDO connection is not available, log an error and return.
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        error_log("Admin Logger: Database connection not available.");
        return 0;
    }
    
    try {
        // Check if table exists
        $check_table = $pdo->query("SHOW TABLES LIKE 'admin_logs'");
        if ($check_table->rowCount() === 0) {
            return 0; // Table doesn't exist yet
        }
        
        // Build query
        $query = "SELECT COUNT(*) FROM admin_logs WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['user_id'])) {
            $query .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        // Remove username filter as it's causing issues
        // if (!empty($filters['username'])) {
        //     $query .= " AND username LIKE ?";
        //     $params[] = '%' . $filters['username'] . '%';
        // }
        
        if (!empty($filters['action_type'])) {
            $query .= " AND action_type = ?";
            $params[] = $filters['action_type'];
        }
        
        if (!empty($filters['entity_type'])) {
            $query .= " AND entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['entity_id'])) {
            $query .= " AND entity_id = ?";
            $params[] = $filters['entity_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (action LIKE ? OR details LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            // Removed username from search
        }
        
        // Execute query
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Admin Logger: Error counting logs: " . $e->getMessage());
        return 0;
    }
}
