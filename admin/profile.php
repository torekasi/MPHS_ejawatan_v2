<?php
session_start();
// Centralized bootstrap (logging, DB helper, global handlers)
require_once '../includes/bootstrap.php';
require_once 'auth.php';
require_once 'includes/error_handler.php';
// Do NOT include admin_logger.php directly; bootstrap auto-loads it in admin context

// Get database connection from main config
$config = require_once '../config.php';

$error = '';
$success = '';
$user = null;

try {
    // Get database connection using the merged function
    $result = get_database_connection($config);
    $pdo = $result['pdo'];
    
    if (!$pdo) {
        logError('Database connection not available in profile.php', 'DATABASE_ERROR');
        throw new Exception('Sambungan pangkalan data tidak tersedia');
    }
    $stmt = $pdo->prepare('SELECT * FROM user WHERE username = ? LIMIT 1');
    $stmt->execute([$_SESSION['admin_username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = 'Pengguna tidak dijumpai.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if (empty($email)) {
            $error = 'Emel diperlukan.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format emel tidak sah.';
        } elseif (!empty($password) && $password !== $confirm) {
            $error = 'Kata laluan tidak sepadan.';
        } else {
            // Check if email is already used by another user
            $stmt = $pdo->prepare('SELECT id FROM user WHERE email = ? AND id != ? LIMIT 1');
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $error = 'Emel telah digunakan oleh pengguna lain.';
            } else {
                if (!empty($password)) {
                    $stmt = $pdo->prepare('UPDATE user SET email = ?, full_name = ?, password = ? WHERE id = ?');
                    $stmt->execute([$email, $full_name, password_hash($password, PASSWORD_DEFAULT), $user['id']]);
                } else {
                    $stmt = $pdo->prepare('UPDATE user SET email = ?, full_name = ? WHERE id = ?');
                    $stmt->execute([$email, $full_name, $user['id']]);
                }
                $success = 'Profil berjaya dikemaskini.';
                // Refresh user data
                $stmt = $pdo->prepare('SELECT * FROM user WHERE id = ? LIMIT 1');
                $stmt->execute([$user['id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    }
} catch (Exception $e) {
    $error = 'Ralat pangkalan data: ' . htmlspecialchars($e->getMessage());
}

include 'templates/header.php';
?>
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-bold text-blue-900 mb-6">Profil Saya</h2>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($user): ?>
    <form method="post" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pengguna</label>
            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full px-3 py-2 border rounded bg-gray-100" disabled>
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Emel <span class="text-red-600">*</span></label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Penuh</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Laluan Baru <small>(Biarkan kosong jika tidak mahu tukar)</small></label>
            <input type="password" id="password" name="password" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="confirm" class="block text-sm font-medium text-gray-700 mb-1">Sahkan Kata Laluan Baru</label>
            <input type="password" id="confirm" name="confirm" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex gap-4 pt-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">Simpan</button>
            <a href="index.php" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 transition">Kembali</a>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php include 'templates/footer.php'; ?>
