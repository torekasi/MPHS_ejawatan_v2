<?php
session_start();

// Centralized bootstrap (logging, DB helper, global handlers)
require_once '../includes/bootstrap.php';
require_once 'includes/error_handler.php';
require_once 'auth.php';
// Do NOT include admin_logger.php directly; bootstrap auto-loads it in admin context

// Get database connection from main config
$config = require '../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

// Check for job_code parameter first, then fallback to id
if (isset($_GET['job_code']) && !empty($_GET['job_code'])) {
    $job_code = $_GET['job_code'];
    logError('Job Code Parameter in job-edit.php: ' . $job_code, 'DEBUG_INFO');
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    logError('ID Parameter in job-edit.php: ' . $id, 'DEBUG_INFO');
} else {
    logError('No valid job_code or ID parameter provided in job-edit.php', 'DEBUG_INFO');
    header('Location: job-list.php');
    exit;
}

$error = '';
$success = '';

try {
    // Fetch job based on available parameter
    if (isset($job_code)) {
        $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE job_code = ? LIMIT 1');
        $stmt->execute([$job_code]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM job_postings WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
    }
    
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$job) {
        header('Location: job-list.php');
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $edaran_iklan = $_POST['edaran_iklan'] ?? ($job['edaran_iklan'] ?? '');
        $job_title = strtoupper(trim($_POST['job_title'] ?? ''));
        $ad_date = trim($_POST['ad_date'] ?? '');
        $ad_close_date = trim($_POST['ad_close_date'] ?? '');
        $edaran_iklan = strtoupper(trim($_POST['edaran_iklan'] ?? ''));
        $kod_gred = strtoupper(trim($_POST['kod_gred'] ?? ''));
        $salary_min = trim($_POST['salary_min'] ?? '');
$salary_max = trim($_POST['salary_max'] ?? '');
// Server-side validation for currency
$salary_min = str_replace(',', '', trim($_POST['salary_min'] ?? ''));
$salary_max = str_replace(',', '', trim($_POST['salary_max'] ?? ''));
if (!is_numeric($salary_min) || !is_numeric($salary_max)) {
    $error = 'Gaji mesti dalam format nombor (cth: 1,234.56)';
}
$salary_min = number_format((float)$salary_min, 2, '.', '');
$salary_max = number_format((float)$salary_max, 2, '.', '');
        $requirements = trim($_POST['requirements'] ?? '');

        // Validation
        if (!$job_title || !$ad_date || !$ad_close_date || !$edaran_iklan || !$kod_gred || !$salary_min || !$salary_max || !$requirements) {
            $error = 'Sila isi semua ruangan yang wajib.';
        } elseif (!strtotime($ad_date) || !strtotime($ad_close_date)) {
            $error = 'Format tarikh tidak sah. Sila guna format yang betul.';
        } elseif (!is_numeric($salary_min) || !is_numeric($salary_max)) {
            $error = 'Gaji mesti dalam bentuk nombor.';
        } else {
            // Store old job data for logging changes
            $old_job = $job;
            
            $stmt = $pdo->prepare('UPDATE job_postings SET job_title=?, ad_date=?, ad_close_date=?, edaran_iklan=?, kod_gred=?, salary_min=?, salary_max=?, requirements=? WHERE id=?');
            $stmt->execute([$job_title, $ad_date, $ad_close_date, $edaran_iklan, $kod_gred, $salary_min, $salary_max, $requirements, $id]);
            
            // Log the job update action (use integer entity_id, keep formatted code in details)
            // Use job_code if available, otherwise use formatted job_id
$formatted_job_id = !empty($job['job_code']) ? $job['job_code'] : 'JOB-' . str_pad($job['id'], 6, '0', STR_PAD_LEFT);

$log_details = [
    'job_code' => $job['job_code'] ?? $formatted_job_id,
                'changes' => [
                    'job_title' => ['from' => $old_job['job_title'], 'to' => $job_title],
                    'ad_date' => ['from' => $old_job['ad_date'], 'to' => $ad_date],
                    'ad_close_date' => ['from' => $old_job['ad_close_date'], 'to' => $ad_close_date],
                    'edaran_iklan' => ['from' => $old_job['edaran_iklan'], 'to' => $edaran_iklan],
                    'kod_gred' => ['from' => $old_job['kod_gred'], 'to' => $kod_gred],
                    'salary_min' => ['from' => $old_job['salary_min'], 'to' => $salary_min],
                    'salary_max' => ['from' => $old_job['salary_max'], 'to' => $salary_max]
                ]
            ];
            log_admin_action('Updated job posting', 'UPDATE', 'job', $id, $log_details);
            
            // Set success message in session for popup notification
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Jawatan berjaya dikemaskini!'
            ];
            
            // Redirect to job list page after successful update
            header('Location: job-list.php');
            exit;
        }
    }
} catch (Exception $e) {
    logError($e->getMessage(), 'DATABASE_ERROR');
    
    // Set error message in session for popup notification
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Ralat pangkalan data: ' . htmlspecialchars($e->getMessage())
    ];
    
    // Redirect to job list page even on error
    header('Location: job-list.php');
    exit;
}

// If validation errors occur, set them in the session and redirect
if ($error) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => $error
    ];
    header('Location: job-list.php');
    exit;
}

