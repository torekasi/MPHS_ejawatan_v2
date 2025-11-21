<?php
// PHPMailer Autoloader
// Downloaded from https://github.com/PHPMailer/PHPMailer
// Place the PHPMailer src files in admin/PHPMailer/
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
}
