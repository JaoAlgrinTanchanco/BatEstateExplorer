<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';

$is_logged_in = is_logged_in();
$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_admin = ($current_user && $current_user['user_type'] === 'admin');
}

if (!$is_logged_in || !$is_admin) {
    header('Location: admin_login.php');
    exit;
}

// Fetch direct agents listings
$queryDirect = "SELECT
  p.id AS property_id,
  p.title AS property_name,
  p.property_type,
  p.price,
  p.created_at AS date_uploaded
FROM properties p
JOIN agents a ON p.agent_id = a.id
JOIN users u ON a.user_id = u.id
WHERE u.user_type = 'direct_agent'
ORDER BY p.created_at DESC
";

$resultDirect = mysqli_query($conn, $queryDirect);
$direct_properties = [];
while ($row = mysqli_fetch_assoc($resultDirect)) {
    $direct_properties[] = $row;
}

// Fetch associate agents listings
$queryAssociate = "SELECT 
    p.id AS property_id,
    p.title AS property_name,
    p.property_type,
    p.price,
    p.created_at AS date_uploaded
FROM properties p
JOIN agents a ON p.agent_id = a.id
JOIN users u ON a.user_id = u.id
WHERE u.user_type = 'associate_agent'
ORDER BY p.created_at DESC";

$resultAssociate = mysqli_query($conn, $queryAssociate);
$associate_properties = [];
while ($row = mysqli_fetch_assoc($resultAssociate)) {
    $associate_properties[] = $row;
}
?>

<header class="content-header">
    <h1>Property Listings</h1>
    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($current_user['email']); ?></span>
    </div>
</header>

<div class="content-body">

    <!-- Tabs -->
    <div class="tab-bar">
        <button class="tab-btn active" data-tab="direct">Direct Agents</button>
        <button class="tab-btn" data-tab="associate">Associate Agents</button>
    </div>

    <!-- Tab Content -->
    <div id="tab-content">

        <!-- Direct Agents Tab -->
        <div class="tab-panel" id="tab-direct" style="display: block;">
            <?php if (empty($direct_properties)): ?>
                <div>No property listings found for direct agents.</div>
            <?php else: ?>
                <?php foreach ($direct_properties as $property): ?>
                    <div class="property-card"
                         data-name="<?php echo htmlspecialchars($property['property_name'] ?? ''); ?>"
                         data-type="<?php echo htmlspecialchars($property['property_type'] ?? ''); ?>"
                         data-price="<?php echo (int)($property['price'] ?? 0); ?>"
                         data-date="<?php echo htmlspecialchars($property['date_uploaded'] ?? ''); ?>">

                        <div class="property-image"
                             style="background-image: url('<?php echo htmlspecialchars($property['image_url'] ?? 'Pictures/bg4.jpg'); ?>')">
                        </div>

                        <div class="property-info">
                            <div class="property-name"><?php echo htmlspecialchars($property['property_name'] ?? ''); ?></div>
                            <div class="property-meta">
                                <span>Type: <?php echo htmlspecialchars($property['property_type'] ?? 'Unknown'); ?></span>
                                <span>₱<?php echo number_format((float)($property['price'] ?? 0), 2); ?></span>
                                <span>Date: <?php echo htmlspecialchars($property['date_uploaded'] ?? ''); ?></span>
                            </div>
                        </div>

                        <div class="property-actions">
                            <button class="btn-view" data-id="<?php echo $property['property_id'] ?? ''; ?>">View Post</button>
                            <button class="btn-remove" data-id="<?php echo $property['property_id'] ?? ''; ?>">Remove Post</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Associate Agents Tab -->
        <div class="tab-panel" id="tab-associate" style="display: none;">
            <?php if (empty($associate_properties)): ?>
                <div>No property listings found for associate agents.</div>
            <?php else: ?>
                <?php foreach ($associate_properties as $property): ?>
                    <div class="property-card"
                         data-name="<?php echo htmlspecialchars($property['property_name'] ?? ''); ?>"
                         data-type="<?php echo htmlspecialchars($property['property_type'] ?? ''); ?>"
                         data-price="<?php echo (int)($property['price'] ?? 0); ?>"
                         data-date="<?php echo htmlspecialchars($property['date_uploaded'] ?? ''); ?>">

                        <div class="property-image"
                             style="background-image: url('<?php echo htmlspecialchars($property['image_url'] ?? 'Pictures/bg4.jpg'); ?>')">
                        </div>

                        <div class="property-info">
                            <div class="property-name"><?php echo htmlspecialchars($property['property_name'] ?? ''); ?></div>
                            <div class="property-meta">
                                <span>Type: <?php echo htmlspecialchars($property['property_type'] ?? 'Unknown'); ?></span>
                                <span>₱<?php echo number_format((float)($property['price'] ?? 0), 2); ?></span>
                                <span>Date: <?php echo htmlspecialchars($property['date_uploaded'] ?? ''); ?></span>
                            </div>
                        </div>

                        <div class="property-actions">
                            <button class="btn-view" data-id="<?php echo $property['property_id'] ?? ''; ?>">View Post</button>
                            <button class="btn-doc" data-id="<?php echo $property['property_id'] ?? ''; ?>">View Property Document</button>
                            <button class="btn-remove" data-id="<?php echo $property['property_id'] ?? ''; ?>">Remove Post</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

</div>

<script>
document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', () => {
        // Remove active class from all buttons and panels
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(panel => panel.style.display = 'none');

        // Add active to clicked tab button
        button.classList.add('active');

        // Show corresponding tab panel
        const tab = button.dataset.tab;
        document.getElementById('tab-' + tab).style.display = 'block';
    });
});
</script>
