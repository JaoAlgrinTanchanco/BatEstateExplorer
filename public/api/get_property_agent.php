<?php
// C:\xampp\htdocs\BatEstateExplorer\public\api\get_property_agent.php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../app/bootstrap.php';

if (!isset($_GET['property_id'])) {
    echo json_encode([
        "success" => false,
        "error" => "Missing property_id"
    ]);
    exit;
}

$property_id = (int) $_GET['property_id'];

// Fetch agent info using agents table
$sql = "
    SELECT 
        p.id AS property_id,
        p.title,
        p.location,
        p.price,
        p.property_type,
        p.agent_id,
        a.user_id AS agent_user_id,
        u.first_name,
        u.last_name,
        u.user_type
    FROM properties p
    LEFT JOIN agents a ON p.agent_id = a.id
    LEFT JOIN users u ON u.id = a.user_id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Use user_id from agents table as the agent_id for messaging
    $row['agent_id'] = $row['agent_user_id'];
    $row['agent_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

    echo json_encode([
        "success" => true,
        "property" => $row
    ]);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Property not found"
    ]);
}

$stmt->close();
