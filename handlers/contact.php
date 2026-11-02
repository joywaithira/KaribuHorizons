<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

// basic rate limiting per session: max 6 submissions per hour
if (!isset($_SESSION['contact_submissions'])) {
    $_SESSION['contact_submissions'] = [];
}
$now = time();
// remove older than 1 hour
$_SESSION['contact_submissions'] = array_filter($_SESSION['contact_submissions'], function($t) use ($now){ return ($now - $t) < 3600; });
if (count($_SESSION['contact_submissions']) >= 6) {
    http_response_code(429);
    echo "Too many submissions. Please try again later.";
    exit;
}

// CSRF check if available
if (isset($_POST['csrf']) && isset($_SESSION['csrf'])) {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(400);
        echo "Invalid CSRF token";
        exit;
    }
}

require_once __DIR__ . '/../includes/dbh.inc.php';

// sanitize and validate inputs
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$phoneCountryCode = trim($_POST['phone_country_code'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$email || !$phone || !$phoneCountryCode || !$message) {
    http_response_code(400);
    echo "Please fill all required fields.";
    exit;
}
if (count(preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY)) < 2) {
    http_response_code(400);
    echo "Please enter at least your first and last name.";
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "Invalid email address.";
    exit;
}

$phoneCountryDigits = ltrim($phoneCountryCode, '+');
$phoneDigits = ltrim(preg_replace('/\D+/', '', $phone), '0');
$internationalDigits = $phoneCountryDigits . $phoneDigits;
if (!preg_match('/^\+[1-9][0-9]{0,2}$/', $phoneCountryCode) || strlen($internationalDigits) < 8 || strlen($internationalDigits) > 15 || $phoneDigits === '') {
    http_response_code(400);
    echo "Enter a valid phone number and select its country calling code.";
    exit;
}

try {
    $pdo->exec("ALTER TABLE contacts ADD COLUMN IF NOT EXISTS phone VARCHAR(50) NULL");
} catch (Exception $e) {
    // ignore migration issues
}

// simple sanitization for storage
$name = substr($name, 0, 255);
$email = substr($email, 0, 255);
$phone = substr('+' . $internationalDigits, 0, 50);

try {
    $stmt = $pdo->prepare('INSERT INTO contacts (name, email, phone, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, $phone, $message]);
    // record submission time for rate limiting
    $_SESSION['contact_submissions'][] = $now;
    echo "OK";
    exit;
} catch (Exception $e) {
    error_log('Contact handler error: ' . $e->getMessage());
    http_response_code(500);
    echo "Server error";
    exit;
}
