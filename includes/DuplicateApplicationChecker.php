<?php
/**
 * Duplicate Application Checker Module
 * Checks if a user has already applied for a specific job position
 */

class DuplicateApplicationChecker {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if an application with the given NRIC already exists for the specified job
     * 
     * @param string $nric - NRIC to check
     * @param int $job_id - Job ID to check
     * @return array - Result with status and application details if found
     */
    public function checkDuplicateApplication($nric, $job_id) {
        $clean_nric = preg_replace('/[^0-9]/', '', $nric);
        $formatted_nric = '';
        
        // Format NRIC if it has the correct length
        if (strlen($clean_nric) == 12) {
            $formatted_nric = substr($clean_nric, 0, 6) . '-' . substr($clean_nric, 6, 2) . '-' . substr($clean_nric, 8, 4);
        }
        
        try {
            // Check in both tables with a UNION query
            $sql = "
                (SELECT 
                    id, application_reference, nama_penuh AS applicant_name, email, nombor_ic, 
                    jawatan_dipohon AS job_title, 'application_application_main' AS source_table,
                    created_at, updated_at, status, submission_locked
                FROM application_application_main 
                WHERE job_id = ?
                AND (nombor_ic = ? OR nombor_ic = ?)
                AND status != 'deleted'
                LIMIT 1)
                
                UNION
                
                (SELECT 
                    id, application_reference, nama_penuh AS applicant_name, email, nombor_ic,
                    jawatan_dipohon AS job_title, 'job_applications' AS source_table,
                    created_at, updated_at, status, submission_locked
                FROM job_applications 
                WHERE job_id = ?
                AND (nombor_ic = ? OR nombor_ic = ?)
                AND status != 'deleted'
                LIMIT 1)
                
                LIMIT 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$job_id, $clean_nric, $formatted_nric, $job_id, $clean_nric, $formatted_nric]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($application) {
                return [
                    'status' => 'duplicate_found',
                    'message' => $this->getApplicationStatusMessage($application),
                    'application' => $application
                ];
            }
            
            return [
                'status' => 'no_duplicate',
                'message' => 'Tiada permohonan pendua dijumpai.',
                'application' => null
            ];
        } catch (PDOException $e) {
            error_log('Error checking for duplicate application: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Ralat sistem semasa memeriksa permohonan. Sila cuba lagi.',
                'application' => null
            ];
        }
    }
    
    /**
     * Get user-friendly status message for existing application
     * @param array $application - Application data from database
     * @return string - Status message
     */
    private function getApplicationStatusMessage($application) {
        // Handle both application_date and created_at columns safely
        $date_field = $application['application_date'] ?? $application['created_at'] ?? null;
        $created_date = 'N/A';
        
        if ($date_field && !empty($date_field)) {
            $timestamp = strtotime($date_field);
            if ($timestamp !== false) {
                $created_date = date('d/m/Y', $timestamp);
            }
        }
        
        $app_ref = $application['application_reference'] ?? 'N/A';
        
        // Check if application is completed/locked
        if (isset($application['submission_locked']) && $application['submission_locked'] == 1) {
            $status = strtoupper($application['status']);
            switch ($status) {
                case 'PENDING':
                    return "Anda telah membuat permohonan untuk jawatan ini pada {$created_date}. Rujukan: {$app_ref}. Status: DALAM PEMPROSESAN.";
                case 'SHORTLISTED':
                    return "Permohonan anda telah dipendekkan pada {$created_date}. Rujukan: {$app_ref}. Status: DIPENDEKKAN.";
                case 'INTERVIEWED':
                    return "Anda telah ditemu duga untuk jawatan ini pada {$created_date}. Rujukan: {$app_ref}. Status: TELAH DITEMU DUGA.";
                case 'OFFERED':
                    return "Anda telah ditawarkan jawatan ini pada {$created_date}. Rujukan: {$app_ref}. Status: DITAWARKAN.";
                case 'ACCEPTED':
                    return "Anda telah menerima tawaran jawatan ini pada {$created_date}. Rujukan: {$app_ref}. Status: DITERIMA.";
                case 'REJECTED':
                    return "Permohonan anda untuk jawatan ini telah ditolak pada {$created_date}. Rujukan: {$app_ref}. Status: DITOLAK.";
                default:
                    return "Anda telah membuat permohonan untuk jawatan ini pada {$created_date}. Rujukan: {$app_ref}. Status: {$status}.";
            }
        } else {
            // Application is still editable
            return "Anda telah memulakan permohonan untuk jawatan ini pada {$created_date}. Rujukan: {$app_ref}. Permohonan masih boleh diedit.";
        }
    }
    
    /**
     * Check duplicate before payment
     * @param string $nric - User's NRIC
     * @param string $email - User's email
     * @param int $job_id - Job ID
     * @return array - Result with status and message
     */
    public function checkBeforePayment($nric, $email, $job_id) {
        $duplicate_check = $this->checkDuplicateApplication($nric, $job_id);
        
        if ($duplicate_check['status'] === 'duplicate_found') {
            $application = $duplicate_check['application'];
            
            // If application exists and has payment reference, don't allow duplicate payment
            if (!empty($application['payment_reference'])) {
                return [
                    'status' => 'payment_exists',
                    'message' => 'Anda telah membuat pembayaran untuk jawatan ini. ' . $duplicate_check['message'],
                    'application' => $application
                ];
            }
            
            // If application exists but no payment, allow payment (user might be resuming)
            return [
                'status' => 'allow_payment',
                'message' => 'Permohonan dijumpai tanpa pembayaran. Anda boleh meneruskan pembayaran.',
                'application' => $application
            ];
        } elseif ($duplicate_check['status'] === 'duplicate_found') {
            // If we found a duplicate but didn't check payment status, check it now
            $application = $duplicate_check['application'];
            
            // If application exists and has payment reference, don't allow duplicate payment
            if (!empty($application['payment_reference'])) {
                return [
                    'status' => 'payment_exists',
                    'message' => 'Anda telah membuat pembayaran untuk jawatan ini. ' . $duplicate_check['message'],
                    'application' => $application
                ];
            }
            
            // If application exists but no payment, allow payment
            return [
                'status' => 'allow_payment',
                'message' => 'Permohonan dijumpai tanpa pembayaran. Anda boleh meneruskan pembayaran.',
                'application' => $application
            ];
        }
        
        return $duplicate_check;
    }
    
    /**
     * Get application status for display
     * @param int $job_id - Job ID
     * @param string $nric - User's NRIC
     * @return array - Application status data
     */
    public function getApplicationStatus($job_id, $nric) {
        $duplicate_check = $this->checkDuplicateApplication($nric, $job_id);
        
        if ($duplicate_check['status'] === 'duplicate_found') {
            $application = $duplicate_check['application'];
            
            // Get job information
            try {
                $stmt = $this->pdo->prepare('SELECT job_title, kod_gred FROM job_postings WHERE id = ? LIMIT 1');
                $stmt->execute([$job_id]);
                $job = $stmt->fetch();
                
                return [
                    'found' => true,
                    'application' => $application,
                    'job' => $job,
                    'status_message' => $duplicate_check['message']
                ];
            } catch (PDOException $e) {
                error_log('Error getting job information: ' . $e->getMessage());
                return [
                    'found' => true,
                    'application' => $application,
                    'job' => null,
                    'status_message' => $duplicate_check['message']
                ];
            }
        }
        
        return [
            'found' => false,
            'application' => null,
            'job' => null,
            'status_message' => 'Tiada permohonan dijumpai untuk jawatan ini.'
        ];
    }
}
?>
