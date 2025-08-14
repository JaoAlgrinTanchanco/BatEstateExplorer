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

// If approve, copy to `users` table with all application columns
if ($action === 'approve') {
    $stmt = $conn->prepare("
        SELECT * FROM applications WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$application) {
        echo json_encode(['success' => false, 'message' => 'Application not found']);
        exit;
    }

    // Map agent_type to user_type
    $user_type = match ($application['agent_type'] ?? '') {
        'direct_agent' => 'direct_agent',
        'associate_agent' => 'associate_agent',
        default => 'user'
    };

    // Insert all relevant columns into users table
    $stmt = $conn->prepare("
        INSERT INTO users (
            first_name, last_name, email, password_hash, phone, address,
            user_type, status,
            education, school, course, graduation_year, certifications, training,
            broker_license_path, prc_license_path, resume_path, valid_id_path, additional_docs_path,
            agent_type, company_id, broker_id, license_number, experience_years,
            specialization, bio
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $active_status = 'active';
    $stmt->bind_param(
        "sssssssssssssssssssssssss",
        $application['first_name'],
        $application['last_name'],
        $application['email'],
        $application['password_hash'],
        $application['phone'],
        $application['address'],
        $user_type,
        $active_status,
        $application['education'],
        $application['school'],
        $application['course'],
        $application['graduation_year'],
        $application['certifications'],
        $application['training'],
        $application['broker_license_path'],
        $application['prc_license_path'],
        $application['resume_path'],
        $application['valid_id_path'],
        $application['additional_docs_path'],
        $application['agent_type'],
        $application['company_id'],
        $application['broker_id'],
        $application['license_number'],
        $application['experience_years'],
        $application['specialization'],
        $application['bio']
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
