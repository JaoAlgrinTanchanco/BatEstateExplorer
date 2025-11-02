<?php
// admin_delete_property.php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not found.']);
    exit;
}

// --- Get JSON input ---
$data       = json_decode(file_get_contents('php://input'), true);
$propertyId = $data['property_id'] ?? null;

if (!$propertyId || !is_numeric($propertyId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Property ID is required and must be numeric.']);
    exit;
}

// --- Fetch property info ---
$property = $conn->query("SELECT id, title, agent_id, user_id FROM properties WHERE id = " . intval($propertyId))->fetch_assoc();
if (!$property) {
    http_response_code(404);
    echo json_encode(['error' => 'Property not found.']);
    exit;
}

try {
    $conn->begin_transaction();

    // Return response immediately with property details (pretend it’s blocked)
    echo json_encode([
        'success'          => true,
        'message'          => 'Property will be deleted shortly.',
        'status'           => 'blocked', // hardcoded
        'duration'         => 'lifetime',
        'property_id'      => $propertyId,
        'property_title'   => $property['title'] ?? 'Unknown Property',
        'property_owner_id'=> $property['user_id'] ?? null,
        'agent_id'         => $property['agent_id'] ?? null,
        'blockage_date'    => date('Y-m-d H:i:s')
    ]);

    // Flush output so the client gets response immediately
    flush();

    // --- Delay actual deletion by 5 seconds ---
    sleep(5);

    // Delete all reports for this property
    $stmtReports = $conn->prepare("DELETE FROM property_reports WHERE property_id = ?");
    $stmtReports->bind_param("i", $propertyId);
    $stmtReports->execute();

    // Delete the property itself
    $stmtProp = $conn->prepare("DELETE FROM properties WHERE id = ?");
    $stmtProp->bind_param("i", $propertyId);
    $stmtProp->execute();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    error_log('Failed to delete property: ' . $e->getMessage());
}
