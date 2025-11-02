<?php
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

// --- Fetch property info (also get owner_id via agents.user_id fallback) ---
$query = "
    SELECT 
        p.id, 
        p.title, 
        p.agent_id, 
        COALESCE(p.user_id, a.user_id) AS owner_id
    FROM properties p
    LEFT JOIN agents a ON p.agent_id = a.id
    WHERE p.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();

if (!$property) {
    http_response_code(404);
    echo json_encode(['error' => 'Property not found.']);
    exit;
}

try {
    $conn->begin_transaction();

    // ✅ Validate owner_id
    if (empty($property['owner_id'])) {
        throw new Exception('Owner ID not found for this property.');
    }

    // ✅ INSERT into property_notices before deletion
    $noticeType = 'take_down';
    $category   = 'admin_action';
    $message    = 'Property "' . $property['title'] . '" has been permanently removed by admin.';
    $duration   = 'lifetime';
    $is_read    = 0;

    $stmtNotice = $conn->prepare("
        INSERT INTO property_notices 
        (property_id, owner_id, agent_id, notice_type, category, message, duration, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmtNotice->bind_param(
        "iiissssi",
        $property['id'],
        $property['owner_id'],
        $property['agent_id'],
        $noticeType,
        $category,
        $message,
        $duration,
        $is_read
    );

    if (!$stmtNotice->execute()) {
        throw new Exception("Failed to insert property notice: " . $stmtNotice->error);
    }

    // ✅ Capture notice_id for response
    $noticeId = $stmtNotice->insert_id;

    // Delete all reports for this property
    $stmtReports = $conn->prepare("DELETE FROM property_reports WHERE property_id = ?");
    $stmtReports->bind_param("i", $propertyId);
    if (!$stmtReports->execute()) {
        throw new Exception("Failed to delete property reports: " . $stmtReports->error);
    }

    // Delete the property itself
    $stmtProp = $conn->prepare("DELETE FROM properties WHERE id = ?");
    $stmtProp->bind_param("i", $propertyId);
    if (!$stmtProp->execute()) {
        throw new Exception("Failed to delete property: " . $stmtProp->error);
    }

    $conn->commit();

    // ✅ Full detailed JSON response
    echo json_encode([
        'success'             => true,
        'message'             => 'Property deleted successfully and notice recorded.',
        'status'              => 'blocked',
        'duration'            => 'lifetime',
        'property_id'         => $propertyId,
        'property_title'      => $property['title'] ?? 'Unknown Property',
        'property_owner_id'   => $property['owner_id'] ?? null,
        'agent_id'            => $property['agent_id'] ?? null,
        'blockage_date'       => date('Y-m-d H:i:s'),
        // ✅ Include notice insertion result
        'notice_inserted'     => true,
        'notice_id'           => $noticeId,
        'notice_message'      => $message
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'error'   => 'Failed to delete property.',
        'details' => $e->getMessage()
    ]);
}
