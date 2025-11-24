<?php
session_start();
require_once '../includes/bootstrap.php';
require_once 'auth.php';
$config = require '../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

function hasCol(PDO $pdo, string $table, string $col): bool {
    try { $r = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'")->fetch(PDO::FETCH_ASSOC); return (bool)$r; } catch (Throwable $e) { return false; }
}
$hasSub = hasCol($pdo, 'application_statuses', 'email_template_subject');
$hasBody = hasCol($pdo, 'application_statuses', 'email_template_body');
$hasEnabled = hasCol($pdo, 'application_statuses', 'email_template_enabled');
$hasDesc = hasCol($pdo, 'application_statuses', 'description');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_one'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrf)) {
        $_SESSION['error'] = 'CSRF tidak sah.';
        header('Location: status-templates.php');
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    $fields = [];
    $params = [];
    if (isset($_POST['code'])) { $fields[] = 'code = ?'; $params[] = trim((string)$_POST['code']); }
    if (isset($_POST['name'])) { $fields[] = 'name = ?'; $params[] = trim((string)$_POST['name']); }
    if ($hasDesc && isset($_POST['description'])) { $fields[] = 'description = ?'; $params[] = trim((string)$_POST['description']); }
    if ($hasSub && isset($_POST['subject'])) { $fields[] = 'email_template_subject = ?'; $params[] = trim((string)$_POST['subject']); }
    if ($hasBody && isset($_POST['body'])) { $fields[] = 'email_template_body = ?'; $params[] = (string)$_POST['body']; }
    if ($hasEnabled && isset($_POST['enabled'])) { $fields[] = 'email_template_enabled = ?'; $params[] = ($_POST['enabled'] == '1') ? 1 : 0; }
    if (!empty($fields) && $id > 0) {
        $params[] = $id;
        $sql = 'UPDATE application_statuses SET ' . implode(', ', $fields) . ' WHERE id = ?';
        try { $stmt = $pdo->prepare($sql); $stmt->execute($params); } catch (Throwable $e) {}
    }
    $_SESSION['success'] = 'Rekod berjaya dikemaskini.';
    header('Location: status-templates.php');
    exit;
}

