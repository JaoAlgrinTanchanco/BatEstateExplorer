<?php
// C:\xampp\htdocs\BatEstateExplorer\public\api\get_property_details.php
header('Content-Type: application/json');

// Fix path to bootstrap.php
require_once __DIR__ . '/../app/bootstrap.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid property ID']);
    exit;
}

$sql = "
    SELECT 
        p.id, 
        p.title, 
        p.description, 
        p.location, 
        p.price, 
        p.bedrooms, 
        p.bathrooms, 
        p.sqm, 
        p.lot_size, 
        p.created_at, 
        pi.image_path
    FROM properties p
    LEFT JOIN property_images pi 
        ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE p.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();

if (!$property) {
    echo json_encode(['error' => 'Property not found']);
    exit;
}

// Ensure image path is safe for frontend
$property['image_url'] = $property['image_path'] 
    ? '/BatEstateExplorer/assets/images/' . basename($property['image_path']) 
    : '/BatEstateExplorer/assets/images/default.jpg';

echo json_encode($property);
