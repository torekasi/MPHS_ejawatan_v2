<?php
require_once '../includes/bootstrap.php';

// Get database connection
$config = require '../config.php';
$result = get_database_connection($config);
$pdo = $result['pdo'];

if (!$pdo) {
    die("Database connection failed: " . ($result['error_details'] ?? 'Unknown error'));
}

try {
    // Test each query individually and output results
    echo "<h2>Application Statistics Test</h2>";
    
    // Total applications
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM job_applications");
    echo "<p>Total Applications Query: " . ($total_stmt ? 'Success' : 'Failed') . "</p>";
    if ($total_stmt) {
        $total = $total_stmt->fetchColumn();
        echo "<p>Total Applications: $total</p>";
    }
    
    // Pending applications
    $pending_stmt = $pdo->query("SELECT COUNT(*) FROM job_applications WHERE status = 'PENDING'");
    echo "<p>Pending Applications Query: " . ($pending_stmt ? 'Success' : 'Failed') . "</p>";
    if ($pending_stmt) {
        $pending = $pending_stmt->fetchColumn();
        echo "<p>Pending Applications: $pending</p>";
    }
    
    // Approved applications
    $approved_stmt = $pdo->query("SELECT COUNT(*) FROM job_applications WHERE status = 'APPROVED'");
    echo "<p>Approved Applications Query: " . ($approved_stmt ? 'Success' : 'Failed') . "</p>";
    if ($approved_stmt) {
        $approved = $approved_stmt->fetchColumn();
        echo "<p>Approved Applications: $approved</p>";
    }
    
    // Rejected applications
    $rejected_stmt = $pdo->query("SELECT COUNT(*) FROM job_applications WHERE status = 'REJECTED'");
    echo "<p>Rejected Applications Query: " . ($rejected_stmt ? 'Success' : 'Failed') . "</p>";
    if ($rejected_stmt) {
        $rejected = $rejected_stmt->fetchColumn();
        echo "<p>Rejected Applications: $rejected</p>";
    }
    
    // Show sample of actual data
    echo "<h3>Sample Application Data:</h3>";
    $sample_stmt = $pdo->query("SELECT id, status FROM job_applications LIMIT 5");
    if ($sample_stmt) {
        $samples = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($samples, true) . "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>