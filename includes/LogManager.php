<?php
/**
 * Log Manager for Frontend and Admin
 * Handles separate logging for frontend and admin operations
 */

class LogManager {
    private static $instance = null;
    private $frontend_log_file;
    private $admin_log_file;
    private $admin_file_logging_enabled;
    
    private function __construct() {
        $this->frontend_log_file = __DIR__ . '/../logs/error.log';
        $this->admin_log_file = __DIR__ . '/../admin/logs/error.log';
        $this->admin_file_logging_enabled = (bool)(($GLOBALS['config']['admin_file_logging'] ?? false) === true);
        $this->ensureLogDirectories();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Ensure log directories exist
     */
    private function ensureLogDirectories() {
        $frontend_dir = dirname($this->frontend_log_file);
        $admin_dir = dirname($this->admin_log_file);
        
        if (!is_dir($frontend_dir)) {
            @mkdir($frontend_dir, 0755, true);
        }
        
        if (!is_dir($admin_dir)) {
            @mkdir($admin_dir, 0755, true);
        }
    }
    
    /**
     * Log frontend activity
     */
    public function logFrontend($level, $message, $details = null) {
        $this->writeToLog($this->frontend_log_file, $level, $message, $details, 'frontend');
    }
    
    /**
     * Log admin activity
     */
    public function logAdmin($level, $message, $details = null) {
        if (!$this->admin_file_logging_enabled) {
            return;
        }
        $this->writeToLog($this->admin_log_file, $level, $message, $details, 'admin');
    }
    
    /**
     * Write to log file
     */
    private function writeToLog($log_file, $level, $message, $details = null, $context = 'system') {
        try {
            // Respect global log level from config
            $config = $GLOBALS['config'] ?? [];
            $minLevel = strtoupper($config['log_level'] ?? 'WARNING');
            $levelValue = $this->levelToValue(strtoupper($level));
            $minLevelValue = $this->levelToValue($minLevel);
            if ($levelValue < $minLevelValue) {
                return; // Skip logging below threshold
            }

            $timestamp = date('Y-m-d H:i:s');
            $pid = getmypid();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
            $method = $_SERVER['REQUEST_METHOD'] ?? 'Unknown';
            
            // Get user information
            $user_info = $this->getCurrentUserInfo($context);
            
            // Format details
            $details_str = '';
            if ($details) {
                if (is_array($details) || is_object($details)) {
                    $details_str = ' | EXTRA: ' . json_encode($details, JSON_UNESCAPED_UNICODE);
                } else {
                    $details_str = ' | EXTRA: ' . $details;
                }
            }
            
            $log_entry = sprintf(
                "[%s] [%s] [PID:%s] [IP:%s] [%s] USER: %s | TYPE: %s | ACTION: %s | ENTITY: %s | ID: %s | DETAILS: %s%s" . PHP_EOL,
                $timestamp,
                strtoupper($level),
                $pid,
                $ip,
                $context,
                $user_info['user_display'],
                $context,
                $user_info['action_type'],
                $user_info['entity_type'],
                $user_info['entity_id'],
                $message,
                $details_str
            );
            
            // Rotate log if it gets too large (>10MB)
            if (file_exists($log_file) && filesize($log_file) > 10 * 1024 * 1024) {
                $this->rotateLogFile($log_file);
            }
            
            file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            // Fallback to PHP error log if file writing fails
            error_log("LogManager file write failed: " . $e->getMessage());
        }
    }

    /**
     * Map level to numeric value for comparison
     */
    private function levelToValue($level) {
        switch ($level) {
            case 'CRITICAL': return 50;
            case 'ERROR': return 40;
            case 'WARNING': return 30;
            case 'INFO': return 20;
            case 'DEBUG': return 10;
            default: return 20; // default INFO
        }
    }
    
    /**
     * Get current user information
     */
    private function getCurrentUserInfo($context) {
        $user_info = [
            'user_display' => 'System',
            'action_type' => 'OTHER',
            'entity_type' => 'system',
            'entity_id' => 'N/A'
        ];
        
        if ($context === 'admin') {
            if (isset($_SESSION['admin_id'])) {
                $user_info['user_display'] = 'Admin ID: ' . $_SESSION['admin_id'];
                $user_info['action_type'] = 'ADMIN_ACTION';
                $user_info['entity_type'] = 'admin';
            }
        } elseif ($context === 'frontend') {
            if (isset($_SESSION['user_id'])) {
                $user_info['user_display'] = 'User ID: ' . $_SESSION['user_id'];
                $user_info['action_type'] = 'USER_ACTION';
                $user_info['entity_type'] = 'user';
            } else {
                $user_info['user_display'] = 'Public User';
                $user_info['action_type'] = 'PUBLIC_ACTION';
                $user_info['entity_type'] = 'public';
            }
        }
        
        return $user_info;
    }
    
    /**
     * Rotate log file when it gets too large
     */
    private function rotateLogFile($log_file) {
        try {
            $backup_file = $log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
            rename($log_file, $backup_file);
            
            // Keep only last 5 backup files
            $log_dir = dirname($log_file);
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
}

// Global convenience functions for frontend logging
function log_frontend_info($message, $details = null) {
    LogManager::getInstance()->logFrontend('INFO', $message, $details);
}

function log_frontend_error($message, $details = null) {
    LogManager::getInstance()->logFrontend('ERROR', $message, $details);
}

function log_frontend_warning($message, $details = null) {
    LogManager::getInstance()->logFrontend('WARNING', $message, $details);
}

function log_frontend_debug($message, $details = null) {
    LogManager::getInstance()->logFrontend('DEBUG', $message, $details);
}

// Global convenience functions for admin logging
function log_admin_info($message, $details = null) {
    LogManager::getInstance()->logAdmin('INFO', $message, $details);
}

function log_admin_error($message, $details = null) {
    LogManager::getInstance()->logAdmin('ERROR', $message, $details);
}

function log_admin_warning($message, $details = null) {
    LogManager::getInstance()->logAdmin('WARNING', $message, $details);
}

function log_admin_debug($message, $details = null) {
    LogManager::getInstance()->logAdmin('DEBUG', $message, $details);
}
?>
