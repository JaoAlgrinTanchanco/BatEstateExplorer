<?php
session_start();
header('Content-Type: application/json');

try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/config/pdo_database.php';
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
    // ======= Helper Functions =======
    function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

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

    function handleUpload($file, $prefix, $docsDir, $imgsDir, $allowedTypes) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;

        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type: {$file['name']}");
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("File too large: {$file['name']}");
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fname = $prefix . '_' . uniqid() . '.' . $ext;
        $dest = (strpos($file['type'], 'image/') === 0) ? $imgsDir.$fname : $docsDir.$fname;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new Exception("Failed to save file: {$file['name']}");
        }

        return $dest;
    }

    // ======= Collect Form Inputs =======
    $first_name  = sanitize($_POST['first_name'] ?? '');
    $last_name   = sanitize($_POST['last_name'] ?? '');
    $email       = sanitize($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $user_type   = sanitize($_POST['user_type'] ?? '');
    $phone       = sanitize($_POST['phone'] ?? '');
    $address     = sanitize($_POST['address'] ?? '');
    $company_id  = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;

    // Professional Info
    $broker_id        = sanitize($_POST['broker_id'] ?? null);
    $prc_number       = sanitize($_POST['prc_number'] ?? null);
    $experience_years = sanitize($_POST['experience_years'] ?? null);
    $specializations  = sanitize($_POST['specializations'] ?? null);
    $experience_details = sanitize($_POST['experience_details'] ?? null);

    // Education
    $education       = sanitize($_POST['education'] ?? null);
    $school          = sanitize($_POST['school'] ?? null);
    $course          = sanitize($_POST['course'] ?? null);
    $graduation_year = sanitize($_POST['graduation_year'] ?? null);

    // Certifications & Training
    $certifications = sanitize($_POST['certifications'] ?? null);
    $training       = sanitize($_POST['training'] ?? null);

    // ======= Validation =======
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($user_type)) {
        throw new Exception("All required fields must be filled");
    }
    if ($user_type === 'associate_agent' && !$company_id) {
        throw new Exception("Company selection is required for associate agents");
    }

    // Unique email check
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception("Email already exists");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // ======= File Uploads =======
    $docsDir = $_SERVER['DOCUMENT_ROOT'].'/BatEstateExplorer/storage/uploads/documents/';
    $imgsDir = $_SERVER['DOCUMENT_ROOT'].'/BatEstateExplorer/storage/uploads/images/';
    if (!file_exists($docsDir)) mkdir($docsDir, 0755, true);
    if (!file_exists($imgsDir)) mkdir($imgsDir, 0755, true);

    $allowedTypes = [
        'image/jpeg','image/jpg','image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    $broker_license = $prc_license = $resume = $valid_id = null;
    if ($user_type === 'direct_agent') {
        $broker_license = handleUpload(getNestedFile($_FILES['documents'], 'broker_license'), 'broker_license', $docsDir, $imgsDir, $allowedTypes);
        $prc_license    = handleUpload(getNestedFile($_FILES['documents'], 'prc_license'), 'prc_license', $docsDir, $imgsDir, $allowedTypes);
        $resume         = handleUpload(getNestedFile($_FILES['documents'], 'resume'), 'resume', $docsDir, $imgsDir, $allowedTypes);
        $valid_id       = handleUpload(getNestedFile($_FILES['documents'], 'valid_id'), 'valid_id', $docsDir, $imgsDir, $allowedTypes);

        if (!$broker_license || !$prc_license || !$resume || !$valid_id) {
            throw new Exception("All required documents must be uploaded for Direct Agents");
        }
    }

    // ======= Insert Into DB =======
    $pdo->beginTransaction();

$stmt = $pdo->prepare("
    INSERT INTO applications (
        first_name, last_name, email, password_hash, phone, address,
        education, school, course, graduation_year, certifications, training,
        broker_license_path, prc_license_path, resume_path, valid_id_path, additional_docs_path,
        agent_type, company_id, broker_id, license_number, experience_years, specialization, bio,
        status, created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW()
    )
");

$stmt->execute([
    $first_name,
    $last_name,
    $email,
    $password_hash,
    $phone,
    $address,
    $education ?? null,
    $school ?? null,
    $course ?? null,
    $graduation_year ?? null,
    $certifications ?? null,
    $training ?? null,
    $broker_license,
    $prc_license,
    $resume,
    $valid_id,
    $additional_docs ?? null,
    $user_type,
    $company_id,
    $broker_id ?? null,
    $prc_number ?? null,           // <-- PRC Number
    $experience_years ?? null,
    $specializations ?? null,      // <-- Specializations
    $experience_details ?? null    // <-- Experience Details
]);

$pdo->commit();

    // Redirect on success
    header("Location: http://localhost/BatEstateExplorer/index.php");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    echo json_encode($response);
    exit;
}
