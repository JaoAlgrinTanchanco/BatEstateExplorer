<?php
session_start();
require_once 'config/database.php';

// Handle AJAX login requests (for agent login from index.php and Auth Modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $email = sanitize_input($conn, $_POST['email']);
        $password = $_POST['password'];
        $user_type = isset($_POST['user_type']) ? sanitize_input($conn, $_POST['user_type']) : null;
        
        if (empty($email) || empty($password)) {
            throw new Exception('Please enter both email and password.');
        }
        
        // Find user by email
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            // Verify password
            if (verify_password($password, $user['password_hash'])) {
                // If user_type is specified, check if it matches
                if ($user_type && $user['user_type'] !== $user_type) {
                    throw new Exception('Access denied. ' . ucfirst(str_replace('_', ' ', $user_type)) . ' privileges required.');
                }
                
                // Check if user is approved
                if ($user['status'] !== 'active') {
                    throw new Exception('Your account is pending approval. Please wait for admin review.');
                }
                
                // Generate token and store in session
                $token = generate_token($user['id'], $user['email'], $user['user_type']);
                $_SESSION['user_token'] = $token;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                
                $response['success'] = true;
                $response['message'] = 'Login successful!';
                $response['user_type'] = $user['user_type'];
                
            } else {
                throw new Exception('Invalid email or password.');
            }
        } else {
            throw new Exception('Invalid email or password.');
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle regular form login (existing functionality)
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($conn, $_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Find user by email
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            // Verify password
            if (verify_password($password, $user['password_hash'])) {
                // Check if user is approved
                if ($user['status'] !== 'active') {
                    $error = "Your account is pending approval. Please wait for admin review.";
                } else {
                    // Generate token and store in session
                    $token = generate_token($user['id'], $user['email'], $user['user_type']);
                    $_SESSION['user_token'] = $token;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    
                    // Redirect based on user type
                    switch ($user['user_type']) {
                        case 'admin':
                            header('Location: admin_dashboard_new.php');
                            exit;
                        case 'direct_agent':
                            header('Location: direct_agent_dashboard_full.php');
                            exit;
                        case 'associate_agent':
                            header('Location: associate_agent_dashboard_full.php');
                            exit;
                        default:
                            header('Location: user_index.php');
                            exit;
                    }
                }
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BatEstate</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .form-container {
            padding: 40px;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin: 0 10px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .divider {
            margin: 0 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏠 BatEstate</h1>
            <p>Welcome Back</p>
        </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="submit-btn">Login</button>
            </form>
            
        </div>
    </div>
</body>
</html> 