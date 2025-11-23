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
        
        // Capture job requirements for filtering
        $job_requirements = [
            'license' => $_POST['req_license'] ?? [],
            'gender' => $_POST['req_gender'] ?? '',
            'nationality' => $_POST['req_nationality'] ?? '',
            'bangsa' => $_POST['req_bangsa'] ?? '',
            'min_selangor_years' => $_POST['req_min_selangor_years'] ?? '',
            'birth_state' => $_POST['req_birth_state'] ?? '',
            'min_education' => $_POST['req_min_education'] ?? ''
        ];
        $job_requirements_json = json_encode($job_requirements);

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
            
            $stmt = $pdo->prepare('UPDATE job_postings SET job_title=?, ad_date=?, ad_close_date=?, edaran_iklan=?, kod_gred=?, salary_min=?, salary_max=?, requirements=?, job_requirements=? WHERE id=?');
            $stmt->execute([$job_title, $ad_date, $ad_close_date, $edaran_iklan, $kod_gred, $salary_min, $salary_max, $requirements, $job_requirements_json, $id]);
            
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

        <!-- Candidate Filtering Requirements Section -->
        <?php 
        $reqs = json_decode($job['job_requirements'] ?? '{}', true) ?: [];
        ?>

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

        <!-- Candidate Filtering Requirements Section -->
        <div class="md:col-span-2 bg-blue-50 p-6 rounded-lg border border-blue-100 mt-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center justify-between cursor-pointer" onclick="toggleSection('criteria-section')">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Kriteria Penapisan Calon (Ideal Candidate)
                </span>
                <svg id="criteria-icon" class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </h3>
            <p class="text-sm text-blue-700 mb-4">
                Kriteria ini akan membantu dalam penapisan senarai pemohon dengan mengenal pasti calon yang disyorkan berdasarkan keperluan jawatan.
            </p>
            <div id="criteria-section" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- License Requirements (Collapsible) -->
                <div class="col-span-1 md:col-span-3 border-b border-blue-200 pb-4 mb-2">
                    <button type="button" class="flex items-center text-sm font-medium text-blue-700 hover:text-blue-900 focus:outline-none" onclick="toggleLicense()">
                        <span id="license-toggle-icon" class="mr-2">▶</span>
                        Lesen Memandu Diperlukan
                    </button>
                    <div id="license-container" class="hidden mt-3 grid grid-cols-2 md:grid-cols-5 gap-3">
                        <?php 
                        $licenses = ['A', 'B', 'B2', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
                        $selected_licenses = $reqs['license'] ?? [];
                        foreach ($licenses as $lic): 
                        ?>
                        <label class="inline-flex items-center bg-white px-3 py-2 rounded border border-gray-200 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="req_license[]" value="<?php echo $lic; ?>" class="form-checkbox h-4 w-4 text-blue-600" <?php echo in_array($lic, $selected_licenses) ? 'checked' : ''; ?>>
                            <span class="ml-2 text-sm text-gray-700">Lesen <?php echo $lic; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Gender -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jantina</label>
                    <select name="req_gender" class="w-full border rounded px-3 py-2 bg-white">
                        <option value="">Semua Jantina</option>
                        <option value="Lelaki" <?php echo ($reqs['gender'] ?? '') === 'Lelaki' ? 'selected' : ''; ?>>Lelaki</option>
                        <option value="Perempuan" <?php echo ($reqs['gender'] ?? '') === 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>

                <!-- Nationality -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Warganegara</label>
                    <select name="req_nationality" class="w-full border rounded px-3 py-2 bg-white">
                        <option value="">Semua</option>
                        <?php 
                        $nationalities = ["Warganegara Malaysia", "Penduduk Tetap", "Bukan Warganegara", "Pelancong"];
                        foreach ($nationalities as $nat): 
                        ?>
                        <option value="<?php echo $nat; ?>" <?php echo ($reqs['nationality'] ?? '') === $nat ? 'selected' : ''; ?>><?php echo $nat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Bangsa (New) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bangsa</label>
                    <select name="req_bangsa" class="w-full border rounded px-3 py-2 bg-white">
                        <option value="">Semua Bangsa</option>
                        <?php 
                        $races = ["Melayu", "Cina", "India", "Kadazan", "Lain-lain"];
                        foreach ($races as $race): 
                        ?>
                        <option value="<?php echo $race; ?>" <?php echo ($reqs['bangsa'] ?? '') === $race ? 'selected' : ''; ?>><?php echo $race; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Birth State (New) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Negeri Kelahiran</label>
                    <select name="req_birth_state" class="w-full border rounded px-3 py-2 bg-white">
                        <option value="">Semua Negeri</option>
                        <?php 
                        $states = ["Johor", "Kedah", "Kelantan", "Melaka", "Negeri Sembilan", "Pahang", "Perak", "Perlis", "Pulau Pinang", "Sabah", "Sarawak", "Selangor", "Terengganu", "Wilayah Persekutuan"];
                        foreach ($states as $st): 
                        ?>
                        <option value="<?php echo $st; ?>" <?php echo ($reqs['birth_state'] ?? '') === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Education -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Minima Tahap Pendidikan</label>
                    <select name="req_min_education" class="w-full border rounded px-3 py-2 bg-white">
                        <option value="">Tiada Had</option>
                        <option value="SPM" <?php echo ($reqs['min_education'] ?? '') === 'SPM' ? 'selected' : ''; ?>>SPM</option>
                        <option value="STPM" <?php echo ($reqs['min_education'] ?? '') === 'STPM' ? 'selected' : ''; ?>>STPM / Sijil</option>
                        <option value="Diploma" <?php echo ($reqs['min_education'] ?? '') === 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
                        <option value="Ijazah" <?php echo ($reqs['min_education'] ?? '') === 'Ijazah' ? 'selected' : ''; ?>>Ijazah Sarjana Muda</option>
                        <option value="Master" <?php echo ($reqs['min_education'] ?? '') === 'Master' ? 'selected' : ''; ?>>Sarjana (Master) / PhD</option>
                    </select>
                </div>


                <!-- Years in Selangor -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Minima Menetap di Selangor (Tahun)</label>
                    <input type="number" name="req_min_selangor_years" class="w-full border rounded px-3 py-2" min="0" max="99" placeholder="Contoh: 5" value="<?php echo htmlspecialchars($reqs['min_selangor_years'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <script>
        function toggleSection(id) {
            var section = document.getElementById(id);
            var icon = document.getElementById('criteria-icon');
            if (section.classList.contains('hidden')) {
                section.classList.remove('hidden');
                icon.classList.remove('-rotate-90');
            } else {
                section.classList.add('hidden');
                icon.classList.add('-rotate-90');
            }
        }

        function toggleLicense() {
            var container = document.getElementById('license-container');
            var icon = document.getElementById('license-toggle-icon');
            if (container.classList.contains('hidden')) {
                container.classList.remove('hidden');
                icon.innerHTML = '▼';
            } else {
                container.classList.add('hidden');
                icon.innerHTML = '▶';
            }
        }
        
        // Auto-expand license section if any license is selected
        document.addEventListener('DOMContentLoaded', function() {
            var checkboxes = document.querySelectorAll('input[name="req_license[]"]');
            var isChecked = Array.from(checkboxes).some(cb => cb.checked);
            if (isChecked) {
                toggleLicense();
            }
        });
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
