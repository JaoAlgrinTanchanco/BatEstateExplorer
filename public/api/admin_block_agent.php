<?php
// admin_block_agent.php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not found.']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$agentId = $data['agent_id'] ?? null;
$duration = $data['duration'] ?? null;
$action = $data['action'] ?? 'block'; // default to block

if (!$agentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Agent ID is required.']);
    exit;
}

try {
    $conn->begin_transaction();

    // Block or Unblock agent
    if ($action === 'block') {
        $stmt = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
    }
    $stmt->bind_param("i", $agentId);
    $stmt->execute();

    // Update all related reports to resolved and set duration if provided
    if ($action === 'block') {
        $stmt2 = $conn->prepare("UPDATE agent_reports SET status = 'resolved', duration = ? WHERE agent_id = ?");
        $stmt2->bind_param("si", $duration, $agentId);
        $stmt2->execute();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => ($action === 'block' ? 'Agent blocked successfully.' : 'Agent unblocked successfully.')
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update agent.', 'details' => $e->getMessage()]);
}
