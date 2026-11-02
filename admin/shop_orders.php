<?php
require __DIR__ . '/_auth.php';

$orderFile = __DIR__ . '/../shop/data/orders.json';
$productFile = __DIR__ . '/../shop/data/products.json';
function loadShopOrders(string $file): array {
    $orders = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    return is_array($orders) ? array_values($orders) : [];
}
function saveShopOrders(string $file, array $orders): bool {
    $dir = dirname($file);
    $lock = fopen($dir . '/orders.lock', 'c');
    if (!$lock) return false;
    flock($lock, LOCK_EX);
    $saved = file_put_contents($file, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    flock($lock, LOCK_UN);
    fclose($lock);
    return $saved !== false;
}
function adminStatus(string $status): string {
    return ucwords(str_replace('_', ' ', $status));
}

if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$error = '';
$message = '';
$orders = loadShopOrders($orderFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $number = trim((string) ($_POST['order_number'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? ''));
        $paymentStatus = trim((string) ($_POST['payment_status'] ?? ''));
        $gatewayReference = trim((string) ($_POST['gateway_reference'] ?? ''));
        $internalNotes = trim((string) ($_POST['internal_notes'] ?? ''));
        $allowedStatuses = ['pending_payment','payment_review','confirmed','processing','ready_for_pickup','shipped','delivered','cancelled','refunded'];
        $allowedPayments = ['pending','reviewing','paid','failed','refunded'];
        if (!$number || !in_array($status, $allowedStatuses, true) || !in_array($paymentStatus, $allowedPayments, true)) {
            $error = 'Please select valid order and payment statuses.';
        } else {
            $found = false;
            foreach ($orders as $index => $order) {
                if ((string) ($order['order_number'] ?? '') === $number) {
                    $orders[$index]['status'] = $status;
                    $orders[$index]['payment_status'] = $paymentStatus;
                    $orders[$index]['gateway_reference'] = $gatewayReference ?: null;
                    $orders[$index]['internal_notes'] = $internalNotes;
                    $orders[$index]['updated_at'] = gmdate('c');
                    $found = true;
                    break;
                }
            }
            if (!$found) $error = 'Order not found.';
            elseif (!saveShopOrders($orderFile, $orders)) $error = 'Could not save the order.';
            else $message = 'Order ' . $number . ' updated.';
        }
    }
}

$query = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$filtered = array_values(array_filter($orders, function (array $order) use ($query, $statusFilter): bool {
    if ($statusFilter && (string) ($order['status'] ?? '') !== $statusFilter) return false;
    if (!$query) return true;
    $haystack = strtolower(json_encode($order));
    return str_contains($haystack, strtolower($query));
}));
usort($filtered, fn($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
$detailNumber = trim((string) ($_GET['order'] ?? ''));
$detail = null;
foreach ($orders as $order) if ((string) ($order['order_number'] ?? '') === $detailNumber) { $detail = $order; break; }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Shop Orders — Admin</title><link rel="stylesheet" href="/assets/css/site.css"></head><body>
<nav class="site-nav"><div class="container nav-inner"><div class="brand"><img class="site-logo" src="/assets/images/TransparentTravelDen-Logo.png" alt="TraveL Den Adventures"><div><h1>Admin</h1></div></div><div style="margin-left:auto"><a href="index.php">Dashboard</a> | <a href="/shop/products.php" target="_blank">View Shop</a> | <a href="logout.php">Logout</a></div></div></nav>
<main class="container" style="margin-top:20px"><p><a href="index.php">&larr; Dashboard</a></p><h2>Shop Orders</h2>
<?php if ($error): ?><div class="card" style="background:#ffecec;color:#900;padding:12px"><?= htmlspecialchars($error) ?></div><?php endif; ?><?php if ($message): ?><div class="card" style="background:#eaf6ea;color:#246;padding:12px"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<form method="get" style="flex-direction:row;align-items:end;max-width:900px"><label style="flex:1">Search<input type="search" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Order, customer, email, or product"></label><label>Status<select name="status"><option value="">All statuses</option><?php foreach(['pending_payment','payment_review','confirmed','processing','ready_for_pickup','shipped','delivered','cancelled','refunded'] as $status): ?><option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= adminStatus($status) ?></option><?php endforeach; ?></select></label><button class="primary" type="submit">Filter</button></form>
<div class="card" style="margin-top:18px;overflow-x:auto"><table style="width:100%;border-collapse:collapse"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Created</th><th></th></tr></thead><tbody><?php foreach ($filtered as $order): ?><tr><td><strong><?= htmlspecialchars((string) $order['order_number']) ?></strong></td><td><?= htmlspecialchars((string) ($order['customer']['full_name'] ?? '')) ?><br><small><?= htmlspecialchars((string) ($order['customer']['email'] ?? '')) ?></small></td><td>KSh <?= number_format((float) ($order['total'] ?? 0), 2) ?></td><td><?= adminStatus((string) ($order['payment_status'] ?? 'pending')) ?></td><td><?= adminStatus((string) ($order['status'] ?? 'pending_payment')) ?></td><td><?= htmlspecialchars(substr((string) ($order['created_at'] ?? ''), 0, 16)) ?></td><td><a href="shop_orders.php?order=<?= urlencode((string) $order['order_number']) ?>">View</a></td></tr><?php endforeach; if (!$filtered): ?><tr><td colspan="7">No orders match this view.</td></tr><?php endif; ?></tbody></table></div>
<?php if ($detail): ?><div class="card" style="margin-top:18px"><h3>Order <?= htmlspecialchars((string) $detail['order_number']) ?></h3><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px"><div><h4>Customer</h4><p><strong><?= htmlspecialchars((string) ($detail['customer']['full_name'] ?? '')) ?></strong><br><?= htmlspecialchars((string) ($detail['customer']['email'] ?? '')) ?><br><?= htmlspecialchars((string) ($detail['customer']['phone'] ?? '')) ?></p><p><strong>Delivery:</strong> <?= htmlspecialchars((string) ($detail['delivery_method'] ?? '')) ?><br><?= htmlspecialchars((string) ($detail['customer']['county'] ?? '')) ?><br><?= nl2br(htmlspecialchars((string) ($detail['customer']['address'] ?? ''))) ?></p><p><strong>Customer notes:</strong><br><?= nl2br(htmlspecialchars((string) ($detail['customer']['notes'] ?? ''))) ?></p></div><div><h4>Items</h4><ul><?php foreach (($detail['items'] ?? []) as $item): ?><li><?= (int) ($item['quantity'] ?? 0) ?> × <?= htmlspecialchars((string) ($item['product']['name'] ?? '')) ?> — KSh <?= number_format((float) ($item['line_total'] ?? 0), 2) ?></li><?php endforeach; ?></ul><p>Subtotal: KSh <?= number_format((float) ($detail['subtotal'] ?? 0), 2) ?><br>Delivery: KSh <?= number_format((float) ($detail['delivery_fee'] ?? 0), 2) ?><br><strong>Total: KSh <?= number_format((float) ($detail['total'] ?? 0), 2) ?></strong></p></div></div><hr><form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>"><input type="hidden" name="order_number" value="<?= htmlspecialchars((string) $detail['order_number']) ?>"><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px"><label>Order status<select name="status"><?php foreach(['pending_payment','payment_review','confirmed','processing','ready_for_pickup','shipped','delivered','cancelled','refunded'] as $status): ?><option value="<?= $status ?>" <?= ($detail['status'] ?? '') === $status ? 'selected' : '' ?>><?= adminStatus($status) ?></option><?php endforeach; ?></select></label><label>Payment status<select name="payment_status"><?php foreach(['pending','reviewing','paid','failed','refunded'] as $status): ?><option value="<?= $status ?>" <?= ($detail['payment_status'] ?? 'pending') === $status ? 'selected' : '' ?>><?= adminStatus($status) ?></option><?php endforeach; ?></select></label><label>Payment/gateway reference<input name="gateway_reference" value="<?= htmlspecialchars((string) ($detail['gateway_reference'] ?? '')) ?>"></label></div><label>Internal staff notes<textarea name="internal_notes" rows="4"><?= htmlspecialchars((string) ($detail['internal_notes'] ?? '')) ?></textarea></label><button class="primary" type="submit">Update Order</button></form></div><?php endif; ?></main></body></html>
