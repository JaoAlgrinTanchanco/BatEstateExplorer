<?php
session_start();
require_once 'config/database.php';

echo "<h2>Check Agent Properties</h2>";

// Check if user is logged in and is a direct agent or associate agent
$is_logged_in = is_logged_in();
if (!$is_logged_in) {
    throw new Exception('User not logged in');
}

$current_user = get_logged_in_user($conn);
if (!$current_user || ($current_user['user_type'] !== 'direct_agent' && $current_user['user_type'] !== 'associate_agent')) {
    throw new Exception('Unauthorized access');
}

echo "<h3>User Info:</h3>";
echo "<p>User ID: " . $current_user['id'] . "</p>";
echo "<p>User Type: " . $current_user['user_type'] . "</p>";

// Get agent ID
$agent_query = "SELECT a.id FROM agents a WHERE a.user_id = ?";
$stmt = mysqli_prepare($conn, $agent_query);
mysqli_stmt_bind_param($stmt, "i", $current_user['id']);
mysqli_stmt_execute($stmt);
$agent_result = mysqli_stmt_get_result($stmt);
$agent = mysqli_fetch_assoc($agent_result);

if (!$agent) {
    echo "<p>❌ Agent record not found</p>";
    exit;
}

echo "<p>✅ Agent ID: " . $agent['id'] . "</p>";

// Get properties owned by this agent
$properties_query = "SELECT id, title, property_type, status FROM properties WHERE agent_id = ?";
$stmt = mysqli_prepare($conn, $properties_query);
mysqli_stmt_bind_param($stmt, "i", $agent['id']);
mysqli_stmt_execute($stmt);
$properties_result = mysqli_stmt_get_result($stmt);

echo "<h3>Properties Owned by This Agent:</h3>";
if (mysqli_num_rows($properties_result) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Status</th></tr>";
    while ($property = mysqli_fetch_assoc($properties_result)) {
        echo "<tr>";
        echo "<td>" . $property['id'] . "</td>";
        echo "<td>" . $property['title'] . "</td>";
        echo "<td>" . $property['property_type'] . "</td>";
        echo "<td>" . $property['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No properties found for this agent.</p>";
}
?> 