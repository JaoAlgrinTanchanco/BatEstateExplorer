<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check admin login
$is_logged_in = is_logged_in();
$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_admin = ($current_user && $current_user['user_type'] === 'admin');
}

if (!$is_logged_in || !$is_admin) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get application ID
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$application_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid application ID']);
    exit;
}

// Fetch application details
$query = "
    SELECT 
        a.*, 
        c.name AS company_name 
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

    // Remove sensitive info
    unset($application['password_hash']);

    // Fix document URLs for Direct Agents
    if ($application['agent_type'] === 'direct_agent') {
        $docBaseUrl = 'http://localhost/BatEstateExplorer/storage/uploads/documents/';
        $imgBaseUrl = 'http://localhost/BatEstateExplorer/storage/uploads/images/';

        function isImage($path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            return in_array($ext, ['jpg','jpeg','png','gif']);
        }

        $docs = ['broker_license_path', 'prc_license_path', 'resume_path', 'valid_id_path'];
        foreach ($docs as $doc) {
            if (!empty($application[$doc])) {
                $application[$doc] = isImage($application[$doc])
                    ? $imgBaseUrl . basename($application[$doc])
                    : $docBaseUrl . basename($application[$doc]);
            } else {
                $application[$doc] = '';
            }
        }
    }

    // Sanitize all other fields except document URLs
    foreach ($application as $key => $value) {
        if (!in_array($key, ['broker_license_path','prc_license_path','resume_path','valid_id_path'])) {
            $application[$key] = htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
        }
    }

    // Map DB fields to modal-friendly fields with fallbacks
    $application['prc_number'] = !empty($application['license_number']) ? $application['license_number'] : 'N/A';

    // Decode specialization JSON string to array
    $application['specializations'] = [];
    if (!empty($application['specialization'])) {
        // Decode HTML entities first
        $decoded_json = html_entity_decode($application['specialization']);
        $decoded = json_decode($decoded_json, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $application['specializations'] = $decoded;
        } else {
            // fallback if it's comma-separated
            $application['specializations'] = array_map('trim', explode(',', strip_tags($application['specialization'])));
        }
    }

    // Remove the raw specialization string
    unset($application['specialization']);

    // Add bio as experience details
    $application['experience_details'] = !empty($application['bio']) ? $application['bio'] : 'N/A';

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
