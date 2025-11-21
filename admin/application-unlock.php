<?php
// Admin page to unlock applications that have expired tokens
require_once '../includes/AdminAuth.php';
require_once '../config.php';

// Check admin authentication
$adminAuth = new AdminAuth($pdo);
if (!$adminAuth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_application_id'])) {
    $application_id = (int)$_POST['unlock_application_id'];

    try {
        // Check if application exists and is locked - check new table first
        $stmt = $pdo->prepare('SELECT id, nama_penuh, application_reference, submission_locked, token_expiry FROM application_application_main WHERE id = ?');
        $stmt->execute([$application_id]);
        $application = $stmt->fetch();

        // If not found in new table, try old table
        if (!$application) {
            $stmt = $pdo->prepare('SELECT id, nama_penuh, application_reference, submission_locked, NULL as token_expiry FROM job_applications WHERE id = ?');
            $stmt->execute([$application_id]);
            $application = $stmt->fetch();
        }

        if (!$application) {
            $error = 'Permohonan tidak dijumpai.';
        } elseif ($application['submission_locked'] == 0) {
            $error = 'Permohonan ini tidak dikunci.';
        } else {
            // Unlock the application and extend token expiry by another 12 hours
            $new_expiry = date('Y-m-d H:i:s', time() + (12 * 3600));

            // Try to update new table first, then old table
            $updated = false;
            try {
                $stmt = $pdo->prepare('UPDATE application_application_main SET submission_locked = 0, token_expiry = ? WHERE id = ?');
                $stmt->execute([$new_expiry, $application_id]);
                $updated = true;
            } catch (Exception $e) {
                // If new table update fails, try old table
                try {
                    $stmt = $pdo->prepare('UPDATE job_applications SET submission_locked = 0 WHERE id = ?');
                    $stmt->execute([$application_id]);
                    $updated = true;
                } catch (Exception $e2) {
                    // Both updates failed
                }
            }

            if ($updated) {
                $message = 'Permohonan telah berjaya dibuka kunci. Token edit baharu sah selama 12 jam lagi.';

                // Log the admin action
                log_admin_action('unlock_application', $application_id, 'Application unlocked by admin', [
                    'application_reference' => $application['application_reference'],
                    'nama_penuh' => $application['nama_penuh']
                ]);
            } else {
                $error = 'Gagal membuka kunci permohonan.';
            }
        }
    } catch (Exception $e) {
        $error = 'Ralat membuka kunci permohonan: ' . $e->getMessage();
    }
}

// Get locked applications from both tables
try {
    // Get from new table
    $stmt = $pdo->prepare('
        SELECT id, nama_penuh, application_reference, token_expiry, created_at, "new" as table_source
        FROM application_application_main
        WHERE submission_locked = 1
        ORDER BY created_at DESC
        LIMIT 50
    ');
    $stmt->execute();
    $locked_new = $stmt->fetchAll();

    // Get from old table
    $stmt = $pdo->prepare('
        SELECT id, nama_penuh, application_reference, NULL as token_expiry, application_date as created_at, "old" as table_source
        FROM job_applications
        WHERE submission_locked = 1
        ORDER BY application_date DESC
        LIMIT 50
    ');
    $stmt->execute();
    $locked_old = $stmt->fetchAll();

    // Combine and sort by date
    $locked_applications = array_merge($locked_new, $locked_old);
    usort($locked_applications, function($a, $b) {
        $dateA = strtotime($a['created_at']);
        $dateB = strtotime($b['created_at']);
        return $dateB - $dateA;
    });

    // Limit to 50 total
    $locked_applications = array_slice($locked_applications, 0, 50);
} catch (Exception $e) {
    $error = 'Ralat mengambil senarai permohonan dikunci: ' . $e->getMessage();
    $locked_applications = [];
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buka Kunci Permohonan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Buka Kunci Permohonan</h1>
                        <p class="text-gray-600 mt-1">Urus permohonan yang telah dikunci kerana token tamat tempoh</p>
                    </div>
                    <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                        ← Kembali ke Dashboard
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Locked Applications List -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold mb-4">Senarai Permohonan Dikunci</h2>

                <?php if (empty($locked_applications)): ?>
                    <p class="text-gray-500 text-center py-8">Tiada permohonan yang dikunci pada masa ini.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pemohon</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rujukan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token Tamat</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dicipta</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($locked_applications as $app): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($app['id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($app['nama_penuh']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($app['application_reference']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $app['token_expiry'] ? date('d/m/Y H:i', strtotime($app['token_expiry'])) : 'N/A'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('d/m/Y H:i', strtotime($app['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="unlock_application_id" value="<?php echo htmlspecialchars($app['id']); ?>">
                                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm"
                                                        onclick="return confirm('Adakah anda pasti ingin membuka kunci permohonan ini? Token edit akan dilanjutkan selama 12 jam lagi.')">
                                                    Buka Kunci
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
