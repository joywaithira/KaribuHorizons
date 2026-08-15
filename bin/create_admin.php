<?php
// bin/create_admin.php
// Usage: php bin/create_admin.php email@example.com YourPassword
require_once __DIR__ . '/../includes/dbh.inc.php';

if ($argc < 3) {
    echo "Usage: php bin/create_admin.php email@example.com password\n";
    exit(1);
}
$email = $argv[1];
$pass = $argv[2];
$hash = password_hash($pass, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
try {
    $stmt->execute([$email, $hash, 'admin']);
    echo "Admin user created: $email\n";
} catch (Exception $e) {
    echo "Error creating admin: " . $e->getMessage() . "\n";
    exit(1);
}
