<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$property_type = trim($_POST['property_type'] ?? '');
$tier_plan = trim($_POST['tier_plan'] ?? 'Basic');
$listing_id = intval($_POST['listing_id'] ?? 0);

if (!$property_type || !$listing_id) {
    echo json_encode(['success' => false, 'error' => 'Missing property type or listing ID']);
    exit;
}

// -----------------------------
// Tier plan configuration
// -----------------------------
$tier_plans = [
    'Basic' => ['price' => 399, 'duration' => 30, 'is_featured' => 0],
    'Standard' => ['price' => 699, 'duration' => 45, 'is_featured' => 0],
    'Premium' => ['price' => 1199, 'duration' => 60, 'is_featured' => 1],
    'Platinum' => ['price' => 1799, 'duration' => 90, 'is_featured' => 1],
];

// Default fallback
$tier = $tier_plans[$tier_plan] ?? $tier_plans['Basic'];
$tierCost = $tier['price'];
$tierDuration = $tier['duration'];
$isFeatured = $tier['is_featured'];

// -----------------------------
// Tier Cost = Listing Fee
// VAT applies to tier cost only
// -----------------------------
$baseFee = $tierCost;
$vat = round($tierCost * 0.12, 2);
$totalDeduction = round($tierCost + $vat, 2);

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

    // Deduct from agent wallet
    $newAgentBalance = $agentBalance - $totalDeduction;
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->bind_param("di", $newAgentBalance, $user_id);
    $stmt->execute();
    $stmt->close();

    // Lock admin wallet
    $stmt = $conn->prepare("SELECT id, wallet_balance FROM users WHERE user_type = 'admin' LIMIT 1 FOR UPDATE");
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$admin) throw new Exception("Admin not found");

    // Add total to admin wallet
    $newAdminBalance = (float)$admin['wallet_balance'] + $totalDeduction;
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->bind_param("di", $newAdminBalance, $admin['id']);
    $stmt->execute();
    $stmt->close();

    // Record transaction
    $property = "Listing Fee ({$property_type}, {$tier_plan} Plan)";
    $status = 'completed';
    $method = 'wallet';

    $stmt = $conn->prepare("
        INSERT INTO transactions (user_id, property, amount, status, method, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isdss", $user_id, $property, $totalDeduction, $status, $method);
    $stmt->execute();
    $stmt->close();

    // -----------------------------
    // Update the property record
    // -----------------------------
    $featuredUntil = date('Y-m-d H:i:s', strtotime("+{$tierDuration} days"));
    $stmt = $conn->prepare("
        UPDATE properties
        SET is_featured = ?, featured_until = ?, tier_plan = ?, plan_duration = ?
        WHERE id = ?
    ");
    $stmt->bind_param("issii", $isFeatured, $featuredUntil, $tier_plan, $tierDuration, $listing_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'new_balance' => $newAgentBalance,
        'base_fee' => $baseFee,
        'vat' => $vat,
        'tier_cost' => $tierCost,
        'total_deduction' => $totalDeduction,
        'is_featured' => $isFeatured,
        'featured_until' => $featuredUntil,
        'plan_duration' => $tierDuration
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
