<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not found.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$noticeId = $data['id'] ?? null;

if (!$noticeId || !is_numeric($noticeId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid notice ID.']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM property_notices WHERE id = ?");
    $stmt->bind_param("i", $noticeId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Notice deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Notice not found or already deleted.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete notice.', 'details' => $e->getMessage()]);
}
