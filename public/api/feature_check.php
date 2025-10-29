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
    $stmt = $conn->prepare("
        SELECT id, title, featured_until
        FROM properties
        WHERE is_featured = 1 AND featured_until IS NOT NULL AND featured_until < NOW()
    ");
    $stmt->execute();
    $expiredProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($expiredProperties)) {
        echo json_encode(['success' => true, 'message' => 'No expired featured properties found.']);
        exit;
    }

    // --- Unfeature them ---
    $updateStmt = $conn->prepare("
        UPDATE properties
        SET is_featured = 0, featured_until = NULL
        WHERE id = ?
    ");

    foreach ($expiredProperties as $prop) {
        $updateStmt->execute([$prop['id']]);
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
