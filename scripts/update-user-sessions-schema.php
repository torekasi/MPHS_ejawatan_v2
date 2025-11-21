<?php
// Update user_sessions schema to support application-linked edit tokens
// Safe to run multiple times; only adds missing columns and indexes.

$result = require __DIR__ . '/../config.php';
$config = $result['config'] ?? $result;

$dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
$pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return (bool) $stmt->fetch();
}

function indexExists(PDO $pdo, string $table, string $index): bool {
    $stmt = $pdo->query("SHOW INDEX FROM `{$table}`");
    foreach ($stmt as $row) {
        if (($row['Key_name'] ?? '') === $index) return true;
    }
    return false;
}

try {
    // Start transaction if supported
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }
    // Add application_id column if missing
    if (!columnExists($pdo, 'user_sessions', 'application_id')) {
        $pdo->exec("ALTER TABLE `user_sessions` ADD COLUMN `application_id` INT NULL AFTER `user_id`");
    }
    // Add idx_application_id index if missing
    if (!indexExists($pdo, 'user_sessions', 'idx_application_id')) {
        $pdo->exec("ALTER TABLE `user_sessions` ADD INDEX `idx_application_id` (`application_id`)");
    }
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
    echo "Schema update complete.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Schema update failed: " . $e->getMessage() . "\n";
    exit(1);
}