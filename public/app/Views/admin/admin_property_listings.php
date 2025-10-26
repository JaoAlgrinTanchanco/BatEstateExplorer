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
    <div id="tab-content">

        <!-- Direct Agents Tab -->
        <div class="tab-panel" id="tab-direct" style="display: block;">
            <?php if (empty($direct_properties)): ?>
                <div>No property listings found for direct agents.</div>
            <?php else: ?>
                <div class="grid-container">
                    <?php foreach ($direct_properties as $property): ?>
                        <?php
                        $image_url = $property['image_path'] 
                            ? '/BatEstateExplorer/' . ltrim($property['image_path'], '/')
                            : '/BatEstateExplorer/assets/images/bg4.jpg';
                        ?>
                        <div class="property-card">
                            <!-- Badge -->
                            <div class="property-badge <?php echo htmlspecialchars($property['status']); ?>">
                                <?php echo ucfirst($property['status']); ?>
                            </div>

                            <!-- Card background and image -->
                            <div class="property-image" style="background-image: url('<?php echo htmlspecialchars($image_url); ?>');"></div>

                            <!-- Single Overlay (info + actions) -->
                            <div class="property-overlay">
                                <div class="overlay-content">
                                    <div class="property-name"><?php echo htmlspecialchars($property['property_name']); ?></div>
                                    <div class="property-meta">
                                        <span>Type: <?php echo htmlspecialchars($property['property_type']); ?></span>
                                        <span>₱<?php echo number_format((float)$property['price'], 2); ?></span>
                                        <span>Date: <?php echo htmlspecialchars($property['date_uploaded']); ?></span>
                                    </div>
                                    <div class="property-actions">
                                        <button class="btn-view" data-id="<?php echo $property['property_id']; ?>">View</button>
                                        <?php if ($property['status'] === 'pending'): ?>
                                            <button class="btn-approve" data-id="<?php echo $property['property_id']; ?>">Approve</button>
                                            <button class="btn-reject" data-id="<?php echo $property['property_id']; ?>">Reject</button>
                                        <?php else: ?>
                                            <button class="btn-remove" data-id="<?php echo $property['property_id']; ?>">Remove</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
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
                <div class="grid-container">
                    <?php foreach ($associate_properties as $property): ?>
                        <?php
                        $image_url = $property['image_path'] 
                            ? '/BatEstateExplorer/' . ltrim($property['image_path'], '/')
                            : '/BatEstateExplorer/assets/images/bg4.jpg';
                        ?>
                        <div class="property-card">
                            <!-- Badge -->
                            <div class="property-badge <?php echo htmlspecialchars($property['status']); ?>">
                                <?php echo ucfirst($property['status']); ?>
                            </div>

                            <!-- Card background and image -->
                            <div class="property-image" style="background-image: url('<?php echo htmlspecialchars($image_url); ?>');"></div>

                            <!-- Single Overlay (info + actions) -->
                            <div class="property-overlay">
                                <div class="overlay-content">
                                    <div class="property-name"><?php echo htmlspecialchars($property['property_name']); ?></div>
                                    <div class="property-meta">
                                        <span>Type: <?php echo htmlspecialchars($property['property_type']); ?></span>
                                        <span>₱<?php echo number_format((float)$property['price'], 2); ?></span>
                                        <span>Date: <?php echo htmlspecialchars($property['date_uploaded']); ?></span>
                                    </div>
                                    <div class="property-actions">
                                        <button class="btn-view" data-id="<?php echo $property['property_id']; ?>">View</button>
                                        <?php if ($property['status'] === 'pending'): ?>
                                            <button class="btn-approve" data-id="<?php echo $property['property_id']; ?>">Approve</button>
                                            <button class="btn-reject" data-id="<?php echo $property['property_id']; ?>">Reject</button>
                                        <?php else: ?>
                                            <button class="btn-remove" data-id="<?php echo $property['property_id']; ?>">Remove</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
<!-- Modal -->
<div id="propertyModal" class="modal" style="display:none;">
  <div class="modal-content">
        <span class="close">&times;</span>

        <div class="modal-left">
            <div class="property-main-image" style="background-image: url('');"></div>
            <div class="property-name"></div>
            <div class="property-images"></div>
    </div>

        <div class="modal-right">
            <section><span class="label">Location:</span> <span class="value location"></span></section>
            <section><span class="label">Price:</span> <span class="value price"></span></section>
            <section><span class="label">Property Type:</span> <span class="value property-type"></span></section>
            <section><span class="label">Bedrooms:</span> <span class="value bedrooms"></span></section>
            <section><span class="label">Bathrooms:</span> <span class="value bathrooms"></span></section>
            <section><span class="label">Area:</span> <span class="value sqm"></span></section>
            <section><span class="label">Lot Size:</span> <span class="value lot_size"></span></section>
            <section><span class="label">Status:</span> <span class="value status"></span></section>
            <section><span class="label">Date Uploaded:</span> <span class="value date_uploaded"></span></section>

            <section>
                <span class="label">Description:</span>
                <div class="property-description"></div>
            </section>
    </div>
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

        // Get property_type from button data attribute
        const propertyTypeFromBtn = e.target.dataset.type || '-';

        modal.style.display = 'block';

        // Clear existing content
        const mainImage = modal.querySelector('.property-main-image');
        modal.querySelector('.property-name').textContent = '';
        modal.querySelector('.property-type').textContent = '';
        modal.querySelectorAll('.modal-right .value').forEach(v => v.textContent = '');
        modal.querySelector('.property-images').innerHTML = '';
        modal.querySelector('.property-description').textContent = '';

        try {
            const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${propertyId}`);
            const data = await res.json();

            if (!data.success || !data.property) {
                alert(data.error || 'Failed to load property details.');
                modal.style.display = 'none';
                return;
            }

            const prop = data.property;

            // Populate left column
            mainImage.style.backgroundImage = `url('${prop.images?.[0] || '/BatEstateExplorer/assets/images/bg4.jpg'}')`;
            modal.querySelector('.property-name').textContent = prop.title || '-';

            // Populate right column
            modal.querySelector('.location').textContent = prop.location || '-';
            modal.querySelector('.price').textContent = `₱${parseFloat(prop.price || 0).toLocaleString()}`;
            modal.querySelector('.property-type').textContent = prop.property_type || propertyTypeFromBtn || '-'; // fallback
            modal.querySelector('.bedrooms').textContent = prop.bedrooms || 0;
            modal.querySelector('.bathrooms').textContent = prop.bathrooms || 0;
            modal.querySelector('.sqm').textContent = `${prop.sqm || 0} sqm`;
            modal.querySelector('.lot_size').textContent = `${prop.lot_size || 0} sqm`;
            modal.querySelector('.status').textContent = prop.status || '-';
            modal.querySelector('.date_uploaded').textContent = prop.date_uploaded || '-';

            // Description
            modal.querySelector('.property-description').textContent = prop.description || '';

            // Populate images gallery with selection functionality
            const gallery = modal.querySelector('.property-images');
            (prop.images || []).forEach((img, idx) => {
                const imgEl = document.createElement('img');
                imgEl.src = img;

                if(idx === 0) imgEl.classList.add('active'); // first image selected by default

                imgEl.addEventListener('click', () => {
                    mainImage.style.backgroundImage = `url('${img}')`;
                    gallery.querySelectorAll('img').forEach(i => i.classList.remove('active'));
                    imgEl.classList.add('active');
                });

                gallery.appendChild(imgEl);
            });

        } catch (err) {
            console.error(err);
            alert('An unexpected error occurred.');
            modal.style.display = 'none';
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

