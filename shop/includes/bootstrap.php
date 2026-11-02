<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../data/shop-errors.log');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
if (empty($_SESSION['cart'])) $_SESSION['cart'] = [];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

function shop_e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function shop_money(int|float $amount): string { return 'KSh ' . number_format((float)$amount, 2, '.', ','); }
function shop_url(string $path): string { return '/shop/' . ltrim($path, '/'); }
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . shop_e($_SESSION['csrf_token']) . '">'; }
function verify_csrf(): bool { return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token']); }
function shop_redirect(string $path): never { header('Location: ' . shop_url($path)); exit; }

$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/orders.php';
