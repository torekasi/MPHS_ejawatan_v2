<?php
// Admin Password Reset Page
session_start();
require_once __DIR__ . '/../includes/ActivityLogger.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/includes/error_handler.php';
require_once __DIR__ . '/includes/admin_logger.php';

// Get database connection from main config
$config = require '../config.php';

// Include auth.php to handle redirections
require_once 'auth.php';

// Initialize variables
$pdo = null;
$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Connect to database
try {
    // Get database connection using the merged function
    $result = get_database_connection($config);
    $pdo = $result['pdo'];
    
    if (!$pdo) {
        $error = 'Database connection error.';
        logError('Database connection not available in reset-password.php', 'DATABASE_ERROR');
    }
} catch (PDOException $e) {
    $error = 'Database connection error.';
    logError('Database connection error in password reset: ' . $e->getMessage(), 'DATABASE_ERROR');
}

// Validate token
$user = null;
if ($pdo && $token) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, reset_token, reset_expires FROM user WHERE reset_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'Invalid reset token.';
        } elseif (strtotime($user['reset_expires']) < time()) {
            $error = 'Reset token has expired.';
            // Clean up expired token
            $stmt = $pdo->prepare("UPDATE user SET reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
        }
    } catch (PDOException $e) {
        $error = 'Error validating reset token.';
        error_log('Error validating reset token: ' . $e->getMessage());
    }
} elseif (!$token) {
    $error = 'No reset token provided.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && !$error) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($new_password)) {
        $error = 'New password is required.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Hash new password and update user
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE user SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $stmt->execute([$hashed_password, $user['id']]);
            
            $success = 'Password has been successfully reset! You can now log in with your new password.';
            
            // Log the password reset activity
            error_log("Password reset successful for admin user: {$user['username']} (ID: {$user['id']})");
            
        } catch (PDOException $e) {
            $error = 'Error updating password.';
            error_log('Error updating password: ' . $e->getMessage());
        }
    }
}
?>
<?php include 'templates/header.php'; ?>

    <div class="standard-container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto">
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-blue-900 mb-2">eJawatan MPHS</h1>
                <p class="text-blue-600">Reset Admin Password</p>
            </div>

            <!-- Reset Form -->
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="px-6 py-8">
                    <?php if ($error): ?>
                        <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p><?php echo htmlspecialchars($success); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <a href="login.php" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                </svg>
                                Go to Login
                            </a>
                        </div>
                    <?php elseif ($user && !$error): ?>
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Reset Password</h2>
                            <p class="text-gray-600 text-sm mb-4">
                                Resetting password for: <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                (<?php echo htmlspecialchars($user['email']); ?>)
                            </p>
                        </div>

                        <form method="POST" action="">
                            <div class="space-y-4">
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        New Password
                                    </label>
                                    <input type="password" id="new_password" name="new_password" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Enter new password (min. 8 characters)"
                                           minlength="8">
                                </div>

                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        Confirm Password
                                    </label>
                                    <input type="password" id="confirm_password" name="confirm_password" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Confirm new password">
                                </div>

                                <div class="pt-4">
                                    <button type="submit"
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                                        Reset Password
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="mt-6 text-center">
                            <p class="text-sm text-gray-600">
                                Remember your password? 
                                <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">Sign in instead</a>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="text-center">
                            <div class="mb-4">
                                <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Invalid Reset Link</h2>
                            <p class="text-gray-600 mb-6">
                                The password reset link is invalid or has expired. Please request a new reset link.
                            </p>
                            <a href="login.php" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013 3v1"></path>
                                </svg>
                                Back to Login
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-blue-100 text-sm">
                    © <?php echo date('Y'); ?> Majlis Perbandaran Hulu Selangor. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirm = this.value;
            
            if (confirm && password !== confirm) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
<?php include 'templates/footer.php'; ?>
