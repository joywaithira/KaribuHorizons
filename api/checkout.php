<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['cart'])){
    http_response_code(400);
    echo json_encode(['error'=>'Cart is required']);
    exit;
}

$cart = $data['cart'];

try{
    // fetch product prices for items in cart
    $ids = array_map(function($i){return $i['id'];}, $cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, title, price FROM products WHERE id IN ({$placeholders})");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $prodMap = [];
    foreach($products as $p) $prodMap[$p['id']] = $p;

    $subtotal = 0.0;
    foreach($cart as $item){
        $id = $item['id']; $qty = max(1,intval($item['qty'] ?? 1));
        if (!isset($prodMap[$id])){
            http_response_code(400);
            echo json_encode(['error'=>"Product not found: {$id}"]); exit;
        }
        $price = floatval($prodMap[$id]['price']);
        $subtotal += $price * $qty;
    }

    // create order and order items in transaction
    $pdo->beginTransaction();
    $orderStmt = $pdo->prepare('INSERT INTO orders (customer_name, phone, address, total) VALUES (?, ?, ?, ?)');
    $customer = $data['customer_name'] ?? 'Guest';
    $phone = $data['phone'] ?? null;
    $address = $data['address'] ?? null;
    $orderStmt->execute([$customer, $phone, $address, $subtotal]);
    $orderId = $pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)');
    foreach($cart as $item){
        $id = $item['id']; $qty = max(1,intval($item['qty'] ?? 1));
        $price = floatval($prodMap[$id]['price']);
        $itemStmt->execute([$orderId, $id, $qty, $price]);
    }
    $pdo->commit();

    echo json_encode(['status'=>'OK','order_id'=>$orderId,'total'=>$subtotal]);
    exit;
} catch(Exception $e){
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Checkout API error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['error'=>'Server error']);
    exit;
}
