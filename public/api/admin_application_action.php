<?php
session_start();
require_once __DIR__ . '/../../config/pdo_database.php';

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

try {
    if ($action === 'approve') {
        // Fetch the application
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

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

        // Prepare integer fields
        $company_id = (int)($application['company_id'] ?? 0);
        $experience_years = (int)($application['experience_years'] ?? 0);

        // Build INSERT with named placeholders (no agent_type)
        $insert = "
            INSERT INTO users (
                first_name, last_name, email, password_hash, phone, address,
                user_type, status,
                education, school, course, graduation_year,
                certifications, training,
                broker_license_path, prc_license_path, resume_path, valid_id_path, additional_docs_path,
                company_id, broker_id, license_number, experience_years,
                specialization, bio
            ) VALUES (
                :first_name, :last_name, :email, :password_hash, :phone, :address,
                :user_type, :status,
                :education, :school, :course, :graduation_year,
                :certifications, :training,
                :broker_license_path, :prc_license_path, :resume_path, :valid_id_path, :additional_docs_path,
                :company_id, :broker_id, :license_number, :experience_years,
                :specialization, :bio
            )
        ";

        $stmt = $pdo->prepare($insert);

        $stmt->execute([
            ':first_name' => $application['first_name'] ?? '',
            ':last_name' => $application['last_name'] ?? '',
            ':email' => $application['email'] ?? '',
            ':password_hash' => $application['password_hash'] ?? '',
            ':phone' => $application['phone'] ?? '',
            ':address' => $application['address'] ?? '',
            ':user_type' => $user_type,
            ':status' => 'active',
            ':education' => $application['education'] ?? '',
            ':school' => $application['school'] ?? '',
            ':course' => $application['course'] ?? '',
            ':graduation_year' => $application['graduation_year'] ?? '',
            ':certifications' => $application['certifications'] ?? '',
            ':training' => $application['training'] ?? '',
            ':broker_license_path' => $application['broker_license_path'] ?? '',
            ':prc_license_path' => $application['prc_license_path'] ?? '',
            ':resume_path' => $application['resume_path'] ?? '',
            ':valid_id_path' => $application['valid_id_path'] ?? '',
            ':additional_docs_path' => $application['additional_docs_path'] ?? '',
            ':company_id' => $company_id,
            ':broker_id' => $application['broker_id'] ?? '',
            ':license_number' => $application['license_number'] ?? '',
            ':experience_years' => $experience_years,
            ':specialization' => $application['specialization'] ?? '',
            ':bio' => $application['bio'] ?? ''
        ]);
    }

    // Update application status
    $stmt = $pdo->prepare("UPDATE applications SET status = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':id' => $id
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
