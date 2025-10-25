<?php
session_start();
require_once __DIR__ . '/../../config/pdo_database.php';

// --- Security check ---
if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// --- Parse input ---
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'], $input['action'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

$id = (int)$input['id'];
$action = strtolower($input['action']);

if (!in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
    exit;
}

$status = ($action === 'approve') ? 'approved' : 'rejected';

try {
    $pdo->beginTransaction();

    // --- Fetch application ---
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        throw new Exception('Application not found');
    }

    if ($action === 'approve') {
        // --- Map agent_type to user_type ---
        $user_type = match ($application['agent_type'] ?? '') {
            'direct_agent' => 'direct_agent',
            'associate_agent' => 'associate_agent',
            default => 'user'
        };

        $company_id = !empty($application['company_id']) ? (int)$application['company_id'] : null;
        $experience_years = !empty($application['experience_years']) ? (int)$application['experience_years'] : 0;

        // --- Check for existing user ---
        $stmt = $pdo->prepare("SELECT id, user_type FROM users WHERE email = ?");
        $stmt->execute([$application['email']]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            if ($existingUser['user_type'] === 'user') {
                $pdo->prepare("DELETE FROM users WHERE id = ?")
                    ->execute([$existingUser['id']]);
            } else {
                throw new Exception(
                    "Email {$application['email']} is already an {$existingUser['user_type']}"
                );
            }
        }

        // --- Convert specialization to JSON ---
        $specialization_json = null;
        if (!empty($application['specialization'])) {
            $spec = $application['specialization'];
            if (is_string($spec)) {
                $decoded = json_decode($spec, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $specialization_json = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                } else {
                    $items = array_map('trim', explode(',', $spec));
                    $specialization_json = json_encode($items, JSON_UNESCAPED_UNICODE);
                }
            } elseif (is_array($spec)) {
                $specialization_json = json_encode($spec, JSON_UNESCAPED_UNICODE);
            }
        }

        // --- Insert into users (merged single insert with all fields) ---
        $insertUserSQL = "
            INSERT INTO users (
                first_name, last_name, email, password_hash, phone, address,
                user_type, status,
                education, school, course, graduation_year,
                certifications, training,
                broker_license_path, prc_license_path, resume_path, valid_id_path,
                property_location, property_image_path, property_document_path, additional_docs_path,
                company_id, broker_id, license_number, experience_years,
                specialization, bio, profile_image_path
            ) VALUES (
                :first_name, :last_name, :email, :password_hash, :phone, :address,
                :user_type, :status,
                :education, :school, :course, :graduation_year,
                :certifications, :training,
                :broker_license_path, :prc_license_path, :resume_path, :valid_id_path,
                :property_location, :property_image_path, :property_document_path, :additional_docs_path,
                :company_id, :broker_id, :license_number, :experience_years,
                :specialization, :bio, :profile_image_path
            )
        ";

        $stmt = $pdo->prepare($insertUserSQL);
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
            ':property_location' => $application['property_location'] ?? '',
            ':property_image_path' => $application['property_image_path'] ?? '',
            ':property_document_path' => $application['property_document_path'] ?? '',
            ':additional_docs_path' => $application['additional_docs_path'] ?? '',
            ':company_id' => $company_id,
            ':broker_id' => $application['broker_id'] ?? null,
            ':license_number' => $application['license_number'] ?? null,
            ':experience_years' => $experience_years,
            ':specialization' => $specialization_json,
            ':bio' => $application['bio'] ?? null,
            ':profile_image_path' => $application['profile_image_path'] ?? null
        ]);

        $new_user_id = $pdo->lastInsertId();
    }

    // --- Update application status ---
    $stmt = $pdo->prepare("UPDATE applications SET status = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':id' => $id
    ]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
