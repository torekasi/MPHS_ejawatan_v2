<?php
/**
 * Comprehensive Activity Logger for eJawatan System
 * 
 * This class provides centralized logging for all activities across the application
 * including admin operations, user activities, errors, and debug information.
 * 
 * Features:
 * - Database logging to admin_logs table
 * - File logging to single error.log file
 * - Support for both admin and public user activities
 * - Error and debug information capture
 * - Automatic log rotation and cleanup
 */

// Include LogManager if not already included
if (!class_exists('LogManager')) {
    require_once __DIR__ . '/LogManager.php';
}

class ActivityLogger {
    private static $instance = null;
    private $pdo = null;
    private $config = null;
    private $log_file = null;
    private $file_logging_enabled = false;
    
    // Log levels
    const LOG_LEVEL_DEBUG = 'DEBUG';
    const LOG_LEVEL_INFO = 'INFO';
    const LOG_LEVEL_WARNING = 'WARNING';
    const LOG_LEVEL_ERROR = 'ERROR';
    const LOG_LEVEL_CRITICAL = 'CRITICAL';
    
    // Activity types - updated to match database schema
    const ACTION_CREATE = 'CREATE';
    const ACTION_READ = 'READ';
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';
    const ACTION_LOGIN = 'LOGIN';
    const ACTION_LOGOUT = 'LOGOUT';
    const ACTION_VIEW = 'VIEW';
    const ACTION_DOWNLOAD = 'DOWNLOAD';
    const ACTION_UPLOAD = 'UPLOAD';
    const ACTION_ACCESS = 'ACCESS';
    const ACTION_OTHER = 'OTHER';
    
