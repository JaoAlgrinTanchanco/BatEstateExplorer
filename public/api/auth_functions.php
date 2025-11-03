<?php
function login_user($conn, $email, $password = null, $isGoogle = false) {
    // Fetch user by email
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if (!$user) return ['error' => 'No account found with that email.'];

    // If not Google login, verify password
    if (!$isGoogle && !verify_password($password, $user['password_hash'])) {
        return ['error' => 'Incorrect password.'];
    }

    // 🚫 Block check
    if ((int)$user['is_blocked'] === 1) {
        $blockInfo = null;

        // Determine where to check (agent_reports or user_reports)
        if ($user['user_type'] === 'direct_agent' || $user['user_type'] === 'associate_agent') {
            $reportQuery = "
                SELECT reason, other_reason, duration
                FROM agent_reports
                WHERE agent_id = ? AND status = 'blocked'
                ORDER BY blockage_date DESC
                LIMIT 1
            ";
        } else {
            $reportQuery = "
                SELECT reason, other_reason, duration
                FROM user_reports
                WHERE reported_user_id = ? AND status = 'blocked'
                ORDER BY blockage_date DESC
                LIMIT 1
            ";
        }

        $blockQuery = mysqli_prepare($conn, $reportQuery);
        mysqli_stmt_bind_param($blockQuery, "i", $user['id']);
        mysqli_stmt_execute($blockQuery);
        $blockResult = mysqli_stmt_get_result($blockQuery);
        $blockInfo = mysqli_fetch_assoc($blockResult);

        // Determine reason + duration
        $rawReason = $blockInfo['reason'] === 'other'
            ? $blockInfo['other_reason']
            : ($blockInfo['reason'] ?? 'Violation');

        $reasonText = ucwords(str_replace(['_', '-'], ' ', $rawReason));
        $duration = $blockInfo['duration'] ?? '7 days';

        return ['blocked' => true, 'reason' => $reasonText, 'duration' => $duration];
    }

    // 🚫 Account status check
    if ($user['status'] !== 'active') {
        return ['error' => 'Your account is pending approval. Please wait for admin review.'];
    }

    // ✅ Successful login
    $_SESSION['user_token'] = generate_token($user['id'], $user['email'], $user['user_type']);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'profile_image_path' => $user['profile_image_path'] ?? null,
    ];

    return ['success' => true, 'user_id' => $user['id'], 'user_type' => $user['user_type']];
}
?>