$extra = '';
if ($hasSub) { $extra .= ', email_template_subject'; }
if ($hasBody) { $extra .= ', email_template_body'; }
if ($hasEnabled) { $extra .= ', email_template_enabled'; }
$extra .= $hasDesc ? ', description' : '';
$statuses = [];
try { $stmt = $pdo->query('SELECT id, code, name' . $extra . ' FROM application_statuses ORDER BY sort_order, id'); $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch (Throwable $e) { $statuses = []; }

$page_title = 'Templat Emel Status';
include 'templates/header.php';
?>
<div class="standard-container mx-auto">
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h1 class="text-xl font-semibold">Templat Emel Status</h1>
            <div class="text-sm text-gray-600">Status emel lalai: <?php echo !empty($config['status_email_enabled']) ? '<span class="text-green-600 font-semibold">Dihidupkan</span>' : '<span class="text-red-600 font-semibold">Dimatikan</span>'; ?></div>
        </div>
        <div class="p-6">
            <?php if (!$hasSub || !$hasBody): ?>
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-4">Lajur templat emel belum wujud. Sila jalankan SQL skema untuk menambah lajur templat emel.</div>
            <?php endif; ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kod</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            
                            <?php if ($hasEnabled): ?><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktif</th><?php endif; ?>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($statuses as $st): ?>
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($st['code']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($st['name']); ?></td>
                                
                                <?php if ($hasEnabled): ?><td class="px-4 py-3 text-sm text-gray-900"><?php echo !empty($st['email_template_enabled']) ? 'Ya' : 'Tidak'; ?></td><?php endif; ?>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                    <button type="button" class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 edit-btn" data-id="<?php echo (int)$st['id']; ?>" data-code="<?php echo htmlspecialchars($st['code']); ?>" data-name="<?php echo htmlspecialchars($st['name']); ?>" data-description="<?php echo htmlspecialchars($st['description'] ?? ''); ?>" data-subject="<?php echo htmlspecialchars($st['email_template_subject'] ?? ''); ?>" data-body="<?php echo htmlspecialchars($st['email_template_body'] ?? ''); ?>" data-enabled="<?php echo !empty($st['email_template_enabled']) ? '1' : '0'; ?>">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div id="editModal" class="fixed inset-0 z-50 hidden overflow-auto bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-lg font-semibold">Edit Status</h3>
            <button type="button" class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700" id="closeEditModal">X</button>
        </div>
        <form method="post" action="status-templates.php" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?php if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="save_one" value="1">
            <input type="hidden" name="id" id="edit_id" value="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kod</label>
                    <input type="text" name="code" id="edit_code" class="w-full px-3 py-2 border rounded" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                    <input type="text" name="name" id="edit_name" class="w-full px-3 py-2 border rounded" required>
                </div>
            </div>
            <?php if ($hasDesc): ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                    <button type="button" id="fillDescSample" class="text-xs px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700">Gunakan Contoh</button>
                </div>
                <input type="hidden" name="description" id="edit_description">
                <div id="quill_description" class="w-full border rounded" style="height: 72px;"></div>
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subjek Emel</label>
                <input type="text" name="subject" id="edit_subject" class="w-full px-3 py-2 border rounded">
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Badan Emel</label>
                    <button type="button" id="fillBodySample" class="text-xs px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700">Gunakan Contoh</button>
                </div>
                <input type="hidden" name="body" id="edit_body">
                <div id="quill_body" class="w-full border rounded" style="height: 240px;"></div>
                <div class="text-xs text-gray-500 mt-1">Pemegang tempat: {APPLICANT_NAME}, {APPLICATION_REFERENCE}, {STATUS_NAME}, {STATUS_CODE}, {JOB_TITLE}, {KOD_GRED}, {NOTES}, {BASE_URL}</div>
            </div>
            <?php if ($hasEnabled): ?>
            <div>
                <label class="inline-flex items-center space-x-2"><input type="checkbox" name="enabled" id="edit_enabled" value="1"> <span>Aktif</span></label>
            </div>
            <?php endif; ?>
            <div class="flex justify-end space-x-2">
                <button type="button" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700" id="cancelEdit">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
    
</div>
<!-- Quill CSS/JS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
var quillDesc = null;
var quillBody = null;
var sampleDescHtml = '<p>Maklumat status: <strong>{STATUS_NAME}</strong> untuk rujukan <strong>{APPLICATION_REFERENCE}</strong>.</p><p>Penerangan ringkas di sini.</p>';
var sampleBodyHtml = '<div style="font-family:Arial,sans-serif">\n<h2>Makluman Status Permohonan</h2>\n<p>Kepada <strong>{APPLICANT_NAME}</strong>,</p>\n<p>Status permohonan anda telah dikemas kini kepada: <strong>{STATUS_NAME}</strong>.</p>\n<p>Rujukan: <strong>{APPLICATION_REFERENCE}</strong></p>\n<p>Nota: {NOTES}</p>\n<p>Terima kasih.</p>\n</div>';
function ensureQuillInit() {
    if (!quillDesc && document.getElementById('quill_description')) {
        quillDesc = new Quill('#quill_description', {
            theme: 'snow',
            placeholder: 'Taip keterangan di sini. Gunakan {APPLICANT_NAME}, {APPLICATION_REFERENCE}, {STATUS_NAME}, {STATUS_CODE}, {JOB_TITLE}, {KOD_GRED}, {NOTES}, {BASE_URL}',
            modules: { toolbar: [[{'header': [1,2,false]}], ['bold','italic','underline'], [{'list':'ordered'},{'list':'bullet'}], [{'align':[]}], ['link','clean']] }
        });
    }
    if (!quillBody && document.getElementById('quill_body')) {
        quillBody = new Quill('#quill_body', {
            theme: 'snow',
            placeholder: 'Taip badan emel HTML di sini. Gunakan {APPLICANT_NAME}, {APPLICATION_REFERENCE}, {STATUS_NAME}, {STATUS_CODE}, {JOB_TITLE}, {KOD_GRED}, {NOTES}, {BASE_URL}',
            modules: { toolbar: [[{'header': [1,2,false]}], ['bold','italic','underline'], [{'list':'ordered'},{'list':'bullet'}], [{'align':[]}], ['link','clean']] }
        });
    }
}
document.querySelectorAll('.edit-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var m = document.getElementById('editModal');
        document.getElementById('edit_id').value = this.dataset.id || '';
        document.getElementById('edit_code').value = this.dataset.code || '';
        document.getElementById('edit_name').value = this.dataset.name || '';
        document.getElementById('edit_subject').value = this.dataset.subject || '';
        ensureQuillInit();
        if (quillDesc) { quillDesc.clipboard.dangerouslyPasteHTML((this.dataset.description && this.dataset.description.length > 0) ? this.dataset.description : sampleDescHtml); }
        if (quillBody) { quillBody.clipboard.dangerouslyPasteHTML((this.dataset.body && this.dataset.body.length > 0) ? this.dataset.body : sampleBodyHtml); }
        var e = document.getElementById('edit_enabled'); if (e) e.checked = (this.dataset.enabled === '1');
        m.classList.remove('hidden');
    });
});
document.getElementById('closeEditModal').addEventListener('click', function(){ document.getElementById('editModal').classList.add('hidden'); });
document.getElementById('cancelEdit').addEventListener('click', function(){ document.getElementById('editModal').classList.add('hidden'); });
document.querySelector('#editModal form').addEventListener('submit', function(){
    ensureQuillInit();
    var d = document.getElementById('edit_description'); if (d && quillDesc) d.value = quillDesc.root.innerHTML;
    var b = document.getElementById('edit_body'); if (b && quillBody) b.value = quillBody.root.innerHTML;
});
var btnDesc = document.getElementById('fillDescSample'); if (btnDesc) { btnDesc.addEventListener('click', function(){ ensureQuillInit(); if (quillDesc) quillDesc.clipboard.dangerouslyPasteHTML(sampleDescHtml); }); }
var btnBody = document.getElementById('fillBodySample'); if (btnBody) { btnBody.addEventListener('click', function(){ ensureQuillInit(); if (quillBody) quillBody.clipboard.dangerouslyPasteHTML(sampleBodyHtml); }); }
</script>
<?php include 'templates/footer.php'; ?>
