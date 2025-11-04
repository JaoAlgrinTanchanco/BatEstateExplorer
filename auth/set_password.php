<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../public/api/auth_functions.php';

// Ensure user got here via the OAuth flow
if (empty($_SESSION['oauth_authenticated']) || empty($_SESSION['oauth_user_id'])) {
    // Not allowed — redirect to login
    header('Location: /BatEstateExplorer/public/auth/login.php');
    exit;
}

$user_id = (int) $_SESSION['oauth_user_id'];
$email   = $_SESSION['oauth_email'] ?? '';

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, auth_provider = 'local' WHERE id = ?");
        $stmt->bind_param('si', $hash, $user_id);
        $stmt->execute();

        // Clear oauth flags and log the user in
        unset($_SESSION['oauth_authenticated']);
        unset($_SESSION['oauth_user_id']);
        unset($_SESSION['oauth_email']);

        // Set session via login_user (or directly)
        $login = login_user($conn, $email, null, true); // oauth true to bypass password check
        if (isset($login['error'])) {
            $_SESSION['notification'] = ['type' => 'error', 'message' => $login['error']];
            header('Location: /BatEstateExplorer/public/auth/login.php');
            exit;
        }

        // success -> go to dashboard
        header('Location: /BatEstateExplorer/public/controllers/user_dashboard.php?view=home');
        exit;
    }
}
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Set Local Password</title>
  <link rel="stylesheet" href="/BatEstateExplorer/assets/css/signup.css">
</head>
<body>
  <div class="container">
    <h1>Create a local password for BatEstateExplorer</h1>
    <p>You're signed in as <strong><?php echo htmlspecialchars($email); ?></strong></p>

    <?php if (!empty($errors)): ?>
      <div class="errors">
        <?php foreach ($errors as $e): ?>
          <p style="color:red;"><?php echo htmlspecialchars($e); ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <div>
        <label>New Password</label>
        <input type="password" name="password" required minlength="6">
      </div>
      <div>
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required minlength="6">
      </div>

      <button type="submit">Set Password</button>
    </form>
  </div>
</body>
</html>
