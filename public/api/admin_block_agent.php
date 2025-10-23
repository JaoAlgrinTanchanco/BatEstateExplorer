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

// --- Get JSON input ---
$data     = json_decode(file_get_contents('php://input'), true);
$agentId  = $data['agent_id'] ?? null;
$duration = $data['duration'] ?? null;   // Admin-selected duration for 'other'
$action   = $data['action'] ?? 'block';  // 'block' or 'unblock'
$category = $data['category'] ?? null;   // reason/category of report

if (!$agentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Agent ID is required.']);
    exit;
}

try {
    $conn->begin_transaction();

    if ($action === 'block') {

        // --- 1. Block the agent ---
        $stmt = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
        $stmt->bind_param("i", $agentId);
        $stmt->execute();

        // --- 2. Determine duration based on category ---
        if ($category && $category !== 'other') {
            switch ($category) {
                case 'fraudulent_listing':
                case 'harassment':
                    $duration = 'lifetime';
                    break;
                case 'misinformation':
                    $duration = '7days';
                    break;
                case 'spam':
                    $duration = '48hrs';
                    break;
                default:
                    $duration = '7days';
            }
        } elseif (!$duration) {
            $duration = '7days'; // default fallback
        }

        // --- 3. Update agent_reports table ---
        //     Sets status = blocked, duration = chosen value,
        //     and blockage_date = current timestamp
        $stmt2 = $conn->prepare("
            UPDATE agent_reports 
            SET status = 'blocked', duration = ?, blockage_date = NOW() 
            WHERE agent_id = ?
        ");
        $stmt2->bind_param("si", $duration, $agentId);
        $stmt2->execute();

        $message = 'Agent blocked successfully.';
        $status  = 'blocked';

    } else {
        // --- Unblock the agent ---
        $stmt = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
        $stmt->bind_param("i", $agentId);
        $stmt->execute();

        // --- Mark reports as unblocked, clear duration and blockage_date ---
        $stmt2 = $conn->prepare("
            UPDATE agent_reports 
            SET status = 'unblocked', duration = NULL, blockage_date = NULL 
            WHERE agent_id = ?
        ");
        $stmt2->bind_param("i", $agentId);
        $stmt2->execute();

        $message  = 'Agent unblocked successfully.';
        $status   = 'unblocked';
        $duration = null;
    }

    $conn->commit();

    echo json_encode([
        'success'     => true,
        'message'     => $message,
        'status'      => $status,
        'duration'    => $duration,
        'agent_id'    => $agentId,
        'blockage_date' => $action === 'block' ? date('Y-m-d H:i:s') : null
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'error'   => 'Failed to update agent.',
        'details' => $e->getMessage()
    ]);
}
