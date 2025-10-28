<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/pdo_database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
  exit;
}

try {
  $stmt = $pdo->prepare("
    SELECT 
      id,
      email,
      first_name,
      last_name,
      profile_image_path AS profile_image
    FROM users
    WHERE id = ?
    LIMIT 1
  ");
  $stmt->execute([$id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
  }

  // Normalize image path
  if (!empty($user['profile_image']) && !str_starts_with($user['profile_image'], '/')) {
    $user['profile_image'] = '/' . ltrim($user['profile_image'], '/');
  }

  echo json_encode(['success' => true, 'user' => $user]);
} catch (PDOException $e) {
  echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
