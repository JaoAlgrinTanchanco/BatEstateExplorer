<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// --- Check if user is logged in ---
$is_logged_in = is_logged_in();
$current_user = null;
if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
}
if (!$is_logged_in || !$current_user) {
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings&error=unauthorized");
    exit;
}

// Only direct or associate agents can use this
if (!in_array($current_user['user_type'], ['direct_agent', 'associate_agent'])) {
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings&error=forbidden");
    exit;
}

$property_id = intval($_POST['property_id'] ?? 0);
if (!$property_id) {
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings&error=invalid_id");
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
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings&error=agent_not_found");
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
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings&error=not_rejected");
    exit;
}

// 🗑 Delete property
$query = "DELETE FROM properties WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $property_id);

if ($stmt->execute()) {
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings&success=deleted");
    exit;
} else {
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings&error=delete_failed");
    exit;
}
