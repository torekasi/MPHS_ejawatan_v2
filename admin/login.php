<?php
session_start();
// Centralized bootstrap (logging, DB helper, global handlers)
require_once '../includes/bootstrap.php';

// Get database connection from main config
$config = require_once '../config.php';
$recaptcha_v2_site_key = $config['recaptcha_v2_site_key'] ?? (getenv('RECAPTCHA_V2_SITE_KEY') ?: '');
$recaptcha_v2_secret_key = $config['recaptcha_v2_secret_key'] ?? (getenv('RECAPTCHA_V2_SECRET_KEY') ?: '');

// Include auth.php to handle redirections
require_once 'auth.php';

// Log page access
log_activity('Admin login page accessed', 'OTHER', 'admin', null, null, 'admin');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Enhanced input validation and sanitization
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    if (!empty($recaptcha_v2_secret_key)) {
        $verify_data = http_build_query([
            'secret' => $recaptcha_v2_secret_key,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        $context = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $verify_data, 'timeout' => 5]]);
        $verify = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        $vres = is_string($verify) ? json_decode($verify, true) : null;
        if (!$vres || empty($vres['success'])) {
            $error = 'reCAPTCHA validation failed.';
        }
    } elseif (($config['app_env'] ?? 'development') === 'production') {
        $error = 'Security configuration missing.';
    }
    
    // Improved error handling for database connection
    try {
        if ($error) { throw new Exception('RECAPTCHA_FAILED'); }
        $result = get_database_connection($config);
        $pdo = $result['pdo'];
    
        if (!$pdo) {
            log_error('Database connection failed in login.php', ['error_details' => $result['error_details'] ?? 'Unknown error']);
            throw new Exception('Database connection unavailable.');
        }
    
        // Securely prepare and execute SQL query
        $stmt = $pdo->prepare('SELECT id, username, password FROM user WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
    
            log_activity('Successful login', 'LOGIN', 'admin', $admin['id'], ['user_identifier' => $username], 'admin');
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
            log_warning('Login failed - invalid credentials', ['user_identifier' => $username]);
        }
    } catch (Exception $e) {
        $error_code = uniqid('ERR', true);
        $error = 'Database error. Reference Code: ' . $error_code;
        log_error('Database error during login', ['error_code' => $error_code, 'exception' => $e->getMessage(), 'user_identifier' => $username]);
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log Masuk Admin - ejawatan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($config['favicon']); ?>">
    <?php if (!empty($recaptcha_v2_site_key)): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col justify-center items-center">
    <header class="w-full flex flex-col items-center mt-10 mb-6">
        <img src="<?php echo htmlspecialchars($config['logo_url']); ?>" alt="Logo" class="h-20 w-auto mx-auto mb-4 drop-shadow-lg">
    </header>
    <main class="w-full max-w-md bg-white p-8 rounded shadow-xl">
        <h2 class="text-2xl font-bold mb-6 text-center text-blue-900">Log Masuk Admin</h2>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-center"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off" class="space-y-4">
            <div>
                <label for="username" class="block mb-1 font-medium">Nama Pengguna</label>
                <input type="text" id="username" name="username" required autofocus class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="password" class="block mb-1 font-medium">Kata Laluan</label>
                <input type="password" id="password" name="password" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <?php if (!empty($recaptcha_v2_site_key)): ?>
            <div>
                <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptcha_v2_site_key); ?>"></div>
            </div>
            <?php endif; ?>
            <button type="submit" class="w-full bg-blue-900 text-white py-2 rounded font-semibold hover:bg-blue-800 transition">Log Masuk</button>
        </form>
        <div class="mt-4 text-center">
            <a href="forgot-password.php" class="text-blue-700 hover:underline text-sm">Lupa kata laluan?</a>
        </div>
    </main>
    <footer class="mt-4 text-center text-gray-500 text-sm">
        <p>Version 1.0.0</p>
    </footer>
</body>
</html>

<?php
// DB helper comes from includes/bootstrap.php
?>
