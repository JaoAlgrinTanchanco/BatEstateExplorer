<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

// Start session only if not active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Auth check ---
if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$agent_id = $_SESSION['user_id'];

// --- Parse JSON body ---
$input = json_decode(file_get_contents('php://input'), true);
$property_id = $input['property_id'] ?? null;
$plan = $input['plan'] ?? null;

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
$agent_stmt->bind_param("i", $agent_id);
$agent_stmt->execute();
$agent_result = $agent_stmt->get_result();
$agent = $agent_result->fetch_assoc();
$agent_stmt->close();

if (!$agent) {
    echo json_encode(['error' => 'Agent not found.']);
    exit;
}

// --- Get admin ---
$admin_stmt = $conn->prepare("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1");
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin = $admin_result->fetch_assoc();
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

// --- Check if property is already featured ---
$check_stmt = $conn->prepare("SELECT is_featured, featured_until FROM properties WHERE id = ?");
$check_stmt->bind_param("i", $property_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$property = $check_result->fetch_assoc();
$check_stmt->close();

if (!$property) {
    echo json_encode(['error' => 'Property not found.']);
    exit;
}

if ($property['is_featured'] && $property['featured_until'] && strtotime($property['featured_until']) > time()) {
    echo json_encode(['error' => 'This property is already featured.']);
    exit;
}

// --- Transaction start ---
$conn->begin_transaction();

try {
    // Deduct from agent
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
    $stmt->bind_param("di", $cost, $agent_id);
    $stmt->execute();
    $stmt->close();

    // Credit admin
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
    $stmt->bind_param("di", $cost, $admin['id']);
    $stmt->execute();
    $stmt->close();

    // Log transactions
    $trans_stmt = $conn->prepare("
        INSERT INTO transactions (user_id, property, amount, status, method)
        VALUES (?, ?, ?, 'completed', 'wallet')
    ");

    $desc = "Property #$property_id Featured ($plan plan)";

    // Agent debit
    $neg_cost = -$cost;
    $trans_stmt->bind_param("isd", $agent_id, $desc, $neg_cost);
    $trans_stmt->execute();

    // Admin credit
    $trans_stmt->bind_param("isd", $admin['id'], $desc, $cost);
    $trans_stmt->execute();
    $trans_stmt->close();

    // --- Update property as featured ---
    $update_stmt = $conn->prepare("
        UPDATE properties
        SET is_featured = 1,
            featured_until = DATE_ADD(NOW(), INTERVAL ? DAY),
            updated_at = NOW()
        WHERE id = ?
    ");
    $update_stmt->bind_param("ii", $duration, $property_id);
    $update_stmt->execute();
    $update_stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Property successfully featured!',
        'plan' => $plan,
        'duration_days' => $duration,
        'cost' => $cost
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
}
