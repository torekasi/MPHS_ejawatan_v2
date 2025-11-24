<?php
session_start();
require_once '../includes/bootstrap.php';
require_once 'auth.php';

// Secure: only allow logged-in admins
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Log access
log_admin_action('Accessed status normalization tool', 'OTHER', 'maintenance', $_SESSION['admin_id'] ?? null, []);

// DB connection
$config = require '../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

if (!$pdo) {
    log_error('DB connection failed in normalize-statuses.php', [
        'error_type' => 'DATABASE_ERROR',
        'error_details' => $result['error_details'] ?? 'No details'
    ]);
    die('System Error: Could not connect to database.');
}

function fetch_status_counts($pdo) {
    $stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM job_applications GROUP BY status ORDER BY status");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

$ran = isset($_GET['run']) && $_GET['run'] == '1';
$before = fetch_status_counts($pdo);
$updated = false;
$error = null;

if ($ran) {
    try {
        $pdo->beginTransaction();

        // Normalize common legacy/lowercase values to standardized uppercase statuses
        $updates = [
            ["UPDATE job_applications SET status='PENDING'    WHERE status IN ('pending')"],
            ["UPDATE job_applications SET status='REVIEWED'   WHERE status IN ('shortlisted','SHORTLISTED','reviewed','Reviewed')"],
            ["UPDATE job_applications SET status='APPROVED'   WHERE status IN ('accepted','ACCEPTED','approved','Approved')"],
            ["UPDATE job_applications SET status='REJECTED'   WHERE status IN ('rejected','Rejected')"],
            ["UPDATE job_applications SET status='INTERVIEWED' WHERE status IN ('interviewed','Interviewed')"],
            ["UPDATE job_applications SET status='OFFERED'     WHERE status IN ('offered','Offered')"],
            // Final pass: uppercase any remaining lowercase common states without changing meaning
            ["UPDATE job_applications SET status=UPPER(status) WHERE status IN ('pending','reviewed','approved','rejected','interviewed','offered')"],
        ];

        foreach ($updates as $u) {
            $pdo->exec($u[0]);
        }

        $pdo->commit();
        $updated = true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
        log_error('Status normalization failed', [
            'error_type' => 'DATABASE_ERROR',
            'error_details' => $error
        ]);
    }
}

$after = $ran ? fetch_status_counts($pdo) : $before;

include 'templates/header.php';
?>
<div class="standard-container mx-auto bg-white rounded-lg shadow-sm p-6">
  <h1 class="text-2xl font-bold mb-4">Normalize Application Statuses</h1>
  <p class="text-gray-600 mb-4">This tool converts legacy/lowercase statuses to standardized uppercase values used by the admin panel.</p>

  <?php if ($error): ?>
    <div class="bg-red-50 text-red-700 p-3 rounded mb-4">Error: <?php echo htmlspecialchars($error); ?></div>
  <?php elseif ($ran && $updated): ?>
    <div class="bg-green-50 text-green-700 p-3 rounded mb-4">Normalization completed successfully.</div>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      <h2 class="font-semibold mb-2">Before</h2>
      <div class="border rounded">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($before)): ?>
              <tr><td class="px-4 py-2" colspan="2">No data</td></tr>
            <?php else: foreach ($before as $row): ?>
              <tr>
                <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($row['status']); ?></td>
                <td class="px-4 py-2 text-sm text-gray-900"><?php echo (int)$row['cnt']; ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div>
      <h2 class="font-semibold mb-2">After</h2>
      <div class="border rounded">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($after)): ?>
              <tr><td class="px-4 py-2" colspan="2">No data</td></tr>
            <?php else: foreach ($after as $row): ?>
              <tr>
                <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($row['status']); ?></td>
                <td class="px-4 py-2 text-sm text-gray-900"><?php echo (int)$row['cnt']; ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mt-6 flex items-center gap-3">
    <a href="normalize-statuses.php?run=1" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Run Normalization</a>
    <a href="index.php" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded">Back to Dashboard</a>
  </div>
</div>
<?php include 'templates/footer.php'; ?>
