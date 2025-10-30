<?php
/**
 * feature_check.php
 *
 * This script checks all featured properties and unfeatures any that have expired.
 * Can be run via cron job or triggered manually by an admin.
 */

require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');

try {
    // --- Find all expired featured properties ---
    $query = "
        SELECT id, title, featured_until
        FROM properties
        WHERE is_featured = 1 
          AND featured_until IS NOT NULL 
          AND featured_until < NOW()
    ";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $expiredProperties = [];
    while ($row = $result->fetch_assoc()) {
        $expiredProperties[] = $row;
    }

    if (empty($expiredProperties)) {
        echo json_encode([
            'success' => true,
            'message' => 'No expired featured properties found.'
        ]);
        exit;
    }

    // --- Unfeature expired properties ---
    $updateQuery = "
        UPDATE properties
        SET is_featured = 0, featured_until = NULL
        WHERE id = ?
    ";
    $updateStmt = $conn->prepare($updateQuery);

    if (!$updateStmt) {
        throw new Exception("Failed to prepare update query: " . $conn->error);
    }

    foreach ($expiredProperties as $prop) {
        $updateStmt->bind_param('i', $prop['id']);
        $updateStmt->execute();
    }

    echo json_encode([
        'success' => true,
        'message' => count($expiredProperties) . ' expired featured properties have been updated.',
        'expired' => $expiredProperties
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Error while checking featured properties: ' . $e->getMessage()
    ]);
}
