<?php
session_start();
header('Content-Type: application/json');

// --- Load bootstrap ---
require_once __DIR__ . '/../app/bootstrap.php';

// --- Ensure logged in ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to report an agent.']);
    exit;
}

// --- Only accept POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// --- Gather & sanitize inputs ---
$reporter_id  = (int) ($_SESSION['user_id'] ?? 0);
$agent_id     = isset($_POST['agent_id']) ? (int) $_POST['agent_id'] : null;
$reason       = isset($_POST['reason']) ? trim($_POST['reason']) : null;
$other_reason = isset($_POST['other_reason']) ? trim($_POST['other_reason']) : null;
$details      = isset($_POST['details']) ? trim($_POST['details']) : null;

// --- Basic validation ---
if (!$agent_id || !$reason) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a reason for your report.']);
    exit;
}

// --- Prevent self-report ---
if ($reporter_id === $agent_id) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot report yourself.']);
    exit;
}

// --- If reason is not 'other', ignore other_reason ---
if ($reason !== 'other') {
    $other_reason = null;
}

// Optional: handle uploaded evidence (commented for now)
// $evidence_path = null;

try {
    // Determine which DB connection is available
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("
            INSERT INTO agent_reports (reporter_id, agent_id, reason, other_reason, details)
            VALUES (:reporter_id, :agent_id, :reason, :other_reason, :details)
        ");
        $stmt->execute([
            ':reporter_id'  => $reporter_id,
            ':agent_id'     => $agent_id,
            ':reason'       => $reason,
            ':other_reason' => $other_reason,
            ':details'      => $details
        ]);
    } elseif (isset($db) && is_object($db) && method_exists($db, 'getConnection')) {
        $connObj = $db->getConnection();
        if ($connObj instanceof PDO) {
            $stmt = $connObj->prepare("
                INSERT INTO agent_reports (reporter_id, agent_id, reason, other_reason, details)
                VALUES (:reporter_id, :agent_id, :reason, :other_reason, :details)
            ");
            $stmt->execute([
                ':reporter_id'  => $reporter_id,
                ':agent_id'     => $agent_id,
                ':reason'       => $reason,
                ':other_reason' => $other_reason,
                ':details'      => $details
            ]);
        } elseif ($connObj instanceof mysqli) {
            $stmt = $connObj->prepare("
                INSERT INTO agent_reports (reporter_id, agent_id, reason, other_reason, details)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('iisss', $reporter_id, $agent_id, $reason, $other_reason, $details);
            $stmt->execute();
        }
    } elseif (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("
            INSERT INTO agent_reports (reporter_id, agent_id, reason, other_reason, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iisss', $reporter_id, $agent_id, $reason, $other_reason, $details);
        $stmt->execute();
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection not found.'
        ]);
        exit;
    }

    // Success
    echo json_encode(['status' => 'success', 'message' => 'Report submitted successfully.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
