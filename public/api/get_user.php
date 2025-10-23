<?php
require_once __DIR__ . '/../../config/database.php';

$email = $_GET['email'] ?? '';
if (!$email) { echo json_encode(['error'=>'Email required']); exit; }

$stmt = $conn->prepare("SELECT first_name,last_name,email,privilege_tag FROM users WHERE email=?");
$stmt->bind_param("s",$email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user) echo json_encode(['error'=>'User not found']);
else echo json_encode($user);
?>
