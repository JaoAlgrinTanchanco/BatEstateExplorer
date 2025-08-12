<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and is admin
$is_logged_in = is_logged_in();
$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_admin = ($current_user && $current_user['user_type'] === 'admin');
}

// Redirect if not logged in or not admin
if (!$is_logged_in || !$is_admin) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get application ID from request
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$application_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid application ID']);
    exit;
}

// Fetch application details
$query = "
    SELECT a.*, c.name AS company_name
    FROM applications a
    LEFT JOIN companies c ON a.company_id = c.id
    WHERE a.id = ?
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $application_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $application = mysqli_fetch_assoc($result);
    
    // Sanitize values for JSON output
    $application = array_map(function ($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }, $application);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'application' => $application
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Application not found'
    ]);
}
