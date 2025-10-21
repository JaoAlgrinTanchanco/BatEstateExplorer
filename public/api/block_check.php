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
// Helper function to convert duration strings to hours
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
            return 0; // Unknown or expired
    }
}

try {
    // --- Fetch all blocked users with active reports ---
    $sql = "
        SELECT ar.id AS report_id, ar.agent_id, ar.duration, ar.created_at,
               u.is_blocked, u.first_name, u.last_name
        FROM agent_reports ar
        INNER JOIN users u ON u.id = ar.agent_id
        WHERE u.is_blocked = 1 AND ar.status = 'blocked'
    ";

    $result = $conn->query($sql);

    $unblockedAgents = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $durationHours = getDurationHours($row['duration']);
            if ($durationHours === PHP_INT_MAX) continue; // Skip lifetime bans

            $blockedAt = new DateTime($row['created_at']);
            $now = new DateTime('now');
            $diffHours = ($now->getTimestamp() - $blockedAt->getTimestamp()) / 3600;

            // If the penalty duration has expired
            if ($diffHours >= $durationHours) {
                // --- Unblock the agent ---
                $stmt1 = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
                $stmt1->bind_param("i", $row['agent_id']);
                $stmt1->execute();

                // --- Delete the report record ---
                $stmt2 = $conn->prepare("DELETE FROM agent_reports WHERE id = ?");
                $stmt2->bind_param("i", $row['report_id']);
                $stmt2->execute();

                $unblockedAgents[] = [
                    'agent_id' => $row['agent_id'],
                    'name' => "{$row['first_name']} {$row['last_name']}",
                    'duration' => $row['duration'],
                    'expired_after_hours' => round($diffHours)
                ];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Block check completed.',
        'unblocked_agents' => $unblockedAgents
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to perform block check.',
        'details' => $e->getMessage()
    ]);
}
