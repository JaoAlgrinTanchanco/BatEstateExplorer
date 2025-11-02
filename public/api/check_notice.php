<?php
// check_notice.php
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
    // === Step 1: Fetch all unread notices for this owner ===
    $query = "
        SELECT 
            n.id,
            n.property_id,
            p.title AS property_title,
            n.agent_id,
            n.notice_type,
            n.category,
            n.message,
            n.duration,
            n.is_read,
            n.created_at
        FROM property_notices n
        LEFT JOIN properties p ON n.property_id = p.id
        WHERE n.owner_id = ?
        AND n.is_read = 0
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
            'property_id'     => (int)$row['property_id'],
            'property_title'  => $row['property_title'] ?? '(Deleted Property)',
            'agent_id'        => (int)$row['agent_id'],
            'notice_type'     => $row['notice_type'],
            'category'        => $row['category'],
            'message'         => $row['message'],
            'duration'        => $row['duration'],
            'is_read'         => (bool)$row['is_read'],
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
        'error' => 'Failed to retrieve property notices.',
        'details' => $e->getMessage()
    ]);
}
