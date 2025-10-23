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

<!-- Universal Agent Modal -->
<div id="agentModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Agent Details</h2>
    </div>
    <div class="modal-body" id="modalBody">
      <!-- Loaded dynamically -->
    </div>
    <div class="modal-footer">
      <button class="cancel-btn">Close</button>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // ----------------------
        // Tab Switching
        // ----------------------
        const tabs = document.querySelectorAll('.tab-btn');
        const panels = document.querySelectorAll('.tab-panel');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                panels.forEach(p => p.style.display = 'none');
                document.getElementById('tab-' + tab.dataset.tab).style.display = 'block';
            });
        });

        // ----------------------
        // Modal Logic
        // ----------------------
        const agentModal = document.getElementById('agentModal');
        const modalBody = document.getElementById('modalBody');

        // Helper: generate document link
        function docLink(label, path) {
            if (!path || path.trim() === '' || path === 'null') return '';
            const filename = decodeURIComponent(path.split('/').pop() || 'Document');
            return `
                <div class="detail-row">
                    <div class="detail-label">${label}:</div>
                    <div class="detail-value">
                        <a href="${path}" target="_blank" class="document-link" title="${filename}">View</a>
                    </div>
                </div>
            `;
        }

        document.body.addEventListener('click', e => {
            if (!e.target.matches('.btn-view')) return;

            const card = e.target.closest('.direct-agent-card, .associate-agent-card');
            if (!card) return;

            const profileImg = card.dataset.profileImagePath?.trim() || null;
            let specialization = 'N/A';

            // parse specialization if JSON array
            if (card.dataset.specialization) {
                try {
                    const specArray = JSON.parse(card.dataset.specialization);
                    if (Array.isArray(specArray) && specArray.length > 0) {
                        specialization = specArray.join(', ');
                    } else {
                        specialization = card.dataset.specialization;
                    }
                } catch {
                    specialization = card.dataset.specialization;
                }
            }

            // Direct agents always show documents; associate only if exists
            let documentsHtml = '';
            const isDirect = card.dataset.userType?.toLowerCase() === 'direct agent';
            const docFields = ['brokerLicensePath','prcLicensePath','resumePath','validIdPath','additionalDocsPath'];

            if (isDirect || docFields.some(f => card.dataset[f])) {
                documentsHtml = `<section class="documents"><h3>Uploaded Documents</h3>`;

                // Main documents
                documentsHtml += docLink('Broker License', card.dataset.brokerLicensePath);
                documentsHtml += docLink('PRC License', card.dataset.prcLicensePath);
                documentsHtml += docLink('Resume/CV', card.dataset.resumePath);
                documentsHtml += docLink('Valid ID', card.dataset.validIdPath);

                // Additional documents (comma-separated)
                if (card.dataset.additionalDocsPath) {
                    const additionalDocs = card.dataset.additionalDocsPath.split(',').map(d => d.trim()).filter(Boolean);
                    if (additionalDocs.length) {
                        additionalDocs.forEach((doc, idx) => {
                            documentsHtml += docLink(`Additional Document ${idx+1}`, doc);
                        });
                    }
                }

                if (!documentsHtml.match('<div class="detail-row">')) {
                    documentsHtml += `<div class="detail-row"><div class="detail-value">No documents uploaded</div></div>`;
                }

                documentsHtml += `</section>`;
            }

            modalBody.innerHTML = `
                <section class="personal-info">
                    <h3>Personal Information</h3>
                    <div class="agent-modal-header">
                        <div class="profile-col">
                            ${
                            profileImg
                                ? `<img src="${profileImg}" alt="Profile Picture" class="modal-profile-img" />`
                                : `<i class="fa-solid fa-user modal-profile-icon"></i>`
                            }
                        </div>
                        <div class="info-col">
                            <h3 class="agent-fullname">${card.dataset.firstName || ''} ${card.dataset.lastName || ''}</h3>
                            <p class="agent-email">${card.dataset.email || 'N/A'}</p>
                            <p class="agent-type">Type: ${card.dataset.userType || 'Direct Agent'}</p>
                        </div>
                    </div>
                    <div class="detail-row"><div class="detail-label">Phone:</div><div class="detail-value">${card.dataset.phone || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">Address:</div><div class="detail-value">${card.dataset.address || 'N/A'}</div></div>
                </section>

                <section class="agent-info">
                    <h3>Agent Information</h3>
                    <div class="detail-row"><div class="detail-label">Broker ID:</div><div class="detail-value">${card.dataset.brokerId || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">License Number:</div><div class="detail-value">${card.dataset.licenseNumber || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">Experience:</div><div class="detail-value">${card.dataset.experienceYears || '0'} years</div></div>
                    <div class="detail-row"><div class="detail-label">Specialization:</div><div class="detail-value">${specialization}</div></div>
                </section>

                <section class="education">
                    <h3>Education & Qualifications</h3>
                    <div class="detail-row"><div class="detail-label">Education:</div><div class="detail-value">${card.dataset.education || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">School:</div><div class="detail-value">${card.dataset.school || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">Course:</div><div class="detail-value">${card.dataset.course || 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">Graduation Year:</div><div class="detail-value">${card.dataset.graduationYear || 'N/A'}</div></div>
                </section>

                ${documentsHtml}

                <section class="account">
                    <h3>Account Information</h3>
                    <div class="detail-row"><div class="detail-label">Account Created:</div><div class="detail-value">${card.dataset.accountCreated ? new Date(card.dataset.accountCreated).toLocaleDateString() : 'N/A'}</div></div>
                    <div class="detail-row"><div class="detail-label">Status:</div><div class="detail-value">${card.dataset.status || 'N/A'}</div></div>
                </section>
            `;

            agentModal.style.display = 'block';
        });

        // Close modal
        agentModal.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.addEventListener('click', () => agentModal.style.display = 'none');
        });
        window.addEventListener('click', e => { if (e.target === agentModal) agentModal.style.display = 'none'; });
    });
</script>
