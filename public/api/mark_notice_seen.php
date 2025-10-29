<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$notice_id = (int)($data['id'] ?? 0);

if (!$notice_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid notice ID']);
    exit;
}

$stmt = $conn->prepare("UPDATE notices SET isseen = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notice_id, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
$conn->close();
