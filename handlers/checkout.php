<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

// simple CSRF check
if (!isset($_POST['csrf']) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    http_response_code(400);
    echo "Invalid CSRF token";
    exit;
}

require_once __DIR__ . '/../includes/dbh.inc.php';

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$cartJson = $_POST['cart'] ?? '[]';
$cart = json_decode($cartJson, true);

if (!$name || !is_array($cart) || count($cart) === 0) {
    // invalid input
    header('Location: /checkout.php?success=0');
    exit;
}

try {
    $pdo->beginTransaction();

    // compute total by looking up product prices in DB (recommended)
    $total = 0.0;
    $stmtP = $pdo->prepare('SELECT id, price FROM products WHERE id = ?');

    foreach ($cart as $item) {
        $stmtP->execute([$item['id']]);
        $p = $stmtP->fetch(PDO::FETCH_ASSOC);
        if ($p) {
            $total += (float)$p['price'] * (int)$item['qty'];
        }
    }

    $stmt = $pdo->prepare('INSERT INTO orders (customer_name, phone, address, total) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $phone, $address, $total]);
    $orderId = $pdo->lastInsertId();

    $stmtItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)');
    foreach ($cart as $item) {
        $stmtP->execute([$item['id']]);
        $p = $stmtP->fetch(PDO::FETCH_ASSOC);
        $price = $p ? (float)$p['price'] : 0.0;
        $stmtItem->execute([$orderId, $item['id'], (int)$item['qty'], $price]);
    }

    $pdo->commit();

    // redirect back with success
    header('Location: /checkout.php?success=1');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Checkout error: '.$e->getMessage());
    header('Location: /checkout.php?success=0');
    exit;
}

