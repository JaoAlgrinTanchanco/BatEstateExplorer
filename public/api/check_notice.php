<?php
// check_notice.php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

// ✅ Start session safely
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

// ✅ Logged-in user ID
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(403);
    echo json_encode(['error' => 'User not logged in.']);
    exit;
}

try {
    // === Step 1: Get all properties that belong to this user and are reported ===
    $prop_stmt = $conn->prepare("
        SELECT id, title 
        FROM properties 
        WHERE user_id = ? 
        AND is_reported = 1
    ");
    $prop_stmt->bind_param("i", $user_id);
    $prop_stmt->execute();
    $props_result = $prop_stmt->get_result();

    $property_ids = [];
    $property_titles = [];

    while ($row = $props_result->fetch_assoc()) {
        $property_ids[] = (int)$row['id'];
        $property_titles[$row['id']] = $row['title'];
    }

    // 🟡 No reported properties
    if (empty($property_ids)) {
        echo json_encode([
            'success' => true,
            'notices' => [],
            'message' => 'No reported properties found.'
        ]);
        exit;
    }

    // === Step 2: Fetch unseen notices for those properties ===
    $placeholders = implode(',', array_fill(0, count($property_ids), '?'));
    $types = str_repeat('i', count($property_ids));

    $query = "
        SELECT 
            id, property_id, agent_id, notice_type, 
            category, message, duration, isseen, created_at
        FROM property_notices
        WHERE property_id IN ($placeholders)
        AND isseen = 0
        ORDER BY created_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$property_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $notices = [];
    while ($row = $result->fetch_assoc()) {
        $pid = (int)$row['property_id'];
        $notices[] = [
            'id' => (int)$row['id'],
            'property_id' => $pid,
            'property_title' => $property_titles[$pid] ?? '(Untitled Property)',
            'agent_id' => (int)$row['agent_id'],
            'notice_type' => $row['notice_type'],
            'category' => $row['category'],
            'message' => $row['message'],
            'duration' => $row['duration'],
            'isseen' => (bool)$row['isseen'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'notices' => $notices,
        'count' => count($notices)
    ]);

    $stmt->close();
    $prop_stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to retrieve property notices.',
        'details' => $e->getMessage()
    ]);
}
