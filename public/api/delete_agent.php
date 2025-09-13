<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);

        if (mysqli_stmt_execute($stmt)) {
            // Set notification BEFORE destroying session
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Account deleted successfully.'
            ];

            // Destroy session
            session_unset();
            session_destroy();

            // Redirect to login page (notification will show if login page includes the component)
            header("Location: /BatEstateExplorer/auth/login.php");
            exit;
        } else {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Error deleting account. Please try again.'
            ];
            header("Location: /BatEstateExplorer/public/direct_profile.php?tab=overview");
            exit;
        }
    } else {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Invalid user ID.'
        ];
        header("Location: /BatEstateExplorer/public/direct_profile.php?tab=overview");
        exit;
    }
}
