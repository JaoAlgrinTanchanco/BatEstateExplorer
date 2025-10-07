<?php
session_start();
<<<<<<< HEAD
require_once $_SERVER['DOCUMENT_ROOT'] . '/BatEstateExplorer/config/pdo_database.php';

// === Helper Functions ===
function is_ajax() {
    return (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) || (!empty($_POST['ajax']) && $_POST['ajax'] == 1);
}

function respond($status, $message, $old_inputs = []) {
    if (is_ajax()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => $status, 'message' => $message]);
    } else {
        $_SESSION['notification'] = ['type' => $status, 'message' => $message];
        if(!empty($old_inputs)) $_SESSION['old_inputs'] = $old_inputs;
        $redirect = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "/auth/agent_registration.php";
        header("Location: $redirect");
=======
require_once $_SERVER['DOCUMENT_ROOT'].'/BatEstateExplorer/config/pdo_database.php';

// === Helper Functions ===
function is_ajax(): bool {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest')
        || (!empty($_POST['ajax']) && $_POST['ajax']==1);
}

function respond(string $status, string $message, array $old_inputs=[], array $debug=[]): void {
    if (is_ajax()) {
        header('Content-Type: application/json');
        echo json_encode(['status'=>$status,'message'=>$message,'old_inputs'=>$old_inputs,'debug'=>$debug]);
    } else {
        $_SESSION['notification'] = ['type'=>$status,'message'=>$message];
        if (!empty($old_inputs)) $_SESSION['old_inputs'] = $old_inputs;
        $redirect = $_SERVER['HTTP_REFERER'] ?? "/auth/agent_registration.php";
        header("Location:$redirect");
>>>>>>> origin/ansel
    }
    exit;
}

<<<<<<< HEAD
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

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
    // No size limit for smooth UX
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fname = $prefix.'_'.uniqid().'.'.$ext;
    $dest = (strpos($file['type'],'image/')===0) ? $imgsDir.$fname : $docsDir.$fname;
    if(!move_uploaded_file($file['tmp_name'],$dest)) throw new Exception("Failed to save file: {$file['name']}");
    return $dest;
}

// Only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Invalid request method.');
}

try {
    // --- Sanitize inputs ---
=======
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function getNestedFile(array $files, string $key): ?array {
    if (!isset($files['name'][$key])) return null;
    return [
        'name' => $files['name'][$key],
        'type' => $files['type'][$key],
        'tmp_name' => $files['tmp_name'][$key],
        'error' => $files['error'][$key],
        'size' => $files['size'][$key]
    ];
}

function handleUpload(array $file=null, string $prefix, string $docsDir, string $imgsDir, array $allowedTypes, array &$debug=[]): ?string {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $debug[] = "No file or upload error: " . ($file['error'] ?? 'N/A');
        return null;
    }
    if (!in_array($file['type'], $allowedTypes, true)) throw new Exception("Invalid file type: {$file['name']}");
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fname = $prefix.'_'.uniqid().'.'.$ext;
    $dest = (strpos($file['type'], 'image/') === 0) ? $imgsDir.$fname : $docsDir.$fname;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $debug[] = "Failed move_uploaded_file: tmp_name={$file['tmp_name']} dest=$dest";
        throw new Exception("Failed to save file: {$file['name']}");
    }
    $debug[] = "File uploaded: $dest";
    return str_replace($_SERVER['DOCUMENT_ROOT'].'/', '', $dest);
}

// === MAIN LOGIC ===
$debug = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond('error','Invalid request method.',$debug);

