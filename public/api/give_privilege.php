<?php
// give_privilege.php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 🔹 Search user by email
    $email = $_GET['email'] ?? '';
    if (!$email) {
        echo json_encode(['error' => 'Missing email']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, privileges FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Build full name
    $user['name'] = trim($user['first_name'] . ' ' . $user['last_name']);

    // Ensure privileges is a valid JSON array
    $user['privileges'] = $user['privileges'] ? json_decode($user['privileges'], true) : [];

    echo json_encode($user);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 🔹 Give privilege
    $email = $_POST['email'] ?? '';
    $property_id = $_POST['property_id'] ?? '';

    if (!$email || !$property_id) {
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }

    // Fetch user
    $stmt = $conn->prepare("SELECT id, privileges FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Decode privileges
    $privileges = $user['privileges'] ? json_decode($user['privileges'], true) : [];
    if (!in_array($property_id, $privileges)) {
        $privileges[] = $property_id;
    }

    // Save privileges back
    $privileges_json = json_encode($privileges);
    $stmt = $conn->prepare("UPDATE users SET privileges = ? WHERE email = ?");
    $stmt->bind_param("ss", $privileges_json, $email);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'privileges' => $privileges]);
    } else {
        echo json_encode(['error' => 'Database update failed']);
    }

    $stmt->close();
    $conn->close();
}
?>
