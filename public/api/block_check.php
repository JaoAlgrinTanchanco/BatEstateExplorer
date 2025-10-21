<?php
// ======================================
// block_check.php
// Periodic cleanup of expired penalties (with debug info + delete expired reports)
// ======================================
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

// Force timezone to match database timezone
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
        case '2mins':
        case '2 mins':
        case '2min':
        case '2 min':
            return 2 * 60; // 2 minutes
        case '10mins':
        case '10 mins':
        case '10min':
        case '10 min':
            return 10 * 60; // 10 minutes
        case '48hrs':
        case '48 hours':
            return 48 * 3600; // 48 hours
        case '7days':
        case '7 days':
            return 7 * 24 * 3600; // 7 days
        case 'lifetime':
            return PHP_INT_MAX; // permanent
        default:
            return 0;
    }
}

try {
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
    $unblockedAgents = [];
    $debugInfo = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $durationSeconds = getDurationSeconds($row['duration']);
            if ($durationSeconds === PHP_INT_MAX) continue; // Skip lifetime bans

            $blockedAt = new DateTime($row['blockage_date']);
            $now = new DateTime('now');

            $elapsedSeconds = $now->getTimestamp() - $blockedAt->getTimestamp();
            $shouldUnblock = $elapsedSeconds >= $durationSeconds;

            $debugInfo[] = [
                'agent_id' => $row['agent_id'],
                'name' => "{$row['first_name']} {$row['last_name']}",
                'duration' => $row['duration'],
                'duration_seconds' => $durationSeconds,
                'blocked_at' => $blockedAt->format('Y-m-d H:i:s'),
                'now' => $now->format('Y-m-d H:i:s'),
                'elapsed_seconds' => $elapsedSeconds,
                'elapsed_minutes' => round($elapsedSeconds / 60, 2),
                'should_unblock' => $shouldUnblock ? 'YES' : 'NO'
            ];

            if ($shouldUnblock) {
                $conn->begin_transaction();
                try {
                    // 1. Unblock agent
                    $stmt1 = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
                    $stmt1->bind_param("i", $row['agent_id']);
                    $stmt1->execute();

                    // 2. Delete the report record completely
                    $stmt2 = $conn->prepare("DELETE FROM agent_reports WHERE id = ?");
                    $stmt2->bind_param("i", $row['report_id']);
                    $stmt2->execute();

                    $conn->commit();

                    $unblockedAgents[] = [
                        'agent_id' => $row['agent_id'],
                        'name' => "{$row['first_name']} {$row['last_name']}",
                        'duration' => $row['duration'],
                        'elapsed_seconds' => $elapsedSeconds,
                        'blocked_at' => $row['blockage_date']
                    ];
                } catch (Exception $innerEx) {
                    $conn->rollback();
                    throw $innerEx;
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Block check completed successfully.',
        'unblocked_agents' => $unblockedAgents,
        'debug' => $debugInfo
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to perform block check.',
        'details' => $e->getMessage()
    ]);
}
