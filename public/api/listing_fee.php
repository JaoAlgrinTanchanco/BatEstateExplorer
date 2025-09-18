<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// ✅ Ensure user logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$listingFee = 20; // PHP 20

// Begin DB transaction
$conn->begin_transaction();

try {
    // 🔹 Fetch agent/direct wallet (lock row)
    $stmt = $conn->prepare("SELECT wallet_balance, user_type FROM users WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        throw new Exception("User not found");
    }

    $currentBalance = (float)$user['wallet_balance'];
    $userType = $user['user_type']; // 'associate' or 'direct'

    if ($currentBalance < $listingFee) {
        throw new Exception("Insufficient wallet balance");
    }

    // 🔹 Deduct fee from user
    $newBalance = $currentBalance - $listingFee;
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->bind_param("di", $newBalance, $user_id);
    $stmt->execute();
    $stmt->close();

    // 🔹 Fetch admin wallet (lock row)
    $stmt = $conn->prepare("SELECT id, wallet_balance FROM users WHERE user_type = 'admin' LIMIT 1 FOR UPDATE");
    $stmt->execute();
    $res = $stmt->get_result();
    $admin = $res->fetch_assoc();
    $stmt->close();

    if (!$admin) {
        throw new Exception("Admin not found");
    }

    // 🔹 Credit fee to admin
    $newAdminBalance = (float)$admin['wallet_balance'] + $listingFee;
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->bind_param("di", $newAdminBalance, $admin['id']);
    $stmt->execute();
    $stmt->close();

    // 🔹 Record transaction for user
    $property = 'Listing Fee';
    $amount   = $listingFee;
    $status   = 'completed';
    $method   = 'wallet';

    $stmt = $conn->prepare("
        INSERT INTO transactions (user_id, property, amount, status, method, created_at, remarks) 
        VALUES (?, ?, ?, ?, ?, NOW(), ?)
    ");
    $remarks = ucfirst($userType) . " listing fee";
    $stmt->bind_param("isdsss", $user_id, $property, $amount, $status, $method, $remarks);
    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'new_balance' => $newBalance,
        'user_type' => $userType
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
