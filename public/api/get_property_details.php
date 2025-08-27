<?php
// C:\xampp\htdocs\BatEstateExplorer\public\api\get_property_details.php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../app/bootstrap.php';

// Load logged-in user (if any)
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

// Validate property id
$property_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($property_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid property ID']);
    exit;
}

// Fetch property details
$sql = "SELECT id, title, description, location, price, bedrooms, bathrooms, sqm, lot_size, status, created_at
        FROM properties
        WHERE id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    echo json_encode(['success' => false, 'error' => 'Property not found']);
    exit;
}

// Fetch property images
$sqlImg = "SELECT image_path FROM property_images WHERE property_id = ? ORDER BY id ASC";
$stmtImg = $conn->prepare($sqlImg);
$stmtImg->bind_param("i", $property_id);
$stmtImg->execute();
$resultImg = $stmtImg->get_result();
$images = [];
while ($row = $resultImg->fetch_assoc()) {
    $images[] = '/' . ltrim($row['image_path'], '/');
}
$stmtImg->close();

if (empty($images)) {
    $images[] = '/BatEstateExplorer/assets/images/default.jpg';
}

// Check privilege from users.privileges JSON column
$has_privilege = false;
$debug_info = [];

if (isset($_SESSION['user_id'])) {  // <-- safer check
    $uid = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT privileges FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $privileges = json_decode($row['privileges'], true);
        $debug_info['raw_privileges'] = $row['privileges'];
        $debug_info['decoded'] = $privileges;
        $debug_info['check_property_id'] = $property_id;

        if (is_array($privileges)) {
            if (in_array((string)$property_id, $privileges, true) || in_array((int)$property_id, $privileges, true)) {
                $has_privilege = true;
            }
        }
    }
    $stmt->close();
} else {
    $debug_info['error'] = 'No user_id in session';
}

// Send response
echo json_encode([
    'success' => true,
    'property' => [
        'id' => $property['id'],
        'title' => $property['title'],
        'description' => $property['description'],
        'location' => $property['location'],
        'price' => $property['price'],
        'bedrooms' => $property['bedrooms'],
        'bathrooms' => $property['bathrooms'],
        'sqm' => $property['sqm'],
        'lot_size' => $property['lot_size'],
        'status' => $property['status'],
        'date_uploaded' => $property['created_at'],
        'images' => $images
    ],
    'has_privilege' => $has_privilege,
    'debug' => $debug_info
]);
