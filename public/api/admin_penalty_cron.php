<?php
// admin_penalty_cron.php
require_once __DIR__ . '/../app/bootstrap.php';

$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    die("Database connection not found.");
}

// Temporary durations in seconds
$durations = [
    '48hrs'  => 48 * 3600,
    '7days'  => 7 * 24 * 3600,
    '30days' => 30 * 24 * 3600,
    // lifetime is never auto-unblocked
];

try {
    $conn->begin_transaction();

    // Fetch all blocked reports with a duration
    $sql = "SELECT * FROM agent_reports 
            WHERE status = 'blocked' 
            AND duration IS NOT NULL 
            AND duration != 'lifetime'";
    $result = $conn->query($sql);

    $now = time();
    $unblockedAgents = [];

    while ($row = $result->fetch_assoc()) {
        $agentId   = $row['agent_id'];
        $createdAt = strtotime($row['created_at']);
        $duration  = $row['duration'];

        if (!isset($durations[$duration])) continue;

        $expiryTime = $createdAt + $durations[$duration];
        if ($now >= $expiryTime) {
            // Delete the report
            $stmt = $conn->prepare("DELETE FROM agent_reports WHERE id = ?");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();

            // Update the user as unblocked
            $stmt2 = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
            $stmt2->bind_param("i", $agentId);
            $stmt2->execute();

            $unblockedAgents[] = $agentId;
        }
    }

    $conn->commit();

    echo "[" . date('Y-m-d H:i:s') . "] Unblocked Agents: " . implode(', ', $unblockedAgents) . PHP_EOL;

} catch (Exception $e) {
    $conn->rollback();
    echo "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . PHP_EOL;
}
