<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$property_type = trim($_POST['property_type'] ?? '');

if (!$property_type) {
    echo json_encode(['success' => false, 'error' => 'Property type is required']);
    exit;
}

// Property-type fees
$fee_map = [
    'Condominium' => 50,
    'Apartment' => 40,
    'House' => 30,
    'Lot' => 20,
    'Land' => 20,
    'Commercial Space' => 60
];

$baseFee = $fee_map[$property_type] ?? 20;
$vat = round($baseFee * 0.12, 2);
$totalDeduction = round($baseFee + $vat, 2);

try {
    // Get agent wallet balance (no locking, no deduction)
    $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $agent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$agent) throw new Exception("Agent not found");

    echo json_encode([
        'success' => true,
        'base_fee' => $baseFee,
        'vat' => $vat,
        'total_deduction' => $totalDeduction,
        'wallet_balance' => (float)$agent['wallet_balance']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
