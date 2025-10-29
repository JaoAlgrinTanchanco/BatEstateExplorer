<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$agent_id    = $_SESSION['user_id'];
$property_id = $_POST['property_id'] ?? null;
$duration    = (int)($_POST['duration_days'] ?? 0);

if (!$property_id || !$duration) {
    echo json_encode(['error' => 'Missing required parameters.']);
    exit;
}

// --- Pricing tiers ---
$FEATURE_PACKAGES = [
    7  => 499.00,
    14 => 899.00,
    30 => 1499.00
];

if (!array_key_exists($duration, $FEATURE_PACKAGES)) {
    echo json_encode(['error' => 'Invalid duration selected.']);
    exit;
}

$cost = $FEATURE_PACKAGES[$duration];

// --- Fetch agent and admin info ---
$agent_stmt = $conn->prepare("SELECT id, wallet_balance FROM users WHERE id = ?");
$agent_stmt->execute([$agent_id]);
$agent = $agent_stmt->fetch(PDO::FETCH_ASSOC);

if (!$agent) {
    echo json_encode(['error' => 'Agent not found.']);
    exit;
}

$admin_stmt = $conn->prepare("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1");
$admin_stmt->execute();
$admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo json_encode(['error' => 'Admin account not found.']);
    exit;
}

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

// --- Begin transaction ---
$conn->beginTransaction();

try {
    // Deduct from agent
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
    $stmt->execute([$cost, $agent_id]);

    // Add to admin
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
    $stmt->execute([$cost, $admin['id']]);

    // Log transactions
    $trans_stmt = $conn->prepare("
        INSERT INTO transactions (user_id, property, amount, status, method)
        VALUES (?, ?, ?, 'completed', 'wallet')
    ");
    // Agent debit
    $trans_stmt->execute([$agent_id, "Property #$property_id", -$cost]);
    // Admin credit
    $trans_stmt->execute([$admin['id'], "Property #$property_id", $cost]);

    // Update property as featured
    $update_stmt = $conn->prepare("
        UPDATE properties
        SET is_featured = 1, featured_until = DATE_ADD(NOW(), INTERVAL ? DAY)
        WHERE id = ?
    ");
    $update_stmt->execute([$duration, $property_id]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Property successfully featured.']);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
}
