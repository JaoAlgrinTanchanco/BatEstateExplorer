<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// --- Check if user is logged in and admin ---
$is_logged_in = is_logged_in();
$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_admin = ($current_user && $current_user['user_type'] === 'admin');
}

if (!$is_logged_in || !$is_admin) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// --- Validate request method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// --- Get POST data ---
$action = $_POST['action'] ?? '';
$property_id = intval($_POST['property_id'] ?? 0);

if (!$property_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$response = ['success' => false, 'error' => 'Unknown action'];

// --- Fetch agent ID from property before approving ---
$agent_id = null;
if ($action === 'approve') {
    $stmt = $conn->prepare("SELECT agent_id FROM properties WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $agent_id = intval($row['agent_id']);
    }
    $stmt->close();

    if ($agent_id) {
        // --- Update property to approved and set listed_by_agent_id ---
        $stmt = $conn->prepare("UPDATE properties SET status = 'available', listed_by_agent_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $agent_id, $property_id);
        $stmt->execute();
        $stmt->close();

        // --- Update user's listed_by_agent_id column ---
        // Fetch existing property IDs in user's column
        $stmt = $conn->prepare("SELECT listed_by_agent_id FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $agent_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $existing_ids = '';
        if ($row = $res->fetch_assoc()) {
            $existing_ids = $row['listed_by_agent_id'] ?? '';
        }
        $stmt->close();

        // Append property ID if not already present
        $ids_array = array_filter(explode(',', $existing_ids));
        if (!in_array($property_id, $ids_array)) {
            $ids_array[] = $property_id;
        }
        $new_ids = implode(',', $ids_array);

        // Update user table
        $stmt = $conn->prepare("UPDATE users SET listed_by_agent_id = ? WHERE id = ?");
        $stmt->bind_param("si", $new_ids, $agent_id);
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Property approved and agent linked'];
        } else {
            $response = ['success' => false, 'error' => 'Failed to update user listed properties'];
        }
        $stmt->close();
    } else {
        $response = ['success' => false, 'error' => 'Agent not found for this property'];
    }

} elseif ($action === 'reject') {
    // Mark property as rejected
    $stmt = $conn->prepare("SELECT agent_id FROM properties WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $agent_id = null;
    if ($row = $res->fetch_assoc()) {
        $agent_id = intval($row['agent_id']);
    }
    $stmt->close();

    if (!$agent_id) {
        echo json_encode(['success' => false, 'error' => 'Agent not found for this property']);
        exit;
    }

    // Update property status
    $stmt = $conn->prepare("UPDATE properties SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $stmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        $deductAmount = 20.00;                 // system "fee"
        $agentAmount  = $deductAmount * 0.98;  // ₱19.60 to agent
        $profit       = $deductAmount - $agentAmount; // ₱0.40 profit for admin

        // Deduct only ₱19.60 from admin balance (not full ₱20)
        $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
        $stmt->bind_param("di", $agentAmount, $current_user['id']);
        $stmt->execute();
        $stmt->close();

        // Add ₱19.60 to agent balance
        $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->bind_param("di", $agentAmount, $agent_id);
        $stmt->execute();
        $stmt->close();

        // Insert transaction for admin (-19.60 instead of -20)
        $stmt = $conn->prepare("
            INSERT INTO transactions (user_id, property, amount, status, method)
            VALUES (?, 'Reject Fee Deduction', ?, 'completed', 'system')
        ");
        $negAmount = -$agentAmount; // -19.60
        $stmt->bind_param("id", $current_user['id'], $negAmount);
        $stmt->execute();
        $stmt->close();

        // Insert transaction for agent (+19.60)
        $stmt = $conn->prepare("
            INSERT INTO transactions (user_id, property, amount, status, method)
            VALUES (?, 'Reject Fee Credit', ?, 'completed', 'system')
        ");
        $stmt->bind_param("id", $agent_id, $agentAmount);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        $response = [
            'success' => true,
            'message' => "Property rejected. ₱{$agentAmount} credited to agent."
        ];

    } catch (Exception $e) {
        $conn->rollback();
        $response = ['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()];
    }
} elseif ($action === 'remove') {
    $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->bind_param("i", $property_id);
    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Property removed'];
    } else {
        $response = ['success' => false, 'error' => 'Failed to remove property'];
    }
    $stmt->close();
}

echo json_encode($response);
exit;
