<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// --- Check if user is logged in ---
$is_logged_in = is_logged_in();
$current_user = null;
if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
}
if (!$is_logged_in || !$current_user) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Unauthorized access.'
    ];
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings");
    exit;
}

// Only direct or associate agents can use this
if (!in_array($current_user['user_type'], ['direct_agent', 'associate_agent'])) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Forbidden action.'
    ];
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings");
    exit;
}

// Get property_id (support POST or GET)
$property_id = intval($_POST['property_id'] ?? $_GET['property_id'] ?? 0);

if (!$property_id) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Invalid property ID.'
    ];
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings");
    exit;
}

// 🔍 Get agent_id for current user
$query = "SELECT id FROM agents WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_user['id']);
$stmt->execute();
$result = $stmt->get_result();
$agent = $result->fetch_assoc();
$stmt->close();

if (!$agent) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Agent not found.'
    ];
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings");
    exit;
}

$agent_id = $agent['id'];

// 🔍 Verify property belongs to this agent
$query = "SELECT id FROM properties WHERE id = ? AND agent_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $property_id, $agent_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'You cannot delete this property.'
    ];
    header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings");
    exit;
}

// --- Delete related images (DB + filesystem) ---
$query = "SELECT image_path FROM property_images WHERE property_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();

$image_paths = [];
while ($row = $result->fetch_assoc()) {
    $image_paths[] = $row['image_path'];
    $file_path = $_SERVER['DOCUMENT_ROOT'] . "/BatEstateExplorer/public/" . $row['image_path'];
    if (file_exists($file_path)) {
        unlink($file_path); // 🗑 Delete file from filesystem
    }
}
$stmt->close();

// Delete image records from DB
$query = "DELETE FROM property_images WHERE property_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$stmt->close();

// --- Orphan Cleanup ---
// Folders where images are stored
$image_dirs = [
    $_SERVER['DOCUMENT_ROOT'] . "/BatEstateExplorer/public/uploads/property_images/",
    $_SERVER['DOCUMENT_ROOT'] . "/BatEstateExplorer/public/storage/uploads/property_images/"
];

foreach ($image_dirs as $dir) {
    if (is_dir($dir)) {
        foreach (glob($dir . "*") as $file) {
            // Check if file is associated with this property (id embedded or leftover)
            if (strpos($file, (string)$property_id) !== false) {
                if (file_exists($file)) {
                    unlink($file); // remove orphaned files
                }
            }
        }
    }
}

// --- Delete property ---
$query = "DELETE FROM properties WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $property_id);

if ($stmt->execute()) {
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Property deleted successfully.'
    ];
} else {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Failed to delete property.'
    ];
}

$stmt->close();
header("Location: /BatEstateExplorer/public/controllers/agent_dashboard.php?view=direct_profile&tab=my_listings");
exit;
