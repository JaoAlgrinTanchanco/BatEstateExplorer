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
$data = json_decode(file_get_contents('php://input'), true);
$agentId    = $data['agent_id'] ?? null;
$propertyId = $data['property_id'] ?? null;
$duration   = $data['duration'] ?? null; // optional, can be stored in reports

if (!$agentId || !$propertyId) {
    http_response_code(400);
    echo json_encode(['error' => 'Agent ID and Property ID are required.']);
    exit;
}

try {
    $conn->begin_transaction();

    // --- 1. Block the agent ---
    $stmtBlock = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
    $stmtBlock->bind_param("i", $agentId);
    if (!$stmtBlock->execute()) {
        throw new Exception("Failed to block agent: " . $stmtBlock->error);
    }

    // --- 2. Update agent_reports (optional: track duration and blockage_date) ---
    $stmtReport = $conn->prepare("
        UPDATE agent_reports 
        SET status = 'blocked', duration = ?, blockage_date = NOW() 
        WHERE agent_id = ?
    ");
    $stmtReport->bind_param("si", $duration, $agentId);
    $stmtReport->execute();

    $conn->commit();

    echo json_encode([
        'success'      => true,
        'message'      => 'Agent blocked successfully for already sold property.',
        'agent_id'     => $agentId,
        'property_id'  => $propertyId,
        'status'       => 'blocked',
        'duration'     => $duration,
        'blockage_date'=> date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'error'   => 'Failed to block agent for already_sold case.',
        'details' => $e->getMessage()
    ]);
}
