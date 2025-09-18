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

// Start transaction
$conn->begin_transaction();

try {
    // Update wallet_balance
    $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $userId);
    if (!$stmt->execute()) throw new Exception('Failed to update wallet balance.');
    $stmt->close();

    // Insert into transactions table
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, property, amount, status, method, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $property = 'Deposit'; // Since this is a wallet top-up
    $status   = 'completed';
    $method   = 'PayPal';
    $stmt->bind_param("isdss", $userId, $property, $amount, $status, $method);
    if (!$stmt->execute()) throw new Exception('Failed to log transaction.');
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
