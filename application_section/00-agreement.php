<?php
/**
 * @FileID: app_section_agreement_001
 * @Module: ApplicationSectionAgreement
 * @Author: Nefi
 * @LastModified: 2025-11-09T09:20:00Z
 * @SecurityTag: validated
 */
if (!defined('APP_SECURE')) { http_response_code(403); exit; }
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="section-title">Pengakuan</div>
    <div class="p-6 space-y-6">
        <div class="bg-blue-50 border border-blue-200 p-4 rounded-md">
            <?php
            // Utamakan kandungan dari admin/page-content.php (content_key: 'pengistiharan_terms')
            $agreement_html = '';
            try {
                // Muatkan bootstrap dan config untuk akses DB helper
                require_once __DIR__ . '/../includes/bootstrap.php';
                $config = require __DIR__ . '/../config.php';
                $db = get_database_connection($config);
                if (!empty($db['pdo'])) {
                    $stmt = $db['pdo']->prepare('SELECT content_value FROM page_content WHERE content_key = ? LIMIT 1');
                    $stmt->execute(['pengistiharan_terms']);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row && !empty($row['content_value'])) {
                        $agreement_html = $row['content_value'];
                    }
                }
            } catch (Throwable $e) {
                // Log minimum; elakkan pendedahan ralat kepada pengguna
                if (function_exists('log_error')) {
                    log_error('Gagal memuat kandungan pengisytiharan dari DB', ['error' => $e->getMessage()]);
                }
            }

            // Fallback kepada sumber lain jika tiada kandungan admin
            if (empty($agreement_html)) {
                try {
                    @require_once __DIR__ . '/../includes/functions.php';
                    if (function_exists('getActiveAcknowledgment')) {
                        $active_ack = ''; // function getActiveAcknowledgment() not defined; treat as empty
                        if (!empty($active_ack)) { $agreement_html = $active_ack; }
                    }
                } catch (Throwable $e) { /* abaikan */ }

                if (empty($agreement_html)) {
                    if (!empty($job['declaration_text'])) {
                        $agreement_html = $job['declaration_text'];
                    } elseif (!empty($config['default_declaration_text'])) {
                        $agreement_html = $config['default_declaration_text'];
                    } else {
                        $agreement_html = '<p class="text-gray-800">Saya mengaku bahawa segala maklumat yang diberikan di atas adalah benar dan lengkap. Saya faham bahawa sekiranya terdapat maklumat yang tidak benar atau palsu, permohonan saya boleh dibatalkan atau tawaran yang telah dibuat akan ditarik balik atau perkhidmatan saya akan ditamatkan pada bila-bila masa tanpa notis.</p>';
                    }
                }
            }
            ?>
            <style>
                /* Enable bullets and numbering with readable indentation */
                .agreement-content ul { list-style-type: disc; margin-left: 1.25em; padding-left: 0; }
                .agreement-content ol { list-style-type: decimal; margin-left: 1.25em; padding-left: 0; }
                .agreement-content li { margin-bottom: 0.35em; }
                /* Smaller base font for compact display */
                .agreement-content { font-size: 0.80rem; line-height: 1.45; }
                .agreement-content p { margin-bottom: 0.5em; }
                .agreement-content strong { font-weight: 600; }
                .agreement-content h1 { font-size: 1.0rem; margin-bottom: 0.45rem; }
                .agreement-content h2 { font-size: 0.95rem; margin-bottom: 0.45rem; }
                .agreement-content h3 { font-size: 0.90rem; margin-bottom: 0.35rem; }
                /* Map Quill indent classes to progressive indent */
                .agreement-content .ql-indent-1 { margin-left: 1.5em !important; }
                .agreement-content .ql-indent-2 { margin-left: 3em !important; }
                .agreement-content .ql-indent-3 { margin-left: 4.5em !important; }
                .agreement-content .ql-indent-4 { margin-left: 6em !important; }
                .agreement-content .ql-indent-5 { margin-left: 7.5em !important; }
                .agreement-content .ql-indent-6 { margin-left: 9em !important; }
            </style>
            <div class="agreement-content text-gray-800">
                <?php echo $agreement_html; ?>
            </div>
        </div>
        <label class="inline-flex items-center">
            <?php $agreeChecked = ((int)($application['pengistiharan'] ?? 0) === 1) || (strtoupper($application['pengisytiharan_pengesahan'] ?? '')==='YA'); ?>
            <input type="checkbox" name="pengisytiharan_pengesahan" value="YA" class="h-4 w-4 text-blue-600" required <?php echo $agreeChecked ? 'checked' : ''; ?>>
            <span class="ml-2 text-sm text-blue-800">Saya mengesahkan semua maklumat yang diberikan adalah benar.<span class="required">*</span></span>
        </label>
    </div>
</div>