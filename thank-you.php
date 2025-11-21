<?php
session_start();

// Get application reference from session
$application_reference = $_SESSION['application_reference'] ?? 'Tidak diketahui';

// Clear the session variable to prevent reuse
unset($_SESSION['application_reference']);

// Include header with favicon
require_once 'header.php';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permohonan Berjaya Dihantar</title>
    <link href="assets/css/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8 text-center">
            <svg class="w-16 h-16 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Permohonan Anda Telah Berjaya Dihantar!</h1>
            <p class="text-gray-600 mb-6">Terima kasih kerana memohon. Permohonan anda sedang diproses.</p>
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-500">Nombor Rujukan Permohonan Anda:</p>
                <p class="text-2xl font-semibold text-gray-900"><?php echo htmlspecialchars($application_reference); ?></p>
            </div>
            <p class="text-sm text-gray-600 mb-8">Sila simpan nombor rujukan ini untuk semakan status permohonan anda di masa hadapan.</p>
            <a href="index.php" class="inline-block bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors duration-300">Kembali ke Laman Utama</a>
        </div>
    </div>
</body>
</html>