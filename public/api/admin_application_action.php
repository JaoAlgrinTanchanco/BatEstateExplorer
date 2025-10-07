<?php
session_start();
require_once __DIR__ . '/../../config/pdo_database.php';

<<<<<<< HEAD
// --- Security: check admin login ---
=======
// --- Security check ---
>>>>>>> origin/ansel
if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

<<<<<<< HEAD
// --- Read input JSON ---
=======
// --- Parse input ---
>>>>>>> origin/ansel
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'], $input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = (int) $input['id'];
<<<<<<< HEAD
$action = $input['action'];
=======
$action = strtolower($input['action']);
>>>>>>> origin/ansel

if (!in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
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
        // Map agent_type to user_type
        $user_type = match ($application['agent_type'] ?? '') {
            'direct_agent' => 'direct_agent',
            'associate_agent' => 'associate_agent',
            default => 'user'
        };

<<<<<<< HEAD
        // Normalize fields
        $company_id = (int)($application['company_id'] ?? 0);
        $experience_years = (int)($application['experience_years'] ?? 0);

        // --- Check if user with same email exists ---
=======
        $company_id = !empty($application['company_id']) ? (int)$application['company_id'] : null;
        $experience_years = !empty($application['experience_years']) ? (int)$application['experience_years'] : 0;

        // --- Check for existing user ---
>>>>>>> origin/ansel
        $stmt = $pdo->prepare("SELECT id, user_type FROM users WHERE email = ?");
        $stmt->execute([$application['email']]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            if ($existingUser['user_type'] === 'user') {
<<<<<<< HEAD
                // Delete old user safely
                $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $delStmt->execute([$existingUser['id']]);
=======
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$existingUser['id']]);
>>>>>>> origin/ansel
            } else {
                throw new Exception("Email {$application['email']} is already an {$existingUser['user_type']}");
            }
        }

<<<<<<< HEAD
        // --- Insert into users ---
        $insertUser = "
=======
        // --- Convert specialization to JSON ---
        $specialization_json = null;

        if (!empty($application['specialization'])) {
            $spec = $application['specialization'];

            // If it's a string, try decoding it first
            if (is_string($spec)) {
                $decoded = json_decode($spec, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // It was already JSON
                    $specialization_json = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                } else {
                    // It’s a comma-separated list
                    $items = array_map('trim', explode(',', $spec));
                    $specialization_json = json_encode($items, JSON_UNESCAPED_UNICODE);
                }
            } elseif (is_array($spec)) {
                // Already an array
                $specialization_json = json_encode($spec, JSON_UNESCAPED_UNICODE);
            }
        }

        // --- Insert into users ---
        $insertUserSQL = "
>>>>>>> origin/ansel
            INSERT INTO users (
                first_name, last_name, email, password_hash, phone, address,
                user_type, status,
                education, school, course, graduation_year,
                certifications, training,
                broker_license_path, prc_license_path, resume_path, valid_id_path, additional_docs_path,
                company_id, broker_id, license_number, experience_years,
<<<<<<< HEAD
                specialization, bio
=======
                specialization, bio, profile_image_path
>>>>>>> origin/ansel
            ) VALUES (
                :first_name, :last_name, :email, :password_hash, :phone, :address,
                :user_type, :status,
                :education, :school, :course, :graduation_year,
                :certifications, :training,
                :broker_license_path, :prc_license_path, :resume_path, :valid_id_path, :additional_docs_path,
                :company_id, :broker_id, :license_number, :experience_years,
<<<<<<< HEAD
                :specialization, :bio
            )
        ";
        $stmt = $pdo->prepare($insertUser);
=======
                :specialization, :bio, :profile_image_path
            )
        ";
        $stmt = $pdo->prepare($insertUserSQL);
>>>>>>> origin/ansel
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
<<<<<<< HEAD
            ':company_id' => $company_id ?: null,
            ':broker_id' => $application['broker_id'] ?? null,
            ':license_number' => $application['license_number'] ?? null,
            ':experience_years' => $experience_years,
            ':specialization' => $application['specialization'] ?? null,
            ':bio' => $application['bio'] ?? null
=======
            ':company_id' => $company_id,
            ':broker_id' => $application['broker_id'] ?? null,
            ':license_number' => $application['license_number'] ?? null,
            ':experience_years' => $experience_years,
            ':specialization' => $specialization_json,
            ':bio' => $application['bio'] ?? null,
            ':profile_image_path' => $application['profile_image_path'] ?? null
>>>>>>> origin/ansel
        ]);

        $new_user_id = $pdo->lastInsertId();

        // --- Insert into agents ---
<<<<<<< HEAD
        $insertAgent = "
            INSERT INTO agents (user_id, company_id, broker_id, license_number, experience_years, specialization, bio)
            VALUES (:user_id, :company_id, :broker_id, :license_number, :experience_years, :specialization, :bio)
        ";
        $stmt = $pdo->prepare($insertAgent);
        $stmt->execute([
            ':user_id' => $new_user_id,
            ':company_id' => $company_id ?: null,
            ':broker_id' => $application['broker_id'] ?? null,
            ':license_number' => $application['license_number'] ?? null,
            ':experience_years' => $experience_years,
            ':specialization' => $application['specialization'] ?? null,
=======
        $stmt = $pdo->prepare("
            INSERT INTO agents (user_id, company_id, broker_id, license_number, experience_years, specialization, bio)
            VALUES (:user_id, :company_id, :broker_id, :license_number, :experience_years, :specialization, :bio)
        ");
        $stmt->execute([
            ':user_id' => $new_user_id,
            ':company_id' => $company_id,
            ':broker_id' => $application['broker_id'] ?? null,
            ':license_number' => $application['license_number'] ?? null,
            ':experience_years' => $experience_years,
            ':specialization' => $specialization_json,
>>>>>>> origin/ansel
            ':bio' => $application['bio'] ?? null
        ]);
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
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
