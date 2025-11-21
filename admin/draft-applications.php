<?php
session_start();
// Centralized bootstrap (logging, DB helper, global handlers)
require_once '../includes/bootstrap.php';
// Keep page-level auth
require_once 'auth.php';

// Get database connection from main config
$config = require '../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

// Log admin action
// Initialize filters
$filters = [];
$where_clauses = [];
$params = [];

log_admin_info('Viewed draft applications list', [
    'action' => 'VIEW_DRAFT_APPLICATIONS_LIST',
    'filters' => $filters
]);

// Handle search and filters
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(aa.nama_penuh LIKE ? OR aa.nombor_ic LIKE ? OR aa.email LIKE ? OR aa.application_reference LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $filters['search'] = $_GET['search'];
}

if (isset($_GET['job_id']) && !empty($_GET['job_id'])) {
    $where_clauses[] = "aa.job_id = ?";
    $params[] = $_GET['job_id'];
    $filters['job_id'] = $_GET['job_id'];
}

// Add filter to only show draft applications
$where_clauses[] = "aa.submission_status = 'Draft'";

// Build the WHERE clause
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Count total draft applications
$count_sql = "SELECT COUNT(*) FROM application_application_main aa" . $where_sql;
// Check if PDO connection exists before preparing statement
if (!$pdo) {
    throw new Exception('Database connection failed');
}
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_applications = $count_stmt->fetchColumn();
$total_pages = ceil($total_applications / $per_page);

// Get draft applications with job title
$sql = "SELECT aa.*, jp.job_title, jp.job_code AS job_code, jp.kod_gred 
        FROM application_application_main aa 
        LEFT JOIN job_postings jp ON aa.job_id = jp.id" . 
        $where_sql . 
        " ORDER BY aa.created_at DESC LIMIT ? OFFSET ?";

