<?php
session_start();
require_once __DIR__ . '/../../config/pdo_database.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Security check ---
if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// --- Parse input ---
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'], $input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = (int)$input['id'];
$action = strtolower($input['action']);
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
    if (!$application) throw new Exception('Application not found');

    // --- Approve: create user ---
    if ($action === 'approve') {
        $user_type = match ($application['agent_type'] ?? '') {
            'direct_agent' => 'direct_agent',
            'associate_agent' => 'associate_agent',
            default => 'user'
        };
        $company_id = !empty($application['company_id']) ? (int)$application['company_id'] : null;
        $experience_years = !empty($application['experience_years']) ? (int)$application['experience_years'] : 0;

        // Check existing user
        $stmt = $pdo->prepare("SELECT id, user_type FROM users WHERE email = ?");
        $stmt->execute([$application['email']]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existingUser && $existingUser['user_type'] === 'user') {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$existingUser['id']]);
        } elseif ($existingUser) {
            throw new Exception("Email {$application['email']} is already an {$existingUser['user_type']}");
        }

        // Convert specialization to JSON
        $specialization_json = null;
        if (!empty($application['specialization'])) {
            $spec = $application['specialization'];
            if (is_string($spec)) {
                $decoded = json_decode($spec, true);
                $specialization_json = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                    ? json_encode($decoded, JSON_UNESCAPED_UNICODE)
                    : json_encode(array_map('trim', explode(',', $spec)), JSON_UNESCAPED_UNICODE);
            } elseif (is_array($spec)) {
                $specialization_json = json_encode($spec, JSON_UNESCAPED_UNICODE);
            }
        }

        // Insert user
        $insertUserSQL = "INSERT INTO users (
            first_name,last_name,email,password_hash,phone,address,
            user_type,status,education,school,course,graduation_year,
            certifications,training,broker_license_path,prc_license_path,resume_path,valid_id_path,
            property_location,property_image_path,property_document_path,additional_docs_path,
            company_id,broker_id,license_number,experience_years,
            specialization,bio,profile_image_path
        ) VALUES (
            :first_name,:last_name,:email,:password_hash,:phone,:address,
            :user_type,:status,:education,:school,:course,:graduation_year,
            :certifications,:training,:broker_license_path,:prc_license_path,:resume_path,:valid_id_path,
            :property_location,:property_image_path,:property_document_path,:additional_docs_path,
            :company_id,:broker_id,:license_number,:experience_years,
            :specialization,:bio,:profile_image_path
        )";

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
    $stmt->execute([':status' => $status, ':id' => $id]);

    // --- Insert notice ---
    $noticeMessage = ($action === 'approve') ? "Your application has been approved." : "Your application has been rejected.";
    $stmt = $pdo->prepare("INSERT INTO reg_notices (application_id, notice_type, message, is_seen) VALUES (:application_id, :notice_type, :message, 0)");
    $stmt->execute([':application_id' => $id, ':notice_type' => $action, ':message' => $noticeMessage]);

    // --- Send email ---
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'batestate07@gmail.com';
    $mail->Password   = 'jgsiczkzxyvuxvgb';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('batestate07@gmail.com', 'BatEstateExplorer');
    $mail->addAddress($application['email']);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    $subject = ($action === 'approve') ? "Application Approved - BatEstateExplorer" : "Application Rejected - BatEstateExplorer";
    $bodyColor = ($action === 'approve') ? "#ecfdf5" : "#fef2f2";
    $accentColor = ($action === 'approve') ? "#10b981" : "#ef4444";

    $mail->Body = "
    <div style='font-family:Arial,sans-serif;background:#f9fafc;padding:30px;'>
        <table style='max-width:600px;margin:auto;background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.08);padding:20px;'>
            <tr>
                <td style='text-align:center;padding-bottom:20px;'>
                    <h2 style='color:#111;margin:0;'>BatEstateExplorer</h2>
                    <p style='color:#555;font-size:14px;margin-top:5px;'>Application Update</p>
                </td>
            </tr>
            <tr>
                <td style='font-size:15px;color:#333;line-height:1.6;background:$bodyColor;padding:20px;border-radius:8px;text-align:center;'>
                    <h3 style='color:$accentColor;margin-bottom:15px;'>".htmlspecialchars($subject)."</h3>
                    <p>".htmlspecialchars($noticeMessage)."</p>
                    <p>Date: ".date("Y-m-d H:i:s")."</p>
                </td>
            </tr>
            <tr>
                <td style='text-align:center;font-size:12px;color:#999;padding-top:20px;border-top:1px solid #eee;'>
                    © ".date("Y")." BatEstateExplorer. All rights reserved.
                </td>
            </tr>
        </table>
    </div>
    ";
    $mail->AltBody = $noticeMessage;
    $mail->send();

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