include 'templates/header.php';
?>
<div class="max-w-7xl mx-auto bg-white rounded-lg shadow-sm p-8 mt-8">
    <h2 class="text-2xl font-bold mb-6 text-blue-900">Edit Jawatan</h2>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6" autocomplete="off">
        <div class="md:col-span-2">
            <label class="block font-semibold mb-1">NAMA JAWATAN *</label>
<input type="text" name="job_title" class="w-full border rounded px-3 py-2 uppercase" required value="<?php echo htmlspecialchars(strtoupper($job['job_title'])); ?>" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase();">
        </div>
        <div>
            <label class="block font-semibold mb-1">EDARAN IKLAN *</label>
<select name="edaran_iklan" class="w-full border rounded px-3 py-2 uppercase" required style="text-transform:uppercase" onchange="this.value = this.value.toUpperCase();">
    <option value="DALAMAN SAHAJA (MPHS)" <?php if(strtoupper($job['edaran_iklan'])=='DALAMAN SAHAJA (MPHS)') echo 'selected'; ?>>DALAMAN SAHAJA (MPHS)</option>
    <option value="IKLAN UMUM" <?php if(strtoupper($job['edaran_iklan'])=='IKLAN UMUM') echo 'selected'; ?>>IKLAN UMUM</option>
</select>
        </div>
        <div>
            <label class="block font-semibold mb-1">KOD JAWATAN & GRED *</label>
<input type="text" name="kod_gred" class="w-full border rounded px-3 py-2 uppercase" placeholder="Contoh: B6/N19" required value="<?php echo htmlspecialchars(strtoupper($job['kod_gred'] ?? '')); ?>" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase();">
        </div>
        <div>
            <label class="block font-semibold mb-1">Tarikh Iklan *</label>
            <input type="date" name="ad_date" class="w-full border rounded px-3 py-2" required value="<?php echo date('Y-m-d', strtotime($job['ad_date'])); ?>">
        </div>
        <div>
            <label class="block font-semibold mb-1">Tarikh Tutup Iklan *</label>
            <input type="date" name="ad_close_date" class="w-full border rounded px-3 py-2" required value="<?php echo date('Y-m-d', strtotime($job['ad_close_date'])); ?>">
        </div>
        <div>
            <label class="block font-semibold mb-1">GAJI MINIMUM *</label>
<input type="text" id="salary_min" name="salary_min" class="w-full border rounded px-3 py-2" required value="<?php echo number_format($job['salary_min'], 2); ?>" pattern="^\d{1,3}(,\d{3})*(\.\d{2})?$" title="Sila masukkan nilai dalam format 1,234.56" inputmode="decimal" autocomplete="off">
        </div>
        <div>
            <label class="block font-semibold mb-1">GAJI MAKSIMUM *</label>
<input type="text" id="salary_max" name="salary_max" class="w-full border rounded px-3 py-2" required value="<?php echo number_format($job['salary_max'], 2); ?>" pattern="^\d{1,3}(,\d{3})*(\.\d{2})?$" title="Sila masukkan nilai dalam format 1,234.56" inputmode="decimal" autocomplete="off">
        </div>
        <div class="md:col-span-2">
            <label class="block font-semibold mb-1">Syarat & Kelayakan Lantikan *</label>
            <input type="hidden" name="requirements" id="requirements">
            <div id="editor" class="w-full border rounded" style="height: 250px;"></div>
        </div>
        <!-- Include stylesheet -->
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
        <!-- Include the Quill library -->
        <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
        <script>
            var quill = new Quill('#editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link']
                    ]
                }
            });

            // Set initial content if any
            quill.root.innerHTML = <?php echo json_encode($job['requirements']); ?>;

            // When the form is submitted, update the hidden input
            document.querySelector('form').onsubmit = function() {
                document.querySelector('#requirements').value = quill.root.innerHTML;
                return true;
            };
        </script>
        <div class="md:col-span-2 flex gap-4 pt-4">
            <button type="submit" class="bg-blue-600 text-white px-8 py-2 rounded hover:bg-blue-700 transition">Simpan</button>
            <a href="job-list.php" class="bg-gray-500 text-white px-8 py-2 rounded hover:bg-gray-600 transition">Kembali</a>
        </div>
        <script>
    function formatNumberInput(input) {
        let value = input.value.replace(/[^\d.]/g, '');
        // Only allow one decimal point
        let parts = value.split('.');
        let intPart = parts[0];
        let decPart = parts[1] ? parts[1].slice(0,2) : '';
        intPart = intPart.replace(/^0+/, '') || '0';
        intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        input.value = decPart !== '' ? intPart + '.' + decPart : intPart;
    }
    document.getElementById('salary_min').addEventListener('input', function(e) { formatNumberInput(e.target); });
    document.getElementById('salary_max').addEventListener('input', function(e) { formatNumberInput(e.target); });
    document.querySelector('form').addEventListener('submit', function(e) {
        const min = document.getElementById('salary_min');
        const max = document.getElementById('salary_max');
        const regex = /^\d{1,3}(,\d{3})*(\.\d{2})?$/;
        if (!regex.test(min.value) || !regex.test(max.value)) {
            alert('Sila masukkan gaji dalam format 1,234.56');
            e.preventDefault();
        }
    });
    </script>
</form>
</div>
<?php include 'templates/footer.php'; ?>
