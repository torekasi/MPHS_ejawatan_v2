ejawatan_users<?php
// Confirmation Section (First step)
// This file is included in application-form.php

// Security check to prevent direct access
if (!defined('INCLUDED_IN_APPLICATION_FORM')) {
    header('Location: index.php');
    exit;
}
?>

<!-- Section 1: Pengesahan -->
<div class="accordion-item" data-section-id="pengesahan">
    <h2 class="accordion-header" id="heading-pengesahan">
        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-pengesahan" aria-expanded="true" aria-controls="collapse-pengesahan">
            Pengesahan
        </button>
    </h2>
    <div id="collapse-pengesahan" class="accordion-collapse collapse show" aria-labelledby="heading-pengesahan" data-bs-parent="#formAccordion">
        <div class="accordion-body">
            <div class="alert alert-info">
                <p><strong>Sila baca dengan teliti pengesahan permohonan dibawah:</strong></p>
                <style>
                    .declaration-content {
                        margin-top: 15px;
                    }
                    .declaration-content p {
                        margin-bottom: 10px;
                    }
                    .declaration-content ul, 
                    .declaration-content ol,
                    .acknowledgment-wrapper ul,
                    .acknowledgment-wrapper ol {
                        margin-top: 10px;
                        margin-bottom: 10px;
                        padding-left: 25px;
                    }
                    .declaration-content ul,
                    .acknowledgment-wrapper ul {
                        list-style-type: disc !important;
                    }
                    .declaration-content ol,
                    .acknowledgment-wrapper ol {
                        list-style-type: decimal !important;
                        margin-left: 40px;
                    }
                    .declaration-content li,
                    .acknowledgment-wrapper li {
                        margin-bottom: 8px;
                        display: list-item !important;
                        list-style-position: outside !important;
                        padding-left: 5px;
                        text-indent: -5px;
                    }
                    /* Handle nested lists */
                    .declaration-content ul ul,
                    .declaration-content ol ol,
                    .declaration-content ul ol,
                    .declaration-content ol ul,
                    .acknowledgment-wrapper ul ul,
                    .acknowledgment-wrapper ol ol,
                    .acknowledgment-wrapper ul ol,
                    .acknowledgment-wrapper ol ul {
                        margin-top: 5px;
                        margin-bottom: 5px;
                    }
                    /* Proper indentation for sub-items */
                    .declaration-content ul ul,
                    .declaration-content ol ol,
                    .declaration-content ul ol,
                    .declaration-content ol ul,
                    .acknowledgment-wrapper ul ul,
                    .acknowledgment-wrapper ol ol,
                    .acknowledgment-wrapper ul ol,
                    .acknowledgment-wrapper ol ul {
                        padding-left: 40px !important;
                    }
                    /* Styling for different list types */
                    .declaration-content ul ul,
                    .acknowledgment-wrapper ul ul {
                        list-style-type: circle !important;
                    }
                    .declaration-content ul ul ul,
                    .acknowledgment-wrapper ul ul ul {
                        list-style-type: square !important;
                    }
                    .declaration-content ol ol,
                    .acknowledgment-wrapper ol ol {
                        list-style-type: lower-alpha !important;
                    }
                    .declaration-content ol ol ol,
                    .acknowledgment-wrapper ol ol ol {
                        list-style-type: lower-roman !important;
                    }
                    .declaration-content strong,
                    .acknowledgment-wrapper strong {
                        font-weight: bold;
                    }
                    /* Fix for numbered lists */
                    .acknowledgment-wrapper ol > li {
                        counter-increment: item;
                        position: relative;
                    }
                    .acknowledgment-wrapper ol > li::marker {
                        content: counter(item) ". ";
                    }
                    
                    /* Additional spacing for better readability */
                    .acknowledgment-wrapper p,
                    .declaration-content p {
                        line-height: 1.5;
                    }
                    
                    /* Ensure proper indentation for deeply nested items */
                    .acknowledgment-wrapper ul ul ul,
                    .acknowledgment-wrapper ol ol ol,
                    .declaration-content ul ul ul,
                    .declaration-content ol ol ol {
                        padding-left: 50px !important;
                    }
                </style>
                <div class="declaration-content">
                    <?php 
                    // First try to get acknowledgment from database
                    require_once __DIR__ . '/../includes/functions.php';
                    $declaration_text = '';
                    
                    // Try to get active acknowledgment from database
                    $active_acknowledgment = getActiveAcknowledgment();
                    
                    if (!empty($active_acknowledgment)) {
                        // Use the active acknowledgment from the database
                        // Ensure proper HTML structure and indentation is preserved
                        echo '<div class="acknowledgment-wrapper">' . $active_acknowledgment . '</div>';
                    } else {
                        // Fallback to job-specific or default text
                        if (isset($job) && !empty($job['declaration_text'])) {
                            $declaration_text = $job['declaration_text'];
                        } else if (isset($config['default_declaration_text'])) {
                            $declaration_text = $config['default_declaration_text'];
                        }
                        
                        if (empty($declaration_text)) {
                            // Default declaration text
                            $declaration_text = 'Saya mengaku bahawa maklumat yang diberikan dalam borang permohonan ini adalah benar dan tepat. Saya memahami bahawa sebarang maklumat palsu atau tidak tepat boleh menyebabkan permohonan saya ditolak atau tawaran jawatan dibatalkan.';
                        }
                        
                        echo '<p>' . htmlspecialchars($declaration_text) . '</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="declaration_agreement" id="declaration_agreement" value="1" <?php echo isset($form_data['declaration_agreement']) && $form_data['declaration_agreement'] == '1' ? 'checked' : ''; ?> required>
                <label class="form-check-label" for="declaration_agreement">
                    Saya faham dan bersetuju dengan syarat-syarat permohonan di atas
                </label>
                <div class="invalid-feedback">Anda perlu bersetuju dengan pengisytiharan ini untuk meneruskan permohonan.</div>
            </div>

            <!-- Job Information and Payment Reference Field -->
            <div class="card mb-4 border-light bg-light">
                <div class="card-body">
                    <h5 class="card-title">Maklumat Permohonan</h5>
                    <div class="mb-3">
                        <label for="job_title" class="form-label">Jawatan Dipohon</label>
                        <?php
                        // Robustly get job title from job_data (session)
$job_title = $job['title'] ?? $job['job_title'] ?? '';
$job_id = $job['job_id'] ?? $session_data['job_id'] ?? '';
$job_grade = $job['grade'] ?? $job['kod_gred'] ?? '';
$display_title = htmlspecialchars($job_title);
if (!empty($job_title) && !empty($job_id)) {
                            echo '<input type="text" class="form-control" id="job_title" name="job_title" value="' . $display_title . ' - ' . htmlspecialchars($job_id) . '" readonly>';
                        } else {
                            echo '<input type="text" class="form-control" id="job_title" name="job_title" value="Maklumat jawatan tidak dijumpai" readonly>';
                        }
                        ?>
                        <div class="form-text small">
                            <?php if (!empty($job_grade)): ?>
                            <span class="text-muted">Gred: <?php echo htmlspecialchars($job_grade); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (isset($session_data) && isset($session_data['payment_required']) && $session_data['payment_required'] == 1): ?>
                    <!-- Payment Reference -->
                    <div class="mb-3">
                        <label for="payment_ref" class="form-label">Rujukan Bayaran</label>
                        <input type="text" class="form-control" id="payment_ref" name="payment_ref" value="<?php echo htmlspecialchars($session_data['payment_ref_no'] ?? ''); ?>" readonly>
                        <div class="form-text">Rujukan bayaran anda akan dipaparkan di sini selepas pembayaran berjaya.</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-end mt-4">
                <button type="button" class="btn btn-primary next-section">Seterusnya <i class="fas fa-arrow-right ms-2"></i></button>
            </div>
        </div>
    </div>
</div>
