<?php
$dsn = 'mysql:host=localhost;dbname=karibuhorizons';
$dbusername = 'karibuhorizons';
$dbpassword = 'Vision2030';

try {
    $pdo = new PDO($dsn, $dbusername, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}