<?php
// give_privilege.php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Search user by email
    $email = $_GET['email'] ?? '';
    if (!$email) {
        echo json_encode(['error' => 'Missing email']);
        exit;
    }

<<<<<<< HEAD
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, privileges FROM users WHERE email = ?");
=======
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, profile_image_path, created_at, privileges FROM users WHERE email = ? LIMIT 1");
>>>>>>> origin/ansel
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
<<<<<<< HEAD
=======
    $stmt->close();
>>>>>>> origin/ansel

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

<<<<<<< HEAD
    // Build full name
    $user['name'] = trim($user['first_name'] . ' ' . $user['last_name']);

=======
    // Full name
    $user['name'] = trim($user['first_name'] . ' ' . $user['last_name']);

    // Profile image
    $profileImage = '/BatEstateExplorer/assets/images/no-image.png';
    if (!empty($user['profile_image_path'])) {
        $path = str_replace('\\', '/', $user['profile_image_path']);
        $path = str_replace('C:/xampp/htdocs', '', $path);
        if ($path[0] !== '/') $path = '/' . $path;
        $profileImage = $path;
    }
    $user['profile_image'] = $profileImage;

    // Joined duration (human-readable: years, months, weeks, days)
    $created = new DateTime($user['created_at']);
    $now = new DateTime();
    $diff = $now->diff($created);

    if ($diff->y > 0) {
        $user['joined'] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    } elseif ($diff->m > 0) {
        $user['joined'] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    } elseif ($diff->d >= 7) {
        $weeks = floor($diff->d / 7);
        $user['joined'] = $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff->d > 0) {
        $user['joined'] = $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    } elseif ($diff->h > 0) {
        $user['joined'] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->i > 0) {
        $user['joined'] = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    } else {
        $user['joined'] = 'Just now';
    }

>>>>>>> origin/ansel
    // Ensure privileges is a valid JSON array
    $user['privileges'] = $user['privileges'] ? json_decode($user['privileges'], true) : [];

    echo json_encode($user);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Give privilege
    $email = $_POST['email'] ?? '';
    $property_id = $_POST['property_id'] ?? '';

    if (!$email || !$property_id) {
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }

    // Fetch user
    $stmt = $conn->prepare("SELECT id, privileges FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
<<<<<<< HEAD
=======
    $stmt->close();
>>>>>>> origin/ansel

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Decode privileges
    $privileges = $user['privileges'] ? json_decode($user['privileges'], true) : [];
    if (!in_array($property_id, $privileges)) {
        $privileges[] = $property_id;
    }

    // Save privileges back
    $privileges_json = json_encode($privileges);
    $stmt = $conn->prepare("UPDATE users SET privileges = ? WHERE email = ?");
    $stmt->bind_param("ss", $privileges_json, $email);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'privileges' => $privileges]);
    } else {
        echo json_encode(['error' => 'Database update failed']);
    }

    $stmt->close();
    $conn->close();
}
<<<<<<< HEAD
?>
=======
>>>>>>> origin/ansel
