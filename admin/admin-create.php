<?php
session_start();
// Centralized bootstrap (logging, DB helper, global handlers)
require_once '../includes/bootstrap.php';
require_once 'includes/error_handler.php';

// Get database connection from main config
$config = require_once '../config.php';

// Benarkan akses jika tiada admin dalam jadual user, jika tidak, hanya admin boleh daftar
try {
    // Get database connection using the merged function
    $result = get_database_connection($config);
    $pdo = $result['pdo'];
    
    if (!$pdo) {
        logError('Database connection not available in admin-create.php', 'DATABASE_ERROR');
        die('Ralat sambungan ke pangkalan data: Sambungan tidak tersedia');
    }
    $stmt = $pdo->query('SELECT COUNT(*) FROM user');
    $user_count = $stmt->fetchColumn();
    if ($user_count > 0 && empty($_SESSION['admin_logged_in'])) {
        header('Location: login.php');
        exit;
    }
} catch (Exception $e) {
    die('Ralat sambungan ke pangkalan data: ' . htmlspecialchars($e->getMessage()));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = trim($_POST['role'] ?? 'admin');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Validasi input
    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Sila isi semua ruangan yang wajib.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Emel tidak sah.';
    } elseif ($password !== $confirm) {
        $error = 'Kata laluan tidak sepadan.';
    } else {
        try {
            // Reuse the existing PDO connection
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                $result = get_database_connection($config);
                $pdo = $result['pdo'];
                
                if (!$pdo) {
                    logError('Database connection not available in admin-create.php', 'DATABASE_ERROR');
                    throw new Exception('Sambungan pangkalan data tidak tersedia');
                }
            }
            // Pastikan nama pengguna dan emel unik
            $stmt = $pdo->prepare('SELECT id FROM user WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Nama pengguna atau emel telah digunakan.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO user (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$username, $email, $hashed, $full_name, $role]);
                header('Location: users.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Ralat pangkalan data: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akaun Admin - ejawatan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($config['favicon']); ?>">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col justify-center items-center">
    <header class="w-full flex flex-col items-center mt-10 mb-6">
        <img src="<?php echo htmlspecialchars($config['logo_url']); ?>" alt="Logo" class="h-20 w-auto mx-auto mb-4 drop-shadow-lg">
    </header>
    <main class="w-full max-w-md bg-white p-8 rounded shadow-xl">
        <h2 class="text-2xl font-bold mb-6 text-center text-blue-900">Daftar Akaun Admin</h2>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-center"><?php echo $error; ?></div>
        <?php elseif ($success): ?>
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4 text-center"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off" class="space-y-4">
            <div>
                <label for="username" class="block mb-1 font-medium">Nama Pengguna <span class="text-red-600">*</span></label>
                <input type="text" id="username" name="username" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="email" class="block mb-1 font-medium">Emel <span class="text-red-600">*</span></label>
                <input type="email" id="email" name="email" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="full_name" class="block mb-1 font-medium">Nama Penuh</label>
                <input type="text" id="full_name" name="full_name" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="role" class="block mb-1 font-medium">Peranan</label>
                <select id="role" name="role" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="admin">Admin</option>
                    <option value="superadmin">Superadmin</option>
                </select>
            </div>
            <div>
                <label for="password" class="block mb-1 font-medium">Kata Laluan <span class="text-red-600">*</span></label>
                <input type="password" id="password" name="password" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="confirm" class="block mb-1 font-medium">Sahkan Kata Laluan <span class="text-red-600">*</span></label>
                <input type="password" id="confirm" name="confirm" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-900 text-white py-2 rounded font-semibold hover:bg-blue-800 transition">Daftar</button>
        </form>
        <div class="mt-4 text-center">
            <a href="index.php" class="text-blue-700 hover:underline text-sm">Kembali ke Dashboard</a>
        </div>
    </main>
</body>
</html>
