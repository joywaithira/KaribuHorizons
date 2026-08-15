<?php
// admin/_auth.php - require admin session
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /admin/login.php');
    exit;
}
require_once __DIR__ . '/../includes/dbh.inc.php';

// optional: fetch user info
$stmt = $pdo->prepare('SELECT id, email, role FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();
if (!$currentUser) {
    session_unset(); session_destroy();
    header('Location: /admin/login.php'); exit;
}
