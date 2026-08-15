<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// basic rate limiting per session: max 8 bookings per hour
if (!isset($_SESSION['booking_submissions'])) {
    $_SESSION['booking_submissions'] = [];
}
$now = time();
$_SESSION['booking_submissions'] = array_filter($_SESSION['booking_submissions'], function($t) use ($now){ return ($now - $t) < 3600; });
if (count($_SESSION['booking_submissions']) >= 8) {
    http_response_code(429);
    echo "Too many booking attempts. Please try again later.";
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
$trip = trim($_POST['trip'] ?? '');
$start_date = trim($_POST['start_date'] ?? null);
$travellers = intval($_POST['travellers'] ?? 1);
$notes = trim($_POST['notes'] ?? '');

// ensure trips table exists (simple migration)
$pdo->exec("CREATE TABLE IF NOT EXISTS trips (
    id VARCHAR(64) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT
)");

// Upsert some known trips if missing (id,title,price)
$known = [
    ['masai-mara','Masai Mara Reserve — Classic Safari',1250.00],
    ['amboseli','Amboseli National Park — Elephant & Mountain Views',890.00],
    ['tsavo-coastal','Tsavo & Coastal Safaris — Wilderness & Shore',1100.00],
    ['diani','Diani Beach — Coastal Relaxation',420.00],
    ['mount-kenya','Mount Kenya Excursion — Highland Trek',620.00],
    ['thompson-falls','Thompson Falls & Waterways — Scenic Day Trip',120.00],
    ['london','London Highlights — City Break',980.00],
    ['greek-islands','Greek Islands & Culture — Island Hopper',1650.00],
    ['thailand','Thailand Explorer — Culture & Coast',1450.00]
];
foreach($known as $k){
    $stmt = $pdo->prepare('INSERT INTO trips (id,title,price) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE title=VALUES(title), price=VALUES(price)');
    $stmt->execute($k);
}

// Ensure bookings table has extra columns for price and dates
try {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS price_per_person DECIMAL(10,2) NULL, ADD COLUMN IF NOT EXISTS total_price DECIMAL(10,2) NULL, ADD COLUMN IF NOT EXISTS start_date DATE NULL, ADD COLUMN IF NOT EXISTS travellers INT DEFAULT 1 NULL");
} catch (Exception $e) {
    // Some MySQL versions don't support ADD COLUMN IF NOT EXISTS with multiple adds; attempt individually
    try { $pdo->exec("ALTER TABLE bookings ADD COLUMN price_per_person DECIMAL(10,2) NULL"); } catch(Exception$e){}
    try { $pdo->exec("ALTER TABLE bookings ADD COLUMN total_price DECIMAL(10,2) NULL"); } catch(Exception$e){}
    try { $pdo->exec("ALTER TABLE bookings ADD COLUMN start_date DATE NULL"); } catch(Exception$e){}
    try { $pdo->exec("ALTER TABLE bookings ADD COLUMN travellers INT DEFAULT 1 NULL"); } catch(Exception$e){}
}

if (!$name || !$email || !$phone || !$trip) {
    http_response_code(400);
    echo json_encode(['error'=>'Please fill required fields.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid email address.']);
    exit;
}

$name = substr($name,0,255);
$email = substr($email,0,255);
$phone = substr($phone,0,50);

// Find trip by title or slug
$tripStmt = $pdo->prepare('SELECT * FROM trips WHERE title = ? OR id = ? LIMIT 1');
$tripSlug = strtolower(preg_replace('/[^a-z0-9]+/','-',trim($trip)));
$tripStmt->execute([$trip, $tripSlug]);
$tripRow = $tripStmt->fetch();
if (!$tripRow) {
    // attempt fuzzy match
    $fstmt = $pdo->prepare('SELECT * FROM trips'); $fstmt->execute(); $all = $fstmt->fetchAll();
    foreach($all as $r){ if (stripos($r['title'],$trip)!==false){ $tripRow=$r; break; } }
}
if (!$tripRow) {
    http_response_code(400);
    echo json_encode(['error'=>'Selected trip not found']);
    exit;
}

$price_per_person = floatval($tripRow['price']);
$travellers = max(1,intval($travellers));
$total_price = $price_per_person * $travellers;

try {
    $ins = $pdo->prepare('INSERT INTO bookings (name, email, phone, trip_id, details, price_per_person, total_price, start_date, travellers) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $ins->execute([$name,$email,$phone,$tripRow['id'],$notes,$price_per_person,$total_price, $start_date ?: null, $travellers]);
    $_SESSION['booking_submissions'][] = $now;
    echo json_encode(['status'=>'OK','booking_id'=>$pdo->lastInsertId(),'total_price'=>$total_price]);
    exit;
} catch (Exception $e) {
    error_log('Bookings handler error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error'=>'Server error']);
    exit;
}
