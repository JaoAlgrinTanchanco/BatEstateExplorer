<?php
// save_property.php
session_start();
require_once __DIR__ . '/../app/bootstrap.php'; // DB connection etc.

header('Content-Type: application/json');

// --- Get user from session or token ---
$userId = $_SESSION['user']['id'] 
    ?? $_SESSION['user_id'] 
    ?? null;

// If no session, check user_token from POST
if (!$userId && isset($_POST['user_token'])) {
    $token = $_POST['user_token'];

    // Decode token (assuming base64 JSON; adjust if JWT)
    $decoded = json_decode(base64_decode($token), true);

    if ($decoded && isset($decoded['user_id'])) {
        $userId = (int)$decoded['user_id'];

        // Optional: store in session for later
        $_SESSION['user'] = [
            'id'    => $userId,
            'token' => $token,
            'type'  => $decoded['user_type'] ?? ''
        ];
    }
}

if (!$userId) {
    echo json_encode([
        'success' => false,
        'error'   => 'User not logged in.',
        'session' => $_SESSION
    ]);
    exit;
}

// --- Property ID & action ---
$propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : null;
$action     = $_POST['action'] ?? 'save';

if (!$propertyId) {
    echo json_encode(['success' => false, 'error' => 'No property specified.']);
    exit;
}

// --- Check property exists ---
$stmt = $conn->prepare("SELECT id FROM properties WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'Property does not exist.']);
    exit;
}
$stmt->close();

// --- Check if saved ---
$stmt = $conn->prepare("SELECT id FROM saved_properties WHERE user_id = ? AND property_id = ? LIMIT 1");
$stmt->bind_param("ii", $userId, $propertyId);
$stmt->execute();
$res = $stmt->get_result();
$alreadySaved = $res->num_rows > 0;
$stmt->close();

// --- Perform action ---
switch ($action) {
    case 'save':
        if ($alreadySaved) {
            echo json_encode(['success' => true, 'saved' => true, 'message' => 'Property already saved.']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO saved_properties (user_id, property_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $userId, $propertyId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'saved' => true, 'message' => 'Property saved successfully!']);
        break;

    case 'unsave':
        if (!$alreadySaved) {
            echo json_encode(['success' => true, 'saved' => false, 'message' => 'Property removed from saved list.']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM saved_properties WHERE user_id = ? AND property_id = ?");
        $stmt->bind_param("ii", $userId, $propertyId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'saved' => false, 'message' => 'Property removed from saved list.']);
        break;

    case 'check':
        echo json_encode(['success' => true, 'saved' => $alreadySaved]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
        break;
}

exit;