    // Entity types
    const ENTITY_JOB = 'job_posting';
    const ENTITY_USER = 'user';
    const ENTITY_ADMIN = 'admin';
    const ENTITY_SETTING = 'setting';
    const ENTITY_PAGE_CONTENT = 'page_content';
    const ENTITY_SESSION = 'session';
    const ENTITY_SYSTEM = 'system';
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->initializeLogger();
    }
    
    /**
     * Initialize the logger with database connection and configuration
     */
    private function initializeLogger() {
        try {
            // Load configuration
            $config_result = require_once __DIR__ . '/../config.php';
            if (is_array($config_result)) {
                $this->config = $config_result;
            }
            
            // Set log file path
            $this->log_file = __DIR__ . '/../admin/logs/error.log';
            $this->file_logging_enabled = (bool)(($GLOBALS['config']['admin_file_logging'] ?? false) === true);
            
            // Ensure logs directory exists
            $log_dir = dirname($this->log_file);
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0755, true);
            }
            
            // Don't initialize database connection immediately
            // It will be initialized when needed
            
        } catch (Exception $e) {
            $this->writeToFile(self::LOG_LEVEL_ERROR, 'Logger initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize database connection for logging
     */
    private function initializeDatabaseConnection() {
        try {
            if ($this->config) {
                $host = $this->config['db_host'] ?? 'localhost';
                $dbname = $this->config['db_name'] ?? '';
                $port = $this->config['db_port'] ?? null;
                $dsn = "mysql:host={$host}" . ($port ? ";port={$port}" : '') . ";dbname={$dbname};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $this->pdo = new PDO($dsn, $this->config['db_user'], $this->config['db_pass'], $options);
            }
        } catch (PDOException $e) {
            // Log to file only, don't throw exception
            $this->writeToFile(self::LOG_LEVEL_ERROR, 'Database connection for logging failed: ' . $e->getMessage());
            $this->pdo = null; // Ensure pdo is null on failure
        } catch (Exception $e) {
            // Log to file only, don't throw exception
            $this->writeToFile(self::LOG_LEVEL_ERROR, 'Database connection for logging failed: ' . $e->getMessage());
            $this->pdo = null; // Ensure pdo is null on failure
        }
    }
    
    /**
     * Log an activity to both database and file
     * 
     * @param string $action Description of the action
     * @param string $action_type Type of action (CREATE, READ, UPDATE, DELETE, etc.)
     * @param string $entity_type Type of entity affected
     * @param mixed $entity_id ID of the affected entity
     * @param mixed $details Additional details
     * @param string $log_level Log level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
     * @param string $user_type Type of user (admin, public, system)
     */
    public function logActivity($action, $action_type, $entity_type, $entity_id = null, $details = null, $log_level = self::LOG_LEVEL_INFO, $user_type = 'admin') {
        // Log to database
        $this->logToDatabase($action, $action_type, $entity_type, $entity_id, $details, $user_type);
        
        // Log to file
        $log_message = $this->formatLogMessage($action, $action_type, $entity_type, $entity_id, $details, $user_type);
        $this->writeToFile($log_level, $log_message);
    }
    
    /**
     * Log to database
     */
    private function logToDatabase($action, $action_type, $entity_type, $entity_id, $details, $user_type) {
        // Initialize database connection if not already done
        if (!$this->pdo) {
            $this->initializeDatabaseConnection();
        }
        
        if (!$this->pdo) {
            return false;
        }
        
        try {
            // Normalize action_type to valid database values
            $valid_actions = ['CREATE', 'READ', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'VIEW', 'OTHER', 'ACCESS'];
            $action_type = strtoupper(trim($action_type));
            
            if (!in_array($action_type, $valid_actions)) {
                $action_type = 'OTHER';
            }
            
            // Get user information based on user type
            $user_info = $this->getCurrentUserInfo($user_type);
            
            // Convert details to JSON if it's an array
            if (is_array($details) || is_object($details)) {
                $details = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
            
            // Truncate fields to fit database constraints
            $action = substr($action, 0, 255);
            $entity_type = substr($entity_type, 0, 100);
            $entity_id = is_null($entity_id) ? null : substr((string)$entity_id, 0, 100);
            
            // Insert log entry
            $stmt = $this->pdo->prepare("
                INSERT INTO admin_logs 
                (user_id, action, action_type, entity_type, entity_id, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $user_info['user_id'],
                $action,
                $action_type,
                $entity_type,
                $entity_id,
                $details,
                $user_info['ip_address'],
                $user_info['user_agent']
            ]);
            
            return true;
        } catch (PDOException $e) {
            $this->writeToFile(self::LOG_LEVEL_ERROR, 'Database logging failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current user information
     */
    private function getCurrentUserInfo($user_type) {
        $user_info = [
            'user_id' => null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        if ($user_type === 'admin') {
            $user_info['user_id'] = $_SESSION['admin_id'] ?? null;
        } elseif ($user_type === 'public') {
            // No user ID for public users
        } elseif ($user_type === 'system') {
            // No user ID for system users
        }
        
        // Add session ID for tracking
        if (isset($_SESSION)) {
            $user_info['session_id'] = session_id();
        }
        
        return $user_info;
    }
    
    /**
     * Format log message for file logging
     */
    private function formatLogMessage($action, $action_type, $entity_type, $entity_id, $details, $user_type) {
        $user_info = $this->getCurrentUserInfo($user_type);
        
        $message_parts = [
            'USER: ' . ($user_type === 'admin' ? ('Admin ID: ' . ($_SESSION['admin_id'] ?? 'Unknown')) : ($user_type === 'public' ? 'Public User' : 'System')),
            'TYPE: ' . $user_type,
            'ACTION: ' . $action_type,
            'ENTITY: ' . $entity_type,
            'ID: ' . ($entity_id ?? 'N/A'),
            'DETAILS: ' . $action
        ];
        
        if ($details) {
            $details_str = is_string($details) ? $details : json_encode($details);
            $message_parts[] = 'EXTRA: ' . $details_str;
        }
        
        return implode(' | ', $message_parts);
    }
    
    /**
     * Write to log file
     */
    private function writeToFile($level, $message) {
        try {
            if (!$this->file_logging_enabled) {
                return;
            }
            // Respect global minimum log level (defaults to WARNING)
            $config = $GLOBALS['config'] ?? [];
            $minLevel = strtoupper($config['log_level'] ?? 'WARNING');
            $levelValue = $this->levelToValue(strtoupper($level));
            $minLevelValue = $this->levelToValue($minLevel);
            if ($levelValue < $minLevelValue) {
                return; // Skip logging below threshold
            }
            $timestamp = date('Y-m-d H:i:s');
            $pid = getmypid();
            $user_info = $this->getCurrentUserInfo('system');
            
            $log_entry = sprintf(
                "[%s] [%s] [PID:%s] [IP:%s] %s" . PHP_EOL,
                $timestamp,
                $level,
                $pid,
                $user_info['ip_address'],
                $message
            );
            
            // Rotate log if it gets too large (>10MB)
            if (file_exists($this->log_file) && filesize($this->log_file) > 10 * 1024 * 1024) {
                $this->rotateLogFile();
            }
            
            file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Fallback to PHP error log if file writing fails
            error_log("ActivityLogger file write failed: " . $e->getMessage());
        }
    }

    /**
     * Map log level to a numeric value for threshold comparison
     */
    private function levelToValue($level) {
        switch ($level) {
            case self::LOG_LEVEL_CRITICAL: return 50;
            case self::LOG_LEVEL_ERROR: return 40;
            case self::LOG_LEVEL_WARNING: return 30;
            case self::LOG_LEVEL_INFO: return 20;
            case self::LOG_LEVEL_DEBUG: return 10;
            default: return 20; // default to INFO if unknown
        }
    }
    
    /**
     * Rotate log file when it gets too large
     */
    private function rotateLogFile() {
        try {
            $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
            rename($this->log_file, $backup_file);
            
            // Keep only last 5 backup files
            $log_dir = dirname($this->log_file);
            $backup_files = glob($log_dir . '/error.log.*.bak');
            if (count($backup_files) > 5) {
                sort($backup_files);
                $files_to_delete = array_slice($backup_files, 0, -5);
                foreach ($files_to_delete as $file) {
                    unlink($file);
                }
            }
        } catch (Exception $e) {
            error_log("Log rotation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Convenience methods for different log levels
     */
    public function debug($message, $details = null) {
        $this->logActivity($message, self::ACTION_OTHER, self::ENTITY_SYSTEM, null, $details, self::LOG_LEVEL_DEBUG, 'system');
    }
    
    public function info($message, $details = null) {
        $this->logActivity($message, self::ACTION_OTHER, self::ENTITY_SYSTEM, null, $details, self::LOG_LEVEL_INFO, 'system');
    }
    
    public function warning($message, $details = null) {
        $this->logActivity($message, self::ACTION_OTHER, self::ENTITY_SYSTEM, null, $details, self::LOG_LEVEL_WARNING, 'system');
    }
    
    public function error($message, $details = null) {
        $this->logActivity($message, self::ACTION_OTHER, self::ENTITY_SYSTEM, null, $details, self::LOG_LEVEL_ERROR, 'system');
    }
    
    public function critical($message, $details = null) {
        $this->logActivity($message, self::ACTION_OTHER, self::ENTITY_SYSTEM, null, $details, self::LOG_LEVEL_CRITICAL, 'system');
    }
    
    /**
     * Log admin activity
     */
    public function logAdminActivity($action, $action_type, $entity_type, $entity_id = null, $details = null) {
        $this->logActivity($action, $action_type, $entity_type, $entity_id, $details, self::LOG_LEVEL_INFO, 'admin');
    }
    
    /**
     * Log public user activity
     */
    public function logPublicActivity($action, $action_type, $entity_type, $entity_id = null, $details = null) {
        $this->logActivity($action, $action_type, $entity_type, $entity_id, $details, self::LOG_LEVEL_INFO, 'public');
    }
    
    /**
     * Get recent logs
     */
    public function getRecentLogs($limit = 100, $filters = []) {
        if (!$this->pdo) {
            return [];
        }
        
        try {
            $query = "SELECT * FROM admin_logs WHERE 1=1";
            $params = [];
            
            // Apply filters
            if (!empty($filters['action_type'])) {
                $query .= " AND action_type = ?";
                $params[] = $filters['action_type'];
            }
            
            if (!empty($filters['entity_type'])) {
                $query .= " AND entity_type = ?";
                $params[] = $filters['entity_type'];
            }
            
            // Username filter removed as column doesn't exist
            // if (!empty($filters['username'])) {
            //     $query .= " AND username LIKE ?";
            //     $params[] = '%' . $filters['username'] . '%';
            // }
            
            if (!empty($filters['date_from'])) {
                $query .= " AND created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            $query .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = (int)$limit;
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->error('Failed to retrieve logs: ' . $e->getMessage());
            return [];
        }
    }
}

// Global convenience functions
function log_activity($action, $action_type, $entity_type, $entity_id = null, $details = null, $user_type = 'admin') {
    ActivityLogger::getInstance()->logActivity($action, $action_type, $entity_type, $entity_id, $details, ActivityLogger::LOG_LEVEL_INFO, $user_type);
}

function activity_log_admin_action($action, $action_type, $entity_type, $entity_id = null, $details = null) {
    ActivityLogger::getInstance()->logAdminActivity($action, $action_type, $entity_type, $entity_id, $details);
}

function log_public_action($action, $action_type, $entity_type, $entity_id = null, $details = null) {
    ActivityLogger::getInstance()->logPublicActivity($action, $action_type, $entity_type, $entity_id, $details);
}

function log_error($message, $details = null) {
    // Determine context based on current script location
    $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script_path, '/admin/') !== false) {
        log_admin_error($message, $details);
    } else {
        log_frontend_error($message, $details);
    }
}

function log_debug($message, $details = null) {
    // Determine context based on current script location
    $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script_path, '/admin/') !== false) {
        log_admin_debug($message, $details);
    } else {
        log_frontend_debug($message, $details);
    }
}

function log_info($message, $details = null) {
    // Determine context based on current script location
    $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script_path, '/admin/') !== false) {
        log_admin_info($message, $details);
    } else {
        log_frontend_info($message, $details);
    }
}

function log_warning($message, $details = null) {
    // Determine context based on current script location
    $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script_path, '/admin/') !== false) {
        log_admin_warning($message, $details);
    } else {
        log_frontend_warning($message, $details);
    }
}

function log_critical($message, $details = null) {
    // Determine context based on current script location
    $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script_path, '/admin/') !== false) {
        log_admin_error($message, $details); // Use error level for critical
    } else {
        log_frontend_error($message, $details); // Use error level for critical
    }
}