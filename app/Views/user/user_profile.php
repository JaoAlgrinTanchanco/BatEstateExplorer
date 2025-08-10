<?php
// user_profile.php

ob_start();

// Example: fetch current user info from session or DB
// Assuming $_SESSION['user_id'] holds logged in user's id

$current_user = null;

if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];

    // Assuming you have $conn as your DB connection
    $stmt = $conn->prepare("SELECT email, user_type, status, created_at FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $current_user = $result->fetch_assoc();
    }
}
?>
<div class="profile-card">
    <h2>Account Information</h2>
    <div class="profile-info">
        <p><strong>Full Name:</strong> <?= htmlspecialchars($current_user['fullname'] ?? 'N/A') ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($current_user['email'] ?? 'N/A') ?></p>
        <p><strong>Phone Number:</strong> <?= htmlspecialchars($current_user['phone_number'] ?? 'N/A') ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($current_user['status'] ?? 'N/A') ?></p>
        <p><strong>Created:</strong> <?= htmlspecialchars($current_user['created_at'] ?? 'N/A') ?></p>
    </div>
</div>
