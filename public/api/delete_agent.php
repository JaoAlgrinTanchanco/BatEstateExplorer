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
            // Flash message before destroying session
            $_SESSION['flash_success'] = 'Account deleted successfully.';

            // Clear session
            session_unset();
            session_destroy();

            // Redirect to login with query message
            header("Location: /BatEstateExplorer/auth/login.php?message=Account+deleted+successfully");
            exit;
        } else {
            $_SESSION['flash_error'] = 'Error deleting account. Please try again.';
            header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=overview");
            exit;
        }
    } else {
        $_SESSION['flash_error'] = 'Invalid user ID.';
        header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=overview");
        exit;
    }
}
?>
