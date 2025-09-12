<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/config/pdo_database.php';

// Helper to detect AJAX
function is_ajax() {
    return (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) || (!empty($_POST['ajax']) && $_POST['ajax'] == 1);
}

// Respond either JSON (AJAX) or redirect
function respond($status, $message, $old_inputs = []) {
    if(is_ajax()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => $status, 'message' => $message]);
    } else {
        $_SESSION['notification'] = ['type' => $status, 'message' => $message];
        if(!empty($old_inputs)) $_SESSION['old_inputs'] = $old_inputs;
        $redirect = !empty($_SERVER['HTTP_REFERER']) 
                    ? $_SERVER['HTTP_REFERER'] 
                    : "/auth/agent_registration.php";
        header("Location: $redirect");
    }
    exit;
}

// Only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Invalid request method.');
}

try {
    // --- Sanitize inputs ---
    function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    $fields = [
        'first_name','last_name','email','password','user_type','phone','address',
        'company_id','broker_id','prc_number','experience_years','specializations','experience_details',
        'education','school','course','graduation_year','certifications','training'
    ];

    $old_inputs = [];
    foreach($fields as $f) $old_inputs[$f] = sanitize($_POST[$f] ?? '');

    $old_inputs['company_id'] = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;

    // --- Validation ---
    $required = ['first_name','last_name','email','password','user_type'];
    foreach($required as $r) {
        if(empty($old_inputs[$r])) throw new Exception("All required fields must be filled.");
    }

    if($old_inputs['user_type'] === 'associate_agent' && !$old_inputs['company_id']) {
        throw new Exception("Company selection is required for associate agents.");
    }

    // --- Email duplication checks ---
    $stmt = $pdo->prepare("SELECT id, user_type FROM users WHERE email = ?");
    $stmt->execute([$old_inputs['email']]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if($existingUser) {
        $existingType = $existingUser['user_type'];
        $newType = $old_inputs['user_type'];

        if($existingType === 'user' && ($newType === 'direct_agent' || $newType === 'associate_agent')) {
            // OK
        } elseif($existingType === 'direct_agent' && $newType === 'associate_agent') {
            throw new Exception("Direct agents cannot convert to associate agents.");
        } elseif($existingType === 'associate_agent' && $newType === 'direct_agent') {
            throw new Exception("Associate agents cannot convert to direct agents.");
        } else {
            throw new Exception("Email already exists with the same or incompatible role.");
        }
    }

    // --- Check pending application ---
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE email = ? AND status = 'pending'");
    $stmt->execute([$old_inputs['email']]);
    if($stmt->fetch(PDO::FETCH_ASSOC)) throw new Exception("You already submitted an application. Please wait for approval.");

    // --- Password hash ---
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // --- Handle file uploads ---
    $docsDir = $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/documents/';
    $imgsDir = $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/storage/uploads/images/';
    if(!file_exists($docsDir)) mkdir($docsDir, 0755, true);
    if(!file_exists($imgsDir)) mkdir($imgsDir, 0755, true);

    $allowedTypes = [
        'image/jpeg','image/jpg','image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    function getNestedFile($files, $key) {
        if(!isset($files['name'][$key])) return null;
        return [
            'name'=>$files['name'][$key],
            'type'=>$files['type'][$key],
            'tmp_name'=>$files['tmp_name'][$key],
            'error'=>$files['error'][$key],
            'size'=>$files['size'][$key]
        ];
    }

    function handleUpload($file, $prefix, $docsDir, $imgsDir, $allowedTypes) {
        if(!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
        if(!in_array($file['type'], $allowedTypes)) throw new Exception("Invalid file type: {$file['name']}");
        if($file['size'] > 5*1024*1024) throw new Exception("File too large: {$file['name']}");
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fname = $prefix.'_'.uniqid().'.'.$ext;
        $dest = (strpos($file['type'],'image/')===0)? $imgsDir.$fname : $docsDir.$fname;
        if(!move_uploaded_file($file['tmp_name'],$dest)) throw new Exception("Failed to save file: {$file['name']}");
        return $dest;
    }

    $broker_license = $prc_license = $resume = $valid_id = null;
    if($old_inputs['user_type'] === 'direct_agent') {
        $broker_license = handleUpload(getNestedFile($_FILES['documents'],'broker_license'),'broker_license',$docsDir,$imgsDir,$allowedTypes);
        $prc_license = handleUpload(getNestedFile($_FILES,'prc_license'),'prc_license',$docsDir,$imgsDir,$allowedTypes);
        $resume = handleUpload(getNestedFile($_FILES,'resume'),'resume',$docsDir,$imgsDir,$allowedTypes);
        $valid_id = handleUpload(getNestedFile($_FILES,'valid_id'),'valid_id',$docsDir,$imgsDir,$allowedTypes);
        if(!$broker_license || !$prc_license || !$resume || !$valid_id) throw new Exception("All required documents must be uploaded for Direct Agents.");
    }

    // --- Insert application ---
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO applications (
        user_id, first_name, last_name, email, password_hash, phone, address,
        education, school, course, graduation_year, certifications, training,
        broker_license_path, prc_license_path, resume_path, valid_id_path,
        agent_type, company_id, broker_id, license_number, experience_years, specialization, bio, status
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')");
    
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $old_inputs['first_name'],
        $old_inputs['last_name'],
        $old_inputs['email'],
        $password_hash,
        $old_inputs['phone'],
        $old_inputs['address'],
        $old_inputs['education'] ?: null,
        $old_inputs['school'] ?: null,
        $old_inputs['course'] ?: null,
        $old_inputs['graduation_year'] ?: null,
        $old_inputs['certifications'] ?: null,
        $old_inputs['training'] ?: null,
        $broker_license,
        $prc_license,
        $resume,
        $valid_id,
        $old_inputs['user_type'],
        $old_inputs['company_id'],
        $old_inputs['broker_id'] ?: null,
        $old_inputs['prc_number'] ?: null,
        $old_inputs['experience_years'] ?: null,
        $old_inputs['specializations'] ?: null,
        $old_inputs['experience_details'] ?: null
    ]);
    $pdo->commit();

    respond('success','Submitted successfully! Awaiting approval.');
    
} catch(Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    respond('error',$e->getMessage(),$old_inputs);
}
