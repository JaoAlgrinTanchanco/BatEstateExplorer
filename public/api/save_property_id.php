<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

$property_id = intval($_POST['property_id'] ?? 0);

if (!$property_id) {
    echo json_encode(['success' => false, 'error' => 'Missing property_id']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1) Get the agent_id of the given property
    $stmt = $conn->prepare("SELECT agent_id FROM properties WHERE id = ? LIMIT 1");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("i", $property_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $property = $res->fetch_assoc();
    $stmt->close();

    if (!$property) throw new Exception("Property not found.");
    $agent_id = intval($property['agent_id']);

    // 2) Map agent_id to user_id
    $stmt = $conn->prepare("SELECT user_id FROM agents WHERE id = ? LIMIT 1");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $agent = $res->fetch_assoc();
    $stmt->close();

    if (!$agent) throw new Exception("Agent mapping not found.");
    $user_id = intval($agent['user_id']);

    // 3) Find latest transaction for this user where property_id is NULL or 0
    $stmt = $conn->prepare("
        SELECT * 
        FROM transactions
        WHERE user_id = ? AND (property_id IS NULL OR property_id = 0) AND property LIKE 'Listing Fee%'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $transaction = $res->fetch_assoc();
    $stmt->close();

    $affectedRows = 0;

    if ($transaction) {
        $transaction_id = intval($transaction['id']);

        // 4) Update that single transaction
        $stmt = $conn->prepare("UPDATE transactions SET property_id = ? WHERE id = ?");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param("ii", $property_id, $transaction_id);

        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }

        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        if ($affectedRows === 0) {
            // This means the update didn't change anything (rare if new value != old)
            throw new Exception("Update executed but no rows were affected.");
        }
    }

    // 5) Fetch latest transaction for debugging
    $stmt = $conn->prepare("
        SELECT * 
        FROM transactions
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $latest_transaction_debug = $res->fetch_assoc();
    $stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $transaction 
            ? "Property ID #$property_id linked to latest listing fee transaction successfully." 
            : "No matching transaction found for update.",
        'property_id' => $property_id,
        'agent_id' => $agent_id,
        'user_id' => $user_id,
        'updated_transactions' => $affectedRows,
        'latest_transaction' => $transaction,
        'latest_transaction_debug' => $latest_transaction_debug
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
