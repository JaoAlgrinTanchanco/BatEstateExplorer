<?php
session_start();
require_once 'config/database.php';

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
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Handle GET request to get agent details
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $agent_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($agent_id) {
        // Get agent details with user and company information
        $query = "SELECT u.*, a.broker_id, a.license_number, a.experience_years, a.specialization, a.bio, c.name as company_name 
                  FROM users u 
                  LEFT JOIN agents a ON u.id = a.user_id 
                  LEFT JOIN companies c ON a.company_id = c.id 
                  WHERE u.id = ? AND u.user_type = 'direct_agent'";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $agent_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $agent = mysqli_fetch_assoc($result);
            
            // Get application details for additional information
            $app_query = "SELECT * FROM applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
            $app_stmt = mysqli_prepare($conn, $app_query);
            mysqli_stmt_bind_param($app_stmt, "i", $agent_id);
            mysqli_stmt_execute($app_stmt);
            $app_result = mysqli_stmt_get_result($app_stmt);
            $application = mysqli_fetch_assoc($app_result);
            
            // Combine agent and application data
            $agent_data = array_merge($agent, $application ?: []);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'agent' => $agent_data]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Agent not found']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid agent ID']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 