<?php
// Simple DB connectivity test — run from CLI: php bin/test_db.php
require __DIR__ . '/../includes/dbh.inc.php';

try {
    // run a lightweight query
    $stmt = $pdo->query('SELECT VERSION() as v');
    $version = $stmt->fetchColumn();
    echo "OK: connected to MySQL, version: {$version}\n";
} catch (Throwable $e) {
    // Print the exception message to help debugging (safe in local/dev).
    echo "Connection test failed: " . $e->getMessage() . "\n";
    // Also echo DSN-related env state (non-sensitive) to help diagnose host/name issues.
    echo "DB host: " . (getenv('DB_HOST') ?: 'localhost') . "\n";
    echo "DB name: " . (getenv('DB_NAME') ?: 'karibuhorizons') . "\n";
    exit(1);
}
