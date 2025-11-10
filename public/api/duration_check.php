<?php
/**
 * duration_check.php
 *
 * Checks all properties' plan durations and deletes any that have expired.
 * Returns the list of deleted properties.
 */

require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');

try {
    // --- Get all properties with a non-zero plan_duration ---
    $query = "
        SELECT id, title, plan_duration, created_at
        FROM properties
        WHERE plan_duration > 0
    ";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $expiredProperties = [];

    while ($row = $result->fetch_assoc()) {
        $planEnd = date('Y-m-d H:i:s', strtotime($row['created_at'] . " +{$row['plan_duration']} days"));
        if (new DateTime($planEnd) < new DateTime()) {
            $expiredProperties[] = $row;
        }
    }

    // --- Delete expired properties ---
    if (!empty($expiredProperties)) {
        $deleteQuery = "DELETE FROM properties WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);

        if (!$deleteStmt) {
            throw new Exception("Failed to prepare delete query: " . $conn->error);
        }

        foreach ($expiredProperties as $prop) {
            $deleteStmt->bind_param('i', $prop['id']);
            $deleteStmt->execute();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Duration check completed.',
        'deleted_properties' => $expiredProperties
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Error while checking property durations: ' . $e->getMessage()
    ]);
}
