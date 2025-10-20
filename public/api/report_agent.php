<?php
session_start();

header('Content-Type: application/json');

// --- Load bootstrap (correct path for your structure) ---
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

// Basic validation
if (!$agent_id || !$reason) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a reason for your report.']);
    exit;
}

// If reason is not 'other', ignore other_reason
if ($reason !== 'other') {
    $other_reason = null;
}

// Optional: handle an uploaded evidence file (commented — enable if you want)
// if (!empty($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
//     // validate file size/type, move to uploads directory, set $evidence_path
//     // $evidence_path = '/path/to/uploads/...';
// } else {
//     $evidence_path = null;
//}

try {
    // Try multiple common bootstrap DB variable patterns:
    // 1) $pdo (PDO instance)
    // 2) $db->getConnection() returning PDO
    // 3) $conn (mysqli)
    $used = null;

    if (isset($pdo) && $pdo instanceof PDO) {
        $used = 'pdo_direct';
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
        // Many projects wrap PDO in a $db service
        $connObj = $db->getConnection();
        if ($connObj instanceof PDO) {
            $used = 'pdo_from_db';
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
        } else {
            // if getConnection returns mysqli
            $used = 'mysqli_from_db';
            $mysqli = $connObj;
            $stmt = $mysqli->prepare("
                INSERT INTO agent_reports (reporter_id, agent_id, reason, other_reason, details)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('iisss', $reporter_id, $agent_id, $reason, $other_reason, $details);
            $stmt->execute();
        }
    } elseif (isset($conn) && $conn instanceof mysqli) {
        // legacy mysqli connection variable
        $used = 'mysqli_direct';
        $stmt = $conn->prepare("
            INSERT INTO agent_reports (reporter_id, agent_id, reason, other_reason, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iisss', $reporter_id, $agent_id, $reason, $other_reason, $details);
        $stmt->execute();
    } else {
        // No known DB connection found in bootstrap
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection not found. Please ensure bootstrap.php exposes $pdo, $db->getConnection(), or $conn (mysqli).'
        ]);
        exit;
    }

    // If we get here without exception, assume success
    echo json_encode(['status' => 'success', 'message' => 'Report submitted successfully.']);

} catch (Exception $e) {
    // PDOException or mysqli error — return safe message
    http_response_code(500);
    $msg = $e->getMessage();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $msg
    ]);
    exit;
}
