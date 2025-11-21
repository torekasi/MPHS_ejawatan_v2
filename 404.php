<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Dijumpai - eJawatan MPHS</title>
    <link href="assets/css/tailwind.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f7f9fc;
        }
    </style>
</head>
<body class="min-h-screen">
    <?php include 'header.php'; ?>

    <main class="container mx-auto px-4 py-16">
        <div class="max-w-2xl mx-auto text-center">
            <h1 class="text-6xl font-bold text-gray-900 mb-4">404</h1>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Halaman Tidak Dijumpai</h2>
            <p class="text-gray-600 mb-8">Maaf, halaman yang anda cari tidak wujud atau telah dialihkan.</p>
            <a href="index.php" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                Kembali ke Laman Utama
            </a>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>