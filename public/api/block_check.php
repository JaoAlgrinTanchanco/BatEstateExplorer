<?php
// ======================================
// block_check.php
// Periodic cleanup of expired penalties (agents + users)
// ======================================
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

date_default_timezone_set('Asia/Manila');
$conn = $GLOBALS['conn'] ?? null;

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not found.']);
    exit;
}

// ===========================
// Helper: Convert duration strings to seconds
// ===========================
function getDurationSeconds($duration) {
    $d = strtolower(trim($duration));
    return match($d) {
        '2mins', '2 mins' => 2 * 60,
        '10mins', '10 mins' => 10 * 60,
        '24hrs', '24 hours' => 24 * 3600,
        '48hrs', '48 hours' => 48 * 3600,
        '7days', '7 days' => 7 * 24 * 3600,
        '30days', '30 days' => 30 * 24 * 3600,
        'lifetime' => PHP_INT_MAX,
        default => 0
    };
}

// ===========================
// Helper: check and unblock if expired
// ===========================
function processBlocks($rows, $type, $conn, &$unblocked, &$debug) {
    foreach ($rows as $row) {
        $durationSeconds = getDurationSeconds($row['duration']);
        $isLifetime = $durationSeconds === PHP_INT_MAX;
        $elapsed = time() - strtotime($row['blockage_date'] ?? '0');

        $shouldUnblock = !$isLifetime && $elapsed >= $durationSeconds;

        $debug[] = [
            'type' => $type,
            'id' => $type === 'agent' ? $row['agent_id'] : $row['reported_user_id'],
            'name' => "{$row['first_name']} {$row['last_name']}",
            'duration' => $row['duration'],
            'elapsed_seconds' => $elapsed,
            'is_lifetime' => $isLifetime,
            'should_unblock' => $shouldUnblock ? 'YES' : 'NO'
        ];

        if ($shouldUnblock) {
            $conn->begin_transaction();
            try {
                $userId = $type === 'agent' ? $row['agent_id'] : $row['reported_user_id'];
                $stmt1 = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
                $stmt1->bind_param("i", $userId);
                $stmt1->execute();

                $reportId = $row['report_id'];
                $table = $type === 'agent' ? 'agent_reports' : 'user_reports';
                $stmt2 = $conn->prepare("DELETE FROM {$table} WHERE id = ?");
                $stmt2->bind_param("i", $reportId);
                $stmt2->execute();

                $conn->commit();

                $unblocked[] = [
                    $type . '_id' => $userId,
                    'name' => "{$row['first_name']} {$row['last_name']}",
                    'duration' => $row['duration'],
                    'elapsed_seconds' => $elapsed
                ];
            } catch (Exception $ex) {
                $conn->rollback();
                throw $ex;
            }
        }
    }
}

try {
    $debugInfo = [];
    $unblockedAgents = [];
    $unblockedUsers = [];

    // ======================
    // AGENT BLOCKS
    // ======================
    $res = $conn->query("
        SELECT 
            ar.id AS report_id, ar.agent_id, ar.duration, ar.blockage_date,
            u.is_blocked, u.first_name, u.last_name
        FROM agent_reports ar
        INNER JOIN users u ON u.id = ar.agent_id
        WHERE u.is_blocked = 1 AND ar.status = 'blocked' AND ar.blockage_date IS NOT NULL
    ");
    $agentRows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    processBlocks($agentRows, 'agent', $conn, $unblockedAgents, $debugInfo);

    // ======================
    // USER BLOCKS
    // ======================
    $res = $conn->query("
        SELECT 
            ur.id AS report_id, ur.reported_user_id, ur.duration, ur.blockage_date,
            u.is_blocked, u.first_name, u.last_name
        FROM user_reports ur
        INNER JOIN users u ON u.id = ur.reported_user_id
        WHERE u.is_blocked = 1 AND ur.status = 'blocked' AND ur.blockage_date IS NOT NULL
    ");
    $userRows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    processBlocks($userRows, 'user', $conn, $unblockedUsers, $debugInfo);

    // ======================
    // Current logged-in user block info
    // ======================
    $currentUserId = $_SESSION['user_id'] ?? null;
    $currentUserBlock = [
        'blocked' => false,
        'message' => null,
        'duration' => null,
        'permanent' => false,
        'property_block' => false
    ];

    if ($currentUserId) {
        $stmt = $conn->prepare("
            SELECT u.is_blocked, ar.duration, n.company_prop_id, n.notice_except, n.message
            FROM users u
            LEFT JOIN agent_reports ar ON ar.agent_id = u.id AND ar.status = 'blocked'
            LEFT JOIN notices n ON n.user_id = u.id
            ORDER BY n.id DESC
            LIMIT 1
        ");
        $stmt->execute();
        $stmt->bind_result($is_blocked, $duration, $company_prop_id, $notice_except, $message);
        if ($stmt->fetch() && (int)$is_blocked === 1) {
            $currentUserBlock['blocked'] = true;
            $currentUserBlock['message'] = $message ?? 'Violation of platform policies';
            $currentUserBlock['duration'] = $duration ?? '24hrs';
            $currentUserBlock['permanent'] = strtolower($duration ?? '') === 'lifetime';
            $currentUserBlock['property_block'] = is_null($company_prop_id) && is_null($notice_except);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Block check completed successfully.',
        'unblocked_agents' => $unblockedAgents,
        'unblocked_users' => $unblockedUsers,
        'current_user_block' => $currentUserBlock,
        'debug' => $debugInfo
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to perform block check.',
        'details' => $e->getMessage()
    ]);
}
