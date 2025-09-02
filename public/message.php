<?php
session_start();
require_once __DIR__ . '/app/bootstrap.php';

// ✅ Check for logged-in user (support both session formats)
if (isset($_SESSION['user']['id'])) {
    $user_id = (int) $_SESSION['user']['id'];
} elseif (isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
} else {
    die("User not logged in.");
}

// ✅ Determine if coming from a property or directly from agent
$property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : null;
$agent_id = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : null;

if ($property_id) {
    // Use new API to fetch agent info from property_id
    $apiUrl = __DIR__ . "/api/get_property_agent.php?property_id=" . $property_id;
    $response = file_get_contents($apiUrl);
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['agent_id'])) {
            $agent_id = (int) $data['agent_id'];
        }
    }
}

// If still no agent_id, fallback to first available agent
if (!$agent_id) {
    $res = $conn->query("SELECT id FROM users WHERE user_type IN ('direct_agent','associate_agent') LIMIT 1");
    if ($row = $res->fetch_assoc()) {
        $agent_id = (int) $row['id'];
    } else {
        die("No agents available.");
    }
}

// ✅ Fetch agent info safely
$stmt = $conn->prepare("SELECT id, first_name, last_name 
                        FROM users 
                        WHERE id = ? AND user_type IN ('direct_agent','associate_agent') 
                        LIMIT 1");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
$agent = $result->fetch_assoc();
$stmt->close();

if (!$agent) {
    die("Agent not found.");
}
$agent_name = trim($agent['first_name'] . " " . $agent['last_name']);

// ✅ Fetch all agents for conversation list
$agents_list = [];
$res = $conn->query("SELECT id, CONCAT(first_name,' ',last_name) AS name 
                     FROM users 
                     WHERE user_type IN ('direct_agent','associate_agent')");
while ($row = $res->fetch_assoc()) {
    $agents_list[$row['id']] = $row['name'];
}
?>
