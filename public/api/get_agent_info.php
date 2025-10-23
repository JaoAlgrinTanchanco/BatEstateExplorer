<?php
// get_agent_info.php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');

$property_id = (int)($_GET['property_id'] ?? $_POST['property_id'] ?? 0);
if (!$property_id) {
    echo json_encode(['success' => false, 'error' => 'Property ID required.']);
    exit;
}

// --- Step 1: Get agent_id from properties ---
$stmt = $conn->prepare("SELECT agent_id FROM properties WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$res = $stmt->get_result();
$property = $res->fetch_assoc();
$stmt->close();

if (!$property || !$property['agent_id']) {
    echo json_encode(['success' => false, 'error' => 'Agent not found for this property.']);
    exit;
}

$agent_id = (int)$property['agent_id'];

// --- Step 2: Get user_id from agents ---
$stmt = $conn->prepare("SELECT user_id FROM agents WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$res = $stmt->get_result();
$agent = $res->fetch_assoc();
$stmt->close();

if (!$agent || !$agent['user_id']) {
    echo json_encode(['success' => false, 'error' => 'User not found for this agent.']);
    exit;
}

$user_id = (int)$agent['user_id'];

// --- Step 3: Get name from users ---
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User details not found.']);
    exit;
}

echo json_encode([
    'success'    => true,
    'agent_id'   => $user_id,
    'agent_name' => trim($user['first_name'] . ' ' . $user['last_name'])
]);
exit;
