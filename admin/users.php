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

        if (isset($_POST['delete_user']) && !empty($_POST['user_id'])) {
            $stmt = $pdo->prepare('DELETE FROM user WHERE id = ? AND username != ?');
            $stmt->execute([$_POST['user_id'], $_SESSION['admin_username']]);
            if ($stmt->rowCount() > 0) {
                $success = 'Pengguna berjaya dipadam.';
            }
        }

        if (isset($_POST['toggle_status']) && !empty($_POST['user_id'])) {
            $stmt = $pdo->prepare('UPDATE user SET status = NOT status WHERE id = ? AND username != ?');
            $stmt->execute([$_POST['user_id'], $_SESSION['admin_username']]);
            if ($stmt->rowCount() > 0) {
                $success = 'Status pengguna berjaya dikemaskini.';
            }
        }

        if (isset($_POST['send_reset_email']) && !empty($_POST['user_id'])) {
            $stmt = $pdo->prepare('SELECT id, username, email FROM user WHERE id = ? LIMIT 1');
            $stmt->execute([$_POST['user_id']]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($u && !empty($u['email'])) {
                require_once __DIR__ . '/../includes/MailSender.php';
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? ($config['base_url_host'] ?? 'localhost');
                $base = rtrim(($config['base_url'] ?? ($scheme . $host . '/')), '/');
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $pdo->prepare('UPDATE user SET reset_token = ?, reset_expires = ? WHERE id = ?')->execute([$token, $expires, $u['id']]);
                $resetUrl = $base . '/admin/reset-password.php?token=' . urlencode($token);
                $logoUrl = $base . '/' . ltrim((string)($config['logo_url'] ?? ''), '/');
                $subject = 'Arahan Reset Kata Laluan Akaun Admin';
                $body = '<!DOCTYPE html><html lang="ms"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Reset Kata Laluan</title><style>body{font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#111827}.container{max-width:600px;margin:0 auto;padding:20px}.header{background:#e0f2fe;color:#1e3a8a;padding:20px;text-align:center}.content{padding:20px;background:#f9fafb}.footer{padding:16px;text-align:center;font-size:12px;color:#6b7280}.button{display:inline-block;padding:10px 18px;background:#ffffff;color:#2563eb;text-decoration:none;border-radius:6px;border:1px solid #2563eb}</style></head><body><div class="container"><div class="header"><img src="' . htmlspecialchars($logoUrl) . '" alt="Logo" style="height:48px;margin-bottom:0"><h1>eJawatan MPHS</h1><h2>Reset Kata Laluan</h2></div><div class="content"><p>Kepada <strong>' . htmlspecialchars($u['username']) . '</strong>,</p><p>Sila klik butang di bawah untuk menetapkan semula kata laluan anda.</p><p style="text-align:center;margin:20px 0"><a class="button" href="' . htmlspecialchars($resetUrl) . '">Reset Kata Laluan</a></p><p>Pautan ini akan tamat pada ' . htmlspecialchars(date('d/m/Y H:i', strtotime($expires))) . '.</p></div><div class="footer"><p>Emel ini dijana secara automatik.</p></div></div></body></html>';
                $mailer = new MailSender($config);
                $mailer->send($u['email'], $subject, $body);
                $success = 'Emel reset kata laluan telah dihantar.';
            }
        }

        if (isset($_POST['send_welcome_email']) && !empty($_POST['user_id'])) {
            $stmt = $pdo->prepare('SELECT id, username, email, role FROM user WHERE id = ? LIMIT 1');
            $stmt->execute([$_POST['user_id']]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($u && !empty($u['email'])) {
                require_once __DIR__ . '/../includes/MailSender.php';
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? ($config['base_url_host'] ?? 'localhost');
                $base = rtrim(($config['base_url'] ?? ($scheme . $host . '/')), '/');
                $loginUrl = $base . '/admin/login.php';
                $forgotUrl = $base . '/admin/forgot-password.php';
                $logoUrl = $base . '/' . ltrim((string)($config['logo_url'] ?? ''), '/');
                $subject = 'Selamat Datang - Akaun Admin';
                $body = '<!DOCTYPE html><html lang="ms"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Selamat Datang</title><style>body{font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#111827}.container{max-width:600px;margin:0 auto;padding:20px}.header{background:#e0f2fe;color:#1e3a8a;padding:20px;text-align:center}.content{padding:20px;background:#f9fafb}.footer{padding:16px;text-align:center;font-size:12px;color:#6b7280}.button{display:inline-block;padding:10px 18px;background:#ffffff;color:#2563eb;text-decoration:none;border-radius:6px;border:1px solid #2563eb}</style></head><body><div class="container"><div class="header"><img src="' . htmlspecialchars($logoUrl) . '" alt="Logo" style="height:48px;margin-bottom:0"><h1>Majlis Perbandaran Hulu Selangor</h1><h2>Selamat Datang</h2></div><div class="content"><p>Kepada <strong>' . htmlspecialchars($u['username']) . '</strong>,</p><p>Akaun admin anda tersedia untuk digunakan.</p><div style="background:#fff;padding:12px;border-left:4px solid #2563eb;margin:12px 0"><ul style="list-style:none;padding:0;margin:0"><li><strong>Nama Pengguna:</strong> ' . htmlspecialchars($u['username']) . '</li><li><strong>Emel:</strong> ' . htmlspecialchars($u['email']) . '</li><li><strong>Peranan:</strong> ' . htmlspecialchars($u['role']) . '</li></ul></div><p style="text-align:center;margin:20px 0"><a class="button" href="' . htmlspecialchars($loginUrl) . '">Log Masuk Admin</a></p><p>Jika perlu menetapkan semula kata laluan, gunakan pautan berikut:</p><p style="text-align:center"><a href="' . htmlspecialchars($forgotUrl) . '">' . htmlspecialchars($forgotUrl) . '</a></p></div><div class="footer"><p>Emel ini dijana secara automatik oleh sistem eJawatan.</p></div></div></body></html>';
                $mailer = new MailSender($config);
                $mailer->send($u['email'], $subject, $body);
                $success = 'Emel selamat datang telah dihantar.';
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
                            <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($user['email']); ?></div>
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
                                <div class="inline-block text-left" style="z-index:0;">
                                    <button type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-3 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none" onclick="toggleDropdown('dd-<?php echo (int)$user['id']; ?>', this)">
                                        Tindakan
                                        <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <div id="dd-<?php echo (int)$user['id']; ?>" class="origin-top-right mt-2 w-44 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden" style="position:fixed;z-index:2147483647;left:0;top:0;">
                                        <div class="py-1">
                                            <a href="user-edit.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit</a>
                                            <form method="post" class="block">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                                <button type="submit" name="delete_user" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" onclick="return confirm('Adakah anda pasti mahu memadamkan pengguna ini?');">Padam</button>
                                            </form>
                                            <form method="post" class="block">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                                <button type="submit" name="send_reset_email" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Hantar Emel Reset Kata Laluan</button>
                                            </form>
                                            <form method="post" class="block">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                                <button type="submit" name="send_welcome_email" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Hantar Emel Selamat Datang</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleDropdown(id, btn) {
    var el = document.getElementById(id);
    if (!el) return;
    document.querySelectorAll('[id^="dd-"]').forEach(function(d) {
        if (d.id !== id) { d.classList.add('hidden'); }
    });
    var rect = btn.getBoundingClientRect();
    el.style.position = 'fixed';
    el.style.top = (rect.bottom + 6) + 'px';
    el.style.left = rect.left + 'px';
    el.style.zIndex = '2147483647';
    el.classList.toggle('hidden');
}
</script>
<?php include 'templates/footer.php'; ?>
