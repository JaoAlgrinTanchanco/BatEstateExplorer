<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    $balance = (float) $row['wallet_balance'];

    echo json_encode([
        'success' => true,
        // raw numeric value (useful for calculations in JS)
        'wallet_balance_raw' => $balance,
        // formatted string with 2 decimals and thousands separators
        'wallet_balance_formatted' => number_format($balance, 2)
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
?>
