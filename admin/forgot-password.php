<?php
session_start();
require_once 'includes/error_handler.php';
require_once 'includes/admin_logger.php';

// Get database connection from main config
$config = require '../config.php';

// Central bootstrap (logging, Composer autoload, aliases)
require_once __DIR__ . '/../includes/bootstrap.php';

// Load Composer autoloader (for PHPMailer) and MailSender wrapper
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/../includes/MailSender.php';

// Include auth.php to handle redirections
require_once 'auth.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $message = 'Please enter your email address.';
    } else {
        try {
            // Get database connection using the merged function
            $result = get_database_connection($config);
            $pdo = $result['pdo'];
            
            if (!$pdo) {
                logError('Database connection not available in forgot-password.php', 'DATABASE_ERROR');
                throw new Exception('Database connection not available');
            }
            $stmt = $pdo->prepare('SELECT id, username FROM user WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $pdo->prepare('UPDATE user SET reset_token = ?, reset_expires = ? WHERE id = ?')->execute([$token, $expires, $user['id']]);
                // Send email
                $reset_link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/admin/reset-password.php?token=' . $token;
                $subject = 'Permintaan Reset Kata Laluan';
                $body = "Hai {$user['username']},<br><br>Untuk reset kata laluan, klik pautan di bawah:<br><a href='$reset_link'>$reset_link</a><br><br>Jika anda tidak meminta ini, abaikan emel ini.";
                // Use MailSender wrapper (will use PHPMailer via SMTP if configured)
                $mailer = new MailSender($config);
                $mailer->send($email, $subject, $body);
                $message = 'Arahan reset kata laluan telah dihantar ke emel anda.';
                $message = 'Password reset instructions have been sent to your email.';
            } else {
                $message = 'Tiada pengguna dijumpai dengan emel tersebut.';
            }
        } catch (Exception $e) {
            $message = 'Database error.';
        }
    }
}
?>
<?php include 'templates/header.php'; ?>

<div class="standard-container mx-auto">
    <div class="w-full max-w-md mx-auto bg-white p-8 rounded shadow-xl">
        <h2 class="text-2xl font-bold mb-6 text-center text-blue-900">Forgot Password</h2>
        <?php if ($message): ?>
            <div class="bg-blue-100 text-blue-700 px-4 py-2 rounded mb-4 text-center"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off" class="space-y-4">
            <div>
                <label for="email" class="block mb-1 font-medium">Email Address</label>
                <input type="email" id="email" name="email" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-900 text-white py-2 rounded font-semibold hover:bg-blue-800 transition">Send Reset Link</button>
        </form>
        <div class="mt-4 text-center">
            <a href="login.php" class="text-blue-700 hover:underline text-sm">Back to login</a>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
