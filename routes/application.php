<?php
/**
 * @FileID: route_application_001
 * @Module: ApplicationRoutes
 * @Author: Nefi
 * @LastModified: 2025-11-10T00:00:00Z
 * @SecurityTag: validated
 */

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline'; font-src 'self' https://fonts.gstatic.com; connect-src 'self';");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// Basic router for application-related actions
try {
    require_once __DIR__ . '/../controllers/ApplicationController.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Ralat memuatkan pengawal aplikasi.';
    exit();
}

// Allow only POST for saving
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

$action = $_POST['action'] ?? 'save_full';
switch ($action) {
    case 'save_full':
        ApplicationController::handleSaveFull();
        break;
    case 'check_nric':
        ApplicationController::checkNricDuplicate();
        break;
    default:
        http_response_code(400);
        echo 'Tindakan tidak disokong.';
        exit();
}

?>