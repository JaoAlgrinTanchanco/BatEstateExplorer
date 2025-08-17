<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

error_log("=== AGENT REGISTRATION START ===");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once '../config/database.php'; // should return $pdo (PDO connection)
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed: '.$e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    // Sanitize helper
    function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    // Collect input
    $first_name  = sanitize($_POST['first_name'] ?? '');
    $last_name   = sanitize($_POST['last_name'] ?? '');
    $email       = sanitize($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $user_type   = sanitize($_POST['user_type'] ?? '');
    $phone       = sanitize($_POST['phone'] ?? '');
    $address     = sanitize($_POST['address'] ?? '');
    $company_id  = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;

    // Required
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($user_type)) {
        throw new Exception("All required fields must be filled");
    }
    if ($user_type === 'associate' && !$company_id) {
        throw new Exception("Company selection is required for associate agents");
    }

    // Validate unique email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception("Email already exists");
    }

    // Password hash
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // File upload settings
    $upload_dir = __DIR__ . "/../storage/uploads/";
    $docs_dir   = $upload_dir . "documents/";
    $imgs_dir   = $upload_dir . "images/";
    if (!file_exists($docs_dir)) mkdir($docs_dir, 0755, true);
    if (!file_exists($imgs_dir)) mkdir($imgs_dir, 0755, true);

    $allowed_types = [
        'image/jpeg', 'image/jpg', 'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    function handleUpload($file, $prefix, $docs_dir, $imgs_dir, $allowed_types) {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;

        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception("Invalid file type: {$file['name']}");
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("File too large: {$file['name']}");
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fname = $prefix . '_' . uniqid() . '.' . $ext;
        $dest = (strpos($file['type'], 'image/') === 0) ? $imgs_dir.$fname : $docs_dir.$fname;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new Exception("Failed to save file: {$file['name']}");
        }
        return $dest;
    }

    // Handle required files
    $broker_license = isset($_FILES['broker_license']) ? handleUpload($_FILES['broker_license'], 'broker_license', $docs_dir, $imgs_dir, $allowed_types) : null;
    $prc_license    = isset($_FILES['prc_license'])    ? handleUpload($_FILES['prc_license'], 'prc_license', $docs_dir, $imgs_dir, $allowed_types) : null;
    $resume         = isset($_FILES['resume'])         ? handleUpload($_FILES['resume'], 'resume', $docs_dir, $imgs_dir, $allowed_types) : null;
    $valid_id       = isset($_FILES['valid_id'])       ? handleUpload($_FILES['valid_id'], 'valid_id', $docs_dir, $imgs_dir, $allowed_types) : null;

    if (!$broker_license || !$prc_license || !$resume || !$valid_id) {
        throw new Exception("All required documents must be uploaded");
    }

    // Save in transaction
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO applications 
        (first_name, last_name, email, password_hash, phone, address, agent_type, company_id,
         broker_license_path, prc_license_path, resume_path, valid_id_path, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");

    $stmt->execute([
        $first_name, $last_name, $email, $password_hash, $phone, $address, 
        $user_type, $company_id, $broker_license, $prc_license, $resume, $valid_id
    ]);

    $app_id = $pdo->lastInsertId();
    $pdo->commit();

    $response['success'] = true;
    $response['message'] = "Application submitted successfully. Awaiting admin review.";
    $response['application_id'] = $app_id;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Exception: ".$e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
