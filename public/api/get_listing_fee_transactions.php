<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Only admin can fetch
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Admin user ID
$admin_id = $_SESSION['user_id'];

try {
    // Fetch last 50 transactions with user info
    $stmt = $conn->prepare("
        SELECT t.id, t.property, t.amount, t.created_at,
               u.first_name, u.last_name, u.profile_image_path
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $res = $stmt->get_result();

    $transactions = [];
    while ($row = $res->fetch_assoc()) {
        // Build correct web-accessible profile image path
        $profile_path = null;
        if (!empty($row['profile_image_path'])) {
            // Normalize slashes
            $clean_path = str_replace('\\', '/', $row['profile_image_path']);
            $clean_path = ltrim($clean_path, '/');

            // Remove any duplicate 'BatEstateExplorer/' if it already exists
            $clean_path = preg_replace('#^(BatEstateExplorer/)+#', 'BatEstateExplorer/', $clean_path);

            // Ensure it starts with the project base once
            if (strpos($clean_path, 'BatEstateExplorer/') !== 0) {
                $clean_path = 'BatEstateExplorer/' . $clean_path;
            }

            // Final accessible URL
            $profile_path = '/' . $clean_path;
        }

        $transactions[] = [
            'transaction_id' => intval($row['id']),
            'agent_name'     => $row['first_name'] . ' ' . $row['last_name'],
            'agent_profile'  => $profile_path,
            'property'       => $row['property'],
            'amount'         => floatval($row['amount']),
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
