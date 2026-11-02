<?php
require_once __DIR__ . '/../includes/dbh.inc.php';
header('Content-Type: application/json; charset=utf-8');
$category = $_GET['category'] ?? '';
$allowed = ['safari', 'kenyan', 'international', 'package'];
if (!in_array($category, $allowed, true)) { http_response_code(400); echo json_encode(['error' => 'Invalid trip category.']); exit; }
try {
    foreach (["ALTER TABLE trips ADD COLUMN IF NOT EXISTS category VARCHAR(32) NOT NULL DEFAULT 'kenyan'", "ALTER TABLE trips ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL", "ALTER TABLE trips ADD COLUMN IF NOT EXISTS detail_link VARCHAR(255) NULL"] as $migration) { try { $pdo->exec($migration); } catch (Exception $e) {} }
    $known = [
        ['masai-mara','Masai Mara Reserve — Classic Safari','safari',1250.00,'assets/images/safaritrips-images/safari1.jpeg','tripspages/trip_masai-mara.html'],
        ['amboseli','Amboseli National Park — Elephant & Mountain Views','safari',890.00,'assets/images/safaritrips-images/safari2.jpeg','tripspages/trip_amboseli.html'],
        ['tsavo-coastal','Tsavo & Coastal Safaris — Wilderness & Shore','safari',1100.00,'assets/images/safaritrips-images/safari3.jpeg','tripspages/trip_tsavo-coastal.html'],
        ['diani','Diani Beach — Coastal Relaxation','kenyan',420.00,'assets/images/kenyantrips-images/diani.jpeg','tripspages/trip_diani.html'],
        ['mount-kenya','Mount Kenya Excursion — Highland Trek','kenyan',620.00,'assets/images/kenyantrips-images/mountkenya.jpeg','tripspages/trip_mount-kenya.html'],
        ['thompson-falls','Thompson Falls & Waterways — Scenic Day Trip','kenyan',120.00,'assets/images/kenyantrips-images/thompsonfalls.jpeg','tripspages/trip_thompson-falls.html'],
        ['london','London Highlights — City Break','international',980.00,'assets/images/internationaltrips-images/london.jpeg','tripspages/trip_london.html'],
        ['greek-islands','Greek Islands & Culture — Island Hopper','international',1650.00,'assets/images/internationaltrips-images/greece.jpeg','tripspages/trip_greek-islands.html'],
        ['thailand','Thailand Explorer — Culture & Coast','international',1450.00,'assets/images/internationaltrips-images/thailand.jpeg','tripspages/trip_thailand.html'],
        ['kenya-highlights','Kenya Highlights Journey','package',1420.00,'assets/images/safaritrips-images/safari1.jpeg','bookings.html'],
        ['highland-heritage','Highland & Heritage Circuit','package',1050.00,'assets/images/kenyantrips-images/mountkenya.jpeg','bookings.html'],
        ['europe-one-go','Europe in One Go','package',1980.00,'assets/images/internationaltrips-images/london.jpeg','bookings.html']
    ];
    $seed = $pdo->prepare('INSERT INTO trips (id,title,category,price,image,detail_link) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE category=VALUES(category), image=COALESCE(image, VALUES(image)), detail_link=COALESCE(detail_link, VALUES(detail_link))');
    foreach ($known as $trip) $seed->execute($trip);
    $stmt = $pdo->prepare('SELECT id, title, price, image, description, detail_link FROM trips WHERE category = ? ORDER BY title');
    $stmt->execute([$category]);
    $trips = [];
    foreach ($stmt->fetchAll() as $trip) $trips[] = ['id' => $trip['id'], 'title' => $trip['title'], 'price' => (float) $trip['price'], 'image' => $trip['image'], 'description' => $trip['description'] ?: 'A carefully planned TraveL Den Adventures experience.', 'detail_link' => $trip['detail_link']];
    echo json_encode($trips);
} catch (PDOException $e) { http_response_code(500); echo json_encode(['error' => 'Unable to load trips.']); }
