<?php
session_start();
require_once __DIR__ . '/app/bootstrap.php';

define('ENCRYPTION_KEY', '12345678901234567890123456789012');

// --- Ensure logged in ---
$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) die("Not logged in.");

// Determine user role
$role = $_SESSION['user_type'] ?? 'user';

// --- Fetch Agent Info ---
$agent_id = $_GET['agent_id'] ?? null;
if (!$agent_id) die("No agent specified.");

$stmt = $conn->prepare("
    SELECT id, first_name, last_name, email, profile_image_path, user_type, created_at
    FROM users
    WHERE id = ? AND user_type IN ('direct_agent','associate_agent')
    LIMIT 1
");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$agent = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$agent) {
    die("Agent not found or invalid.");
}

// --- Agent Full Name & Profile Image ---
$agent_name = trim($agent['first_name'] . ' ' . $agent['last_name']);
$agent_image = !empty($agent['profile_image_path'])
    ? '/BatEstateExplorer/storage/uploads/profile_images/' . basename($agent['profile_image_path'])
    : null;

// --- Fetch Agent Property Listings ---
$properties = [];
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.title,
        p.price,
        p.location,
        p.description,
        p.images_json,
        p.created_at
    FROM properties p
    WHERE p.agent_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['images'] = !empty($row['images_json']) ? json_decode($row['images_json'], true) : [];
    if (!is_array($row['images'])) $row['images'] = [];
    $properties[] = $row;
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($agent_name) ?> — Property Listings</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="agent-profile-page">

    <!-- Agent Header -->
    <div class="agent-header">
        <div class="agent-avatar">
            <?php if ($agent_image): ?>
                <img src="<?= htmlspecialchars($agent_image) ?>" alt="<?= htmlspecialchars($agent_name) ?>">
            <?php else: ?>
                <i class="fa-solid fa-user"></i>
            <?php endif; ?>
        </div>

        <div class="agent-info">
            <h2><?= htmlspecialchars($agent_name) ?></h2>
            <p><?= htmlspecialchars($agent['email']) ?></p>
            <span class="joined-date">
                Joined <?= date('F Y', strtotime($agent['created_at'])) ?>
            </span>
        </div>
    </div>

    <!-- Property Listings -->
    <div class="property-listings">
        <h3>Properties by <?= htmlspecialchars($agent_name) ?></h3>

        <?php if (empty($properties)): ?>
            <p>No properties listed yet.</p>
        <?php else: ?>
            <?php foreach ($properties as $property): ?>
                <div class="property-item" data-id="<?= $property['id'] ?>">
                    <div class="property-image">
                        <?php if (!empty($property['images'])): ?>
                            <img src="<?= htmlspecialchars($property['images'][0]) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                        <?php else: ?>
                            <div class="no-image"><i class="fa-solid fa-image"></i></div>
                        <?php endif; ?>
                    </div>

                    <div class="property-details">
                        <h4><?= htmlspecialchars($property['title']) ?></h4>
                        <p class="price">₱<?= number_format($property['price'], 2) ?></p>
                        <p class="location"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($property['location']) ?></p>
                        <p class="description"><?= nl2br(htmlspecialchars($property['description'])) ?></p>
                        <span class="date">Posted on <?= date('M d, Y', strtotime($property['created_at'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
