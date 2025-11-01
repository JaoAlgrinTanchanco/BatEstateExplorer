<?php
// property_notice.php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not found.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$property_id = $data['property_id'] ?? null;
$owner_id    = $data['owner_id'] ?? null;
$agent_id    = $data['agent_id'] ?? null;
$notice_type = $data['notice_type'] ?? 'info';
$category    = $data['category'] ?? 'general';
$custom_msg  = trim($data['message'] ?? '');
$duration    = $data['duration'] ?? null;

// === Validate input ===
if (!$property_id || !$owner_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: property_id or owner_id.']);
    exit;
}

try {
    // 🔍 Get property title from the properties table
    $stmtProp = $conn->prepare("SELECT title FROM properties WHERE id = ?");
    $stmtProp->bind_param("i", $property_id);
    $stmtProp->execute();
    $result = $stmtProp->get_result();
    $property = $result->fetch_assoc();
    $stmtProp->close();

    if (!$property) {
        http_response_code(404);
        echo json_encode(['error' => 'Property not found.']);
        exit;
    }

    $property_title = $property['title'];

    // 📝 Construct message automatically if not provided
    $message = $custom_msg ?: sprintf(
        'Your property "%s" has been taken down due to "%s". This action cannot be undone.',
        $property_title,
        str_replace('_', ' ', $category)
    );

    // ✅ Insert property notice
    $stmt = $conn->prepare("
        INSERT INTO property_notices 
        (property_id, owner_id, agent_id, property_title, notice_type, category, message, duration, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)
    ");
    $stmt->bind_param(
        "iiisssss",
        $property_id,
        $owner_id,
        $agent_id,
        $property_title,
        $notice_type,
        $category,
        $message,
        $duration
    );
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Property notice created successfully.',
            'notice_id' => $stmt->insert_id,
            'property_title' => $property_title
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create property notice.']);
    }

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error.',
        'details' => $e->getMessage()
    ]);
}
