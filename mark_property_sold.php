<?php
session_start();
require_once 'config/database.php';

// Set JSON content type header
header('Content-Type: application/json');

// Check if user is logged in and is an associate agent
$is_logged_in = is_logged_in();
$current_user = null;
$is_associate_agent = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_associate_agent = ($current_user && $current_user['user_type'] === 'associate_agent');
}

// Redirect if not logged in or not an associate agent
if (!$is_logged_in || !$is_associate_agent) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Handle POST request to mark property as sold
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
        $action = isset($_POST['action']) ? $_POST['action'] : 'mark_sold';
        
        if (!$property_id) {
            throw new Exception('Invalid property ID');
        }
        
        if (!in_array($action, ['mark_sold', 'revert'])) {
            throw new Exception('Invalid action');
        }
        
        // Get the agent's ID
        $agent_query = "SELECT a.id FROM agents a WHERE a.user_id = ?";
        $stmt = mysqli_prepare($conn, $agent_query);
        mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
        mysqli_stmt_execute($stmt);
        $agent_result = mysqli_stmt_get_result($stmt);
        $agent = mysqli_fetch_assoc($agent_result);
        
        if (!$agent) {
            throw new Exception('Agent not found');
        }
        
        $agent_id = $agent['id'];
        
        // Check if property exists and belongs to the same company
        $property_query = "SELECT p.*, a.company_id 
                          FROM properties p 
                          LEFT JOIN agents a ON p.agent_id = a.id 
                          WHERE p.id = ? AND a.company_id = (SELECT company_id FROM agents WHERE user_id = ?)";
        $stmt = mysqli_prepare($conn, $property_query);
        mysqli_stmt_bind_param($stmt, "ii", $property_id, $current_user['id']);
        mysqli_stmt_execute($stmt);
        $property_result = mysqli_stmt_get_result($stmt);
        $property = mysqli_fetch_assoc($property_result);
        
        if (!$property) {
            throw new Exception('Property not found or not accessible');
        }
        
        // Check current status based on action
        if ($action === 'mark_sold' && $property['status'] === 'sold') {
            throw new Exception('Property is already marked as sold');
        }
        
        if ($action === 'revert' && $property['status'] === 'available') {
            throw new Exception('Property is already available');
        }
        
        // Update property status based on action
        if ($action === 'mark_sold') {
            $new_status = 'sold';
            $sold_by_agent_id = $agent_id;
            $message = 'Property marked as sold successfully';
        } else {
            $new_status = 'available';
            $sold_by_agent_id = 'NULL';
            $message = 'Property reverted to available successfully';
        }
        
        // Update property status
        if ($action === 'mark_sold') {
            $update_query = "UPDATE properties SET status = 'sold', sold_by_agent_id = ?, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ii", $agent_id, $property_id);
        } else {
            $update_query = "UPDATE properties SET status = 'available', sold_by_agent_id = NULL, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "i", $property_id);
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update property: ' . mysqli_error($conn));
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $message
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 