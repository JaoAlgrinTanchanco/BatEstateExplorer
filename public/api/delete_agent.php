<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);

        if (mysqli_stmt_execute($stmt)) {
            // Set success notification BEFORE destroying session
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Account deleted successfully.'
            ];

            // Destroy session
            session_unset();
            session_destroy();

            // Redirect to login page (notification shows if login includes component)
            header("Location: /BatEstateExplorer/auth/login.php");
            exit;

        } else {
            // Error notification
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Error deleting account. Please try again.'
            ];
            redirectWithAgentType('overview');
        }
    } else {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Invalid user ID.'
        ];
        redirectWithAgentType('overview');
    }
}

// ==========================
// Helper: Redirect by agent type (using users.user_type)
// ==========================
function redirectWithAgentType($tab = 'overview') {
    global $conn;

    $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
    $agentType = 'direct';

    if ($userId) {
        $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['user_type'] === 'associate_agent') {
                $agentType = 'associate';
            }
        }
        $stmt->close();
    }

    $view = $agentType === 'associate' ? 'associate_profile' : 'direct_profile';
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view={$view}&tab={$tab}");
    exit;
}
