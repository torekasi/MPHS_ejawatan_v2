<?php
session_start();
// Start output buffering to avoid 'headers already sent' issues during redirects
ob_start();
require_once 'includes/ErrorHandler.php';

// Load database configuration
$result = require 'config.php';
$config = $result['config'] ?? $result;

try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Ralat sambungan pangkalan data. Sila cuba lagi.';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Validate required parameters
$application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
$application_ref = $_POST['application_reference'] ?? '';

if (!$application_id || !$application_ref) {
    $_SESSION['error'] = 'Parameter tidak lengkap.';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Server-side duplicate check using centralized validator module
try {
    require_once 'modules/DuplicateValidator.php';
    
    // Initialize validator
    $validator = new DuplicateValidator($pdo);
    
    // Get application data from new table first, then fallback to old table
    $stmt = $pdo->prepare('SELECT id, nombor_ic, job_id, submission_locked FROM application_application_main WHERE id = ? AND application_reference = ? LIMIT 1');
    $stmt->execute([$application_id, $application_ref]);
    $app_data = $stmt->fetch();
    
    if (!$app_data) {
        // Fallback to old table for backward compatibility
        $stmt = $pdo->prepare('SELECT id, nombor_ic, job_id, submission_locked FROM job_applications WHERE id = ? AND application_reference = ? LIMIT 1');
        $stmt->execute([$application_id, $application_ref]);
        $app_data = $stmt->fetch();
    }
    
    if (!$app_data) {
        error_log('[Finalize] Application not found for ID: ' . $application_id . ', Reference: ' . $application_ref);
        $_SESSION['error'] = 'Permohonan tidak dijumpai.';
        header('Location: index.php');
        exit;
    }
    
    // Verify ownership and check for duplicates
    // For finalization, we DO need to check NRIC to ensure it's the same person
    // This is a security check to prevent unauthorized finalization
    $validation_result = $validator->verifyOwnership(
        $application_id, 
        $application_ref, 
        $app_data['nombor_ic'],
        $app_data['job_id']
    );
    
    // Check result
    if (!$validation_result['valid']) {
        error_log('[Finalize] Validation failed: ' . $validation_result['error']);
        
        // Redirect if URL provided
        if (!empty($validation_result['redirect'])) {
            $_SESSION['error'] = $validation_result['error'];
            header("Location: " . $validation_result['redirect']);
            exit();
        }
        
        // Otherwise show error
        $_SESSION['error'] = $validation_result['error'];
        header('Location: index.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log('[Finalize] Validation error: ' . $e->getMessage());
    // Graceful degradation - continue with finalization
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Verify application exists and isn't already locked (check new table first)
    $stmt = $pdo->prepare('SELECT id, submission_locked FROM application_application_main WHERE id = ? AND application_reference = ? LIMIT 1');
    $stmt->execute([$application_id, $application_ref]);
    $application = $stmt->fetch();

    if (!$application) {
        // Fallback to old table for backward compatibility
        $stmt = $pdo->prepare('SELECT id, submission_locked FROM job_applications WHERE id = ? AND application_reference = ? LIMIT 1');
        $stmt->execute([$application_id, $application_ref]);
        $application = $stmt->fetch();
    }

    if (!$application) {
        throw new Exception('Permohonan tidak dijumpai.');
    }

    if ($application['submission_locked']) {
        throw new Exception('Permohonan ini telah dihantar dan dikunci.');
    }

    // Update application status to Pending and lock submission in both tables
    // Try new table first - check which columns exist before updating
    $checkCols = $pdo->query("SHOW COLUMNS FROM application_application_main")->fetchAll(PDO::FETCH_COLUMN);
    $hasSubmissionDate = in_array('submission_date', $checkCols);
    $hasSubmittedAt = in_array('submitted_at', $checkCols);
    $hasStatusId = in_array('status_id', $checkCols);
    $hasSubmissionStatus = in_array('submission_status', $checkCols);
    
    // Build UPDATE query dynamically based on existing columns
    $updateFields = ['submission_locked = 1', 'status = "PENDING"', 'updated_at = NOW()'];
    if ($hasSubmissionStatus) {
        $updateFields[] = 'submission_status = "Pending"';
    }
    if ($hasStatusId) {
        $updateFields[] = 'status_id = 2'; // Assuming 2 is the ID for Pending status
    }
    if ($hasSubmissionDate) {
        $updateFields[] = 'submission_date = NOW()';
    }
    if ($hasSubmittedAt) {
        $updateFields[] = 'submitted_at = NOW()';
    }
    
    $sql = 'UPDATE application_application_main SET ' . implode(', ', $updateFields) . ' WHERE id = ? AND application_reference = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$application_id, $application_ref]);
    $rows_affected = $stmt->rowCount();
    
    // If no rows affected in new table, try old table for backward compatibility
    if ($rows_affected === 0) {
        // Check columns in old table
        $oldCheckCols = $pdo->query("SHOW COLUMNS FROM job_applications")->fetchAll(PDO::FETCH_COLUMN);
        $oldHasSubmissionDate = in_array('submission_date', $oldCheckCols);
        $oldHasSubmittedAt = in_array('submitted_at', $oldCheckCols);
        $oldHasStatusId = in_array('status_id', $oldCheckCols);
        $oldHasCompletedAt = in_array('completed_at', $oldCheckCols);
        $oldHasSubmissionStatus = in_array('submission_status', $oldCheckCols);
        
        $oldUpdateFields = ['submission_locked = 1', 'status = "PENDING"', 'updated_at = NOW()'];
        if ($oldHasSubmissionStatus) {
            $oldUpdateFields[] = 'submission_status = "Pending"';
        }
        if ($oldHasStatusId) {
            $oldUpdateFields[] = 'status_id = 2'; // Assuming 2 is the ID for Pending status
        }
        if ($oldHasSubmissionDate) {
            $oldUpdateFields[] = 'submission_date = NOW()';
        }
        if ($oldHasSubmittedAt) {
            $oldUpdateFields[] = 'submitted_at = NOW()';
        }
        if ($oldHasCompletedAt) {
            $oldUpdateFields[] = 'completed_at = NOW()';
        }
        
        $oldSql = 'UPDATE job_applications SET ' . implode(', ', $oldUpdateFields) . ' WHERE id = ? AND application_reference = ?';
        $stmt = $pdo->prepare($oldSql);
        $stmt->execute([$application_id, $application_ref]);
    }

    // Log the submission
    error_log("Application locked and submitted - ID: $application_id, Ref: $application_ref");

    // Commit transaction
    $pdo->commit();

    $_SESSION['success'] = 'Permohonan anda telah berjaya dihantar. Terima kasih.';

    try {
        $stmt = $pdo->prepare('SELECT * FROM application_application_main WHERE id = ? AND application_reference = ? LIMIT 1');
        $stmt->execute([$application_id, $application_ref]);
        $fullApp = $stmt->fetch();
        if (!$fullApp) {
            $stmt = $pdo->prepare('SELECT * FROM job_applications WHERE id = ? AND application_reference = ? LIMIT 1');
            $stmt->execute([$application_id, $application_ref]);
            $fullApp = $stmt->fetch();
        }
        if ($fullApp && !empty($fullApp['email'])) {
            require_once __DIR__ . '/includes/ApplicationEmailTemplates.php';
            require_once __DIR__ . '/includes/MailSender.php';
            $html = generateApplicationConfirmationEmail($fullApp);
            $mailer = new MailSender($config);
            $mailer->send($fullApp['email'], 'Pengesahan Permohonan Jawatan - ' . $application_ref, $html);
            $_SESSION['email_sent_to'] = $fullApp['email'];
        }
    } catch (Exception $e) {}

    $redirectUrl = "application-thank-you.php?ref=" . urlencode($application_ref);
    // Clean any buffered output before sending headers
    if (ob_get_length() !== false) { 
        ob_end_clean(); 
    }
    header("Location: $redirectUrl");
    // Fallback in case headers were already sent
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;url={$redirectUrl}\"></head><body>";
    echo "<p>Redirecting... If you are not redirected automatically, <a href=\"{$redirectUrl}\">click here</a>.</p>";
    echo "</body></html>";
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Error finalizing application: ' . $e->getMessage());
    error_log('Error trace: ' . $e->getTraceAsString());
    
    // Check if error is related to missing column
    $errorMsg = $e->getMessage();
    if (strpos($errorMsg, 'submission_date') !== false || strpos($errorMsg, 'Column not found') !== false) {
        // Column doesn't exist - this is not critical, proceed with basic update
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE application_application_main SET submission_locked = 1, status = "PENDING", updated_at = NOW() WHERE id = ? AND application_reference = ?');
            $stmt->execute([$application_id, $application_ref]);
            
            if ($stmt->rowCount() === 0) {
                // Try old table
                $stmt = $pdo->prepare('UPDATE job_applications SET submission_locked = 1, status = "PENDING", updated_at = NOW() WHERE id = ? AND application_reference = ?');
                $stmt->execute([$application_id, $application_ref]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = 'Permohonan anda telah berjaya dihantar. Terima kasih.';
            if (ob_get_length() !== false) { 
                ob_end_clean(); 
            }
            header("Location: application-thank-you.php?ref=" . urlencode($application_ref));
            exit;
        } catch (Exception $e2) {
            if ($pdo->inTransaction()) { 
                $pdo->rollBack(); 
            }
            error_log('Error in fallback update: ' . $e2->getMessage());
        }
    }
    
    $_SESSION['error'] = 'Ralat semasa menghantar permohonan: ' . $e->getMessage();
    if (ob_get_length() !== false) { 
        ob_end_clean(); 
    }
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}
