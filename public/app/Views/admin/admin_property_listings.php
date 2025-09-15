<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/database.php';

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

// Function to get first image for a property
function get_property_image($conn, $property_id) {
    $res = mysqli_query($conn, "
        SELECT image_path 
        FROM property_images 
        WHERE property_id = $property_id 
        ORDER BY is_primary DESC, id ASC 
        LIMIT 1
    ");
    $row = mysqli_fetch_assoc($res);
    return $row['image_path'] ?? null;
}

// Direct agents
$queryDirect = "SELECT
  p.id AS property_id,
  p.title AS property_name,
  p.property_type,
  p.price,
  p.status,
  p.created_at AS date_uploaded
FROM properties p
JOIN agents a ON p.agent_id = a.id
JOIN users u ON a.user_id = u.id
WHERE u.user_type = 'direct_agent'
ORDER BY p.created_at DESC";

$resultDirect = mysqli_query($conn, $queryDirect);
$direct_properties = [];
while ($row = mysqli_fetch_assoc($resultDirect)) {
    $row['image_path'] = get_property_image($conn, $row['property_id']);
    $direct_properties[] = $row;
}

// Associate agents
$queryAssociate = "SELECT
  p.id AS property_id,
  p.title AS property_name,
  p.property_type,
  p.price,
  p.status,
  p.created_at AS date_uploaded
FROM properties p
JOIN agents a ON p.agent_id = a.id
JOIN users u ON a.user_id = u.id
WHERE u.user_type = 'associate_agent'
ORDER BY p.created_at DESC";

$resultAssociate = mysqli_query($conn, $queryAssociate);
$associate_properties = [];
while ($row = mysqli_fetch_assoc($resultAssociate)) {
    $row['image_path'] = get_property_image($conn, $row['property_id']);
    $associate_properties[] = $row;
}
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_property_listings.css">

<header class="content-header">
    <h1>Property Listings</h1>
</header>

<div class="content-body">
    <!-- Tabs -->
    <div class="tab-bar">
        <button class="tab-btn active" data-tab="direct">Direct Agents</button>
        <button class="tab-btn" data-tab="associate">Associate Agents</button>
    </div>

    <!-- Tab Content -->
    <div class="content-body">

        <!-- Tab Content -->
        <div id="tab-content">

            <!-- Direct Agents Tab -->
            <div class="tab-panel" id="tab-direct" style="display: block;">
                <?php if (empty($direct_properties)): ?>
                    <div>No property listings found for direct agents.</div>
                <?php else: ?>
                    <div class="grid-container"> <!-- Added grid wrapper -->
                    <?php foreach ($direct_properties as $property): ?>
                        <div class="property-card"
                            data-name="<?php echo htmlspecialchars($property['property_name']); ?>"
                            data-type="<?php echo htmlspecialchars($property['property_type']); ?>"
                            data-price="<?php echo (int)$property['price']; ?>"
                            data-date="<?php echo htmlspecialchars($property['date_uploaded']); ?>">

                            <div class="property-status status-<?php echo $property['status']; ?>">
                                <?php echo ucfirst($property['status']); ?>
                            </div>

                            <?php
                            $image_url = $property['image_path'] 
                                ? '/BatEstateExplorer/' . ltrim($property['image_path'], '/')
                                : '/BatEstateExplorer/assets/images/bg4.jpg';
                            ?>
                            <div class="property-image" style="background-image: url('<?php echo htmlspecialchars($image_url); ?>')"></div>

                            <div class="property-info">
                                <div class="property-name"><?php echo htmlspecialchars($property['property_name']); ?></div>
                                <div class="property-meta">
                                    <span>Type: <?php echo htmlspecialchars($property['property_type']); ?></span>
                                    <span>₱<?php echo number_format((float)$property['price'], 2); ?></span>
                                    <span>Date: <?php echo htmlspecialchars($property['date_uploaded']); ?></span>
                                </div>
                            </div>

                            <div class="property-actions">
                                <button class="btn-view" data-id="<?php echo $property['property_id']; ?>">View Post</button>

                                <?php if ($property['status'] === 'pending'): ?>
                                    <button class="btn-approve" data-id="<?php echo $property['property_id']; ?>">Approve</button>
                                    <button class="btn-reject" data-id="<?php echo $property['property_id']; ?>">Reject</button>
                                <?php else: ?>
                                    <button class="btn-remove" data-id="<?php echo $property['property_id']; ?>">Remove Post</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Associate Agents Tab -->
            <div class="tab-panel" id="tab-associate" style="display: none;">
                <?php if (empty($associate_properties)): ?>
                    <div>No property listings found for associate agents.</div>
                <?php else: ?>
                    <div class="grid-container"> <!-- Added grid wrapper -->
                    <?php foreach ($associate_properties as $property): ?>
                        <div class="property-card"
                            data-name="<?php echo htmlspecialchars($property['property_name']); ?>"
                            data-type="<?php echo htmlspecialchars($property['property_type']); ?>"
                            data-price="<?php echo (int)$property['price']; ?>"
                            data-date="<?php echo htmlspecialchars($property['date_uploaded']); ?>">

                            <div class="property-status status-<?php echo $property['status']; ?>">
                                <?php echo ucfirst($property['status']); ?>
                            </div>

                            <?php
                            $image_url = $property['image_path'] 
                                ? '/BatEstateExplorer/' . ltrim($property['image_path'], '/')
                                : '/BatEstateExplorer/assets/images/bg4.jpg';
                            ?>
                            <div class="property-image" style="background-image: url('<?php echo htmlspecialchars($image_url); ?>')"></div>

                            <div class="property-info">
                                <div class="property-name"><?php echo htmlspecialchars($property['property_name']); ?></div>
                                <div class="property-meta">
                                    <span>Type: <?php echo htmlspecialchars($property['property_type']); ?></span>
                                    <span>₱<?php echo number_format((float)$property['price'], 2); ?></span>
                                    <span>Date: <?php echo htmlspecialchars($property['date_uploaded']); ?></span>
                                </div>
                            </div>

                            <div class="property-actions">
                                <button class="btn-view" data-id="<?php echo $property['property_id']; ?>">View Post</button>

                                <?php if ($property['status'] === 'pending'): ?>
                                    <button class="btn-approve" data-id="<?php echo $property['property_id']; ?>">Approve</button>
                                    <button class="btn-reject" data-id="<?php echo $property['property_id']; ?>">Reject</button>
                                <?php endif; ?>

                                <button class="btn-remove" data-id="<?php echo $property['property_id']; ?>">Remove Post</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>                                
    </div>
</div>

</div>

<!-- Modal Structure -->
<div id="propertyModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="modalBody">Loading...</div>
    </div>
</div>

<script>
// ===== Tabs =====
document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', () => {
        // Remove active from all buttons and hide all panels
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(panel => panel.style.display = 'none');

        // Activate clicked tab and show its panel
        button.classList.add('active');
        const tabPanel = document.getElementById('tab-' + button.dataset.tab);
        if (tabPanel) tabPanel.style.display = 'block';
    });
});

