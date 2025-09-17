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
$listingFee = 20; // PHP 20

// Start transaction
$conn->begin_transaction();

try {
    // Fetch agent wallet
    $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $agent = $res->fetch_assoc();
    $stmt->close();

    if (!$agent) throw new Exception("Agent not found");

    $agentBalance = (float)$agent['wallet_balance'];
    if ($agentBalance < $listingFee) {
        throw new Exception("Insufficient wallet balance");
    }

    // Deduct from agent
    $newAgentBalance = $agentBalance - $listingFee;
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->bind_param("di", $newAgentBalance, $user_id);
    $stmt->execute();
    $stmt->close();

    // Fetch admin user
    $stmt = $conn->prepare("SELECT id, wallet_balance FROM users WHERE user_type = 'admin' LIMIT 1 FOR UPDATE");
    $stmt->execute();
    $res = $stmt->get_result();
    $admin = $res->fetch_assoc();
    $stmt->close();

    if (!$admin) throw new Exception("Admin not found");

    // Add to admin
    $newAdminBalance = (float)$admin['wallet_balance'] + $listingFee;
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->bind_param("di", $newAdminBalance, $admin['id']);
    $stmt->execute();
    $stmt->close();

    // Record transaction for agent
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, property, amount, status, method, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $property = 'Listing Fee';
    $status = 'paid';
    $method = 'wallet';
    $stmt->bind_param("isdss", $user_id, $property, $listingFee, $status, $method);
    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'new_balance' => $newAgentBalance
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
