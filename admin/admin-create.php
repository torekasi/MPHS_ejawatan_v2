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
                $newId = $pdo->lastInsertId();
                try {
                    require_once __DIR__ . '/../includes/MailSender.php';
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                    $host = $_SERVER['HTTP_HOST'] ?? ($config['base_url_host'] ?? 'localhost');
                    $base = rtrim(($config['base_url'] ?? ($scheme . $host . '/')), '/');
                    $loginUrl = $base . '/admin/login.php';
                    $forgotUrl = $base . '/admin/forgot-password.php';
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+14 days'));
                    $pdo->prepare('UPDATE user SET reset_token = ?, reset_expires = ? WHERE id = ?')->execute([$token, $expires, $newId]);
                    $resetUrl = $base . '/admin/reset-password.php?token=' . urlencode($token);
                    $logoUrl = $base . '/' . ltrim((string)($config['logo_url'] ?? ''), '/');
                    $to = $email;
                    $subject = 'Selamat Datang - Akaun Admin Telah Dicipta';
                    $body = '<!DOCTYPE html><html lang="ms"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Selamat Datang</title><style>body{font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#111827}.container{max-width:600px;margin:0 auto;padding:20px}.header{background:#e0f2fe;color:#1e3a8a;padding:20px;text-align:center}.content{padding:20px;background:#f9fafb}.footer{padding:16px;text-align:center;font-size:12px;color:#6b7280}.button{display:inline-block;padding:10px 18px;background:#ffffff;color:#2563eb;text-decoration:none;border-radius:6px;border:1px solid #2563eb}</style></head><body><div class="container"><div class="header"><img src="' . htmlspecialchars($logoUrl) . '" alt="Logo" style="height:48px;margin-bottom:0"><h1>Majlis Perbandaran Hulu Selangor</h1><h2>Akaun Admin Dicipta</h2></div><div class="content"><p>Kepada <strong>' . htmlspecialchars($full_name ?: $username) . '</strong>,</p><p>Akaun admin anda telah dicipta.</p><div style="background:#fff;padding:12px;border-left:4px solid #2563eb;margin:12px 0"><ul style="list-style:none;padding:0;margin:0"><li><strong>Nama Pengguna:</strong> ' . htmlspecialchars($username) . '</li><li><strong>Emel:</strong> ' . htmlspecialchars($email) . '</li><li><strong>Kata Laluan:</strong> ' . htmlspecialchars($password) . '</li><li><strong>Peranan:</strong> ' . htmlspecialchars($role) . '</li></ul></div><p>Untuk akses papan pemuka, klik pautan berikut:</p><p style="text-align:center;margin:20px 0"><a class="button" href="' . htmlspecialchars($loginUrl) . '">Log Masuk Admin</a></p><p>Atau tukar kata laluan kali pertama anda di pautan berikut:</p><p style="text-align:center;margin:8px 0"><a class="button" href="' . htmlspecialchars($resetUrl) . '">Tukar Kata Laluan Kali Pertama</a></p><p>Jika terlupa kata laluan, gunakan pautan berikut:</p><p style="text-align:center"><a href="' . htmlspecialchars($forgotUrl) . '">' . htmlspecialchars($forgotUrl) . '</a></p></div><div class="footer"><p>Emel ini dijana secara automatik oleh sistem eJawatan.</p></div></div></body></html>';
                    $mailer = new MailSender($config);
                    $mailer->send($to, $subject, $body);
                    if (function_exists('log_admin_info')) { log_admin_info('Welcome email sent to new admin', ['user_id' => $newId, 'username' => $username, 'email' => $email]); }
                    if (function_exists('log_admin_action')) { log_admin_action('Created admin user', 'CREATE', 'user', $newId, ['username' => $username, 'email' => $email, 'role' => $role]); }
                } catch (Throwable $e) {
                    if (function_exists('log_admin_error')) { log_admin_error('Failed sending welcome email', ['error' => $e->getMessage(), 'username' => $username, 'email' => $email]); }
                }
                header('Location: users.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Ralat pangkalan data: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<?php include 'templates/header.php'; ?>

<div class="standard-container mx-auto">
    <div class="w-full max-w-md mx-auto bg-white p-8 rounded shadow-xl">
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
    </div>
</div>

<?php include 'templates/footer.php'; ?>
