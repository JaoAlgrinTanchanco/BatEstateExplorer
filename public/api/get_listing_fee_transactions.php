<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Only admin can fetch
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$admin_id = $_SESSION['user_id'];

try {
    // Fetch last 50 transactions where admin is involved
    $stmt = $conn->prepare("
        SELECT t.id, t.property, t.amount, t.created_at,
               t.user_id, t.recipient_id,
               u_admin.first_name AS admin_fname, u_admin.last_name AS admin_lname, u_admin.profile_image_path AS admin_profile,
               u_agent.first_name AS agent_fname, u_agent.last_name AS agent_lname, u_agent.profile_image_path AS agent_profile
        FROM transactions t
        LEFT JOIN users u_admin ON t.user_id = u_admin.id
        LEFT JOIN users u_agent ON t.recipient_id = u_agent.id
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $transactions = [];
    while ($row = $res->fetch_assoc()) {
        // Determine which info to show as "agent_name/agent_profile"
        $name = $row['admin_fname'] . ' ' . $row['admin_lname'];
        $profile_path = $row['admin_profile'];

        // If it's a Listing Fee (money in by agent), use recipient info
        if (strpos($row['property'], 'Listing Fee') !== false && $row['amount'] > 0) {
            $name = $row['agent_fname'] . ' ' . $row['agent_lname'];
            $profile_path = $row['agent_profile'];
        }

        // Normalize profile image path
        if (!empty($profile_path)) {
            $clean_path = str_replace('\\', '/', $profile_path);
            $clean_path = ltrim($clean_path, '/');
            $clean_path = preg_replace('#^(BatEstateExplorer/)+#', 'BatEstateExplorer/', $clean_path);
            if (strpos($clean_path, 'BatEstateExplorer/') !== 0) {
                $clean_path = 'BatEstateExplorer/' . $clean_path;
            }
            $profile_path = '/' . $clean_path;
        }

        $transactions[] = [
            'transaction_id' => intval($row['id']),
            'agent_name'     => $name,
            'agent_profile'  => $profile_path,
            'property'       => $row['property'],
            'amount'         => floatval($row['amount']),
            'user_id'        => intval($row['user_id']),
            'recipient_id'   => intval($row['recipient_id']),
            'datetime'       => date('d/m • h:i A', strtotime($row['created_at']))
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'transactions' => $transactions
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
