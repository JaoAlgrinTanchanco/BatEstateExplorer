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
    // --- 1) Find agent linked to this property ---
    $stmt = $conn->prepare("SELECT agent_id FROM properties WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$row = $res->fetch_assoc()) {
        echo json_encode(['success' => false, 'error' => 'Agent not found for this property']);
        exit;
    }
    $agent_id = intval($row['agent_id']);
    $stmt->close();

    // --- 2) Get agent user_id ---
    $stmt = $conn->prepare("SELECT user_id FROM agents WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$row = $res->fetch_assoc()) {
        echo json_encode(['success' => false, 'error' => 'Agent user not found']);
        exit;
    }
    $agent_user_id = intval($row['user_id']);
    $stmt->close();

    // --- 3) Fetch latest completed Listing Fee ---
    $stmt = $conn->prepare("
        SELECT amount
        FROM transactions
        WHERE user_id = ? AND property LIKE 'Listing Fee%' AND status = 'completed'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $agent_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $listing_fee = floatval($row['amount']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No completed listing fee found for this agent']);
        exit;
    }
    $stmt->close();

    // --- 4) Update property status ---
    $stmt = $conn->prepare("UPDATE properties SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $stmt->close();

    // --- 5) Perform refund with 2% deduction ---
    $conn->begin_transaction();

    try {
        $refundAmount = $listing_fee * 0.98; // Deduct 2% fee

        // Deduct from admin wallet
        $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
        $stmt->bind_param("di", $refundAmount, $current_user['id']);
        $stmt->execute();
        $stmt->close();

        // Credit to agent wallet
        $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->bind_param("di", $refundAmount, $agent_user_id);
        $stmt->execute();
        $stmt->close();

        // Record negative transaction for admin
        $stmt = $conn->prepare("
            INSERT INTO transactions (user_id, property, amount, status, method)
            VALUES (?, CONCAT('Refund Issued: Property #', ?), ?, 'completed', 'system')
        ");
        $negAmount = -$refundAmount;
        $stmt->bind_param("iid", $current_user['id'], $property_id, $negAmount);
        $stmt->execute();
        $stmt->close();

        // Record positive transaction for agent
        $stmt = $conn->prepare("
            INSERT INTO transactions (user_id, property, amount, status, method)
            VALUES (?, CONCAT('Refund Received: Property #', ?), ?, 'completed', 'system')
        ");
        $stmt->bind_param("iid", $agent_user_id, $property_id, $refundAmount);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        $response = [
            'success' => true,
            'message' => "Property rejected. ₱" . number_format($refundAmount, 2) . " refunded to agent (2% processing fee deducted)."
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
