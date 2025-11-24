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

// Set default values for filters and pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Process filter parameters
$filters = [];
if (!empty($_GET['username'])) {
    try {
        if ($pdo) {
            $user_stmt = $pdo->prepare("SELECT id FROM user WHERE username LIKE ?");
        } else {
            throw new PDOException("Database connection failed");
        }
        $user_stmt->execute(['%' . $_GET['username'] . '%']);
        $user_ids = $user_stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($user_ids)) {
            $filters['user_ids'] = $user_ids;
        }
    } catch (PDOException $e) {
        error_log("Error searching users: " . $e->getMessage());
    }
}
if (!empty($_GET['action_type']) && $_GET['action_type'] !== 'ALL') {
    $filters['action_type'] = $_GET['action_type'];
}
if (!empty($_GET['entity_type']) && $_GET['entity_type'] !== 'ALL') {
    $filters['entity_type'] = $_GET['entity_type'];
}
if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get logs with filters
$logs = get_admin_logs($filters, $per_page, $offset);
$total_logs = count_admin_logs($filters);
$total_pages = ceil($total_logs / $per_page);

// Get unique entity types for filter dropdown
try {
    $entity_types = [];
    $check_table = $pdo->query("SHOW TABLES LIKE 'admin_logs'");
    if ($check_table->rowCount() > 0) {
        $stmt = $pdo->query("SELECT DISTINCT entity_type FROM admin_logs ORDER BY entity_type");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity_types[] = $row['entity_type'];
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching entity types: " . $e->getMessage());
    $entity_types = [];
}

// Log this view action
log_admin_action('Viewed activity logs', 'OTHER', 'logs', null, $filters);

include 'templates/header.php';
?>

<div class="standard-container mx-auto bg-white rounded-lg shadow-sm p-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-blue-900">Log Aktiviti Admin</h2>
        <div class="text-sm text-gray-500">
            Total: <?php echo number_format($total_logs); ?> log entries
        </div>
    </div>

    <!-- Filters -->
    <form method="get" class="bg-gray-50 p-4 rounded-lg mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" class="w-full border rounded px-3 py-2 text-sm" 
                    value="<?php echo htmlspecialchars($_GET['username'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Action Type</label>
                <select name="action_type" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="ALL">All Actions</option>
                    <option value="CREATE" <?php if(isset($_GET['action_type']) && $_GET['action_type'] === 'CREATE') echo 'selected'; ?>>CREATE</option>
                    <option value="READ" <?php if(isset($_GET['action_type']) && $_GET['action_type'] === 'READ') echo 'selected'; ?>>READ</option>
                    <option value="UPDATE" <?php if(isset($_GET['action_type']) && $_GET['action_type'] === 'UPDATE') echo 'selected'; ?>>UPDATE</option>
                    <option value="DELETE" <?php if(isset($_GET['action_type']) && $_GET['action_type'] === 'DELETE') echo 'selected'; ?>>DELETE</option>
                    <option value="LOGIN" <?php if(isset($_GET['action_type']) && $_GET['action_type'] === 'LOGIN') echo 'selected'; ?>>LOGIN</option>
                    <option value="LOGOUT" <?php if(isset($_GET['action_type']) && $_GET['action_type'] === 'LOGOUT') echo 'selected'; ?>>LOGOUT</option>
                    <option value="OTHER" <?php if(isset($_GET['action_type']) && $_GET['action_type'] === 'OTHER') echo 'selected'; ?>>OTHER</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Entity Type</label>
                <select name="entity_type" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="ALL">All Entities</option>
                    <?php foreach ($entity_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php if(isset($_GET['entity_type']) && $_GET['entity_type'] === $type) echo 'selected'; ?>>
                            <?php echo htmlspecialchars(ucfirst($type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" name="date_from" class="w-full border rounded px-3 py-2 text-sm" 
                    value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" name="date_to" class="w-full border rounded px-3 py-2 text-sm" 
                    value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
            </div>
        </div>
        <div class="flex items-center">
            <div class="flex-grow">
                <input type="text" name="search" placeholder="Search in actions, details or username..." 
                    class="w-full border rounded px-3 py-2 text-sm" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <div class="ml-4 flex space-x-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition">Filter</button>
                <a href="activity-logs.php" class="bg-gray-500 text-white px-4 py-2 rounded text-sm hover:bg-gray-600 transition">Reset</a>
            </div>
        </div>
    </form>

    <!-- Logs Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full border text-sm">
            <thead>
                <tr class="bg-blue-600">
                    <th class="px-4 py-2 text-left text-white font-medium">Time</th>
                    <th class="px-4 py-2 text-left text-white font-medium">User</th>
                    <th class="px-4 py-2 text-left text-white font-medium">Action</th>
                    <th class="px-4 py-2 text-left text-white font-medium">Type</th>
                    <th class="px-4 py-2 text-left text-white font-medium">Entity</th>
                    <th class="px-4 py-2 text-left text-white font-medium">Job ID</th>
                    <th class="px-4 py-2 text-left text-white font-medium">Details</th>
                    <th class="px-4 py-2 text-left text-white font-medium">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">No activity logs found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600 align-top">
                        <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <?php 
                        // Get username from user table using user_id
                        if (!empty($log['user_id'])) {
                            try {
                                $user_stmt = $pdo->prepare("SELECT username FROM user WHERE id = ?");
                                $user_stmt->execute([$log['user_id']]);
                                $username = $user_stmt->fetchColumn();
                                echo htmlspecialchars($username ?: 'Unknown');
                            } catch (PDOException $e) {
                                error_log("Error fetching username: " . $e->getMessage());
                                echo 'Unknown';
                            }
                        } else {
                            echo 'System';
                        }
                        ?>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <?php echo htmlspecialchars($log['action']); ?>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <span class="inline-block px-2 py-1 text-xs rounded 
                            <?php 
                            switch($log['action_type']) {
                                case 'CREATE': echo 'bg-green-100 text-green-800'; break;
                                case 'READ': echo 'bg-blue-100 text-blue-800'; break;
                                case 'UPDATE': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'DELETE': echo 'bg-red-100 text-red-800'; break;
                                case 'LOGIN': echo 'bg-purple-100 text-purple-800'; break;
                                case 'LOGOUT': echo 'bg-gray-100 text-gray-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo htmlspecialchars($log['action_type']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <?php echo htmlspecialchars(ucfirst($log['entity_type'])); ?>
                    </td>
                    <td class="px-4 py-3 align-top font-mono text-xs">
                        <?php echo htmlspecialchars($log['entity_id'] ?? '-'); ?>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <?php 
                        if (!empty($log['details'])) {
                            // Try to decode JSON details
                            $details = json_decode($log['details'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($details)) {
                                echo '<div class="text-xs max-w-md overflow-hidden">';
                                foreach ($details as $key => $value) {
                                    if (is_array($value)) {
                                        $value = json_encode($value);
                                    }
                                    echo '<div class="mb-1"><span class="font-semibold">' . htmlspecialchars($key) . ':</span> ' . 
                                         htmlspecialchars(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : '') . '</div>';
                                }
                                echo '</div>';
                            } else {
                                // Just display as text
                                echo '<div class="text-xs max-w-md overflow-hidden">' . 
                                     htmlspecialchars(substr($log['details'], 0, 200)) . 
                                     (strlen($log['details']) > 200 ? '...' : '') . '</div>';
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td class="px-4 py-3 align-top font-mono text-xs">
                        <?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-6 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total_logs); ?> of <?php echo $total_logs; ?> entries
        </div>
        <div class="flex space-x-1">
            <?php if ($page > 1): ?>
                <a href="?page=1<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="px-3 py-1 border rounded hover:bg-gray-100">&laquo;</a>
                <a href="?page=<?php echo $page - 1; ?><?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-3 py-1 border rounded hover:bg-gray-100">&lsaquo;</a>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                   class="px-3 py-1 border rounded <?php echo $i === $page ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-3 py-1 border rounded hover:bg-gray-100">&rsaquo;</a>
                <a href="?page=<?php echo $total_pages; ?><?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="px-3 py-1 border rounded hover:bg-gray-100">&raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
