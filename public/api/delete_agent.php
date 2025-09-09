<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        // Delete agent account
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        
        if (mysqli_stmt_execute($stmt)) {
            // Destroy session
            session_unset();
            session_destroy();

            // Redirect to login page
            header("Location: /BatEstateExplorer/auth/login.php?message=Account+deleted+successfully");
            exit;
        } else {
            die("Error deleting account. Please try again.");
        }
    } else {
        die("Invalid user ID.");
    }
}
?>
