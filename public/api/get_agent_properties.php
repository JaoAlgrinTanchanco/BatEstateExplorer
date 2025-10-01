<?php
// get_agent_properties.php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

// Ensure agent is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$agent_id = $_SESSION['user_id'];

// Fetch properties owned by this agent
$stmt = $conn->prepare("
    SELECT p.id, p.title, p.location, pi.image_path
    FROM properties p
    LEFT JOIN property_images pi ON p.id = pi.property_id
    WHERE p.agent_id = ?
    GROUP BY p.id
");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();

$properties = [];
while ($row = $result->fetch_assoc()) {
    $properties[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'location' => $row['location'],
        'image' => $row['image_path'] 
            ? "/BatEstateExplorer/" . $row['image_path'] 
            : "/BatEstateExplorer/assets/no-image.png"
    ];
}

echo json_encode(['properties' => $properties]);

$stmt->close();
$conn->close();
