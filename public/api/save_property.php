<?php
// save_property.php
session_start();
require_once __DIR__ . '/../app/bootstrap.php'; // adjust path to your bootstrap

header('Content-Type: application/json');

// ✅ Get logged-in user from session
$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'User not logged in.']);
    exit;
}

// ✅ Get property ID and action
$propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : null;
$action = $_POST['action'] ?? 'save';

if (!$propertyId) {
    echo json_encode(['success' => false, 'error' => 'No property specified.']);
    exit;
}

// ✅ Check if property exists
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

// ✅ Check if property is already saved
$stmt = $conn->prepare("SELECT id FROM saved_properties WHERE user_id = ? AND property_id = ? LIMIT 1");
$stmt->bind_param("ii", $userId, $propertyId);
$stmt->execute();
$res = $stmt->get_result();
$alreadySaved = $res->num_rows > 0;
$stmt->close();

// ✅ Perform requested action
switch ($action) {
    case 'save':
        if ($alreadySaved) {
            echo json_encode(['success' => true, 'saved' => true, 'message' => 'Property already saved.']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO saved_properties (user_id, property_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $userId, $propertyId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'saved' => true, 'message' => 'Property saved successfully!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save property.']);
        }
        $stmt->close();
        break;

    case 'unsave':
        if (!$alreadySaved) {
            echo json_encode(['success' => true, 'saved' => false, 'message' => 'Property not saved.']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM saved_properties WHERE user_id = ? AND property_id = ?");
        $stmt->bind_param("ii", $userId, $propertyId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'saved' => false, 'message' => 'Property removed from saved list.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to remove saved property.']);
        }
        $stmt->close();
        break;

    case 'check':
        echo json_encode(['success' => true, 'saved' => $alreadySaved]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
        break;
}

exit;
