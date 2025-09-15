<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../app/bootstrap.php';

// Validate property id
$property_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($property_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid property ID']);
    exit;
}

// Fetch property details including agent_id
$sql = "SELECT id, title, property_type, description, location, price, bedrooms, bathrooms, sqm, lot_size, status, created_at, agent_id
        FROM properties
        WHERE id = ? LIMIT 1";

$stmt = $conn->prepare($sql);
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
$uid = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;

if ($uid) {
    $uid = (int)$uid;
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

// ✅ Prepare safe response with defaults
$property_data = [
    'id'           => (int)$property['id'],
    'title'        => $property['title'] ?: 'No Title',
    'property_type'=> $property['property_type'] ?: '-',
    'description'  => $property['description'] ?: 'No description available.',
    'location'     => $property['location'] ?: 'Location not available',
    'price'        => isset($property['price']) ? (float)$property['price'] : 0,
    'bedrooms'     => isset($property['bedrooms']) ? (int)$property['bedrooms'] : 0,
    'bathrooms'    => isset($property['bathrooms']) ? (int)$property['bathrooms'] : 0,
    'sqm'          => isset($property['sqm']) ? (float)$property['sqm'] : 0,
    'lot_size'     => isset($property['lot_size']) ? (float)$property['lot_size'] : 0,
    'status'       => $property['status'] ?? 'pending',
    'date_uploaded'=> $property['created_at'] ?? null,
    'images'       => $images,
    'agent_id'     => $property['agent_id'] ?? null
];

echo json_encode([
    'success'       => true,
    'property'      => $property_data,
    'has_privilege' => $has_privilege,
    'debug'         => $debug_info
]);
