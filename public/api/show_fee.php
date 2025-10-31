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
$tier_plan = ucfirst(strtolower(trim($_POST['tier_plan'] ?? 'Basic')));

// Tier plan pricing
$tier_plans = [
    'Basic' => 399,
    'Standard' => 699,
    'Premium' => 1199,
    'Platinum' => 1799
];

$tierCost = $tier_plans[$tier_plan] ?? 399;

// -----------------------------
// VAT and Total
// -----------------------------
$vat = round($tierCost * 0.12, 2);
$totalDeduction = round($tierCost + $vat, 2);

try {
    // Get agent wallet balance
    $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $agent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$agent) throw new Exception("Agent not found");

    echo json_encode([
        'success' => true,
        'tier_plan' => $tier_plan,
        'base_fee' => $tierCost, // the tier itself is now the listing fee
        'vat' => $vat,
        'total_deduction' => $totalDeduction,
        'wallet_balance' => (float)$agent['wallet_balance']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
