<?php
// user_profile.php

ob_start();
?>

<div class="profile-card">
    <h2>Account Information</h2>
    <div class="profile-info">
        <p><strong>Email:</strong> <?= htmlspecialchars($current_user['email'] ?? 'N/A') ?></p>
        <p><strong>User Type:</strong> <?= htmlspecialchars($current_user['user_type'] ?? 'N/A') ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($current_user['status'] ?? 'N/A') ?></p>
        <p><strong>Created:</strong> <?= htmlspecialchars($current_user['created_at'] ?? 'N/A') ?></p>
    </div>
</div>

