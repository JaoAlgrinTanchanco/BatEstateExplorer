<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/database.php';

header('Content-Type: application/json');

// --- Check if user is logged in ---
$is_logged_in = is_logged_in();
$current_user = null;
if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
}
if (!$is_logged_in || !$current_user) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only direct or associate agents can use this
if (!in_array($current_user['user_type'], ['direct_agent', 'associate_agent'])) {
    echo json_encode(['success' => false, 'error' => 'Only agents can delete properties']);
    exit;
}

$property_id = intval($_POST['property_id'] ?? 0);
if (!$property_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid property ID']);
    exit;
}

// 🔍 Get agent_id for current user
$query = "SELECT id FROM agents WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
$agent = $result->fetch_assoc();
$stmt->close();

if (!$agent) {
    echo json_encode(['success' => false, 'error' => 'Agent record not found']);
    exit;
}

$agent_id = $agent['id'];

// 🔍 Verify property belongs to this agent and is rejected
$query = "SELECT id FROM properties WHERE id = ? AND agent_id = ? AND status = 'rejected'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $property_id, $agent_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    echo json_encode(['success' => false, 'error' => 'Property not found or not rejected']);
    exit;
}

// 🗑 Delete property
$query = "DELETE FROM properties WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $property_id);

if ($stmt->execute()) {
    $response = ['success' => true, 'message' => 'Property deleted successfully'];
} else {
    $response = ['success' => false, 'error' => 'Failed to delete property'];
}
$stmt->close();

echo json_encode($response);
exit;
