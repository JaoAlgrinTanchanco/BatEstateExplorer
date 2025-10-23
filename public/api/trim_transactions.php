<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

$debug = []; // collect debug messages

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$property = 'Listing Fee';

try {
    // 1) Get user type (admin or agent)
    $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception("User not found in database");
    }

    $userType = $row['user_type'] ?? 'agent';
    $debug[] = "User {$userId} type = {$userType}";

    // 2) Build query depending on user type
    if ($userType === 'admin') {
        // Admin: count all listing fees
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM transactions WHERE property = ?");
        $stmt->bind_param("s", $property);
    } else {
        // Agent: count only their own listing fees
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM transactions WHERE user_id = ? AND property = ?");
        $stmt->bind_param("is", $userId, $property);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $count = (int) ($row['cnt'] ?? 0);
    $debug[] = "Counted {$count} listing fee records.";

    if ($count <= 10) {
        echo json_encode([
            'success' => true,
            'deleted_count' => 0,
            'message' => 'No trimming required',
            'debug' => $debug
        ]);
        exit;
    }

    // 3) Get old record IDs (everything after the 10 most recent)
    if ($userType === 'admin') {
        $stmt = $conn->prepare("
            SELECT id
            FROM transactions
            WHERE property = ?
            ORDER BY created_at DESC
            LIMIT 10, 18446744073709551615
        ");
        $stmt->bind_param("s", $property);
    } else {
        $stmt = $conn->prepare("
            SELECT id
            FROM transactions
            WHERE user_id = ? AND property = ?
            ORDER BY created_at DESC
            LIMIT 10, 18446744073709551615
        ");
        $stmt->bind_param("is", $userId, $property);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    while ($r = $res->fetch_assoc()) {
        $ids[] = (int) $r['id'];
    }
    $stmt->close();

    $debug[] = "Old record IDs to delete: " . implode(',', $ids);

    if (empty($ids)) {
        echo json_encode([
            'success' => true,
            'deleted_count' => 0,
            'message' => 'No ids found to delete',
            'debug' => $debug
        ]);
        exit;
    }

    // 4) Delete old records
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "DELETE FROM transactions WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Failed to prepare delete statement: ' . $conn->error);
    }

    $types = str_repeat('i', count($ids));
    $bind_params = [];
    $bind_params[] = &$types;
    foreach ($ids as $k => $id) {
        $bind_params[] = &$ids[$k];
    }

    call_user_func_array([$stmt, 'bind_param'], $bind_params);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();

    $debug[] = "Deleted {$deleted} records.";

    echo json_encode([
        'success' => true,
        'deleted_count' => (int)$deleted,
        'debug' => $debug
    ]);
    exit;

} catch (Exception $e) {
    $debug[] = "Exception: " . $e->getMessage();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => $debug
    ]);
    exit;
}
?>
