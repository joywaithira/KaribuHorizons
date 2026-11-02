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
$phoneCountryCode = trim($_POST['phone_country_code'] ?? '');
$trip = trim($_POST['trip'] ?? '');
$start_date = trim($_POST['start_date'] ?? '');
$checkout_date = trim($_POST['checkout_date'] ?? '');
$travellers = intval($_POST['travellers'] ?? 1);
$travelerTypes = $_POST['traveler_types'] ?? [];
$travelerAges = $_POST['traveler_ages'] ?? [];
$notes = trim($_POST['notes'] ?? '');

// ensure trips table exists (simple migration)
$pdo->exec("CREATE TABLE IF NOT EXISTS trips (
    id VARCHAR(64) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(32) NOT NULL DEFAULT 'kenyan',
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255),
    description TEXT,
    detail_link VARCHAR(255)
)");
foreach (["ALTER TABLE trips ADD COLUMN IF NOT EXISTS category VARCHAR(32) NOT NULL DEFAULT 'kenyan'", "ALTER TABLE trips ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL", "ALTER TABLE trips ADD COLUMN IF NOT EXISTS detail_link VARCHAR(255) NULL"] as $migration) {
    try { $pdo->exec($migration); } catch (Exception $e) {}
}

// Upsert some known trips if missing (id,title,price)
$known = [
    ['masai-mara','Masai Mara Reserve — Classic Safari','safari',1250.00,'assets/images/safaritrips-images/safari1.jpeg','tripspages/trip_masai-mara.html'],
    ['amboseli','Amboseli National Park — Elephant & Mountain Views','safari',890.00,'assets/images/safaritrips-images/safari2.jpeg','tripspages/trip_amboseli.html'],
    ['tsavo-coastal','Tsavo & Coastal Safaris — Wilderness & Shore','safari',1100.00,'assets/images/safaritrips-images/safari3.jpeg','tripspages/trip_tsavo-coastal.html'],
    ['diani','Diani Beach — Coastal Relaxation','kenyan',420.00,'assets/images/kenyantrips-images/diani.jpeg','tripspages/trip_diani.html'],
    ['mount-kenya','Mount Kenya Excursion — Highland Trek','kenyan',620.00,'assets/images/kenyantrips-images/mountkenya.jpeg','tripspages/trip_mount-kenya.html'],
    ['thompson-falls','Thompson Falls & Waterways — Scenic Day Trip','kenyan',120.00,'assets/images/kenyantrips-images/thompsonfalls.jpeg','tripspages/trip_thompson-falls.html'],
    ['london','London Highlights — City Break','international',980.00,'assets/images/internationaltrips-images/london.jpeg','tripspages/trip_london.html'],
    ['greek-islands','Greek Islands & Culture — Island Hopper','international',1650.00,'assets/images/internationaltrips-images/greece.jpeg','tripspages/trip_greek-islands.html'],
    ['thailand','Thailand Explorer — Culture & Coast','international',1450.00,'assets/images/internationaltrips-images/thailand.jpeg','tripspages/trip_thailand.html']
];
foreach($known as $k){
    $stmt = $pdo->prepare('INSERT INTO trips (id,title,category,price,image,detail_link) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=VALUES(title), category=VALUES(category), price=VALUES(price), image=VALUES(image), detail_link=VALUES(detail_link)');
    $stmt->execute($k);
}

// Ensure booking fields exist for databases created from an older schema.
foreach ([
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS price_per_person DECIMAL(10,2) NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS total_price DECIMAL(10,2) NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS start_date DATE NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS checkout_date DATE NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS travellers INT DEFAULT 1 NULL",
    "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS traveler_details TEXT NULL",
] as $migration) {
    try { $pdo->exec($migration); } catch (Exception $e) {
        // Ignore duplicate-column errors on database versions without IF NOT EXISTS support.
    }
}

if (!$name || !$email || !$phone || !$phoneCountryCode || !$trip || !$start_date || !$checkout_date) {
    http_response_code(400);
    echo json_encode(['error'=>'Please fill required fields.']);
    exit;
}
if (count(preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY)) < 2) {
    http_response_code(400);
    echo json_encode(['error'=>'Please enter at least your first and last name.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid email address.']);
    exit;
}

$phoneCountryDigits = ltrim($phoneCountryCode, '+');
$phoneDigits = ltrim(preg_replace('/\D+/', '', $phone), '0');
$internationalDigits = $phoneCountryDigits . $phoneDigits;
if (!preg_match('/^\+[1-9][0-9]{0,2}$/', $phoneCountryCode) || strlen($internationalDigits) < 8 || strlen($internationalDigits) > 15 || $phoneDigits === '') {
    http_response_code(400);
    echo json_encode(['error'=>'Enter a valid phone number and select its country calling code.']);
    exit;
}

$checkInDate = DateTime::createFromFormat('!Y-m-d', $start_date);
$checkOutDate = DateTime::createFromFormat('!Y-m-d', $checkout_date);
if (!$checkInDate || $checkInDate->format('Y-m-d') !== $start_date || !$checkOutDate || $checkOutDate->format('Y-m-d') !== $checkout_date || $checkout_date <= $start_date) {
    http_response_code(400);
    echo json_encode(['error'=>'Enter valid dates. Check-out must be later than check-in.']);
    exit;
}

$travellers = max(1, min(20, $travellers));
if (!is_array($travelerTypes) || count($travelerTypes) !== $travellers) {
    http_response_code(400);
    echo json_encode(['error'=>'Please choose adult or child for every traveller.']);
    exit;
}
$travelerDetails = [];
for ($i = 0; $i < $travellers; $i++) {
    $type = $travelerTypes[$i] ?? '';
    if (!in_array($type, ['adult', 'child'], true)) {
        http_response_code(400);
        echo json_encode(['error'=>'Please choose adult or child for every traveller.']);
        exit;
    }
    $age = null;
    if ($type === 'child') {
        $ageValue = $travelerAges[$i] ?? '';
        if ($ageValue === '' || filter_var($ageValue, FILTER_VALIDATE_INT) === false || (int)$ageValue < 0 || (int)$ageValue > 17) {
            http_response_code(400);
            echo json_encode(['error'=>'Enter an age from 0 to 17 for each child.']);
            exit;
        }
        $age = (int)$ageValue;
    }
    $travelerDetails[] = ['type' => $type, 'age' => $age];
}

$name = substr($name,0,255);
$email = substr($email,0,255);
$phone = substr('+' . $internationalDigits,0,50);

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
$total_price = $price_per_person * $travellers;
$travelerDetailsJson = json_encode($travelerDetails, JSON_UNESCAPED_UNICODE);

try {
    $ins = $pdo->prepare('INSERT INTO bookings (name, email, phone, trip_id, details, price_per_person, total_price, start_date, checkout_date, travellers, traveler_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $ins->execute([$name,$email,$phone,$tripRow['id'],$notes,$price_per_person,$total_price,$start_date,$checkout_date,$travellers,$travelerDetailsJson]);
    $_SESSION['booking_submissions'][] = $now;
    echo json_encode(['status'=>'OK','booking_id'=>$pdo->lastInsertId(),'total_price'=>$total_price]);
    exit;
} catch (Exception $e) {
    error_log('Bookings handler error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error'=>'Server error']);
    exit;
}
