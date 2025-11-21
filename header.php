<?php
/**
 * @FileID: public_header_001
 * @Module: PublicHeader
 * @Author: AI Assistant
 * @LastModified: 2025-11-09T00:00:00Z
 * @SecurityTag: validated
 */
// Public Header - include dynamic logo and favicon
require_once __DIR__ . '/config.php';
$settings = isset($GLOBALS['public_settings']) ? $GLOBALS['public_settings'] : [];
if (!isset($config) || !is_array($config)) {
    $config = include __DIR__ . '/config.php';
}
?>
<?php
$bg_image = !empty($settings['background']) ? htmlspecialchars($settings['background']) : htmlspecialchars($config['background']);
?>
<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($config['favicon']); ?>">

<!-- Import Inter font -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Custom CSS (use relative paths to ensure correct loading regardless of base URL) -->
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/background.css">
<link href="assets/css/tailwind.min.css" rel="stylesheet">

<header class="bg-white shadow">
    <div class="standard-container px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col sm:flex-row justify-between items-center">
            <!-- Logo - Left aligned -->
            <div class="flex-shrink-0 mb-4 sm:mb-0">
                <a href="/" class="flex">
                    <img src="<?php
                         $logoPath = isset($settings['logo']) && $settings['logo'] ? $settings['logo'] : (isset($config['logo_url']) ? $config['logo_url'] : 'uploads/default-logo.svg');
                         echo htmlspecialchars($logoPath);
                      ?>" alt="Logo" class="h-12 w-auto" onerror="this.onerror=null;this.src='uploads/default-logo.svg';">
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex space-x-4">
                <a href="/" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Utama</a>
                <?php if (isset($config['navigation']['show_status_check']) && $config['navigation']['show_status_check']): ?>
                <a href="/semak-status.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Semak Status</a>
                <?php endif; ?>
                <a href="https://ejawatan.mphs.gov.my/doc/manual" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium" target="_blank" rel="noopener noreferrer">Manual</a>
            </nav>
        </div>
    </div>
</header>
