<?php
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get current page for highlighting active menu item
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['PHP_SELF'];
?>

<aside class="w-64 bg-gray-800 text-white min-h-screen flex-shrink-0">
    <div class="px-4 py-5 border-b border-gray-700">
        <div class="flex items-center">
            <img src="assets/favicon.jpeg" alt="MPHS Logo" class="h-8 w-8 mr-3">
            <div>
                <h2 class="text-lg font-semibold">Admin Panel</h2>
                <p class="text-xs text-gray-400">eJawatan MPHS</p>
            </div>
        </div>
    </div>
    
    <nav class="mt-5">
        <div class="px-4 py-2 text-xs text-gray-400 uppercase tracking-wider">
            Main
        </div>
        <a href="dashboard.php" class="flex items-center px-4 py-3 <?php echo $current_page === 'dashboard.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span>Dashboard</span>
        </a>
        
        <div class="px-4 py-2 mt-5 text-xs text-gray-400 uppercase tracking-wider">
            Job Management
        </div>
        <a href="job-list.php" class="flex items-center px-4 py-3 <?php echo $current_page === 'job-list.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <span>Job Listings</span>
        </a>
        <a href="job-create.php" class="flex items-center px-4 py-3 <?php echo $current_page === 'job-create.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span>Add New Job</span>
        </a>
        
        <div class="px-4 py-2 mt-5 text-xs text-gray-400 uppercase tracking-wider">
            Applications
        </div>
        <a href="application-list.php" class="flex items-center px-4 py-3 <?php echo $current_page === 'application-list.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span>All Applications</span>
        </a>
        <a href="application-pending.php" class="flex items-center px-4 py-3 <?php echo $current_page === 'application-pending.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Pending Applications</span>
        </a>
        <a href="application-unlock.php" class="flex items-center px-4 py-3 <?php echo $current_page === 'application-unlock.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm8-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <span>Unlock Applications</span>
        </a>
        
        <div class="px-4 py-2 mt-5 text-xs text-gray-400 uppercase tracking-wider">
            Communication
        </div>
        <a href="notifications.php" class="flex items-center px-4 py-3 <?php echo $current_page === 'notifications.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <span>Notifications</span>
        </a>
        
        <div class="px-4 py-2 mt-5 text-xs text-gray-400 uppercase tracking-wider">
            System
        </div>
        <a href="settings.php" class="flex items-center px-4 py-3 <?php echo $current_page === 'settings.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span>Settings</span>
        </a>
        <a href="db-backup.php" class="flex items-center px-4 py-3 <?php echo $current_page === 'db-backup.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2v-3m-1-9l-6 6m0 0v3m0-3h3m3-9a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <span>DB Backup</span>
        </a>
        <a href="activity-logs.php" class="flex items-center px-4 py-3 <?php echo $current_page === 'activity-logs.php' || $current_page === 'logs.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span>Logs</span>
        </a>
        <a href="email-blast.php" class="flex items-center px-4 py-3 <?php echo $current_page === 'email-blast.php' ? 'bg-gray-700' : 'hover:bg-gray-700'; ?> transition-colors duration-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <span>Email</span>
        </a>
        
        <div class="mt-8 px-4 pb-8">
            <a href="logout.php" class="flex items-center px-4 py-3 bg-red-600 hover:bg-red-700 rounded-md transition-colors duration-200">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</aside>
