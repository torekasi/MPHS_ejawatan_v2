<?php
/**
 * Duplicate Application Validator Module
 * 
 * Centralized module for validating duplicate job applications
 * Can be used across all application save pages
 * 
 * Usage:
 * require_once 'modules/DuplicateValidator.php';
 * $validator = new DuplicateValidator($pdo);
 * $result = $validator->validateSubmission($_POST, $application_id);
 * 
 * @version 1.0
 * @date 2025-10-28
 */

class DuplicateValidator {
    private $pdo;
    private $checker;
    private $errors = [];
    private $warnings = [];
    
    /**
     * Constructor
     * @param PDO $pdo - Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Load DuplicateApplicationChecker if not already loaded
        if (!class_exists('DuplicateApplicationChecker')) {
            require_once __DIR__ . '/../includes/DuplicateApplicationChecker.php';
        }
        
        $this->checker = new DuplicateApplicationChecker($pdo);
    }
    
    /**
     * Main validation method for application submission
     * 
     * @param array $post_data - POST data from form
     * @param int|null $application_id - Current application ID (for edit mode)
     * @param string|null $application_reference - Current application reference
     * @return array - ['valid' => bool, 'error' => string|null, 'redirect' => string|null, 'data' => array]
     */
    public function validateSubmission($post_data, $application_id = null, $application_reference = null) {
        $this->errors = [];
        $this->warnings = [];
        
        // Step 1: Check if this is edit mode
        $is_edit_mode = !empty($post_data['edit_token']) && !empty($application_id);
        
        // Step 2: Extract NRIC and Job ID
        $nric = $this->extractNRIC($post_data);
        $job_id = $this->extractJobID($post_data);
        
        if (!$nric || !$job_id) {
            return $this->errorResponse('Data tidak lengkap. NRIC atau Job ID tidak dijumpai.');
        }
        
        // Step 3: Check for duplicate (skip if edit mode)
        if (!$is_edit_mode) {
            $duplicate_check = $this->checker->checkDuplicateApplication($nric, $job_id);
            
            if ($duplicate_check['status'] === 'duplicate_found') {
                $existing_app = $duplicate_check['application'];
                
                // Log the duplicate attempt
                $this->logDuplicateAttempt($nric, $job_id, $existing_app['application_reference'] ?? 'N/A');
                
                return [
                    'valid' => false,
                    'error' => $duplicate_check['message'],
                    'redirect' => "application-status.php?nric=" . urlencode($nric) . "&job_id=" . $job_id,
                    'data' => [
                        'duplicate_found' => true,
                        'existing_application' => $existing_app
                    ]
                ];
            }
        }
        
        // Step 4: If application_id provided, verify ownership
        if ($application_id && $application_reference) {
            $ownership_check = $this->verifyOwnership($application_id, $application_reference, $nric, $job_id);
            
            if (!$ownership_check['valid']) {
                return $ownership_check;
            }
        }
        
        // Step 5: All validations passed
        return [
            'valid' => true,
            'error' => null,
            'redirect' => null,
            'data' => [
                'nric' => $nric,
                'job_id' => $job_id,
                'is_edit_mode' => $is_edit_mode
            ]
        ];
    }
    
