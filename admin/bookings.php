<?php
require __DIR__ . '/_auth.php';

foreach ([
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS price_per_person DECIMAL(10,2) NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS total_price DECIMAL(10,2) NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS start_date DATE NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS checkout_date DATE NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS travellers INT DEFAULT 1 NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS traveler_details TEXT NULL",
] as $migration) {
    try {
        $pdo->exec($migration);
    } catch (Exception $e) {
        // Keep the dashboard usable when a database already has these columns.
    }
}

$bookings = $pdo->query(
    'SELECT id, name, email, phone, trip_id, details, price_per_person, total_price, start_date, checkout_date, travellers, traveler_details, created_at FROM bookings ORDER BY created_at DESC'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Trip Bookings — Admin</title>
    <link rel="stylesheet" href="/assets/css/site.css">
</head>
<body>
<nav class="site-nav"><div class="container nav-inner"><div class="brand"><img class="site-logo" src="/assets/images/TransparentTravelDen-Logo.png" alt="TraveL Den Adventures"><div><h1>Admin</h1></div></div><div style="margin-left:auto"><a href="index.php">Dashboard</a> | <a href="logout.php">Logout</a></div></div></nav>
<main class="container" style="margin-top:20px">
    <p><a href="index.php">&larr; Dashboard</a></p>
    <h2>Trip Bookings</h2>
    <div class="card" style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr><th>ID</th><th>Customer</th><th>Contact</th><th>Trip</th><th>Travellers</th><th>Check-in</th><th>Check-out</th><th>Price/person</th><th>Total</th><th>Notes</th><th>Submitted</th></tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td>#<?php echo intval($booking['id']) ?></td>
                        <td><?php echo htmlspecialchars($booking['name'] ?? '') ?></td>
                        <td><?php echo htmlspecialchars($booking['email'] ?? '') ?><br><?php echo htmlspecialchars($booking['phone'] ?? '') ?></td>
                        <td><?php echo htmlspecialchars($booking['trip_id'] ?? '') ?></td>
                        <td>
                            <?php
                            $travelerDetails = json_decode($booking['traveler_details'] ?? '', true);
                            echo intval($booking['travellers'] ?? 1);
                            if (is_array($travelerDetails)) {
                                foreach ($travelerDetails as $traveler) {
                                    $label = ucfirst($traveler['type'] ?? '');
                                    if (($traveler['type'] ?? '') === 'child' && isset($traveler['age'])) $label .= ' (age ' . intval($traveler['age']) . ')';
                                    echo '<br>' . htmlspecialchars($label);
                                }
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($booking['start_date'] ?? '') ?></td>
                        <td><?php echo htmlspecialchars($booking['checkout_date'] ?? '') ?></td>
                        <td><?php echo htmlspecialchars($booking['price_per_person'] ?? '') ?></td>
                        <td><?php echo htmlspecialchars($booking['total_price'] ?? '') ?></td>
                        <td><?php echo nl2br(htmlspecialchars($booking['details'] ?? '')) ?></td>
                        <td><?php echo htmlspecialchars($booking['created_at'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$bookings): ?><tr><td colspan="11">No trip bookings yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
