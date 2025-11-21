<?php
/**
 * Application utilities for job application system
 * Contains helper functions for application management
 */

class ApplicationUtils {
    
    /**
     * Generate unique application reference
     * Format: APP-YYYY-JJJJ-XXXXXXXX
     * Where YYYY = year, JJJJ = zero-padded job ID, XXXXXXXX = unique hash
     */
    public static function generateApplicationReference($job_id, $email = null, $ic = null) {
        $year = date('Y');
        $job_part = str_pad($job_id, 4, '0', STR_PAD_LEFT);
        
        // Generate unique part based on time and optional user data
        $unique_data = time() . uniqid();
        if ($email) $unique_data .= $email;
        if ($ic) $unique_data .= $ic;
        
        $unique_part = strtoupper(substr(md5($unique_data), 0, 8));
        
        return "APP-{$year}-{$job_part}-{$unique_part}";
    }
    
    /**
     * Validate application data before submission
     */
    public static function validateApplicationData($data) {
        $errors = [];
        
        // Required fields validation
        $required_fields = [
            'job_id' => 'Job ID',
            'nama_penuh' => 'Full Name',
            'nombor_ic' => 'IC Number',
            'email' => 'Email',
            'nombor_telefon' => 'Phone Number'
        ];
        
        foreach ($required_fields as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = "{$label} is required";
            }
        }
        
        // Email validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // IC number validation (Malaysian format: 123456-12-1234)
        if (!empty($data['nombor_ic']) && !preg_match('/^\d{6}-\d{2}-\d{4}$/', $data['nombor_ic'])) {
            $errors[] = "Invalid IC number format. Expected format: 123456-12-1234";
        }
        
        // Phone number validation (Malaysian format)
        if (!empty($data['nombor_telefon']) && !preg_match('/^(\+?6?01[0-46-9][-\s]?\d{7,8}|\+?603[-\s]?\d{8})$/', $data['nombor_telefon'])) {
            $errors[] = "Invalid Malaysian phone number format";
        }
        
        return $errors;
    }
    
    /**
     * Format application status for display
     */
    public static function formatApplicationStatus($status) {
        $status_map = [
            'PENDING' => ['text' => 'Dalam Proses', 'class' => 'bg-yellow-100 text-yellow-800'],
            'REVIEWED' => ['text' => 'Dikaji Semula', 'class' => 'bg-blue-100 text-blue-800'],
            'APPROVED' => ['text' => 'Diluluskan', 'class' => 'bg-green-100 text-green-800'],
            'REJECTED' => ['text' => 'Ditolak', 'class' => 'bg-red-100 text-red-800'],
            'WITHDRAWN' => ['text' => 'Ditarik Balik', 'class' => 'bg-gray-100 text-gray-800']
        ];
        
        return $status_map[$status] ?? ['text' => 'Unknown', 'class' => 'bg-gray-100 text-gray-800'];
    }
    
    /**
     * Check if user can apply for a job
     */
    public static function canUserApplyForJob($pdo, $job_id, $email, $ic_number) {
        try {
            // Check if job exists and is still open
            $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
            $stmt->execute([$job_id]);
            $job = $stmt->fetch();
            
            if (!$job) {
                return ['can_apply' => false, 'reason' => 'Job not found'];
            }
            
            $today = new DateTime(date('Y-m-d'));
            $ad_close_date = new DateTime($job['ad_close_date']);
            
            if ($ad_close_date < $today) {
                return ['can_apply' => false, 'reason' => 'Application deadline has passed'];
            }
            
            // Check if user has already applied
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM job_applications WHERE job_id = ? AND (email = ? OR nombor_ic = ?)');
            $stmt->execute([$job_id, $email, $ic_number]);
            $existing_count = $stmt->fetchColumn();
            
            if ($existing_count > 0) {
                return ['can_apply' => false, 'reason' => 'You have already applied for this job'];
            }
            
            return ['can_apply' => true, 'reason' => null];
            
        } catch (PDOException $e) {
            return ['can_apply' => false, 'reason' => 'Database error occurred'];
        }
    }
    
    /**
     * Get application statistics
     */
    public static function getApplicationStats($pdo, $job_id = null) {
        try {
            $base_query = "SELECT status, COUNT(*) as count FROM job_applications";
            $params = [];
            
            if ($job_id) {
                $base_query .= " WHERE job_id = ?";
                $params[] = $job_id;
            }
            
            $base_query .= " GROUP BY status";
            
            $stmt = $pdo->prepare($base_query);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stats = [
                'PENDING' => 0,
                'REVIEWED' => 0,
                'APPROVED' => 0,
                'REJECTED' => 0,
                'WITHDRAWN' => 0,
                'total' => 0
            ];
            
            foreach ($results as $row) {
                $stats[$row['status']] = (int)$row['count'];
                $stats['total'] += (int)$row['count'];
            }
            
            return $stats;
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Sanitize and format form data
     */
    public static function sanitizeFormData($data) {
        $cleaned = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $cleaned[$key] = array_map('trim', $value);
            } else {
                $cleaned[$key] = trim($value);
                
                // Convert to uppercase for most fields (except email and specific fields)
                if (!in_array($key, ['email', 'payment_reference', 'poskod_tetap', 'poskod_surat', 'nombor_telefon', 'tarikh_lahir', 'umur', 'berat_kg', 'tinggi_cm'])) {
                    if ($key !== 'email' && !empty($cleaned[$key])) {
                        $cleaned[$key] = strtoupper($cleaned[$key]);
                    }
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Create application notification
     */
    public static function createNotification($pdo, $application_id, $type, $title, $message) {
        try {
            $stmt = $pdo->prepare("INSERT INTO application_notifications (application_id, notification_type, title, message) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$application_id, $type, $title, $message]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get setting value from application_settings table
     */
    public static function getSetting($pdo, $key, $default = null) {
        try {
            $stmt = $pdo->prepare("SELECT setting_value, setting_type FROM application_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return $default;
            }
            
            $value = $result['setting_value'];
            
            // Convert based on type
            switch ($result['setting_type']) {
                case 'boolean':
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                case 'integer':
                    return (int)$value;
                case 'json':
                    return json_decode($value, true);
                default:
                    return $value;
            }
        } catch (PDOException $e) {
            return $default;
        }
    }
    
    /**
     * Update setting value
     */
    public static function updateSetting($pdo, $key, $value, $type = 'string') {
        try {
            // Convert value based on type
            if ($type === 'json') {
                $value = json_encode($value);
            } elseif ($type === 'boolean') {
                $value = $value ? 'true' : 'false';
            } else {
                $value = (string)$value;
            }
            
            $stmt = $pdo->prepare("INSERT INTO application_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, setting_type = ?");
            return $stmt->execute([$key, $value, $type, $value, $type]);
        } catch (PDOException $e) {
            return false;
        }
    }
}