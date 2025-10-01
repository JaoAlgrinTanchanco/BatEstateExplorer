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
$status    = $user['status'];
$userType  = $user['user_type']; // preserve current user_type

if (!$firstName || !$lastName) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'First name and last name are required.'
    ];
    redirectWithAgentType('overview');
}

// Update user (do not touch email)
$stmt = $conn->prepare("
    UPDATE users SET
        first_name = ?, last_name = ?, phone = ?, address = ?, updated_at = NOW()
    WHERE id = ?
");
$stmt->bind_param(
    "ssssi",
    $firstName, $lastName, $phone, $address, $userId
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

    // Redirect based on user type
    if ($agentType === 'associate') {
        $view = 'associate_profile';
        header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view={$view}&tab={$tab}");
    } elseif ($agentType === 'direct') {
        $view = 'direct_profile';
        header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view={$view}&tab={$tab}");
    } else {
        // normal user
        header("Location: /BatEstateExplorer/public/controllers/user_dashboard.php?view=profile&tab={$tab}");
    }
    exit;
}
