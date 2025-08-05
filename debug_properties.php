<?php
session_start();
require_once 'config/database.php';

echo "<h2>Debug Properties and Agent Relationships</h2>";

// Check if user is logged in
$is_logged_in = is_logged_in();
if (!$is_logged_in) {
    echo "Not logged in";
    exit;
}

$current_user = get_logged_in_user($conn);
echo "<p><strong>Current User:</strong> ID: {$current_user['id']}, Type: {$current_user['user_type']}</p>";

// Get agent info
$agent_query = "SELECT a.id, a.company_id, c.name as company_name 
                FROM agents a 
                LEFT JOIN companies c ON a.company_id = c.id 
                WHERE a.user_id = ?";
$stmt = mysqli_prepare($conn, $agent_query);
mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
mysqli_stmt_execute($stmt);
$agent_result = mysqli_stmt_get_result($stmt);
$agent = mysqli_fetch_assoc($agent_result);

if ($agent) {
    echo "<p><strong>Agent Info:</strong> ID: {$agent['id']}, Company: {$agent['company_name']}</p>";
    
    // Get all properties for this company
    $properties_query = "SELECT p.*, u.first_name, u.last_name, a.id as agent_id
                        FROM properties p 
                        LEFT JOIN agents a ON p.agent_id = a.id
                        LEFT JOIN users u ON a.user_id = u.id
                        WHERE a.company_id = ?
                        ORDER BY p.created_at DESC";
    $stmt = mysqli_prepare($conn, $properties_query);
    mysqli_stmt_bind_param($stmt, "i", $agent['company_id']);
    mysqli_stmt_execute($stmt);
    $properties_result = mysqli_stmt_get_result($stmt);
    
    echo "<h3>All Company Properties:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Property ID</th><th>Title</th><th>Agent ID</th><th>Agent Name</th><th>Status</th><th>Created</th></tr>";
    
    while ($property = mysqli_fetch_assoc($properties_result)) {
        $is_own = ($property['agent_id'] == $agent['id']) ? "✅ OWN" : "❌ OTHER";
        echo "<tr>";
        echo "<td>{$property['id']}</td>";
        echo "<td>{$property['title']}</td>";
        echo "<td>{$property['agent_id']} {$is_own}</td>";
        echo "<td>{$property['first_name']} {$property['last_name']}</td>";
        echo "<td>{$property['status']}</td>";
        echo "<td>{$property['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p>Agent record not found</p>";
}

mysqli_close($conn);
?> 