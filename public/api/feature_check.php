<?php
/**
 * feature_check.php
 *
 * Checks all featured properties (all tiers) and unfeatures any that have expired.
 * Returns tier_plan for frontend badge display.
 */

require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');

try {
    // --- Find all expired featured properties (any tier) ---
    $query = "
        SELECT id, title, featured_until, is_featured, tier_plan
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

    // --- Unfeature expired properties ---
    if (!empty($expiredProperties)) {
        $updateQuery = "
            UPDATE properties
            SET is_featured = 0,
                featured_until = NULL,
                tier_plan = NULL
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
    }

    // --- Get all currently active featured properties ---
    $activeQuery = "
        SELECT id, title, featured_until, is_featured, tier_plan
        FROM properties
        WHERE is_featured = 1
    ";
    $activeStmt = $conn->prepare($activeQuery);
    $activeStmt->execute();
    $activeResult = $activeStmt->get_result();

    $featuredProperties = [];
    while ($row = $activeResult->fetch_assoc()) {
        $featuredProperties[] = $row;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Featured properties retrieved.',
        'expired' => $expiredProperties,
        'active' => $featuredProperties
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Error while checking featured properties: ' . $e->getMessage()
    ]);
}
