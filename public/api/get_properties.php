<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/../app/bootstrap.php';

// Connect to database
$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

// Get filters from GET
$location = $_GET['location'] ?? '';
$property_type = $_GET['property_type'] ?? '';
$price_range = $_GET['price_range'] ?? '';
$bedrooms = $_GET['bedrooms'] ?? '';
$bathrooms = $_GET['bathrooms'] ?? '';
$size = $_GET['size'] ?? '';

// Build SQL and params
$sql = "
    SELECT p.*, pi.image_path
    FROM properties p
    LEFT JOIN property_images pi 
        ON p.id = pi.property_id AND pi.is_primary = 1
    WHERE 1=1
";

$params = [];
$types = "";

if ($location !== '') {
    $sql .= " AND p.location = ?";
    $params[] = $location;
    $types .= "s";
}
if ($property_type !== '') {
    $sql .= " AND p.property_type = ?";
    $params[] = $property_type;
    $types .= "s";
}
if ($price_range !== '') {
    if ($price_range === '5000000+') {
        $sql .= " AND p.price >= 5000000";
    } else {
        $parts = explode('-', $price_range);
        if (count($parts) === 2) {
            $min = (float)$parts[0];
            $max = (float)$parts[1];
            $sql .= " AND p.price BETWEEN ? AND ?";
            $params[] = $min;
            $params[] = $max;
            $types .= "dd";
        }
    }
}
if ($bedrooms !== '') {
    $sql .= " AND p.bedrooms >= ?";
    $params[] = (int)$bedrooms;
    $types .= "i";
}
if ($bathrooms !== '') {
    $sql .= " AND p.bathrooms >= ?";
    $params[] = (int)$bathrooms;
    $types .= "i";
}
if ($size !== '') {
    if ($size === '200+') {
        $sql .= " AND p.sqm >= 200";
    } else {
        $parts = explode('-', $size);
        if (count($parts) === 2) {
            $min_sqm = (float)$parts[0];
            $max_sqm = (float)$parts[1];
            $sql .= " AND p.sqm BETWEEN ? AND ?";
            $params[] = $min_sqm;
            $params[] = $max_sqm;
            $types .= "dd";
        }
    }
}

$sql .= " ORDER BY p.created_at DESC";

// DEBUG: Log the final SQL query
error_log("SQL Query: " . $sql);

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'SQL prepare failed: ' . $conn->error]);
    exit;
}

if (!empty($params)) {
    $bind_names = [];
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

$stmt->execute();

$result = $stmt->get_result();
$properties = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$stmt->close();
$conn->close();

// Return JSON response once
echo json_encode(['properties' => $properties]);
exit;
