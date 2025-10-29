<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'notices' => []]);
    exit;
}

try {
    // Fetch all unseen notices that:
    // 1. Belong to this user (user_id = ?)
    // 2. OR are global (user_id IS NULL) but not excluded (notice_except != ?)
    $stmt = $conn->prepare("
        SELECT id, message, company_prop_id
        FROM notices
        WHERE isseen = 0
          AND (
              user_id = ?
              OR (user_id IS NULL AND (notice_except IS NULL OR notice_except != ?))
          )
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $notices = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'success' => true,
        'notices' => $notices
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'notices' => []
    ]);
}

$conn->close();
?>
