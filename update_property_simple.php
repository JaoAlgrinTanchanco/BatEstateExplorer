<?php
// Suppress error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once 'config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if user is logged in and is a direct agent or associate agent
        $is_logged_in = is_logged_in();
        if (!$is_logged_in) {
            throw new Exception('User not logged in');
        }

        $current_user = get_logged_in_user($conn);
        if (!$current_user || ($current_user['user_type'] !== 'direct_agent' && $current_user['user_type'] !== 'associate_agent')) {
            throw new Exception('Unauthorized access');
        }

        $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
        
        if (!$property_id) {
            throw new Exception('Property ID is required');
        }
        
        // Verify the property belongs to this agent or their company
        $agent_query = "SELECT a.id, a.company_id FROM agents a WHERE a.user_id = ?";
        $stmt = mysqli_prepare($conn, $agent_query);
        mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
        mysqli_stmt_execute($stmt);
        $agent_result = mysqli_stmt_get_result($stmt);
        $agent = mysqli_fetch_assoc($agent_result);
        
        if (!$agent) {
            throw new Exception('Agent record not found');
        }
        
        $agent_id = $agent['id'];
        
        // Check if property belongs to this agent or their company
        if ($current_user['user_type'] === 'associate_agent') {
            // Associate agents can edit properties from their company
            $property_check = "SELECT p.id, p.title FROM properties p 
                              LEFT JOIN agents a ON p.agent_id = a.id 
                              WHERE p.id = ? AND a.company_id = ?";
            $stmt = mysqli_prepare($conn, $property_check);
            mysqli_stmt_bind_param($stmt, "ii", $property_id, $agent['company_id']);
        } else {
            // Direct agents can only edit their own properties
            $property_check = "SELECT id, title FROM properties WHERE id = ? AND agent_id = ?";
            $stmt = mysqli_prepare($conn, $property_check);
            mysqli_stmt_bind_param($stmt, "ii", $property_id, $agent_id);
        }
        
        mysqli_stmt_execute($stmt);
        $property_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($property_result) === 0) {
            throw new Exception('Property not found or access denied');
        }
        
        $property = mysqli_fetch_assoc($property_result);
        $property_title = $property['title'];
        
        // Validate required fields
        $required_fields = ['title', 'description', 'location', 'property_type', 'price', 'bedrooms', 'bathrooms', 'sqm', 'status'];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Validate property_type values
        $allowed_property_types = ['Property', 'Lot'];
        if (!in_array($_POST['property_type'], $allowed_property_types)) {
            throw new Exception('Invalid property type. Only Property and Lot are allowed.');
        }
        
        // Validate status values
        $allowed_statuses = ['available', 'sold'];
        if (!in_array($_POST['status'], $allowed_statuses)) {
            throw new Exception('Invalid status. Only Available and Sold are allowed.');
        }
        
        // Sanitize input
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $property_type = mysqli_real_escape_string($conn, $_POST['property_type']);
        $price = (float)$_POST['price'];
        $bedrooms = (int)$_POST['bedrooms'];
        $bathrooms = (int)$_POST['bathrooms'];
        $sqm = (float)$_POST['sqm'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        // Use a simple approach with proper escaping to avoid parameter binding issues
        if ($current_user['user_type'] === 'associate_agent') {
            // Associate agents can update properties from their company
            $query = "UPDATE properties p 
                      LEFT JOIN agents a ON p.agent_id = a.id 
                      SET p.title = '$title', 
                          p.description = '$description', 
                          p.property_type = '$property_type', 
                          p.location = '$location', 
                          p.price = $price, 
                          p.bedrooms = $bedrooms, 
                          p.bathrooms = $bathrooms, 
                          p.sqm = $sqm, 
                          p.status = '$status', 
                          p.updated_at = NOW() 
                      WHERE p.id = $property_id AND a.company_id = " . $agent['company_id'];
        } else {
            // Direct agents can only update their own properties
            $query = "UPDATE properties SET 
                      title = '$title', 
                      description = '$description', 
                      property_type = '$property_type', 
                      location = '$location', 
                      price = $price, 
                      bedrooms = $bedrooms, 
                      bathrooms = $bathrooms, 
                      sqm = $sqm, 
                      status = '$status', 
                      updated_at = NOW() 
                      WHERE id = $property_id AND agent_id = $agent_id";
        }
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception('Failed to update property: ' . mysqli_error($conn));
        }
        
        // Return success response
        echo json_encode([
            'success' => true, 
            'message' => 'Property "' . $property_title . '" updated successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 