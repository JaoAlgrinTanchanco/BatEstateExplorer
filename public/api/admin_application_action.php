<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check admin login
if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Read input JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'], $input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = (int)$input['id'];
$action = $input['action'];

if (!in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$status = ($action === 'approve') ? 'approved' : 'rejected';

// If approve, copy to `users` table
if ($action === 'approve') {
    // Get application data (only needed columns for `users` table)
    $stmt = $conn->prepare("
        SELECT first_name, last_name, email, password_hash, phone, address, agent_type
        FROM applications
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$application) {
        echo json_encode(['success' => false, 'message' => 'Application not found']);
        exit;
    }

    // Map agent_type to valid user_type in `users` table
    // Adjust mapping logic if needed
    $user_type = match ($application['agent_type']) {
        'direct_agent' => 'direct_agent',
        'associate_agent' => 'associate_agent',
        default => 'user'
    };

    // Insert into users table
    $stmt = $conn->prepare("
        INSERT INTO users (
            first_name, last_name, email, password_hash, phone, address,
            user_type, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $active_status = 'active';
    $stmt->bind_param(
        "ssssssss",
        $application['first_name'],
        $application['last_name'],
        $application['email'],
        $application['password_hash'], // already hashed in applications table
        $application['phone'],
        $application['address'],
        $user_type,
        $active_status
    );

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to insert into users table: ' . $stmt->error]);
        exit;
    }
    $stmt->close();
}

// Update application status
$stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update application status']);
}
$stmt->close();
$conn->close();
