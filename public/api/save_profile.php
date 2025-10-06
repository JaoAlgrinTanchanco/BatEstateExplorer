<?php
session_start();
require_once __DIR__ . '/../app/bootstrap.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'User not logged in.'
    ];
    redirectWithAgentType('overview');
}

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'User not found.'
    ];
    redirectWithAgentType('overview');
}

// Collect POST data
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$address   = trim($_POST['address'] ?? '');

if (!$firstName || !$lastName) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'First name and last name are required.'
    ];
    redirectWithAgentType('overview');
}

// ==========================
// Handle Profile Image Upload
// ==========================
$uploadDir = __DIR__ . '/../../storage/uploads/profile_images/';
$profileImagePath = $user['profile_image_path']; // Keep old path if no new upload

if (!empty($_FILES['profile_picture']['name'])) {
    $file = $_FILES['profile_picture'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowedExts)) {
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newFileName = 'pfp_' . $userId . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // ✅ Delete old image (if exists)
            if (!empty($user['profile_image_path'])) {
                $oldFilePath = __DIR__ . '/../../' . $user['profile_image_path'];
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }

            // Save relative path
            $profileImagePath = 'storage/uploads/profile_images/' . $newFileName;
        } else {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Failed to upload profile picture.'
            ];
            redirectWithAgentType('overview');
        }
    } else {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP.'
        ];
        redirectWithAgentType('overview');
    }
}

// ==========================
// Update user record
// ==========================
$stmt = $conn->prepare("
    UPDATE users 
    SET first_name = ?, last_name = ?, phone = ?, address = ?, profile_image_path = ?, updated_at = NOW()
    WHERE id = ?
");
$stmt->bind_param(
    "sssssi",
    $firstName,
    $lastName,
    $phone,
    $address,
    $profileImagePath,
    $userId
);

if ($stmt->execute()) {
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Profile updated successfully.'
    ];
} else {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Failed to update profile: ' . $stmt->error
    ];
}

$stmt->close();

// Redirect back to profile overview tab
redirectWithAgentType('overview');


// ==========================
// Helper: Redirect by agent type
// ==========================
function redirectWithAgentType($tab = 'overview') {
    global $conn;

    $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
    $agentType = 'normal_user';

    if ($userId) {
        $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $userType = $row['user_type'];
            if ($userType === 'associate_agent') {
                $agentType = 'associate';
            } elseif ($userType === 'direct_agent') {
                $agentType = 'direct';
            }
        }
        $stmt->close();
    }

    if ($agentType === 'associate') {
        header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=associate_profile&tab={$tab}");
    } elseif ($agentType === 'direct') {
        header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab={$tab}");
    } else {
        header("Location: /BatEstateExplorer/public/controllers/user_dashboard.php?view=profile&tab={$tab}");
    }
    exit;
}
