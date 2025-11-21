<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - ejawatan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php $config = include(__DIR__ . '/../../config.php'); ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($config['favicon']); ?>">
    <style>
        .dropdown-menu {
            display: none;
        }
        .dropdown-menu.show {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php
    $jawatan_pages = ['job-list.php', 'job-create.php', 'job-edit.php'];
    $is_jawatan_active = in_array(basename($_SERVER['PHP_SELF']), $jawatan_pages);
    ?>
    <!-- Header and Navigation -->
    <header class="bg-white shadow">
        <div class="container mx-auto px-4" style="max-width: 1400px;">
            <!-- Logo and Title -->
            <div class="flex items-center justify-between py-4 border-b">
                <div class="flex items-center gap-4">
                    <img src="<?php echo htmlspecialchars($config['logo_url']); ?>" alt="Logo" class="h-12 w-auto">
                </div>
                <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                <div class="relative dropdown">
                    <button class="text-blue-900 font-semibold focus:outline-none flex items-center gap-2 dropdown-toggle" type="button" id="userDropdown" data-toggle="dropdown" aria-expanded="false">
                        <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg z-20 dropdown-menu" aria-labelledby="userDropdown">
                        <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50">Profil Pengguna</a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50">Logout</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <!-- Navigation Menu -->
            <nav class="flex flex-wrap items-center justify-start py-3 text-base font-medium">
                <a href="index.php" class="px-4 py-2 rounded hover:bg-blue-50 transition <?php if(basename($_SERVER['PHP_SELF'])=='index.php') echo 'text-blue-600'; ?>">Dashboard</a>
                
                <!-- Jawatan Dropdown -->
                <div class="relative dropdown px-1">
                    <button class="px-3 py-2 rounded hover:bg-blue-50 transition flex items-center gap-1 dropdown-toggle <?php if($is_jawatan_active) echo 'text-blue-600'; ?>" type="button" id="jawatanDropdown" data-toggle="dropdown" aria-expanded="false">
                        <span>Jawatan</span>
                        <svg class="w-4 h-4 dropdown-arrow transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute left-0 mt-1 w-48 bg-white rounded shadow-lg z-20 dropdown-menu" aria-labelledby="jawatanDropdown">
                        <a href="job-list.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 <?php if(basename($_SERVER['PHP_SELF'])=='job-list.php') echo 'text-blue-600 bg-blue-50'; ?>">Senarai</a>
                        <a href="job-create.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 <?php if(basename($_SERVER['PHP_SELF'])=='job-create.php') echo 'text-blue-600 bg-blue-50'; ?>">Tambah</a>
                    </div>
                </div>
                
                <a href="applications-list.php" class="px-4 py-2 rounded hover:bg-blue-50 transition <?php if(basename($_SERVER['PHP_SELF'])=='applications-list.php') echo 'text-blue-600'; ?>">Pemohon</a>
                
                <!-- Halaman Dropdown -->
                <div class="relative dropdown px-1">
                    <button class="px-3 py-2 rounded hover:bg-blue-50 transition flex items-center gap-1 dropdown-toggle <?php if(basename($_SERVER['PHP_SELF'])=='page-content.php') echo 'text-blue-600'; ?>" type="button" id="pageDropdown" data-toggle="dropdown" aria-expanded="false">
                        <span>Halaman</span>
                        <svg class="w-4 h-4 dropdown-arrow transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute left-0 mt-1 w-48 bg-white rounded shadow-lg z-20 dropdown-menu" aria-labelledby="pageDropdown">
                        <a href="page-content.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 <?php if(basename($_SERVER['PHP_SELF'])=='page-content.php' && !isset($_GET['id'])) echo 'text-blue-600 bg-blue-50'; ?>">Cara Memohon</a>
                        <a href="page-content.php?id=1" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 <?php if(basename($_SERVER['PHP_SELF'])=='page-content.php' && isset($_GET['id']) && $_GET['id']=='1') echo 'text-blue-600 bg-blue-50'; ?>">Pengishtiharan</a>
                    </div>
                </div>
                
                <a href="users.php" class="px-4 py-2 rounded hover:bg-blue-50 transition <?php if(basename($_SERVER['PHP_SELF'])=='users.php') echo 'text-blue-600'; ?>">Pengguna</a>

                <!-- System Dropdown -->
                <div class="relative dropdown px-1">
                    <button class="px-3 py-2 rounded hover:bg-blue-50 transition flex items-center gap-1 dropdown-toggle <?php if(in_array(basename($_SERVER['PHP_SELF']), ['db-backup.php', 'email-blast.php', 'activity-logs.php'])) echo 'text-blue-600'; ?>" type="button" id="systemDropdown" data-toggle="dropdown" aria-expanded="false">
                        <span>System</span>
                        <svg class="w-4 h-4 dropdown-arrow transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute left-0 mt-1 w-48 bg-white rounded shadow-lg z-20 dropdown-menu" aria-labelledby="systemDropdown">
                        <a href="db-backup.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 <?php if(basename($_SERVER['PHP_SELF'])=='db-backup.php') echo 'text-blue-600 bg-blue-50'; ?>">DB Backup</a>
                        <a href="activity-logs.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 <?php if(basename($_SERVER['PHP_SELF'])=='activity-logs.php') echo 'text-blue-600 bg-blue-50'; ?>">Logs</a>
                        <a href="email-blast.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 <?php if(basename($_SERVER['PHP_SELF'])=='email-blast.php') echo 'text-blue-600 bg-blue-50'; ?>">Email</a>
                        <a href="status-templates.php" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 <?php if(basename($_SERVER['PHP_SELF'])=='status-templates.php') echo 'text-blue-600 bg-blue-50'; ?>">Status Email Templates</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8" style="max-width: 1400px;">

<script>
    // JavaScript to handle dropdown menus
    document.addEventListener('DOMContentLoaded', function() {
        // Get all dropdown toggle buttons
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        
        // Add click event to each dropdown toggle
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                const dropdown = this.nextElementSibling;
                
                // Close all other dropdowns first
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    if (menu !== dropdown) {
                        menu.classList.remove('show');
                    }
                });
                
                // Toggle current dropdown
                dropdown.classList.toggle('show');
                
                // Rotate arrow when dropdown is open
                const arrow = this.querySelector('.dropdown-arrow');
                if (dropdown.classList.contains('show')) {
                    arrow.classList.add('rotate-180');
                } else {
                    arrow.classList.remove('rotate-180');
                }
            });
        });
        
        // Close dropdowns when clicking elsewhere on the page
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
                
                // Reset all arrows
                document.querySelectorAll('.dropdown-arrow').forEach(arrow => {
                    arrow.classList.remove('rotate-180');
                });
            }
        });
    });
</script>
