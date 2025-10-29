<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'notices' => []]);
    exit;
}

$stmt = $conn->prepare("SELECT id, message, company_prop_id FROM notices WHERE user_id = ? AND isseen = 0 ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$notices = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['success' => true, 'notices' => $notices]);
$conn->close();
