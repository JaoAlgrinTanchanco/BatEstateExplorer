<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in.']);
    exit;
}

$userId = $_SESSION['user_id'];
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid deposit amount.']);
    exit;
}

$conn->begin_transaction();

try {
    // Get user type (direct_agent or associate_agent)
    $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($userType);
    $stmt->fetch();
    $stmt->close();

    if (!$userType) {
        throw new Exception('User type not found.');
    }

    // Update wallet balance
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $userId);
    if (!$stmt->execute()) throw new Exception('Failed to update wallet balance.');
    $stmt->close();

    // Insert into transactions (log deposit)
    $stmt = $conn->prepare("
        INSERT INTO transactions (user_id, property, amount, status, method, created_at, user_type) 
        VALUES (?, ?, ?, ?, ?, NOW(), ?)
    ");
    $property = 'Deposit'; // Wallet top-up
    $status   = 'completed';
    $method   = 'PayPal';
    $stmt->bind_param("isdsss", $userId, $property, $amount, $status, $method, $userType);
    if (!$stmt->execute()) throw new Exception('Failed to log transaction.');
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
