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
$duration   = $data['duration'] ?? 'lifetime'; // fallback duration
$reason     = $data['reason'] ?? 'Violation';   // optional reason for notice

if (!$agentId || !$propertyId) {
    http_response_code(400);
    echo json_encode(['error' => 'Agent ID and Property ID are required.']);
    exit;
}

try {
    $conn->begin_transaction();

    // --- 1. Map agent_id to user_id ---
    $stmtMap = $conn->prepare("SELECT user_id FROM agents WHERE id = ?");
    $stmtMap->bind_param("i", $agentId);
    $stmtMap->execute();
    $agentUser = $stmtMap->get_result()->fetch_assoc();
    if (!$agentUser || empty($agentUser['user_id'])) {
        throw new Exception("No user found for agent ID $agentId");
    }
    $userId = (int)$agentUser['user_id'];

    // --- 2. Block the user account ---
    $stmtBlock = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
    $stmtBlock->bind_param("i", $userId);
    if (!$stmtBlock->execute()) {
        throw new Exception("Failed to block agent user: " . $stmtBlock->error);
    }

    // --- 3. Update agent_reports table ---
    $stmtReport = $conn->prepare("
        UPDATE agent_reports 
        SET status = 'blocked', duration = ?, blockage_date = NOW() 
        WHERE agent_id = ?
    ");
    $stmtReport->bind_param("si", $duration, $agentId);
    $stmtReport->execute();

    // --- 4. Fetch property title for notice ---
    $stmtProp = $conn->prepare("SELECT title FROM properties WHERE id = ?");
    $stmtProp->bind_param("i", $propertyId);
    $stmtProp->execute();
    $property = $stmtProp->get_result()->fetch_assoc();
    $propertyTitle = $property['title'] ?? 'Unknown Property';

    // --- 5. Insert notice ---
    $noticeMsg = sprintf(
        'Property "%s" has been reported as already sold. Your account has been blocked.',
        $propertyTitle
    );
    $stmtNotice = $conn->prepare("
        INSERT INTO notices (user_id, message, isseen, created_at)
        VALUES (?, ?, 0, NOW())
    ");
    $stmtNotice->bind_param("is", $userId, $noticeMsg);
    if (!$stmtNotice->execute()) {
        throw new Exception("Failed to insert notice: " . $stmtNotice->error);
    }

    $conn->commit();

    echo json_encode([
        'success'       => true,
        'message'       => 'Agent blocked successfully and notice created.',
        'agent_id'      => $agentId,
        'user_id'       => $userId,
        'property_id'   => $propertyId,
        'status'        => 'blocked',
        'duration'      => $duration,
        'blockage_date' => date('Y-m-d H:i:s'),
        'notice_message'=> $noticeMsg
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'error'   => 'Failed to block agent for already_sold case.',
        'details' => $e->getMessage()
    ]);
}
