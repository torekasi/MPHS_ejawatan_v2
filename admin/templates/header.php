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
        :root { --admin-max-width: 1024px; }
        .standard-container { max-width: var(--admin-max-width); padding-left: 1rem; padding-right: 1rem; }
        .admin-shell { max-width: var(--admin-max-width); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php
    $jawatan_pages = ['job-list.php', 'job-create.php', 'job-edit.php'];
    $is_jawatan_active = in_array(basename($_SERVER['PHP_SELF']), $jawatan_pages);
    ?>
    <!-- Header and Navigation -->
    <header class="bg-white shadow">
        <div class="container mx-auto px-4 admin-shell">
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
            <nav class="flex flex-wrap items-center justify-center py-3 text-sm font-medium gap-1">
                <a href="index.php" class="px-4 py-2.5 rounded-lg hover:bg-blue-50 transition-all duration-200 <?php if(basename($_SERVER['PHP_SELF'])=='index.php') echo 'bg-blue-100 text-blue-700 font-semibold shadow-sm'; else echo 'text-gray-700'; ?>">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span>Dashboard</span>
                    </div>
                </a>
                
                <!-- Jawatan Dropdown -->
                <div class="relative dropdown">
                    <button class="px-4 py-2.5 rounded-lg hover:bg-blue-50 transition-all duration-200 flex items-center gap-2 dropdown-toggle <?php if($is_jawatan_active) echo 'bg-blue-100 text-blue-700 font-semibold shadow-sm'; else echo 'text-gray-700'; ?>" type="button" id="jawatanDropdown" data-toggle="dropdown" aria-expanded="false">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>Jawatan</span>
                        <svg class="w-3.5 h-3.5 dropdown-arrow transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute left-0 mt-1 w-56 bg-white rounded-lg shadow-xl border border-gray-100 z-20 dropdown-menu overflow-hidden" aria-labelledby="jawatanDropdown">
                        <a href="job-list.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 transition-colors duration-150 <?php if(basename($_SERVER['PHP_SELF'])=='job-list.php') echo 'text-blue-700 bg-blue-50 font-medium'; ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                            </svg>
                            <span>Senarai Jawatan</span>
                        </a>
                        <a href="job-create.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 transition-colors duration-150 <?php if(basename($_SERVER['PHP_SELF'])=='job-create.php') echo 'text-blue-700 bg-blue-50 font-medium'; ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Tambah Jawatan</span>
                        </a>
                    </div>
                </div>
                
                <a href="applications-list.php" class="px-4 py-2.5 rounded-lg hover:bg-blue-50 transition-all duration-200 <?php if(basename($_SERVER['PHP_SELF'])=='applications-list.php') echo 'bg-blue-100 text-blue-700 font-semibold shadow-sm'; else echo 'text-gray-700'; ?>">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span>Pemohon</span>
                    </div>
                </a>
                
                <a href="users.php" class="px-4 py-2.5 rounded-lg hover:bg-blue-50 transition-all duration-200 <?php if(basename($_SERVER['PHP_SELF'])=='users.php') echo 'bg-blue-100 text-blue-700 font-semibold shadow-sm'; else echo 'text-gray-700'; ?>">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span>Pengguna</span>
                    </div>
                </a>

                <!-- System Dropdown (includes Halaman) -->
                <div class="relative dropdown">
                    <button class="px-4 py-2.5 rounded-lg hover:bg-blue-50 transition-all duration-200 flex items-center gap-2 dropdown-toggle <?php if(in_array(basename($_SERVER['PHP_SELF']), ['db-backup.php', 'email-blast.php', 'activity-logs.php', 'status-templates.php', 'page-content.php'])) echo 'bg-blue-100 text-blue-700 font-semibold shadow-sm'; else echo 'text-gray-700'; ?>" type="button" id="systemDropdown" data-toggle="dropdown" aria-expanded="false">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>System</span>
                        <svg class="w-3.5 h-3.5 dropdown-arrow transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute left-0 mt-1 w-64 bg-white rounded-lg shadow-xl border border-gray-100 z-20 dropdown-menu overflow-hidden" aria-labelledby="systemDropdown">
                        <!-- Halaman Section -->
                        <button type="button" class="w-full px-3 py-2 bg-gray-50 border-b border-gray-100 flex items-center justify-between group submenu-toggle hover:bg-gray-100 transition-colors" data-target="submenu-halaman">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Halaman</span>
                            <svg class="w-3 h-3 text-gray-400 transform transition-transform duration-200 submenu-arrow rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="submenu-halaman" class="submenu-content bg-white">
                            <a href="page-content.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 transition-colors duration-150 <?php if(basename($_SERVER['PHP_SELF'])=='page-content.php' && !isset($_GET['id'])) echo 'text-blue-700 bg-blue-50 font-medium'; ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Cara Memohon</span>
                            </a>
                            <a href="page-content.php?id=1" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 transition-colors duration-150 <?php if(basename($_SERVER['PHP_SELF'])=='page-content.php' && isset($_GET['id']) && $_GET['id']=='1') echo 'text-blue-700 bg-blue-50 font-medium'; ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Pengishtiharan</span>
                            </a>
                        </div>
                        
                        <!-- System Tools Section -->
                        <button type="button" class="w-full px-3 py-2 bg-gray-50 border-b border-t border-gray-100 flex items-center justify-between group submenu-toggle hover:bg-gray-100 transition-colors" data-target="submenu-system">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">System Tools</span>
                            <svg class="w-3 h-3 text-gray-400 transform transition-transform duration-200 submenu-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="submenu-system" class="submenu-content hidden bg-white">
                            <a href="db-backup.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 transition-colors duration-150 <?php if(basename($_SERVER['PHP_SELF'])=='db-backup.php') echo 'text-blue-700 bg-blue-50 font-medium'; ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                                </svg>
                                <span>Database Backup</span>
                            </a>
                            <a href="activity-logs.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 transition-colors duration-150 <?php if(basename($_SERVER['PHP_SELF'])=='activity-logs.php') echo 'text-blue-700 bg-blue-50 font-medium'; ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Activity Logs</span>
                            </a>
                            <a href="email-blast.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 transition-colors duration-150 <?php if(basename($_SERVER['PHP_SELF'])=='email-blast.php') echo 'text-blue-700 bg-blue-50 font-medium'; ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span>Email Blast</span>
                            </a>
                            <a href="status-templates.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-blue-50 transition-colors duration-150 <?php if(basename($_SERVER['PHP_SELF'])=='status-templates.php') echo 'text-blue-700 bg-blue-50 font-medium'; ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                <span>Status Email Templates</span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 admin-shell">

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
        
        // Handle submenu toggles (Accordion Style)
        const submenuToggles = document.querySelectorAll('.submenu-toggle');
        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent closing the main dropdown
                const targetId = this.getAttribute('data-target');
                const targetContent = document.getElementById(targetId);
                const thisArrow = this.querySelector('.submenu-arrow');
                
                // Close other submenus
                submenuToggles.forEach(otherToggle => {
                    if (otherToggle !== toggle) {
                        const otherTargetId = otherToggle.getAttribute('data-target');
                        const otherContent = document.getElementById(otherTargetId);
                        const otherArrow = otherToggle.querySelector('.submenu-arrow');
                        
                        if (otherContent) {
                            otherContent.classList.add('hidden');
                            otherArrow.classList.remove('rotate-180');
                        }
                    }
                });
                
                // Toggle current submenu
                targetContent.classList.toggle('hidden');
                
                // Rotate arrow
                if (!targetContent.classList.contains('hidden')) {
                    thisArrow.classList.add('rotate-180');
                } else {
                    thisArrow.classList.remove('rotate-180');
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
