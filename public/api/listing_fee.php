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

$conn->begin_transaction();

try {
    // Lock agent wallet
    $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $agent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$agent) throw new Exception("Agent not found");
    $agentBalance = (float)$agent['wallet_balance'];

    if ($agentBalance < $totalDeduction) {
        throw new Exception("Insufficient wallet balance. Required: PHP $totalDeduction");
    }

    // Deduct from agent
    $newAgentBalance = $agentBalance - $totalDeduction;
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->bind_param("di", $newAgentBalance, $user_id);
    $stmt->execute();
    $stmt->close();

    // Fetch admin
    $stmt = $conn->prepare("SELECT id, wallet_balance FROM users WHERE user_type = 'admin' LIMIT 1 FOR UPDATE");
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$admin) throw new Exception("Admin not found");

    // Add total to admin
    $newAdminBalance = (float)$admin['wallet_balance'] + $totalDeduction;
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->bind_param("di", $newAdminBalance, $admin['id']);
    $stmt->execute();
    $stmt->close();

    // Record transaction
    $property = "Listing Fee ({$property_type})";
    $status = 'completed';
    $method = 'wallet';

    $stmt = $conn->prepare("
        INSERT INTO transactions (user_id, property, amount, status, method, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isdss", $user_id, $property, $totalDeduction, $status, $method);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'new_balance' => $newAgentBalance,
        'base_fee' => $baseFee,
        'vat' => $vat,
        'total_deduction' => $totalDeduction
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
