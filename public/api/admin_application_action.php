<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check admin login
if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Read input JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'], $input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = (int)$input['id'];
$action = $input['action'];

if (!in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$status = ($action === 'approve') ? 'approved' : 'rejected';

// Update query
$stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update application status']);
}
$stmt->close();
$conn->close();
