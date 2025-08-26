<?php
// C:\xampp\htdocs\BatEstateExplorer\public\api\get_property_details.php
header('Content-Type: application/json');

require_once __DIR__ . '/../app/bootstrap.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid property ID']);
    exit;
}

// Get property info
$sql = "SELECT 
            id, title, description, location, price, bedrooms, bathrooms, sqm, lot_size, status, created_at
        FROM properties
        WHERE id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    echo json_encode(['success' => false, 'error' => 'Property not found']);
    exit;
}

// Get all images for this property
$sqlImg = "SELECT image_path FROM property_images WHERE property_id = ? ORDER BY id ASC";
$stmtImg = $conn->prepare($sqlImg);
$stmtImg->bind_param("i", $id);
$stmtImg->execute();
$resultImg = $stmtImg->get_result();
$images = [];
while ($row = $resultImg->fetch_assoc()) {
    // Ensure path starts from public root
    $images[] = '/' . ltrim($row['image_path'], '/');
}
$stmtImg->close();

// If no images, use default
if (empty($images)) {
    $images[] = '/BatEstateExplorer/assets/images/default.jpg';
}

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
    ]
]);
