<?php
// ======================================
// block_check.php
// Periodic cleanup of expired penalties
// ======================================
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not found.']);
    exit;
}

// ===========================
// Helper: Convert duration strings to hours
// ===========================
function getDurationHours($duration) {
    switch (strtolower(trim($duration))) {
        case '48hrs':
        case '48 hours':
            return 48;
        case '7days':
        case '7 days':
            return 7 * 24;
        case 'lifetime':
            return PHP_INT_MAX; // Never expires
        default:
            return 0; // Unknown or expired immediately
    }
}

try {
    // ===========================
    // Fetch all blocked users with active "blocked" reports
    // ===========================
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

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            $durationHours = getDurationHours($row['duration']);
            if ($durationHours === PHP_INT_MAX) continue; // Skip lifetime bans

            // --- Calculate time difference (in hours) since blockage_date ---
            $blockedAt = new DateTime($row['blockage_date'], new DateTimeZone('UTC'));
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $diffHours = ($now->getTimestamp() - $blockedAt->getTimestamp()) / 3600;

            // --- Check if penalty duration has expired ---
            if ($diffHours >= $durationHours) {

                $conn->begin_transaction();

                try {
                    // 1. Unblock agent in users table
                    $stmt1 = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
                    $stmt1->bind_param("i", $row['agent_id']);
                    $stmt1->execute();

                    // 2. Mark report as 'expired' instead of deleting
                    $stmt2 = $conn->prepare("
                        UPDATE agent_reports 
                        SET status = 'expired', blockage_date = NULL 
                        WHERE id = ?
                    ");
                    $stmt2->bind_param("i", $row['report_id']);
                    $stmt2->execute();

                    $conn->commit();

                    // Record unblocked agent for API output
                    $unblockedAgents[] = [
                        'agent_id' => $row['agent_id'],
                        'name' => "{$row['first_name']} {$row['last_name']}",
                        'duration' => $row['duration'],
                        'expired_after_hours' => round($diffHours),
                        'blocked_at' => $row['blockage_date']
                    ];

                } catch (Exception $innerEx) {
                    $conn->rollback();
                    throw $innerEx;
                }
            }
        }
    }

    // ===========================
    // Final JSON Response
    // ===========================
    echo json_encode([
        'success' => true,
        'message' => 'Block check completed successfully.',
        'unblocked_agents' => $unblockedAgents
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to perform block check.',
        'details' => $e->getMessage()
    ]);
}
