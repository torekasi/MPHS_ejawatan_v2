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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_title = strtoupper(trim($_POST['job_title'] ?? ''));
    $kod_gred = trim($_POST['kod_gred'] ?? '');
    $edaran_iklan = trim($_POST['edaran_iklan'] ?? '');
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
    $ad_date = trim($_POST['ad_date'] ?? '');
    $ad_close_date = trim($_POST['ad_close_date'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');

    // Generate unique job_code automatically
    $job_code = '';
    try {
        // New format: EJMPHSYYYYMMDDHHMMSS (lowercase prefix)
        // Build code from current timestamp (24-hour clock)
        $job_code = 'EJMPHS' . date('YmdHis');

        // Ensure uniqueness in case two creations happen in the same second
        $attempts = 0;
        while ($attempts < 3) {
            $check_stmt = $pdo->prepare('SELECT COUNT(*) FROM job_postings WHERE job_code = ?');
            $check_stmt->execute([$job_code]);
            if ($check_stmt->fetchColumn() == 0) {
                break; // Unique code found
            }
            // Wait a second and regenerate to avoid collision
            sleep(1);
            $job_code = 'EJMPHS' . date('YmdHis');
            $attempts++;
        }

        if ($attempts >= 3) {
            throw new Exception('Unable to generate unique job code after multiple attempts');
        }
    } catch (Exception $e) {
        $error = 'Ralat menjana kod jawatan: ' . $e->getMessage();
    }

    // Validation (removed job_code from required fields)
    if (!$job_title || !$ad_date || !$ad_close_date || !$edaran_iklan || !$kod_gred || !$salary_min || !$salary_max || !$requirements) {
        $error = 'Sila isi semua ruangan yang wajib.';
    } elseif (!strtotime($ad_date) || !strtotime($ad_close_date)) {
        $error = 'Format tarikh tidak sah. Sila guna format yang betul.';
    } elseif (!is_numeric($salary_min) || !is_numeric($salary_max)) {
        $error = 'Gaji mesti dalam bentuk nombor.';
    } else {
        // Convert date to Y-m-d
        $ad_date_sql = date('Y-m-d', strtotime(str_replace('-', '/', $ad_date)));
        $ad_close_date_sql = date('Y-m-d', strtotime(str_replace('-', '/', $ad_close_date)));
        try {
            $stmt = $pdo->prepare('INSERT INTO job_postings (job_title, job_code, ad_date, ad_close_date, edaran_iklan, kod_gred, salary_min, salary_max, requirements) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$job_title, $job_code, $ad_date_sql, $ad_close_date_sql, $edaran_iklan, $kod_gred, $salary_min, $salary_max, $requirements]);
            
            // Get the newly inserted job ID
            $new_job_id = $pdo->lastInsertId();
            
            // Format job ID for display and logging
            $formatted_job_id = 'JOB-' . str_pad($new_job_id, 6, '0', STR_PAD_LEFT);
            
            // Log the job creation action (use integer entity_id, keep formatted in details)
            $log_details = [
                'job_id' => $formatted_job_id,
                'job_code' => $job_code,
                'job_title' => $job_title,
                'ad_date' => $ad_date_sql,
                'ad_close_date' => $ad_close_date_sql,
                'edaran_iklan' => $edaran_iklan,
                'kod_gred' => $kod_gred,
                'salary_range' => "RM {$salary_min} - RM {$salary_max}"
            ];
            log_admin_action('Created new job posting', 'CREATE', 'job', $new_job_id, $log_details);
            
            // Set success message in session for popup notification
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Jawatan berjaya ditambah!'
            ];
            
            // Redirect to job list page
            header('Location: job-list.php');
            exit;
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
    }
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
<div class="max-w-7xl mx-auto bg-white rounded-lg shadow-sm p-8">
    <h2 class="text-xl font-bold mb-2">Senarai Jawatan Kosong</h2>
    <div class="mb-4 text-gray-600 text-sm">"*" indicates required fields</div>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?php echo $success; ?></div>
    <?php endif; ?>
    <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6" autocomplete="off">
        <div class="col-span-2">
            <label class="font-semibold">NAMA JAWATAN *</label>
<input type="text" name="job_title" class="w-full border rounded px-3 py-2 mt-1 uppercase" placeholder="NYATAKAN NAMA JAWATAN YANG DIIKLANKAN" required value="<?php echo htmlspecialchars(strtoupper($_POST['job_title'] ?? '')); ?>" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase();">
        </div>

        <div>
            <label class="font-semibold">EDARAN IKLAN *</label>
<select name="edaran_iklan" class="w-full border rounded px-3 py-2 mt-1 uppercase" required style="text-transform:uppercase" onchange="this.value = this.value.toUpperCase();">
    <option value="">SILA PILIH</option>
    <option value="DALAMAN SAHAJA (MPHS)" <?php if(strtoupper($_POST['edaran_iklan'] ?? '')=='DALAMAN SAHAJA (MPHS)') echo 'selected'; ?>>DALAMAN SAHAJA (MPHS)</option>
    <option value="IKLAN UMUM" <?php if(strtoupper($_POST['edaran_iklan'] ?? '')=='IKLAN UMUM') echo 'selected'; ?>>IKLAN UMUM</option>
</select>
        </div>
        <div>
            <label class="font-semibold">KOD JAWATAN & GRED *</label>
<input type="text" name="kod_gred" class="w-full border rounded px-3 py-2 mt-1 uppercase" placeholder="CONTOH: B6/N19" required value="<?php echo htmlspecialchars(strtoupper($_POST['kod_gred'] ?? '')); ?>" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase();">
        </div>
        <div>
            <label class="font-semibold">Tarikh Iklan *</label>
            <input type="date" id="ad_date" name="ad_date" class="w-full border rounded px-3 py-2 mt-1" required value="<?php echo htmlspecialchars($_POST['ad_date'] ?? date('Y-m-d')); ?>">
        </div>
        <div>
            <label class="font-semibold">Tarikh Tutup Iklan *</label>
            <input type="date" id="ad_close_date" name="ad_close_date" class="w-full border rounded px-3 py-2 mt-1" required value="<?php echo htmlspecialchars($_POST['ad_close_date'] ?? ''); ?>">
        </div>
        <div>
            <label class="font-semibold">GAJI MINIMUM *</label>
<input type="text" id="salary_min" name="salary_min" class="w-full border rounded px-3 py-2 mt-1" required value="<?php echo isset($_POST['salary_min']) ? number_format(floatval(str_replace([','], '', $_POST['salary_min'])), 2) : ''; ?>" pattern="^\d{1,3}(,\d{3})*(\.\d{2})?$" title="Sila masukkan nilai dalam format 1,234.56" inputmode="decimal" autocomplete="off">
        </div>
        <div>
            <label class="font-semibold">GAJI MAKSIMUM *</label>
<input type="text" id="salary_max" name="salary_max" class="w-full border rounded px-3 py-2 mt-1" required value="<?php echo isset($_POST['salary_max']) ? number_format(floatval(str_replace([','], '', $_POST['salary_max'])), 2) : ''; ?>" pattern="^\d{1,3}(,\d{3})*(\.\d{2})?$" title="Sila masukkan nilai dalam format 1,234.56" inputmode="decimal" autocomplete="off">
        </div>
        <div class="col-span-2">
            <label class="font-semibold">Syarat & Kelayakan Lantikan *</label>
            <input type="hidden" name="requirements" id="requirements">
            <div id="editor" class="w-full border rounded mt-1" style="height: 250px;"></div>
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
            quill.root.innerHTML = <?php echo json_encode($_POST['requirements'] ?? ''); ?>;

            // When the form is submitted, update the hidden input
            document.querySelector('form').onsubmit = function() {
                document.querySelector('#requirements').value = quill.root.innerHTML;
                return true;
            };
        </script>
        <div class="col-span-2 flex gap-4 pt-4">
            <button type="submit" class="bg-blue-600 text-white px-8 py-2 rounded hover:bg-blue-700 transition">Simpan</button>
            <a href="index.php" class="bg-gray-500 text-white px-8 py-2 rounded hover:bg-gray-600 transition">Kembali</a>
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
    
    // Auto-fill end date 45 days after start date
    function updateEndDate() {
        const startDate = document.getElementById('ad_date');
        const endDate = document.getElementById('ad_close_date');
        
        if (startDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(start);
            end.setDate(start.getDate() + 45);
            
            // Format date as YYYY-MM-DD for input[type="date"]
            const year = end.getFullYear();
            const month = String(end.getMonth() + 1).padStart(2, '0');
            const day = String(end.getDate()).padStart(2, '0');
            endDate.value = `${year}-${month}-${day}`;
        }
    }
    
    // Set initial end date on page load if start date has value
    document.addEventListener('DOMContentLoaded', function() {
        updateEndDate();
    });
    
    document.getElementById('salary_min').addEventListener('input', function(e) { formatNumberInput(e.target); });
    document.getElementById('salary_max').addEventListener('input', function(e) { formatNumberInput(e.target); });
    document.getElementById('ad_date').addEventListener('change', updateEndDate);
    
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
