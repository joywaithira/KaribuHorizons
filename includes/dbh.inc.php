<?php
// Database connection using environment variables for credentials.
// Set DB_HOST, DB_NAME, DB_USER, DB_PASS in your environment or web server config.
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'karibuhorizons';
$dbUser = getenv('DB_USER') ?: 'karibuhorizons';
$dbPass = getenv('DB_PASS') ?: 'Vision2030';

$dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // Log the internal error for debugging, but do not expose details to users.
    error_log('Database connection failed: ' . $e->getMessage());
    // In CLI contexts allow exceptions to bubble; in web contexts show generic message.
    if (php_sapi_name() === 'cli') {
        throw $e;
    }
    http_response_code(500);
    echo 'Database connection error.';
    exit;
}