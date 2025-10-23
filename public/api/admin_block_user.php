<?php
// admin_block_user.php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/bootstrap.php';

$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not found.']);
    exit;
}

// --- Get JSON input ---
$data       = json_decode(file_get_contents('php://input'), true);
$userId     = $data['reported_id'] ?? null; // from JS (reported user)
$duration   = $data['duration'] ?? null;    // admin-selected for 'other'
$action     = $data['action'] ?? 'block';   // 'block' or 'unblock'
$category   = $data['category'] ?? null;    // violation type

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required.']);
    exit;
}

try {
    $conn->begin_transaction();

    if ($action === 'block') {

        // --- 1. Block the user account ---
        $stmt = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // --- 2. Determine penalty duration based on category ---
        if ($category && $category !== 'other') {
            switch ($category) {
                case 'harassment':
                    $duration = '7days';
                    break;
                case 'spam':
                    $duration = '48hrs';
                    break;
                case 'fake_review':
                    $duration = '30days';
                    break;
                case 'misinformation':
                    $duration = '7days';
                    break;
                case 'false_report':
                    $duration = '7days';
                    break;
                case 'fraudulent_activity':
                case 'impersonation':
                    $duration = 'lifetime';
                    break;
                default:
                    $duration = '7days';
                    break;
            }
        } elseif (!$duration) {
            $duration = '7days'; // default fallback
        }

        // --- 3. Update user_reports table ---
        $stmt2 = $conn->prepare("
            UPDATE user_reports 
            SET status = 'blocked', duration = ?, blockage_date = NOW()
            WHERE reported_user_id = ?
        ");
        $stmt2->bind_param("si", $duration, $userId);
        $stmt2->execute();

        $message = 'User account blocked successfully.';
        $status  = 'blocked';

    } else {

        // --- Unblock the user ---
        $stmt = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        // --- Clear previous penalties ---
        $stmt2 = $conn->prepare("
            UPDATE user_reports 
            SET status = 'unblocked', duration = NULL, blockage_date = NULL
            WHERE reported_user_id = ?
        ");
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();

        $message  = 'User account unblocked successfully.';
        $status   = 'unblocked';
        $duration = null;
    }

    $conn->commit();

    echo json_encode([
        'success'       => true,
        'message'       => $message,
        'status'        => $status,
        'duration'      => $duration,
        'user_id'       => $userId,
        'blockage_date' => $action === 'block' ? date('Y-m-d H:i:s') : null
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'error'   => 'Failed to update user account.',
        'details' => $e->getMessage()
    ]);
}