    /**
     * Verify application ownership (prevent tampering)
     * 
     * @param int $application_id - Application ID to verify
     * @param string $application_reference - Application reference to verify
     * @param string $nric - User's NRIC
     * @param int $job_id - Job ID
     * @return array - Validation result
     */
    public function verifyOwnership($application_id, $application_reference, $nric, $job_id) {
        try {
            // Get application data from database
            $stmt = $this->pdo->prepare('
                SELECT id, nombor_ic, job_id, application_reference, submission_locked 
                FROM application_application_main 
                WHERE application_reference = ? 
                LIMIT 1
            ');
            $stmt->execute([$application_reference]);
            $app_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$app_data) {
                // Fallback to old table
                $stmt = $this->pdo->prepare('
                    SELECT id, nombor_ic, job_id, application_reference, submission_locked 
                    FROM job_applications 
                    WHERE application_reference = ? 
                    LIMIT 1
                ');
                $stmt->execute([$application_reference]);
                $app_data = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            if (!$app_data) {
                return $this->errorResponse('Permohonan tidak dijumpai. Sila mulakan permohonan baharu.');
            }
            
            // Verify application ID matches
            if ((int)$app_data['id'] !== (int)$application_id) {
                $this->logTamperingAttempt([
                    'expected_id' => $app_data['id'],
                    'provided_id' => $application_id,
                    'reference' => $application_reference
                ]);
                
                return $this->errorResponse('Ralat pengesahan permohonan. Sila cuba lagi.');
            }
            
            // Verify NRIC matches (ownership) - only if NRIC is provided in the request
            // If NRIC is null or empty, skip this check since we're not trying to change the NRIC
            if ($nric !== null && !empty($nric)) {
                $normalized_nric_db = preg_replace('/[^0-9]/', '', $app_data['nombor_ic']);
                $normalized_nric_input = preg_replace('/[^0-9]/', '', $nric);
                
                // Additional check to handle empty NRIC values that might appear as empty strings
                if ($normalized_nric_input === '') {
                    // Skip comparison if input NRIC is empty
                    error_log('[DuplicateValidator] Empty NRIC input provided, skipping comparison');
                } else if ($normalized_nric_db !== $normalized_nric_input) {
                    $this->logTamperingAttempt([
                        'expected_nric' => substr($app_data['nombor_ic'], 0, 6) . '-XX-XXXX',
                        'provided_nric' => substr($nric, 0, 6) . '-XX-XXXX',
                        'reference' => $application_reference
                    ]);
                    
                    return $this->errorResponse('Anda tidak dibenarkan mengubah permohonan ini.');
                }
            }
            
            // Check if application is already locked
            if (!empty($app_data['submission_locked']) && $app_data['submission_locked'] == 1) {
                return $this->errorResponse('Permohonan ini telah dihantar dan dikunci. Tidak boleh diubah lagi.');
            }
            
            // Check for duplicate with different reference - only if NRIC is provided
            if ($nric !== null && !empty($nric)) {
                $duplicate_check = $this->checker->checkDuplicateApplication($nric, $job_id);
                
                if ($duplicate_check['status'] === 'duplicate_found') {
                    $existing_app = $duplicate_check['application'];
                    
                    // Only allow if it's the same application
                    if ($existing_app['application_reference'] !== $application_reference) {
                        $this->logDuplicateAttempt($nric, $job_id, $existing_app['application_reference'], $application_reference);
                        
                        return [
                            'valid' => false,
                            'error' => 'Anda telah membuat permohonan untuk jawatan ini. Permohonan pendua tidak dibenarkan. Rujukan: ' . $existing_app['application_reference'],
                            'redirect' => "application-status.php?nric=" . urlencode($nric) . "&job_id=" . $job_id,
                            'data' => [
                                'duplicate_found' => true,
                                'existing_application' => $existing_app
                            ]
                        ];
                    }
                }
            } else {
                // If no NRIC provided, use the one from the database for any further checks
                $nric = $app_data['nombor_ic'] ?? null;
            }
            
            // All checks passed
            return [
                'valid' => true,
                'error' => null,
                'redirect' => null,
                'data' => [
                    'application' => $app_data
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('[DuplicateValidator] Database error in ownership verification: ' . $e->getMessage());
            
            // Fail open (allow operation) but log the error
            $this->warnings[] = 'Ownership verification failed, but allowing operation to proceed';
            
            return [
                'valid' => true,
                'error' => null,
                'redirect' => null,
                'data' => [
                    'warning' => 'Verification partially failed'
                ]
            ];
        }
    }
    
    /**
     * Check for duplicate via AJAX (for real-time validation)
     * 
     * @param string $nric - User's NRIC
     * @param int $job_id - Job ID to check
     * @return array - JSON response
     */
    public function checkDuplicateAjax($nric, $job_id) {
        if (!$nric || !$job_id) {
            return [
                'status' => 'error',
                'message' => 'Parameter tidak lengkap',
                'duplicate' => false
            ];
        }
        
        $duplicate_check = $this->checker->checkDuplicateApplication($nric, $job_id);
        
        if ($duplicate_check['status'] === 'duplicate_found') {
            return [
                'status' => 'duplicate_found',
                'message' => $duplicate_check['message'],
                'duplicate' => true,
                'application_reference' => $duplicate_check['application']['application_reference'] ?? null
            ];
        }
        
        return [
            'status' => 'success',
            'message' => 'Tiada permohonan pendua dijumpai',
            'duplicate' => false
        ];
    }
    
    /**
     * Extract NRIC from POST data
     * @param array $post_data
     * @return string|null
     */
    private function extractNRIC($post_data) {
        $nric = $post_data['nombor_ic'] ?? null;
        
        if ($nric) {
            // Normalize NRIC format (remove non-digits, then format)
            $clean_nric = preg_replace('/[^0-9]/', '', $nric);
            if (strlen($clean_nric) >= 12) {
                $clean_nric = substr($clean_nric, 0, 12);
                // Format as XXXXXX-XX-XXXX
                return substr($clean_nric, 0, 6) . '-' . substr($clean_nric, 6, 2) . '-' . substr($clean_nric, 8, 4);
            }
            return $nric;
        }
        
        return null;
    }
    
    /**
     * Extract Job ID from POST data
     * @param array $post_data
     * @return int|null
     */
    private function extractJobID($post_data) {
        // Check for direct job_id
        if (!empty($post_data['job_id']) && is_numeric($post_data['job_id'])) {
            return (int)$post_data['job_id'];
        }
        
        // Check for job_code and resolve to job_id
        if (!empty($post_data['job_code'])) {
            try {
                $stmt = $this->pdo->prepare('SELECT id FROM job_postings WHERE job_code = ? LIMIT 1');
                $stmt->execute([$post_data['job_code']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && isset($row['id'])) {
                    return (int)$row['id'];
                }
            } catch (PDOException $e) {
                error_log('[DuplicateValidator] Error resolving job_code to job_id: ' . $e->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Log duplicate attempt
     * @param string $nric
     * @param int $job_id
     * @param string $existing_ref
     * @param string|null $attempted_ref
     */
    private function logDuplicateAttempt($nric, $job_id, $existing_ref, $attempted_ref = null) {
        $masked_nric = substr($nric, 0, 6) . '-XX-XXXX';
        
        $context = [
            'nric' => $masked_nric,
            'job_id' => $job_id,
            'existing_ref' => $existing_ref
        ];
        
        if ($attempted_ref) {
            $context['attempted_ref'] = $attempted_ref;
        }
        
        if (function_exists('log_warning')) {
            log_warning('[DuplicateValidator] Duplicate application attempt detected', $context);
        } else {
            error_log('[DuplicateValidator] Duplicate application attempt: ' . json_encode($context));
        }
    }
    
    /**
     * Log tampering attempt
     * @param array $context
     */
    private function logTamperingAttempt($context) {
        if (function_exists('log_warning')) {
            log_warning('[DuplicateValidator] Possible tampering attempt detected', $context);
        } else {
            error_log('[DuplicateValidator] Tampering attempt: ' . json_encode($context));
        }
    }
    
    /**
     * Create error response
     * @param string $message
     * @return array
     */
    private function errorResponse($message) {
        return [
            'valid' => false,
            'error' => $message,
            'redirect' => null,
            'data' => []
        ];
    }
    
    /**
     * Get all errors
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get all warnings
     * @return array
     */
    public function getWarnings() {
        return $this->warnings;
    }
}


