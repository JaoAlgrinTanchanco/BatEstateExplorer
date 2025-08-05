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

// Handle GET request to get company listings
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get the agent's company_id
        $agent_query = "SELECT a.company_id, c.name as company_name 
                       FROM agents a 
                       LEFT JOIN companies c ON a.company_id = c.id 
                       WHERE a.user_id = ?";
        $stmt = mysqli_prepare($conn, $agent_query);
        mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
        mysqli_stmt_execute($stmt);
        $agent_result = mysqli_stmt_get_result($stmt);
        $agent = mysqli_fetch_assoc($agent_result);
        
        if (!$agent || !$agent['company_id']) {
            echo json_encode(['success' => false, 'message' => 'Agent not found or no company assigned']);
            exit;
        }
        
        // Get all properties from agents in the same company
        $query = "SELECT p.*, 
                         u.first_name, u.last_name,
                         a.company_id, c.name as company_name,
                         sold_by_agent.first_name as sold_by_first_name,
                         sold_by_agent.last_name as sold_by_last_name
                  FROM properties p 
                  LEFT JOIN agents a ON p.agent_id = a.id
                  LEFT JOIN users u ON a.user_id = u.id
                  LEFT JOIN companies c ON a.company_id = c.id
                  LEFT JOIN agents sold_by_agent_table ON p.sold_by_agent_id = sold_by_agent_table.id
                  LEFT JOIN users sold_by_agent ON sold_by_agent_table.user_id = sold_by_agent.id
                  WHERE a.company_id = ? AND p.status IN ('available', 'sold')
                  ORDER BY p.created_at DESC";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $agent['company_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $properties = [];
        while ($property = mysqli_fetch_assoc($result)) {
            // Get property images
            $images_query = "SELECT id, image_path FROM property_images WHERE property_id = ? ORDER BY created_at ASC LIMIT 1";
            $img_stmt = mysqli_prepare($conn, $images_query);
            mysqli_stmt_bind_param($img_stmt, "i", $property['id']);
            mysqli_stmt_execute($img_stmt);
            $images_result = mysqli_stmt_get_result($img_stmt);
            $main_image = mysqli_fetch_assoc($images_result);
            
            $properties[] = [
                'id' => $property['id'],
                'title' => $property['title'],
                'description' => $property['description'],
                'property_type' => $property['property_type'],
                'location' => $property['location'],
                'price' => $property['price'],
                'bedrooms' => $property['bedrooms'],
                'bathrooms' => $property['bathrooms'],
                'sqm' => $property['sqm'],
                'lot_size' => $property['lot_size'],
                'status' => $property['status'],
                'created_at' => $property['created_at'],
                'updated_at' => $property['updated_at'],
                'agent_id' => $property['agent_id'],
                'created_by_name' => $property['first_name'] . ' ' . $property['last_name'],
                'company_name' => $property['company_name'],
                'main_image' => $main_image ? $main_image['image_path'] : null,
                'sold_by_name' => ($property['sold_by_first_name'] && $property['sold_by_last_name']) ? 
                    $property['sold_by_first_name'] . ' ' . $property['sold_by_last_name'] : null
            ];
        }
        
        echo json_encode([
            'success' => true, 
            'properties' => $properties,
            'company_name' => $agent['company_name']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 