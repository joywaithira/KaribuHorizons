<?php
// bin/create_admin_interactive.php
// Run: php bin/create_admin_interactive.php
require_once __DIR__ . '/../includes/dbh.inc.php';

function prompt($msg) {
    fwrite(STDOUT, $msg);
    $line = fgets(STDIN);
    return $line === false ? '' : trim($line);
}

function prompt_hidden($msg) {
    // Try to hide input on Unix-like systems
    if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
        // No reliable cross-platform hidden prompt on Windows in plain PHP
        fwrite(STDOUT, $msg);
        $line = fgets(STDIN);
        return $line === false ? '' : trim($line);
    }
    fwrite(STDOUT, $msg);
    system('stty -echo');
    $line = fgets(STDIN);
    system('stty echo');
    fwrite(STDOUT, PHP_EOL);
    return $line === false ? '' : trim($line);
}

fwrite(STDOUT, "Create admin user (interactive)\n");
$email = prompt("Email: ");
if ($email === '') { fwrite(STDERR, "Email is required.\n"); exit(1); }

$pwd1 = prompt_hidden("Password: ");
$pwd2 = prompt_hidden("Confirm password: ");
if ($pwd1 === '' || $pwd1 !== $pwd2) { fwrite(STDERR, "Passwords do not match or are empty. Aborting.\n"); exit(1); }

$hash = password_hash($pwd1, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
    $stmt->execute([$email, $hash, 'admin']);
    fwrite(STDOUT, "Admin user created: $email\n");
} catch (PDOException $e) {
    fwrite(STDERR, "Error creating admin: " . $e->getMessage() . "\n");
    exit(1);
}
