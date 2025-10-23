<?php
session_start();
header('Content-Type: application/json');

// --- Load bootstrap ---
require_once __DIR__ . '/../app/bootstrap.php';

// --- Ensure logged in ---
$reporter_id = $_SESSION['user_id'] ?? null;
if (!$reporter_id) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to report a user.']);
    exit;
}

// --- Only accept POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// --- Gather & sanitize inputs ---
$reported_user_id = isset($_POST['reported_user_id']) ? (int) $_POST['reported_user_id'] : null;
$reason           = isset($_POST['reason']) ? trim($_POST['reason']) : null;
$other_reason     = isset($_POST['other_reason']) ? trim($_POST['other_reason']) : null;
$details          = isset($_POST['details']) ? trim($_POST['details']) : null;

// --- Basic validation ---
if (!$reported_user_id || !$reason) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a reason for your report.']);
    exit;
}

// --- Prevent self-report ---
if ($reporter_id === $reported_user_id) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot report yourself.']);
    exit;
}

// --- Ignore other_reason if not 'other' ---
if ($reason !== 'other') {
    $other_reason = null;
}

// --- Optional: handle evidence file ---
$evidence_path = null;
if (!empty($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../storage/uploads/reports/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $fileName = uniqid('report_', true) . '_' . basename($_FILES['evidence']['name']);
    $targetPath = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['evidence']['tmp_name'], $targetPath)) {
        $evidence_path = '/BatEstateExplorer/storage/uploads/reports/' . $fileName;
    }
}

try {
    // --- Insert into user_reports ---
    if (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("
            INSERT INTO user_reports 
                (reporter_id, reported_user_id, reason, other_reason, details, evidence_path) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iissss', $reporter_id, $reported_user_id, $reason, $other_reason, $details, $evidence_path);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Report submitted successfully.']);
        exit;
    }

    // --- If using PDO ---
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("
            INSERT INTO user_reports 
                (reporter_id, reported_user_id, reason, other_reason, details, evidence_path) 
            VALUES (:reporter_id, :reported_user_id, :reason, :other_reason, :details, :evidence_path)
        ");
        $stmt->execute([
            ':reporter_id'      => $reporter_id,
            ':reported_user_id' => $reported_user_id,
            ':reason'           => $reason,
            ':other_reason'     => $other_reason,
            ':details'          => $details,
            ':evidence_path'    => $evidence_path
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Report submitted successfully.']);
        exit;
    }

    // --- No known DB connection ---
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection not found. Please ensure bootstrap.php exposes $conn (mysqli) or $pdo.'
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}
