<?php
session_start();
header('Content-Type: application/json');

try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/config/pdo_database.php'; // absolute path
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
    // Sanitization helper
    function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    // Helper to convert nested $_FILES array
    function getNestedFile($files, $key) {
        if (!isset($files['name'][$key])) return null;
        return [
            'name'     => $files['name'][$key],
            'type'     => $files['type'][$key],
            'tmp_name' => $files['tmp_name'][$key],
            'error'    => $files['error'][$key],
            'size'     => $files['size'][$key]
        ];
    }

    // Collect inputs
    $first_name  = sanitize($_POST['first_name'] ?? '');
    $last_name   = sanitize($_POST['last_name'] ?? '');
    $email       = sanitize($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $user_type   = sanitize($_POST['user_type'] ?? '');
    $phone       = sanitize($_POST['phone'] ?? '');
    $address     = sanitize($_POST['address'] ?? '');
    $company_id  = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($user_type)) {
        throw new Exception("All required fields must be filled");
    }

    if ($user_type === 'associate_agent' && !$company_id) {
        throw new Exception("Company selection is required for associate agents");
    }

    // Validate unique email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception("Email already exists");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Upload directories
    $docs_dir   = 'C:/xampp/htdocs/BatEstateExplorer/storage/uploads/documents/';
    $imgs_dir   = __DIR__ . "/../storage/uploads/images/"; // images can stay relative
    if (!file_exists($docs_dir)) mkdir($docs_dir, 0755, true);
    if (!file_exists($imgs_dir)) mkdir($imgs_dir, 0755, true);

    $allowed_types = [
        'image/jpeg', 'image/jpg', 'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    // File upload handler
    function handleUpload($file, $prefix, $docs_dir, $imgs_dir, $allowed_types) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;

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

    // Only handle required files for Direct Agents
    $broker_license = $prc_license = $resume = $valid_id = null;
    if ($user_type === 'direct_agent') {
        $broker_license = handleUpload(getNestedFile($_FILES['documents'], 'broker_license'), 'broker_license', $docs_dir, $imgs_dir, $allowed_types);
        $prc_license    = handleUpload(getNestedFile($_FILES['documents'], 'prc_license'), 'prc_license', $docs_dir, $imgs_dir, $allowed_types);
        $resume         = handleUpload(getNestedFile($_FILES['documents'], 'resume'), 'resume', $docs_dir, $imgs_dir, $allowed_types);
        $valid_id       = handleUpload(getNestedFile($_FILES['documents'], 'valid_id'), 'valid_id', $docs_dir, $imgs_dir, $allowed_types);

        if (!$broker_license || !$prc_license || !$resume || !$valid_id) {
            throw new Exception("All required documents must be uploaded for Direct Agents");
        }
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO applications 
        (first_name, last_name, email, password_hash, phone, address, agent_type, company_id,
         broker_license_path, prc_license_path, resume_path, valid_id_path, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");

    $stmt->execute([
        $first_name, $last_name, $email, $password_hash, $phone, $address,
        $user_type, $company_id, $broker_license, $prc_license, $resume, $valid_id
    ]);

    $pdo->commit();

    // Redirect to home on success
    header("Location: http://localhost/BatEstateExplorer/index.php");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    echo json_encode($response);
    exit;
}
