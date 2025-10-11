<?php
// ==========================================
// send_message.php
// Supports encrypted text + multi-image upload
// Saves images to: /storage/uploads/message_images/
// ==========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../app/bootstrap.php';

// === Load encryption key ===
$secretPath = __DIR__ . '/../../config/secret.php';
if (!file_exists($secretPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "Encryption key file missing at: $secretPath"]);
    exit;
}

$secrets = require $secretPath;
if (empty($secrets['ENCRYPTION_KEY'])) {
    echo json_encode(['success' => false, 'error' => 'ENCRYPTION_KEY missing in secret.php']);
    exit;
}

define('ENCRYPTION_KEY', $secrets['ENCRYPTION_KEY']);

// === Check logged-in user ===
if (isset($_SESSION['user']['id'])) {
    $sender_id = (int) $_SESSION['user']['id'];
} elseif (isset($_SESSION['user_id'])) {
    $sender_id = (int) $_SESSION['user_id'];
} else {
    echo json_encode(['success' => false, 'error' => 'User not logged in.']);
    exit;
}

// === Get POST data ===
$receiver_id = isset($_POST['receiver_id']) ? (int) $_POST['receiver_id'] : null;
$message     = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$receiver_id) {
    echo json_encode(['success' => false, 'error' => 'Missing receiver ID.']);
    exit;
}

// === Validate receiver ===
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res->fetch_assoc()) {
    echo json_encode(['success' => false, 'error' => 'Receiver not found.']);
    $stmt->close();
    exit;
}
$stmt->close();

// === Handle multiple file uploads ===
$image_paths = [];
if (!empty($_FILES['attachments']['name'][0])) {
    $upload_dir = __DIR__ . '/../../storage/uploads/message_images/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    foreach ($_FILES['attachments']['name'] as $index => $original_name) {
        $tmp_name = $_FILES['attachments']['tmp_name'][$index];
        $error    = $_FILES['attachments']['error'][$index];
        $ext      = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if ($error === UPLOAD_ERR_OK && in_array($ext, $allowed)) {
            $new_name = uniqid('msg_', true) . '.' . $ext;
            $target_path = $upload_dir . $new_name;

            if (move_uploaded_file($tmp_name, $target_path)) {
                // Save relative path for web use
                $image_paths[] = '/BatEstateExplorer/storage/uploads/message_images/' . $new_name;
            }
        }
    }
}

// === Encrypt message (if provided) ===
$encrypted_message = '';
if ($message !== '') {
    try {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($message, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
        if ($encrypted === false) throw new Exception('Encryption failed.');
        $encrypted_message = base64_encode($iv . $encrypted);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// === If both message and images are empty, block ===
if ($encrypted_message === '' && empty($image_paths)) {
    echo json_encode(['success' => false, 'error' => 'Cannot send an empty message.']);
    exit;
}

// === Insert message ===
$jsonPaths = !empty($image_paths) ? json_encode($image_paths, JSON_UNESCAPED_SLASHES) : null;

$stmt = $conn->prepare("
    INSERT INTO messages (sender_id, receiver_id, message, image_path, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->bind_param("iiss", $sender_id, $receiver_id, $encrypted_message, $jsonPaths);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => [
            'sender'     => 'You',
            'text'       => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            'images'     => $image_paths,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'DB Error: ' . $stmt->error]);
}

$stmt->close();
exit;
