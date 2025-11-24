<?php
/**
 * MPHS Job Application System Configuration
 * 
 * This file contains all system-wide configuration settings for the MPHS Job Application System.
 * All paths and URLs should be configured here for centralized management.
 */

// Database Configuration
$config['db_host'] = 'db';
$config['db_port'] = 3306; // Default MySQL port
$config['db_name'] = 'ejawatan_db';
$config['db_user'] = 'ejawatan_user';
$config['db_pass'] = 'SecurePass123!';

// Fallback for local development when not running inside Docker
$envDbHost = getenv('DB_HOST');
if (!empty($envDbHost)) {
    $config['db_host'] = $envDbHost;
} else {
    // If using PHP built-in server or 'db' cannot be resolved, use localhost
    if (PHP_SAPI === 'cli-server' || gethostbyname($config['db_host']) === $config['db_host']) {
        $config['db_host'] = '127.0.0.1';
    }
}

// Application Settings
$config['app_name'] = 'eJawatan MPHS';
$config['app_version'] = '2.0.0';
$config['app_env'] = 'development'; // development, staging, production
$config['app_secret'] = 'mphs_ejawatan_secret_key_2025_secure_token_generation'; // For secure token generation
$config['base_url'] = 'http://localhost:8000'; // Base URL for email links


// Logo and Branding Configuration
$config['logo_url'] = '/assets/images/logo-ejawatan-mphs-500x82.png';
$config['favicon'] = '/assets/images/favicon.jpeg';
$config['background'] = '/assets/images/MPHS-bg.jpg';

// File Upload Configuration
$config['upload_dir'] = __DIR__ . '/uploads/applications/';
$config['max_upload_size'] = 10 * 1024 * 1024; // 10MB
$config['allowed_file_types'] = ['pdf', 'jpg', 'jpeg', 'png'];

// Email Configuration
$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_port'] = 587;
$config['smtp_secure'] = 'tls';  // 'tls' or 'ssl' or 'none'
$config['smtp_auth'] = true;
$config['smtp_username'] = 'jkp@mphs.gov.my';
$config['smtp_password'] = 'mphs1234';
$config['admin_email'] = 'jkp@mphs.gov.my';
$config['noreply_email'] = 'jkp@mphs.gov.my';


// Navigation Menu Settings
$config['navigation'] = [
    'show_status_check' => true, // Set to true to show "Semak Status" menu, false to hide
];

// email notification Settings
$config['status_email_enabled'] = true;


$config['recaptcha_v2_site_key'] = getenv('RECAPTCHA_V2_SITE_KEY') ?: '6Le4QhEsAAAAAJVZfZNJUVetkwZsb9-ByGxQPP2T';
$config['recaptcha_v2_secret_key'] = getenv('RECAPTCHA_V2_SECRET_KEY') ?: '6Le4QhEsAAAAAGFmIuAWJppCkRyJRhn8QqjlMSS9';
$config['recaptcha_v3_site_key'] = getenv('RECAPTCHA_V3_SITE_KEY') ?: '6LfQChMsAAAAAE2n9F5ELBLzg0yKecgeBGILK0b_';
$config['recaptcha_v3_secret_key'] = getenv('RECAPTCHA_V3_SECRET_KEY') ?: '6LfQChMsAAAAAF0CPukV-1pI-SnA-2bXvFD1TaUp';
$config['recaptcha_v3_action'] = 'job_application';


// Payment Gateway Configuration
$config['payment'] = [
    'enabled' => false, 
    'amount' => 30.00, // Application fee in RM
    'gateway' => 'toyyibpay',
    'bill_description' => 'Yuran Pemprosesan Permohonan Jawatan MPHS',
    'callback_url' => $config['base_url'] . '/payment-callback.php',
    'return_url' => $config['base_url'] . '/payment-thank-you.php'
];

// Environment-based ToyyibPay Configuration
if ($config['app_env'] === 'production') {
    // Production ToyyibPay Settings
    $config['payment'] += [
        'user_secret_key' => 'your-production-secret-key',
        'category_code' => 'your-production-category-code',
        'api_url' => 'https://toyyibpay.com/index.php/api/',
        'payment_url' => 'https://toyyibpay.com/'
    ];
} else {
    // Development/Testing ToyyibPay Settings
    $config['payment'] += [
        'user_secret_key' => 'hhlcy4ol-f88w-r5mi-iyvc-clz2ans0f0mg',
        'category_code' => 'bg36i0a4',
        'api_url' => 'https://dev.toyyibpay.com/index.php/api/',
        'payment_url' => 'https://dev.toyyibpay.com/'
    ];
}


// Security Settings
$config['session_timeout'] = 3600; // 1 hour
$config['max_login_attempts'] = 5;
$config['password_min_length'] = 8;
$config['csrf_protection'] = true;

// Email Configuration - Use SMTP settings from above
$config['email_from'] = $config['noreply_email'];
$config['email_reply_to'] = $config['admin_email'];
$config['email_from_name'] = 'eJawatan MPHS';

// Map SMTP configuration for MailSender compatibility
$config['mail_host'] = $config['smtp_host'];
$config['mail_port'] = $config['smtp_port'];
$config['mail_username'] = $config['smtp_username'];
$config['mail_password'] = $config['smtp_password'];
$config['mail_encryption'] = $config['smtp_secure'];

// Logging Configuration
$config['log_dir'] = __DIR__ . '/logs/';
$config['log_level'] = 'WARNING'; // Only log warnings and errors (no DEBUG/INFO)
$config['log_rotate'] = true;
$config['log_max_size'] = 10 * 1024 * 1024; // 10MB

// Application URLs
$config['base_url'] = 'http://localhost:8000';
$config['admin_url'] = $config['base_url'] . '/admin';
$config['login_url'] = $config['admin_url'] . '/login.php';



// Date and Time Settings
$config['timezone'] = 'Asia/Kuala_Lumpur';
$config['date_format'] = 'd/m/Y';
$config['datetime_format'] = 'd/m/Y H:i:s';

// Job Application Settings
$config['application_statuses'] = [
    'pending' => 'Calon Simpanan',
    'reviewing' => 'Dalam Semakan',
    'shortlisted' => 'Di Senarai Pendek',
    'rejected' => 'Ditolak',
    'hired' => 'Diterima'
];

// Default Settings (can be overridden by admin)
$GLOBALS['public_settings'] = [
    'logo' => $config['logo_url'],
    'background' => $config['background'],
    'favicon' => $config['favicon'],
    'site_title' => $config['app_name']
];

// Set timezone
date_default_timezone_set($config['timezone']);

// Polyfill for mb_strtoupper if mbstring extension is missing
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($string, $encoding = null) {
        return strtoupper($string);
    }
}

// Error reporting based on environment
if ($config['app_env'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING);
    ini_set('display_errors', 0);
}

// Make config available globally
$GLOBALS['config'] = $config;

return $config;
?>