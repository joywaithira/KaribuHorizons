<?php
// bin/seed_products.php
// Run: php bin/seed_products.php
// Loads assets/data/products.json into the `products` table.

require_once __DIR__ . '/../includes/dbh.inc.php';

$jsonPath = __DIR__ . '/../assets/data/products.json';
if (!file_exists($jsonPath)) {
    fwrite(STDERR, "products.json not found at: $jsonPath\n");
    exit(1);
}

$data = json_decode(file_get_contents($jsonPath), true);
if (!is_array($data)) {
    fwrite(STDERR, "Failed to parse products.json\n");
    exit(1);
}

$sql = 'INSERT INTO products (id, title, price, image, description) VALUES (?, ?, ?, ?, ?)'
     . ' ON DUPLICATE KEY UPDATE title = VALUES(title), price = VALUES(price), image = VALUES(image), description = VALUES(description)';
$stmt = $pdo->prepare($sql);

$count = 0;
try {
    foreach ($data as $p) {
        $id = $p['id'] ?? null;
        if (!$id) continue;
        $title = $p['title'] ?? '';
        $price = isset($p['price']) ? (float)$p['price'] : 0.0;
        $image = $p['image'] ?? null;
        $description = $p['description'] ?? null;
        $stmt->execute([$id, $title, $price, $image, $description]);
        $count++;
    }
    echo "Seeded products: $count\n";
} catch (Exception $e) {
    fwrite(STDERR, "Error seeding products: " . $e->getMessage() . "\n");
    exit(1);
}
