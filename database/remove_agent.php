<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
session_start();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['agentId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing agentId']);
    exit;
}

$agentId = intval($input['agentId']);

require __DIR__ . '/../config/database.php';


$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $agentId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Agent not found']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$stmt->close();
$conn->close();
