<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
$is_logged_in = is_logged_in();
if (!$is_logged_in) {
    echo "Not logged in";
    exit;
}

$current_user = get_logged_in_user($conn);
$agent_query = "SELECT a.id, a.company_id FROM agents a WHERE a.user_id = ?";
$stmt = mysqli_prepare($conn, $agent_query);
mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
mysqli_stmt_execute($stmt);
$agent_result = mysqli_stmt_get_result($stmt);
$agent = mysqli_fetch_assoc($agent_result);

if (!$agent) {
    echo "Agent not found";
    exit;
}

// Get agent's own properties
$properties_query = "SELECT p.*, u.first_name, u.last_name 
                    FROM properties p 
                    LEFT JOIN agents a ON p.agent_id = a.id
                    LEFT JOIN users u ON a.user_id = u.id
                    WHERE p.agent_id = ?
                    ORDER BY p.created_at DESC";
$stmt = mysqli_prepare($conn, $properties_query);
mysqli_stmt_bind_param($stmt, "i", $agent['id']);
mysqli_stmt_execute($stmt);
$properties_result = mysqli_stmt_get_result($stmt);

echo "<h2>Test My Listings - Agent ID: {$agent['id']}</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Property ID</th><th>Title</th><th>Agent ID</th><th>Status</th><th>Actions</th></tr>";

while ($property = mysqli_fetch_assoc($properties_result)) {
    echo "<tr data-property-id='{$property['id']}'>";
    echo "<td>{$property['id']}</td>";
    echo "<td>{$property['title']}</td>";
    echo "<td>{$property['agent_id']}</td>";
    echo "<td>{$property['status']}</td>";
    echo "<td style='display: flex; gap: 8px; align-items: center;'>";
    echo "<button class='glass-btn' onclick='viewPropertyDetails({$property['id']}, \"my\")'>Details</button>";
    echo "<button class='glass-btn' onclick='editListing({$property['id']})'>Edit</button>";
    echo "<button class='glass-btn danger' onclick='deleteListing({$property['id']})'>Delete</button>";
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<script>";
echo "function viewPropertyDetails(id, section) { alert('Details: ' + id + ' - ' + section); }";
echo "function editListing(id) { alert('Edit: ' + id); }";
echo "function deleteListing(id) { alert('Delete: ' + id); }";
echo "</script>";
?> 