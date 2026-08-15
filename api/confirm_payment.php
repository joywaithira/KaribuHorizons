<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);
    exit;
}

$orderId = intval($_POST['order_id'] ?? 0);
$method = $_POST['method'] ?? 'card';

if (!$orderId) { http_response_code(400); echo json_encode(['error'=>'order_id required']); exit; }

// ensure orders table has paid columns
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS paid TINYINT(1) DEFAULT 0 NULL, ADD COLUMN IF NOT EXISTS paid_at DATETIME NULL, ADD COLUMN IF NOT EXISTS payment_method VARCHAR(100) NULL");
} catch(Exception $e){
    try{ $pdo->exec("ALTER TABLE orders ADD COLUMN paid TINYINT(1) DEFAULT 0 NULL"); } catch(Exception$e){}
    try{ $pdo->exec("ALTER TABLE orders ADD COLUMN paid_at DATETIME NULL"); } catch(Exception$e){}
    try{ $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(100) NULL"); } catch(Exception$e){}
}

try{
    // mark order as paid and return receipt
    $upd = $pdo->prepare('UPDATE orders SET paid=1, paid_at=NOW(), payment_method=? WHERE id = ?');
    $upd->execute([$method, $orderId]);

    // fetch order and items
    $o = $pdo->prepare('SELECT * FROM orders WHERE id = ?'); $o->execute([$orderId]); $order = $o->fetch();
    $it = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?'); $it->execute([$orderId]); $items = $it->fetchAll();

    echo json_encode(['status'=>'OK','order'=>$order,'items'=>$items]);
    exit;
} catch(Exception $e){
    error_log('Confirm payment error: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['error'=>'Server error']);
    exit;
}
