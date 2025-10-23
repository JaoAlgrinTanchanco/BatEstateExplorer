<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Only admin can fetch
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT t.amount, t.created_at, u.first_name, u.last_name
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.property = 'Listing Fee'
        ORDER BY t.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $res = $stmt->get_result();

    $transactions = [];
    while ($row = $res->fetch_assoc()) {
        $transactions[] = [
            'agent_name' => $row['first_name'] . ' ' . $row['last_name'],
            'amount' => $row['amount'],
            'datetime' => date('d/m • h:i A', strtotime($row['created_at']))
        ];
    }
    $stmt->close();

    echo json_encode(['success' => true, 'transactions' => $transactions]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