$all_params = array_merge($params, [$per_page, $offset]);
$stmt = $pdo->prepare($sql);
try {
    $stmt->execute($all_params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error
    log_admin_error('Database error in draft-applications.php', [
        'error' => $e->getMessage(),
        'sql' => $sql,
        'params' => $all_params
    ]);
    $applications = [];
    $error_message = "Ralat pangkalan data: " . $e->getMessage();
}

// Get all jobs for filter dropdown
try {
    $jobs_stmt = $pdo->query("SELECT id, job_title, job_code, kod_gred FROM job_postings ORDER BY job_title");
    $jobs = $jobs_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    log_admin_error('Database error getting jobs', [
        'error' => $e->getMessage()
    ]);
    $jobs = [];
}

// Page title
$page_title = "Senarai Permohonan Draf";
include 'templates/header.php';
?>

<div class="standard-container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Senarai Permohonan Draf</h1>
        <div class="flex items-center space-x-4">
            <a href="applications-list.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Permohonan Dihantar
            </a>
            <span class="text-sm text-gray-500">Total: <?php echo $total_applications; ?> draf</span>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($_SESSION['success']); ?>
    </div>
    <?php unset($_SESSION['success']); endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($_SESSION['error']); ?>
    </div>
    <?php unset($_SESSION['error']); endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <strong>Ralat:</strong> <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>
    
    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <div class="flex items-center">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Total Draf</p>
                    <p class="text-lg font-semibold text-gray-900"><?php echo $total_applications; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Tidak Lengkap</p>
                    <p class="text-lg font-semibold text-gray-900">
                        <?php 
                        $incomplete_count = 0;
                        foreach ($applications as $app) {
                            // Check if essential fields are missing
                            if (empty($app['nama_penuh']) || empty($app['nombor_ic']) || empty($app['email'])) {
                                $incomplete_count++;
                            }
                        }
                        echo $incomplete_count;
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Siap Dihantar</p>
                    <p class="text-lg font-semibold text-gray-900">
                        <?php 
                        $ready_count = 0;
                        foreach ($applications as $app) {
                            // Check if essential fields are complete
                            if (!empty($app['nama_penuh']) && !empty($app['nombor_ic']) && !empty($app['email'])) {
                                $ready_count++;
                            }
                        }
                        echo $ready_count;
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Tapis Permohonan Draf</h2>
        <form method="get" class="flex flex-wrap gap-4">
            <div class="w-full md:w-1/2">
                <label class="block text-gray-700 mb-2">Carian</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" 
                       placeholder="Nama / No. IC / Email / No. Rujukan" 
                       class="w-full px-3 py-2 border rounded">
            </div>
            
            <div class="w-full md:w-1/3">
                <label class="block text-gray-700 mb-2">Jawatan</label>
                <select name="job_id" class="w-full px-3 py-2 border rounded">
                    <option value="">Semua Jawatan</option>
                    <?php foreach ($jobs as $job): ?>
                    <option value="<?php echo $job['id']; ?>" <?php echo (isset($filters['job_id']) && $filters['job_id'] == $job['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($job['job_title'] . ' (' . $job['job_code'] . ' ' . $job['kod_gred'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="w-full md:w-auto flex items-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tapis</button>
                <a href="draft-applications.php" class="ml-2 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Draft Applications Table -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
        <?php if (empty($applications)): ?>
        <div class="p-8 text-center text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tiada Permohonan Draf</h3>
            <p class="mt-1 text-sm text-gray-500">Tiada permohonan draf ditemui.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pemohon</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawatan Dipohon</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarikh Direkodkan</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Kelengkapan</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($applications as $application): 
                        // Check completeness
                        $is_complete = !empty($application['nama_penuh']) && !empty($application['nombor_ic']) && !empty($application['email']);
                        $status_class = $is_complete ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : 'bg-red-100 text-red-800 border-red-200';
                        $status_text = $is_complete ? 'Draf' : 'Tidak Lengkap';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($application['id']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <?php if (!empty($application['gambar_passport_path']) && file_exists('../' . $application['gambar_passport_path'])): ?>
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full overflow-hidden">
                                        <img src="../<?php echo htmlspecialchars($application['gambar_passport_path']); ?>" alt="Passport Photo" class="h-full w-full object-cover">
                                    </div>
                                <?php else: ?>
                                    <div class="flex-shrink-0 h-10 w-10 bg-orange-100 rounded-full flex items-center justify-center">
                                        <span class="text-orange-600 font-semibold">
                                            <?php echo !empty($application['nama_penuh']) ? strtoupper(substr($application['nama_penuh'], 0, 1)) : '?'; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo !empty($application['nama_penuh']) ? htmlspecialchars($application['nama_penuh']) : '<span class="text-gray-400">Nama tidak diisi</span>'; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo !empty($application['email']) ? htmlspecialchars($application['email']) : '<span class="text-gray-400">Email tidak diisi</span>'; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo !empty($application['nombor_ic']) ? htmlspecialchars($application['nombor_ic']) : '<span class="text-gray-400">IC tidak diisi</span>'; ?>
                                    </div>
                                    <div class="text-sm text-gray-400">
                                        <small>Ref: <?php echo htmlspecialchars($application['application_reference'] ?? 'N/A'); ?></small>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($application['job_title'] ?? 'N/A'); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars(($application['job_code'] ?? '') . ' ' . ($application['kod_gred'] ?? '')); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php 
                                $date_str = $application['created_at'] ?? $application['application_date'] ?? null;
                                echo $date_str ? date('d/m/Y H:i', strtotime($date_str)) : '-';
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="application-view.php?id=<?php echo $application['id']; ?>" class="text-blue-600 hover:text-blue-900">Lihat</a>
                                <button onclick="deleteDraft(<?php echo $application['id']; ?>)" class="text-red-600 hover:text-red-900 ml-2">Padam</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-6 flex justify-center">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($filters) ? '&' . http_build_query(array_filter($filters)) : ''; ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Previous</span>
                &laquo; Sebelumnya
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                    <?php echo $i; ?>
                </span>
                <?php else: ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($filters) ? '&' . http_build_query(array_filter($filters)) : ''; ?>" 
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <?php echo $i; ?>
                </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($filters) ? '&' . http_build_query(array_filter($filters)) : ''; ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <span class="sr-only">Next</span>
                Seterusnya &raquo;
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
function deleteDraft(applicationId) {
    if (confirm('Adakah anda pasti ingin memadam permohonan draf ini? Tindakan ini tidak boleh dibatalkan.')) {
        // Create a form to submit the delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete-draft.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'application_id';
        input.value = applicationId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'templates/footer.php';
