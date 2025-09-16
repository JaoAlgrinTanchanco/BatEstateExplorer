<?php
// reset_password.php
// ⚠️ Run once, then delete this file for security!

require_once __DIR__ . "/../config/database.php";

$email = "arlenecruz@gmail.com";
$newPassword = "agent123";

// Generate a proper bcrypt hash
$newHash = password_hash($newPassword, PASSWORD_BCRYPT);

// Update in database
$stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?");
$stmt->bind_param("ss", $newHash, $email);

if ($stmt->execute()) {
    echo "Password for {$email} has been reset successfully!<br>";
    echo "New password: {$newPassword}<br>";
    echo "Hash: {$newHash}";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
