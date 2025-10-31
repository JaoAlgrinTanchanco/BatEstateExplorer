<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$agent_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$property_id = $input['property_id'] ?? null;
$plan = $input['plan'] ?? null;

if (!$property_id || !$plan) {
    echo json_encode(['error' => 'Missing required parameters.']);
    exit;
}

$PLAN_PACKAGES = [
    'basic'    => ['days' => 30,  'cost' => 399.00,  'rank' => 1],
    'standard' => ['days' => 45,  'cost' => 699.00,  'rank' => 2],
    'premium'  => ['days' => 60,  'cost' => 1199.00, 'rank' => 3],
    'platinum' => ['days' => 90,  'cost' => 1799.00, 'rank' => 4],
];

if (!array_key_exists($plan, $PLAN_PACKAGES)) {
    echo json_encode(['error' => 'Invalid plan selected.']);
    exit;
}

$duration = $PLAN_PACKAGES[$plan]['days'];
$cost     = $PLAN_PACKAGES[$plan]['cost'];
$new_rank = $PLAN_PACKAGES[$plan]['rank'];

// --- Get agent wallet ---
$agent_stmt = $conn->prepare("SELECT id, wallet_balance FROM users WHERE id = ?");
$agent_stmt->bind_param("i", $agent_id);
$agent_stmt->execute();
$agent = $agent_stmt->get_result()->fetch_assoc();
$agent_stmt->close();

if (!$agent) {
    echo json_encode(['error' => 'Agent not found.']);
    exit;
}

// --- Get admin ---
$admin_stmt = $conn->prepare("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1");
$admin_stmt->execute();
$admin = $admin_stmt->get_result()->fetch_assoc();
$admin_stmt->close();

if (!$admin) {
    echo json_encode(['error' => 'Admin account not found.']);
    exit;
}

// --- Check balance ---
if ($agent['wallet_balance'] < $cost) {
    echo json_encode(['error' => 'Insufficient wallet balance. Please top up first.']);
    exit;
}

// --- Get property info ---
$check_stmt = $conn->prepare("SELECT is_featured, featured_until, tier_plan FROM properties WHERE id = ?");
$check_stmt->bind_param("i", $property_id);
$check_stmt->execute();
$property = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if (!$property) {
    echo json_encode(['error' => 'Property not found.']);
    exit;
}

// --- Prevent upgrading Platinum ---
$current_rank = isset($property['tier_plan']) && array_key_exists(strtolower($property['tier_plan']), $PLAN_PACKAGES)
    ? $PLAN_PACKAGES[strtolower($property['tier_plan'])]['rank']
    : 0;

if ($current_rank === 4) { // Platinum
    echo json_encode(['error' => 'Platinum featured properties cannot be upgraded.']);
    exit;
}

// --- Prevent downgrade ---
if ($new_rank <= $current_rank) {
    echo json_encode(['error' => 'You can only upgrade to a higher plan.']);
    exit;
}

// --- Transaction start ---
$conn->begin_transaction();

try {
    // Deduct agent wallet
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
    $stmt->bind_param("di", $cost, $agent_id);
    $stmt->execute();
    $stmt->close();

    // Credit admin wallet
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
    $stmt->bind_param("di", $cost, $admin['id']);
    $stmt->execute();
    $stmt->close();

    // Log transactions
    $trans_stmt = $conn->prepare("
        INSERT INTO transactions (user_id, property, amount, status, method)
        VALUES (?, ?, ?, 'completed', 'wallet')
    ");
    $desc = "Property #$property_id Featured/Upgrade ($plan plan)";

    $neg_cost = -$cost;
    $trans_stmt->bind_param("isd", $agent_id, $desc, $neg_cost);
    $trans_stmt->execute();

    $trans_stmt->bind_param("isd", $admin['id'], $desc, $cost);
    $trans_stmt->execute();
    $trans_stmt->close();

    // Update property featured info
    $update_stmt = $conn->prepare("
        UPDATE properties
        SET is_featured = 1,
            tier_plan = ?,
            featured_until = DATE_ADD(NOW(), INTERVAL ? DAY),
            updated_at = NOW()
        WHERE id = ?
    ");
    $update_stmt->bind_param("sii", $plan, $duration, $property_id);
    $update_stmt->execute();
    $update_stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Property successfully boosted/upgraded!',
        'plan' => $plan,
        'duration_days' => $duration,
        'cost' => $cost
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
}
