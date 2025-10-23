<?php
require_once __DIR__ . '/../../config/database.php'; // adjust if needed

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Missing property ID']);
    exit;
}

$propertyId = intval($_GET['id']);

try {
    // Fetch property details
    $stmt = $conn->prepare("
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
            p.status, 
            p.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS created_by,
            CONCAT(su.first_name, ' ', su.last_name) AS sold_by
        FROM properties p
        LEFT JOIN agents a ON p.agent_id = a.id
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN agents sa ON p.sold_by_agent_id = sa.id
        LEFT JOIN users su ON sa.user_id = su.id
        WHERE p.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $propertyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $property = $result->fetch_assoc();

    if (!$property) {
        echo json_encode(['error' => 'Property not found']);
        exit;
    }

    // Fetch images
    $imgStmt = $conn->prepare("
        SELECT image_path 
        FROM property_images 
        WHERE property_id = ?
    ");
    $imgStmt->bind_param("i", $propertyId);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();

    $images = [];
    while ($img = $imgResult->fetch_assoc()) {
        $images[] = "/BatEstateExplorer/storage/" . $img['image_path'];
    }

    $property['images'] = $images;

    echo json_encode($property);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
