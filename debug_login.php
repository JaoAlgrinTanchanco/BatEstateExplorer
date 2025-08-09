<?php
// Debug login process
session_start();
echo "<h2>Debug Login Process</h2>";

echo "<h3>1. Session Status:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Data: " . print_r($_SESSION, true) . "</p>";

echo "<h3>2. POST Data:</h3>";
echo "<p>POST Method: " . ($_SERVER['REQUEST_METHOD'] === 'POST' ? 'YES' : 'NO') . "</p>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p>POST Data: " . print_r($_POST, true) . "</p>";
}

echo "<h3>3. Database Connection Test:</h3>";
require_once 'config/database.php';
if (isset($conn)) {
    echo "<p>Database connection: SUCCESS</p>";
    
    // Test if functions exist
    if (function_exists('is_logged_in')) {
        echo "<p>is_logged_in function: EXISTS</p>";
        echo "<p>is_logged_in result: " . (is_logged_in() ? 'TRUE' : 'FALSE') . "</p>";
    } else {
        echo "<p>is_logged_in function: MISSING</p>";
    }
    
    if (function_exists('get_logged_in_user')) {
        echo "<p>get_logged_in_user function: EXISTS</p>";
    } else {
        echo "<p>get_logged_in_user function: MISSING</p>";
    }
    
    if (function_exists('current_user')) {
        echo "<p>current_user function: EXISTS</p>";
    } else {
        echo "<p>current_user function: MISSING</p>";
    }
} else {
    echo "<p>Database connection: FAILED</p>";
}

echo "<h3>4. Redirect Functions Test:</h3>";
require_once 'app/redirects.php';
if (function_exists('redirect_by_user_type')) {
    echo "<p>redirect_by_user_type function: EXISTS</p>";
} else {
    echo "<p>redirect_by_user_type function: MISSING</p>";
}

echo "<h3>5. Test Login Form:</h3>";
?>
<form method="POST" action="">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Test Login</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email']) && !empty($_POST['password'])) {
    echo "<h3>6. Processing Login:</h3>";
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    echo "<p>Email: $email</p>";
    echo "<p>Password: [HIDDEN]</p>";
    
    // Test database query
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            echo "<p>User found: " . $user['email'] . "</p>";
            echo "<p>User type: " . $user['user_type'] . "</p>";
            echo "<p>Status: " . $user['status'] . "</p>";
            
            // Test redirect function
            echo "<p>Testing redirect function...</p>";
            echo "<p>Would redirect to: ";
            switch ($user['user_type']) {
                case 'admin':
                    echo "../public/admin/admin_dashboard.php";
                    break;
                case 'direct_agent':
                case 'associate_agent':
                    echo "../public/agent/agent_dashboard.php";
                    break;
                default:
                    echo "../public/user/user_dashboard.php";
            }
            echo "</p>";
            
        } else {
            echo "<p>User not found</p>";
        }
    } else {
        echo "<p>Database query failed</p>";
    }
}
?>
