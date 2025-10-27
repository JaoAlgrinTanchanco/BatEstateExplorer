<?php
// get_agent_user_id.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/pdo_database.php';
session_start();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get agent_id from query string
$agentId = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : 0;
if ($agentId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid agent ID']);
    exit;
}

try {
    // Fetch the corresponding user_id from agents table
    $stmt = $pdo->prepare("SELECT user_id FROM agents WHERE id = :agentId LIMIT 1");
    $stmt->execute(['agentId' => $agentId]);
    $agent = $stmt->fetch();

    if (!$agent) {
        echo json_encode(['success' => false, 'error' => 'Agent not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'user_id' => (int)$agent['user_id']
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