try {
    // Collect and sanitize inputs
>>>>>>> origin/ansel
    $fields = [
        'first_name','last_name','email','password','user_type','phone','address',
        'company_id','broker_id','prc_number','experience_years','specializations','experience_details',
        'education','school','course','graduation_year','certifications','training'
    ];
    $old_inputs = [];
<<<<<<< HEAD
    foreach($fields as $f) $old_inputs[$f] = sanitize($_POST[$f] ?? '');
    $old_inputs['company_id'] = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;

    // --- Basic Validation ---
    $required = ['first_name','last_name','email','password','user_type'];
    foreach($required as $r) {
        if(empty($old_inputs[$r])) throw new Exception("All required fields must be filled.");
    }

    if($old_inputs['user_type'] === 'associate_agent' && !$old_inputs['company_id']) {
        throw new Exception("Company selection is required for associate agents.");
    }

    // --- Email Duplication Check ---
    $stmt = $pdo->prepare("SELECT id, user_type FROM users WHERE email = ?");
    $stmt->execute([$old_inputs['email']]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if($existingUser) {
        $existingType = $existingUser['user_type'];
        $newType = $old_inputs['user_type'];
        if($existingType === 'user' && in_array($newType, ['direct_agent','associate_agent'])) {
            // OK
        } elseif($existingType === 'direct_agent' && $newType === 'associate_agent') {
            throw new Exception("Direct agents cannot convert to associate agents.");
        } elseif($existingType === 'associate_agent' && $newType === 'direct_agent') {
            throw new Exception("Associate agents cannot convert to direct agents.");
        } else {
            throw new Exception("Email already exists with the same or incompatible role.");
        }
    }

    // --- Pending Application Check ---
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE email = ? AND status = 'pending'");
    $stmt->execute([$old_inputs['email']]);
    if($stmt->fetch(PDO::FETCH_ASSOC)) throw new Exception("You already submitted an application. Please wait for approval.");

    // --- Password Hash ---
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // --- Directories ---
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

    // --- Handle Documents for Direct Agents ---
    $broker_license = $prc_license = $resume = $valid_id = null;
    if($old_inputs['user_type'] === 'direct_agent') {
        $requiredDocs = ['broker_license','prc_license','resume','valid_id'];
        $uploaded = [];
        foreach($requiredDocs as $doc) {
            $uploaded[$doc] = handleUpload(getNestedFile($_FILES['documents'],$doc), $doc, $docsDir, $imgsDir, $allowedTypes);
        }
        if(in_array(null,$uploaded,true)) throw new Exception("All required documents must be uploaded for Direct Agents.");
        $broker_license = $uploaded['broker_license'];
        $prc_license    = $uploaded['prc_license'];
        $resume         = $uploaded['resume'];
        $valid_id       = $uploaded['valid_id'];
    }

    // --- Insert Application ---
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        INSERT INTO applications (
            user_id, first_name, last_name, email, password_hash, phone, address,
            education, school, course, graduation_year, certifications, training,
            broker_license_path, prc_license_path, resume_path, valid_id_path,
            agent_type, company_id, broker_id, license_number, experience_years, specialization, bio, status
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')
    ");
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
    respond('error', $e->getMessage(), $old_inputs);
}
=======
    foreach ($fields as $f) $old_inputs[$f] = isset($_POST[$f]) ? sanitize($_POST[$f]) : '';
    $old_inputs['company_id'] = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;

    // === REQUIRED FIELDS VALIDATION ===
    $required_fields = [
        // Personal Information
        'first_name',
        'last_name',
        'email',
        'password',
        'user_type',
        'phone',
        'address',

        // Professional Information
        'experience_years',  // required
        // 'experience_details' is optional

        // Educational Background
        'education',
        'school',
        'course'
    ];

    foreach ($required_fields as $f) {
        if (empty($old_inputs[$f])) throw new Exception("The field '$f' is required.");
    }

    // Associate agent must select company
    if ($old_inputs['user_type'] === 'associate_agent' && !$old_inputs['company_id']) 
        throw new Exception("Company selection is required for associate agents.");

    // Check existing users and pending applications
    $stmt = $pdo->prepare("SELECT id,user_type FROM users WHERE email=?"); 
    $stmt->execute([$old_inputs['email']]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) throw new Exception("Email already exists with the same or incompatible role.");

    $stmt = $pdo->prepare("SELECT id FROM applications WHERE email=? AND status='pending'"); 
    $stmt->execute([$old_inputs['email']]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) throw new Exception("You already submitted an application. Please wait for approval.");

    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Upload directories
    $docsDir = $_SERVER['DOCUMENT_ROOT'].'/BatEstateExplorer/storage/uploads/documents/';
    $imgsDir = $_SERVER['DOCUMENT_ROOT'].'/BatEstateExplorer/storage/uploads/images/';
    $profileDir = $_SERVER['DOCUMENT_ROOT'].'/BatEstateExplorer/storage/uploads/profile_images/';
    foreach([$docsDir,$imgsDir,$profileDir] as $dir) if(!file_exists($dir)) mkdir($dir,0755,true);

    $allowedTypes = [
        'image/jpeg','image/jpg','image/png',
        'application/pdf','application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    // Handle required docs for direct agents
    $broker_license = $prc_license = $resume = $valid_id = null;
    if ($old_inputs['user_type'] === 'direct_agent') {
        $requiredDocs = ['broker_license','prc_license','resume','valid_id'];
        foreach($requiredDocs as $doc){
            $file = getNestedFile($_FILES['documents'], $doc);
            ${$doc} = handleUpload($file, $doc, $docsDir, $imgsDir, $allowedTypes, $debug);
            if (${$doc} === null) throw new Exception("All required documents must be uploaded for Direct Agents.");
        }
    }

    // Profile picture is now optional
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        $profile_picture = handleUpload(
            $_FILES['profile_picture'],
            'pfp',
            $docsDir,
            $profileDir,
            ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
            $debug
        );
        if (!$profile_picture) $debug[] = "Profile picture handling returned null";
    }

    // Specializations
    $specializations = trim($old_inputs['specializations']);
    $specializations = !empty($specializations) ? json_encode(array_map('trim', explode(',', $specializations))) : null;

    // Insert application
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO applications (
        first_name, last_name, email, password_hash, phone, address,
        education, school, course, graduation_year, certifications, training,
        broker_license_path, prc_license_path, resume_path, valid_id_path,
        agent_type, company_id, broker_id, license_number, experience_years, specialization, bio,
        profile_image_path
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $stmt->execute([
        $old_inputs['first_name'],$old_inputs['last_name'],$old_inputs['email'],$password_hash,
        $old_inputs['phone'],$old_inputs['address'],$old_inputs['education'],$old_inputs['school'],$old_inputs['course'],
        $old_inputs['graduation_year'],$old_inputs['certifications']?:null,$old_inputs['training']?:null,
        $broker_license,$prc_license,$resume,$valid_id,
        $old_inputs['user_type'],$old_inputs['company_id'],$old_inputs['broker_id']?:null,
        $old_inputs['prc_number']?:null,$old_inputs['experience_years'],$specializations,$old_inputs['experience_details'],
        $profile_picture
    ]);

    $application_id = $pdo->lastInsertId();
    $debug[] = "Inserted application_id: $application_id";

    $pdo->commit();
    respond('success','Submitted successfully! Awaiting approval.',$old_inputs,$debug);

} catch(Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $debug[] = "Exception: ".$e->getMessage();
    respond('error',$e->getMessage(),$old_inputs,$debug);
}
?>
>>>>>>> origin/ansel
