<?php
// update_property_status.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/pdo_database.php'; // Adjust path

$pdo = $pdo ?? null;
if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection not found']);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get raw JSON body
$input = json_decode(file_get_contents('php://input'), true);
$propertyId = isset($input['property_id']) ? (int)$input['property_id'] : 0;
$status = $input['status'] ?? '';

$allowedStatuses = ['pending','available','rejected','sold'];
if ($propertyId <= 0 || !in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid property ID or status']);
    exit;
}

// Optional: Check if user is logged in
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Check if property exists and belongs to this agent
    $stmt = $pdo->prepare("SELECT listed_by_agent_id FROM properties WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $propertyId]);
    $property = $stmt->fetch();

    if (!$property) {
        echo json_encode(['success' => false, 'error' => 'Property not found']);
        exit;
    }

    // Update status
    $stmt = $pdo->prepare("UPDATE properties SET status = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':id' => $propertyId
    ]);

    echo json_encode(['success' => true, 'status' => $status, 'message' => "Property status updated to $status"]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
