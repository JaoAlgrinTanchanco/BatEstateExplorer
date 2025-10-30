<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';
session_start();

// --- Auth check ---
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$agent_id    = $_SESSION['user_id'];
$property_id = $_POST['property_id'] ?? null;
$plan        = $_POST['plan'] ?? null;

if (!$property_id || !$plan) {
    echo json_encode(['error' => 'Missing required parameters.']);
    exit;
}

// --- Plan mapping ---
$PLAN_PACKAGES = [
    'basic'    => ['days' => 7,  'cost' => 499.00],
    'standard' => ['days' => 14, 'cost' => 899.00],
    'premium'  => ['days' => 30, 'cost' => 1499.00],
];

if (!array_key_exists($plan, $PLAN_PACKAGES)) {
    echo json_encode(['error' => 'Invalid plan selected.']);
    exit;
}

$duration = $PLAN_PACKAGES[$plan]['days'];
$cost     = $PLAN_PACKAGES[$plan]['cost'];

// --- Get agent wallet ---
$agent_stmt = $conn->prepare("SELECT id, wallet_balance FROM users WHERE id = ?");
$agent_stmt->execute([$agent_id]);
$agent = $agent_stmt->fetch(PDO::FETCH_ASSOC);

if (!$agent) {
    echo json_encode(['error' => 'Agent not found.']);
    exit;
}

// --- Get admin ---
$admin_stmt = $conn->prepare("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1");
$admin_stmt->execute();
$admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo json_encode(['error' => 'Admin account not found.']);
    exit;
}

// --- Check balance ---
if ($agent['wallet_balance'] < $cost) {
    echo json_encode(['error' => 'Insufficient wallet balance. Please top up first.']);
    exit;
}

// --- Check if property is already featured ---
$check_stmt = $conn->prepare("SELECT is_featured, featured_until FROM properties WHERE id = ?");
$check_stmt->execute([$property_id]);
$property = $check_stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    echo json_encode(['error' => 'Property not found.']);
    exit;
}

if ($property['is_featured'] && $property['featured_until'] && strtotime($property['featured_until']) > time()) {
    echo json_encode(['error' => 'This property is already featured.']);
    exit;
}

// --- Transaction start ---
$conn->beginTransaction();

try {
    // Deduct from agent
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
    $stmt->execute([$cost, $agent_id]);

    // Credit admin
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
    $stmt->execute([$cost, $admin['id']]);

    // Log transactions
    $trans_stmt = $conn->prepare("
        INSERT INTO transactions (user_id, property, amount, status, method)
        VALUES (?, ?, ?, 'completed', 'wallet')
    ");
    // Agent debit
    $trans_stmt->execute([$agent_id, "Property #$property_id Featured ($plan plan)", -$cost]);
    // Admin credit
    $trans_stmt->execute([$admin['id'], "Property #$property_id Featured ($plan plan)", $cost]);

    // --- Update property as featured ---
    $update_stmt = $conn->prepare("
        UPDATE properties
        SET is_featured = 1,
            featured_until = DATE_ADD(NOW(), INTERVAL ? DAY),
            updated_at = NOW()
        WHERE id = ?
    ");
    $update_stmt->execute([$duration, $property_id]);

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Property successfully featured!',
        'plan' => $plan,
        'duration_days' => $duration,
        'cost' => $cost
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
}
