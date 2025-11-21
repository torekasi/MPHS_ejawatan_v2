<?php
session_start();
require_once '../includes/bootstrap.php';
require_once 'auth.php';

// Get database connection
$config = require '../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'])) {
    $application_id = (int)$_POST['application_id'];
    
    try {
        // First, verify this is actually a draft application
        $check_stmt = $pdo->prepare("SELECT id, nama_penuh, submission_locked FROM job_applications WHERE id = ?");
        $check_stmt->execute([$application_id]);
        $application = $check_stmt->fetch();
        
        if (!$application) {
            $_SESSION['error'] = 'Permohonan tidak dijumpai.';
        } elseif ($application['submission_locked'] == 1) {
            $_SESSION['error'] = 'Tidak boleh memadam permohonan yang telah dihantar.';
        } else {
            // Delete the draft application
            $delete_stmt = $pdo->prepare("DELETE FROM job_applications WHERE id = ? AND (submission_locked = 0 OR submission_locked IS NULL)");
            $delete_stmt->execute([$application_id]);
            
            if ($delete_stmt->rowCount() > 0) {
                // Log the deletion
                log_admin_info('Deleted draft application', [
                    'action' => 'DELETE_DRAFT_APPLICATION',
                    'application_id' => $application_id,
                    'applicant_name' => $application['nama_penuh'] ?? 'Unknown'
                ]);
                
                $_SESSION['success'] = 'Permohonan draf telah berjaya dipadam.';
            } else {
                $_SESSION['error'] = 'Gagal memadam permohonan draf.';
            }
        }
    } catch (PDOException $e) {
        log_admin_error('Error deleting draft application', [
            'error' => $e->getMessage(),
            'application_id' => $application_id
        ]);
        $_SESSION['error'] = 'Ralat sistem berlaku semasa memadam permohonan.';
    }
} else {
    $_SESSION['error'] = 'Permintaan tidak sah.';
}

// Redirect back to draft applications list
header('Location: draft-applications.php');
exit;
?>
