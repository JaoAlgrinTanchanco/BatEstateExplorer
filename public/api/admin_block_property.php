<?php
// admin_block_property.php
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
$propertyId = $data['reported_id'] ?? $data['property_id'] ?? null;
$action     = $data['action'] ?? 'block'; // 'block' or 'unblock'

if (!$propertyId || !is_numeric($propertyId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Property ID is required and must be numeric.']);
    exit;
}

try {
    $conn->begin_transaction();

    if ($action === 'block') {
        // --- 1. Mark property as blocked permanently ---
        $stmt = $conn->prepare("UPDATE properties SET is_reported = 1 WHERE id = ?");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();

        // --- 2. Update all reports related to this property as permanently blocked ---
        $duration = 'lifetime';
        $stmt2 = $conn->prepare("
            UPDATE property_reports
            SET status = 'blocked',
                duration = ?,
                blockage_date = NOW()
            WHERE property_id = ?
        ");
        $stmt2->bind_param("si", $duration, $propertyId);
        $stmt2->execute();

        $message = 'Property permanently blocked (down for life).';
        $status  = 'blocked';

    } else {
        // --- Unblock property if needed (admin override) ---
        $stmt = $conn->prepare("UPDATE properties SET is_reported = 0 WHERE id = ?");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();

        $stmt2 = $conn->prepare("
            UPDATE property_reports
            SET status = 'unblocked',
                duration = NULL,
                blockage_date = NULL
            WHERE property_id = ?
        ");
        $stmt2->bind_param("i", $propertyId);
        $stmt2->execute();

        $message  = 'Property unblocked successfully.';
        $status   = 'unblocked';
        $duration = null;
    }

    $conn->commit();

    echo json_encode([
        'success'       => true,
        'message'       => $message,
        'status'        => $status,
        'duration'      => $duration,
        'property_id'   => $propertyId,
        'blockage_date' => $action === 'block' ? date('Y-m-d H:i:s') : null
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'error'   => 'Failed to update property.',
        'details' => $e->getMessage()
    ]);
}
