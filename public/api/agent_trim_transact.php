<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

$debug = [];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$agentId = (int) $_SESSION['user_id'];

try {
    // 1) Verify this user is agent
    $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $agentId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception("Agent not found");
    }

    $userType = $row['user_type'];
    if ($userType !== 'direct_agent' && $userType !== 'associate_agent') {
        throw new Exception("Not authorized: Only agents allowed");
    }

    $debug[] = "Agent {$agentId} type = {$userType}";

    // 2) Get all user IDs under this agent
    $stmt = $conn->prepare("SELECT id FROM users WHERE listed_by_agent_id = ?");
    $stmt->bind_param("s", $agentId);
    $stmt->execute();
    $res = $stmt->get_result();

    $userIds = [];
    while ($r = $res->fetch_assoc()) {
        $userIds[] = (int) $r['id'];
    }
    $stmt->close();

    if (empty($userIds)) {
        echo json_encode([
            'success' => true,
            'deleted_count' => 0,
            'message' => 'No users under this agent',
            'debug' => $debug
        ]);
        exit;
    }

    $debug[] = "Users under agent: " . implode(',', $userIds);

    $totalDeleted = 0;
    $deletedDetails = [];

    // 3) For each user under this agent, trim their transactions
    foreach ($userIds as $uid) {
        // Count transactions
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM transactions WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        $count = (int) ($row['cnt'] ?? 0);
        if ($count <= 10) {
            continue; // no trimming needed
        }

        // Find extra transaction IDs
        $stmt = $conn->prepare("
            SELECT id
            FROM transactions
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 10, 18446744073709551615
        ");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();

        $ids = [];
        while ($r = $res->fetch_assoc()) {
            $ids[] = (int) $r['id'];
        }
        $stmt->close();

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM transactions WHERE id IN ($placeholders)";
            $stmt = $conn->prepare($sql);

            $types = str_repeat('i', count($ids));
            $bind = [];
            $bind[] = &$types;
            foreach ($ids as $k => $id) {
                $bind[] = &$ids[$k];
            }
            call_user_func_array([$stmt, 'bind_param'], $bind);

            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            $totalDeleted += $deleted;
            $deletedDetails[$uid] = $deleted;
        }
    }

    echo json_encode([
        'success' => true,
        'deleted_count' => $totalDeleted,
        'details' => $deletedDetails,
        'debug' => $debug
    ]);

} catch (Exception $e) {
    $debug[] = "Exception: " . $e->getMessage();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => $debug
    ]);
    exit;
}
?>
