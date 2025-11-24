<?php
require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['dbname']}", $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = ['job_postings', 'application_license', 'applications'];

    foreach ($tables as $table) {
        echo "Schema for table: $table\n";
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "{$col['Field']} - {$col['Type']}\n";
        }
        echo "\n-----------------------------------\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
