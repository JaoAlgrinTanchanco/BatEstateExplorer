<?php
// save_property.php
session_start();
require_once __DIR__ . '/../app/bootstrap.php'; // DB connection etc.

header('Content-Type: application/json');

// --- Get user from session or token ---
$userId = $_SESSION['user']['id'] 
    ?? $_SESSION['user_id'] 
    ?? null;

if (!$userId && isset($_POST['user_token'])) {
    $token = $_POST['user_token'];
    $decoded = json_decode(base64_decode($token), true);

    if ($decoded && isset($decoded['user_id'])) {
        $userId = (int)$decoded['user_id'];
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

// --- Detect agent type using users.user_type ---
$agentType = 'direct'; // default
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if ($row['user_type'] === 'associate_agent') {
        $agentType = 'associate';
    }
}
$stmt->close();

// --- Property ID & action ---
$propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : null;
$action     = $_POST['action'] ?? 'save';

if (!$propertyId) {
    echo json_encode([
        'success' => false,
        'error'   => 'No property specified.',
        'redirect'=> buildRedirect($agentType, 'my_listings')
    ]);
    exit;
}

// --- Check property exists ---
$stmt = $conn->prepare("SELECT id FROM properties WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $stmt->close();
    echo json_encode([
        'success' => false,
        'error'   => 'Property does not exist.',
        'redirect'=> buildRedirect($agentType, 'my_listings')
    ]);
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
            echo json_encode([
                'success' => true,
                'saved'   => true,
                'message' => 'Property already saved.',
                'redirect'=> buildRedirect($agentType, 'saved')
            ]);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO saved_properties (user_id, property_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $userId, $propertyId);
        $stmt->execute();
        $stmt->close();
        echo json_encode([
            'success' => true,
            'saved'   => true,
            'message' => 'Property saved successfully!',
            'redirect'=> buildRedirect($agentType, 'saved')
        ]);
        break;

    case 'unsave':
        if (!$alreadySaved) {
            echo json_encode([
                'success' => true,
                'saved'   => false,
                'message' => 'Property removed from saved list.',
                'redirect'=> buildRedirect($agentType, 'saved')
            ]);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM saved_properties WHERE user_id = ? AND property_id = ?");
        $stmt->bind_param("ii", $userId, $propertyId);
        $stmt->execute();
        $stmt->close();
        echo json_encode([
            'success' => true,
            'saved'   => false,
            'message' => 'Property removed from saved list.',
            'redirect'=> buildRedirect($agentType, 'saved')
        ]);
        break;

    case 'check':
        echo json_encode([
            'success' => true,
            'saved'   => $alreadySaved,
            'redirect'=> buildRedirect($agentType, 'saved')
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'error'   => 'Invalid action.',
            'redirect'=> buildRedirect($agentType, 'saved')
        ]);
        break;
}

exit;

// ================================
// Helpers
// ================================
function buildRedirect($agentType, $tab = 'my_listings') {
    $view = $agentType === 'associate' ? 'associate_profile' : 'direct_profile';
    return "http://localhost/BatEstateExplorer/public/controllers/agent_dashboard.php?view={$view}&tab={$tab}";
}
