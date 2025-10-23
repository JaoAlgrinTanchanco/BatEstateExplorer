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
    switch (strtolower(trim($duration))) {
        case '2mins': case '2 mins': return 2 * 60;
        case '10mins': case '10 mins': return 10 * 60;
        case '48hrs': case '48 hours': return 48 * 3600;
        case '7days': case '7 days': return 7 * 24 * 3600;
        case 'lifetime': return PHP_INT_MAX;
        default: return 0;
    }
}

try {
    $debugInfo = [];
    $unblockedAgents = [];
    $unblockedUsers = [];

    // ======================
    // AGENT BLOCK CHECK (existing)
    // ======================
    $sql = "
        SELECT 
            ar.id AS report_id,
            ar.agent_id,
            ar.duration,
            ar.blockage_date,
            u.is_blocked,
            u.first_name,
            u.last_name
        FROM agent_reports ar
        INNER JOIN users u ON u.id = ar.agent_id
        WHERE u.is_blocked = 1
          AND ar.status = 'blocked'
          AND ar.blockage_date IS NOT NULL
    ";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $durationSeconds = getDurationSeconds($row['duration']);
            if ($durationSeconds === PHP_INT_MAX) continue;

            $elapsed = time() - strtotime($row['blockage_date']);
            $shouldUnblock = $elapsed >= $durationSeconds;

            $debugInfo[] = [
                'type' => 'agent',
                'id' => $row['agent_id'],
                'name' => "{$row['first_name']} {$row['last_name']}",
                'duration' => $row['duration'],
                'elapsed_seconds' => $elapsed,
                'should_unblock' => $shouldUnblock ? 'YES' : 'NO'
            ];

            if ($shouldUnblock) {
                $conn->begin_transaction();
                try {
                    $stmt1 = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
                    $stmt1->bind_param("i", $row['agent_id']);
                    $stmt1->execute();

                    $stmt2 = $conn->prepare("DELETE FROM agent_reports WHERE id = ?");
                    $stmt2->bind_param("i", $row['report_id']);
                    $stmt2->execute();

                    $conn->commit();

                    $unblockedAgents[] = [
                        'agent_id' => $row['agent_id'],
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

    // ======================
    // USER BLOCK CHECK (NEW)
    // ======================
    $sqlUser = "
        SELECT 
            ur.id AS report_id,
            ur.reported_user_id,
            ur.duration,
            ur.blockage_date,
            u.is_blocked,
            u.first_name,
            u.last_name
        FROM user_reports ur
        INNER JOIN users u ON u.id = ur.reported_user_id
        WHERE u.is_blocked = 1
          AND ur.status = 'blocked'
          AND ur.blockage_date IS NOT NULL
    ";

    $resultUser = $conn->query($sqlUser);

    if ($resultUser && $resultUser->num_rows > 0) {
        while ($row = $resultUser->fetch_assoc()) {
            $durationSeconds = getDurationSeconds($row['duration']);
            if ($durationSeconds === PHP_INT_MAX) continue;

            $elapsed = time() - strtotime($row['blockage_date']);
            $shouldUnblock = $elapsed >= $durationSeconds;

            $debugInfo[] = [
                'type' => 'user',
                'id' => $row['reported_user_id'],
                'name' => "{$row['first_name']} {$row['last_name']}",
                'duration' => $row['duration'],
                'elapsed_seconds' => $elapsed,
                'should_unblock' => $shouldUnblock ? 'YES' : 'NO'
            ];

            if ($shouldUnblock) {
                $conn->begin_transaction();
                try {
                    $stmt1 = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
                    $stmt1->bind_param("i", $row['reported_user_id']);
                    $stmt1->execute();

                    $stmt2 = $conn->prepare("DELETE FROM user_reports WHERE id = ?");
                    $stmt2->bind_param("i", $row['report_id']);
                    $stmt2->execute();

                    $conn->commit();

                    $unblockedUsers[] = [
                        'user_id' => $row['reported_user_id'],
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

    echo json_encode([
        'success' => true,
        'message' => 'Block check completed successfully.',
        'unblocked_agents' => $unblockedAgents,
        'unblocked_users' => $unblockedUsers,
        'debug' => $debugInfo
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to perform block check.',
        'details' => $e->getMessage()
    ]);
}
