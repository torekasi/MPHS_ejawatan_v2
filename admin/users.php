<?php
session_start();
// Centralized bootstrap (logging, DB helper, global handlers)
require_once '../includes/bootstrap.php';
require_once 'auth.php';
require_once 'includes/error_handler.php';
// Do NOT include admin_logger.php directly; bootstrap auto-loads it in admin context

// Get database connection from main config
$config = require '../config.php';

// Initialize variables
$users = [];
$error = '';
$success = '';

try {
    // Get database connection using the merged function
    $result = get_database_connection($config);
    $pdo = $result['pdo'];
    
    if (!$pdo) {
        logError('Database connection not available in users.php', 'DATABASE_ERROR');
        throw new Exception('Sambungan pangkalan data tidak tersedia');
    }

    // Handle user deletion
    if (isset($_POST['delete_user']) && !empty($_POST['user_id'])) {
        $stmt = $pdo->prepare('DELETE FROM user WHERE id = ? AND username != ?');
        $stmt->execute([$_POST['user_id'], $_SESSION['admin_username']]);
        if ($stmt->rowCount() > 0) {
            $success = 'Pengguna berjaya dipadam.';
        }
    }

    // Handle user status toggle
    if (isset($_POST['toggle_status']) && !empty($_POST['user_id'])) {
        $stmt = $pdo->prepare('UPDATE user SET status = NOT status WHERE id = ? AND username != ?');
        $stmt->execute([$_POST['user_id'], $_SESSION['admin_username']]);
        if ($stmt->rowCount() > 0) {
            $success = 'Status pengguna berjaya dikemaskini.';
        }
    }

    // Fetch all users
    $stmt = $pdo->query('SELECT id, username, email, full_name, role, status, created_at FROM user ORDER BY created_at DESC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Ralat pangkalan data: ' . htmlspecialchars($e->getMessage());
}

include 'templates/header.php';
?>

<div class="standard-container mx-auto bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-blue-900">Senarai Pengguna</h2>
        <a href="admin-create.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Tambah Pengguna
        </a>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-blue-600">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-medium text-white uppercase tracking-wider">Nama Pengguna</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-white uppercase tracking-wider">Emel</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-white uppercase tracking-wider">Nama Penuh</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-white uppercase tracking-wider">Peranan</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-white uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-white uppercase tracking-wider">Tarikh Daftar</th>
                    <th class="px-6 py-3 text-right text-sm font-medium text-white uppercase tracking-wider">Tindakan</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] === 'superadmin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form method="post" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                <button type="submit" name="toggle_status" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $user['status'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php if ($user['username'] !== $_SESSION['admin_username']): ?>
                                <a href="user-edit.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                <form method="post" class="inline" onsubmit="return confirm('Adakah anda pasti mahu memadamkan pengguna ini?');">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <button type="submit" name="delete_user" class="text-red-600 hover:text-red-900">Padam</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
