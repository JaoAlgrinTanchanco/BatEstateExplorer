<?php
// check_result.php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Validate DB connection
$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not found.']);
    exit;
}

// ✅ Get logged-in user ID
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(403);
    echo json_encode(['error' => 'User not logged in.']);
    exit;
}

try {
    // === Step 1: Fetch all unread notices for this user's applications ===
    $query = "
        SELECT 
            n.id,
            n.application_id,
            n.notice_type,
            n.message,
            n.is_seen,
            n.created_at
        FROM reg_notices n
        INNER JOIN applications a ON n.application_id = a.id
        WHERE a.user_id = ?
        ORDER BY n.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $notices = [];
    while ($row = $result->fetch_assoc()) {
        $notices[] = [
            'notice_id'       => (int)$row['id'],
            'application_id'  => (int)$row['application_id'],
            'notice_type'     => $row['notice_type'],
            'message'         => $row['message'],
            'is_seen'         => (bool)$row['is_seen'],
            'created_at'      => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'count'   => count($notices),
        'notices' => $notices
    ]);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to retrieve registration notices.',
        'details' => $e->getMessage()
    ]);
}
