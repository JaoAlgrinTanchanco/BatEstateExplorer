<?php
// Test file to verify path calculations
require_once 'app/redirects.php';

echo "<h2>Path Calculation Test</h2>";

// Test from different locations
echo "<h3>Current Script: " . $_SERVER['SCRIPT_NAME'] . "</h3>";

$test_paths = [
    'public/admin/admin_dashboard.php',
    'public/agent/agent_dashboard.php',
    'public/user/user_dashboard.php',
    'auth/login.php',
    'index.php'
];

foreach ($test_paths as $path) {
    $relative = get_relative_path($path);
    echo "<p><strong>$path</strong> → <code>$relative</code></p>";
}

echo "<h3>Testing redirect_by_user_type function:</h3>";
echo "<p>This will redirect you based on user type (but we'll just show the path):</p>";

// Simulate what would happen for each user type
$user_types = ['admin', 'direct_agent', 'associate_agent', 'user'];

foreach ($user_types as $type) {
    echo "<p><strong>$type</strong>: ";
    
    // Get the path without actually redirecting
    switch ($type) {
        case 'admin':
            $path = get_relative_path('public/admin/admin_dashboard.php');
            break;
        case 'direct_agent':
        case 'associate_agent':
            $path = get_relative_path('public/agent/agent_dashboard.php');
            break;
        default:
            $path = get_relative_path('public/user/user_dashboard.php');
    }
    
    echo "<code>$path</code></p>";
}
?>
