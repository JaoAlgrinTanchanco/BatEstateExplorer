<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/database.php';

header('Content-Type: application/json');

// --- Check if user is logged in and admin ---
$is_logged_in = is_logged_in();
$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_admin = ($current_user && $current_user['user_type'] === 'admin');
}

if (!$is_logged_in || !$is_admin) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// --- Validate request method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// --- Get POST data ---
$action = $_POST['action'] ?? '';
$property_id = intval($_POST['property_id'] ?? 0);

if (!$property_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$response = ['success' => false, 'error' => 'Unknown action'];

if ($action === 'approve') {
    $query = "UPDATE properties SET status = 'available' WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $property_id);

    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Property approved'];
    } else {
        $response = ['success' => false, 'error' => 'Failed to approve property'];
    }
    $stmt->close();

} elseif ($action === 'reject') {
    // ✅ Mark as rejected (kept in DB)
    $query = "UPDATE properties SET status = 'rejected' WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $property_id);

    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Property rejected'];
    } else {
        $response = ['success' => false, 'error' => 'Failed to reject property'];
    }
    $stmt->close();

} elseif ($action === 'remove') {
    // ✅ Admin can also force delete if needed
    $query = "DELETE FROM properties WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $property_id);

    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Property removed'];
    } else {
        $response = ['success' => false, 'error' => 'Failed to remove property'];
    }
    $stmt->close();
}

echo json_encode($response);
exit;
