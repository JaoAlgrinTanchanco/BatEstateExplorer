<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$draftId = intval($data['id'] ?? 0);

if (!$draftId) {
    echo json_encode(['success' => false, 'error' => 'Invalid draft ID']);
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM property_drafts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $draftId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
