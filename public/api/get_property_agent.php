<?php
// C:\xampp\htdocs\BatEstateExplorer\public\api\get_property_agent.php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../app/bootstrap.php';

if (!isset($_GET['property_id'])) {
    echo json_encode(["error" => "Missing property_id"]);
    exit;
}

$property_id = (int) $_GET['property_id'];

// ✅ Fetch property with its agent
$sql = "
    SELECT 
        p.id AS property_id,
        p.title,
        p.location,
        p.price,
        p.property_type,
        p.agent_id,
        u.first_name,
        u.last_name,
        u.user_type
    FROM properties p
    LEFT JOIN users u ON p.agent_id = u.id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $row['agent_name'] = trim($row['first_name'] . " " . $row['last_name']);
    echo json_encode($row);
} else {
    echo json_encode(["error" => "Property not found"]);
}

$stmt->close();
