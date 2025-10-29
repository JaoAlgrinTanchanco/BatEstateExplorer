<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

try {
    // --- Mark as seen for this user's relevant notice ---
    // Can be:
    // 1. A direct user notice (user_id = this user)
    // 2. A global notice (user_id IS NULL) but not excluded for this user
    $stmt = $conn->prepare("
        UPDATE notices
        SET isseen = 1
        WHERE id = ?
          AND (
              user_id = ?
              OR (user_id IS NULL AND (notice_except IS NULL OR notice_except != ?))
          )
    ");
    $stmt->bind_param("iii", $notice_id, $user_id, $user_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'success' => $affected > 0
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
