<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Only admin can fetch total listing fees
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Optionally, check if the user is admin
$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT SUM(amount) AS total_listing_fees
        FROM transactions
        WHERE property = 'Listing Fee'
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $total = $row['total_listing_fees'] ?? 0;
    
    // Convert to positive value (admin balance)
    $total = abs($total);

    echo json_encode([
        'success' => true,
        'total_listing_fees' => number_format($total, 2)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
