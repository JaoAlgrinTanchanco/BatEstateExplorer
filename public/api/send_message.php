<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../app/bootstrap.php';

// ✅ Encryption key
define('ENCRYPTION_KEY', '12345678901234567890123456789012'); // 32 chars for AES-256

// Check logged-in user
if (isset($_SESSION['user']['id'])) {
    $sender_id = (int) $_SESSION['user']['id'];
} elseif (isset($_SESSION['user_id'])) {
    $sender_id = (int) $_SESSION['user_id'];
} else {
    echo json_encode(['success'=>false,'error'=>'User not logged in.']);
    exit;
}

// Get POST data
$receiver_id = isset($_POST['receiver_id']) ? (int) $_POST['receiver_id'] : null;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$receiver_id || !$message) {
    echo json_encode(['success'=>false,'error'=>'Missing receiver or message.']);
    exit;
}

// Check if receiver exists
$stmt = $conn->prepare("SELECT id FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i",$receiver_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res->fetch_assoc()) {
    echo json_encode(['success'=>false,'error'=>'Receiver not found.']);
    exit;
}
$stmt->close();

// 🔹 Encrypt message before storing
$iv = random_bytes(16); // 16 bytes IV for AES-256-CBC
$encrypted = openssl_encrypt($message, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
$encrypted_message = base64_encode($iv . $encrypted); // prepend IV to ciphertext

// Insert into DB
$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)");
$stmt->bind_param("iis",$sender_id,$receiver_id,$encrypted_message);

if ($stmt->execute()) {
    echo json_encode([
        'success'=>true,
        'message'=>[
            'sender'=>'You',
            'text'=>$message, // decrypted message for UI
            'created_at'=>date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['success'=>false,'error'=>'Failed to send message.']);
}

$stmt->close();
exit;
