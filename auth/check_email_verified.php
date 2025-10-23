<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Default response
$response = ['verified' => false];

// Check if email is provided
if (isset($_GET['email'])) {
    $email = trim($_GET['email']);

    // If the session has the email marked as verified
    if (isset($_SESSION['verified_email']) && $_SESSION['verified_email'] === $email) {
        $response['verified'] = true;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
