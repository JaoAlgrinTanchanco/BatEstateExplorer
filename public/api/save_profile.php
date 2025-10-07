<?php
session_start();
require_once __DIR__ . '/../app/bootstrap.php';

// Ensure user is logged in
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    setNotification('error', 'User not logged in.');
    redirectWithAgentType('overview');
}

// Fetch user record
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    setNotification('error', 'User not found.');
    redirectWithAgentType('overview');
}

// ==========================
// Collect and validate POST data
// ==========================
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$address   = trim($_POST['address'] ?? '');
$removePfp = isset($_POST['remove_picture']) && $_POST['remove_picture'] === '1';

if (!$firstName || !$lastName) {
    setNotification('error', 'First name and last name are required.');
    redirectWithAgentType('overview');
}

// ==========================
// Profile image handling
// ==========================
$basePath   = 'C:/xampp/htdocs/BatEstateExplorer/';
$uploadDir  = $basePath . 'storage/uploads/profile_images/';
$profileImagePath = $user['profile_image_path']; // Default to current image

// --- Remove old image if user requested it ---
if ($removePfp && !empty($user['profile_image_path'])) {
    $oldFilePath = $basePath . $user['profile_image_path'];
    if (file_exists($oldFilePath)) {
        @unlink($oldFilePath);
    }
    $profileImagePath = null;
}

// --- Handle new image upload ---
if (!empty($_FILES['profile_picture']['name'])) {
    $file = $_FILES['profile_picture'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowedExts)) {
        setNotification('error', 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP.');
        redirectWithAgentType('overview');
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // ✅ OLD FORMAT RESTORED: hashed unique ID (no user ID or timestamp)
    $uniqueHash = uniqid('', true);
    $newFileName = 'pfp_' . $uniqueHash . '.' . $ext;
    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Delete old file if it exists
        if (!empty($user['profile_image_path'])) {
            $oldFilePath = $basePath . $user['profile_image_path'];
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }
        $profileImagePath = 'storage/uploads/profile_images/' . $newFileName;
    } else {
        setNotification('error', 'Failed to upload profile picture.');
        redirectWithAgentType('overview');
    }
}

// ==========================
// Update user record
// ==========================
$stmt = $conn->prepare("
    UPDATE users 
    SET first_name = ?, 
        last_name = ?, 
        phone = ?, 
        address = ?, 
        profile_image_path = ?, 
        updated_at = NOW()
    WHERE id = ?
");
$stmt->bind_param("sssssi", $firstName, $lastName, $phone, $address, $profileImagePath, $userId);

if ($stmt->execute()) {
    setNotification('success', 'Profile updated successfully.');
} else {
    setNotification('error', 'Failed to update profile: ' . $stmt->error);
}
$stmt->close();

redirectWithAgentType('overview');

// ==========================
// Helper functions
// ==========================
function setNotification($type, $message) {
    $_SESSION['notification'] = [
        'type' => $type,
        'message' => $message
    ];
}

function redirectWithAgentType($tab = 'overview') {
    global $conn;
    $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
    $userType = 'user';

    if ($userId) {
        $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $userType = $row['user_type'];
        }
        $stmt->close();
    }

    switch ($userType) {
        case 'associate_agent':
            header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile&tab={$tab}");
            break;
        case 'direct_agent':
            header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab={$tab}");
            break;
        default:
            header("Location: /BatEstateExplorer/public/controllers/user_dashboard.php?view=profile&tab={$tab}");
            break;
    }
    exit;
}
?>