// ===== Modal =====
const modal = document.getElementById('propertyModal');
const modalBody = document.getElementById('modalBody');
const closeBtn = modal.querySelector('.close');

// Close modal
closeBtn.addEventListener('click', () => modal.style.display = 'none');
window.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

// ===== View Property Post =====
document.addEventListener('click', async e => {
    if (!e.target.classList.contains('btn-view')) return;

    const propertyId = e.target.dataset.id;
    if (!propertyId) return;

    modal.style.display = 'block';
    modalBody.innerHTML = '<p>Loading...</p>';

    try {
        const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${propertyId}`);
        const data = await res.json();

        if (!data.success || !data.property) {
            modalBody.innerHTML = `<p>${data.error || 'Failed to load property details.'}</p>`;
            return;
        }

        const prop = data.property;

        // Render property details
        modalBody.innerHTML = `
            <h2>${prop.title}</h2>
            <p><strong>Location:</strong> ${prop.location || '-'}</p>
            <p><strong>Price:</strong> ₱${parseFloat(prop.price || 0).toLocaleString()}</p>
            <p><strong>Bedrooms:</strong> ${prop.bedrooms || 0}</p>
            <p><strong>Bathrooms:</strong> ${prop.bathrooms || 0}</p>
            <p><strong>Area:</strong> ${prop.sqm || 0} sqm</p>
            <p><strong>Lot Size:</strong> ${prop.lot_size || 0} sqm</p>
            <p><strong>Status:</strong> ${prop.status || '-'}</p>
            <p><strong>Date Uploaded:</strong> ${prop.date_uploaded || '-'}</p>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
                ${(prop.images || []).map(img => `
                    <img src="${img}" style="width:120px;height:80px;object-fit:cover;border-radius:6px;">
                `).join('')}
            </div>
            <p style="margin-top:10px;">${prop.description || ''}</p>
        `;
    } catch (err) {
        console.error(err);
        modalBody.innerHTML = '<p>An unexpected error occurred.</p>';
    }
});

// ===== Admin Actions: Approve / Reject / Remove =====
document.addEventListener('click', async e => {
    if (!e.target.classList.contains('btn-approve') &&
        !e.target.classList.contains('btn-reject') &&
        !e.target.classList.contains('btn-remove')) return;

    const propertyId = e.target.dataset.id;
    if (!propertyId) return;

    let action = '';
    if (e.target.classList.contains('btn-approve')) action = 'approve';
    if (e.target.classList.contains('btn-reject')) action = 'reject';
    if (e.target.classList.contains('btn-remove')) action = 'remove';

    if (!action) return;

    if (!confirm(`Are you sure you want to ${action} this property?`)) return;

    try {
        const formData = new FormData();
        formData.append('property_id', propertyId);
        formData.append('action', action);

        const res = await fetch('/BatEstateExplorer/public/api/admin_property_action.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();
        alert(data.message || data.error || 'Unexpected response');

        if (data.success) location.reload();
    } catch (err) {
        console.error(err);
        alert('An error occurred while performing the action.');
    }
});
</script>

