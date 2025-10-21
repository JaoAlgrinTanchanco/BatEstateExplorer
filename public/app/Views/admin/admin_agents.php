<?php

    // ---------------------
    // Helper Function
    // ---------------------
    function buildUploadUrl($filePath) {
        if (empty($filePath)) return '';
        if (str_starts_with($filePath, 'http') || str_starts_with($filePath, '/')) return $filePath;

        $filename = basename($filePath);
        $lower = strtolower($filePath);

        if (str_contains($lower, 'profile') || str_contains($lower, 'pfp')) {
            $baseURL = '/BatEstateExplorer/storage/uploads/profile_images/';
        } elseif (preg_match('/\.(jpg|jpeg|png|gif)$/i', $filename)) {
            $baseURL = '/BatEstateExplorer/storage/uploads/images/';
        } elseif (preg_match('/\.(pdf|doc|docx)$/i', $filename)) {
            $baseURL = '/BatEstateExplorer/storage/uploads/documents/';
        } else {
            $baseURL = '/BatEstateExplorer/storage/uploads/misc/';
        }

        return $baseURL . $filename;
    }

    // ---------------------
    // Fetch Direct Agents
    // ---------------------
    $direct_agents = [];
    $sql = "SELECT * FROM users WHERE user_type='direct_agent'";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['profile_image_path'] = buildUploadUrl($row['profile_image_path'] ?? '');
            $direct_agents[] = $row;
        }
        $stmt->close();
    }

    // ---------------------
    // Fetch Associate Agents
    // ---------------------
    $associate_agents = [];
    $sql = "SELECT * FROM users WHERE user_type='associate_agent'";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['profile_image_path'] = buildUploadUrl($row['profile_image_path'] ?? '');
            $associate_agents[] = $row;
        }
        $stmt->close();
    }
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_agents.css" />

<header class="content-header">
    <h1>Agents</h1>
</header>

<div class="content-body">

    <!-- Tabs -->
    <div class="tab-bar">
        <button class="tab-btn active" data-tab="direct">Direct Agents</button>
        <button class="tab-btn" data-tab="associate">Associate Agents</button>
    </div>

    <!-- Tab Panels -->
    <div id="tab-content">

        <!-- Direct Agents -->
        <div class="tab-panel" id="tab-direct" style="display: block;">
            <?php if (empty($direct_agents)): ?>
                <div class="no-agents">No approved direct agents found.</div>
            <?php else: ?>
                <div class="direct-agent-list">
                    <?php foreach ($direct_agents as $agent): ?>
                        <div class="direct-agent-card agent-card"
                             data-first-name="<?= htmlspecialchars($agent['first_name']); ?>"
                             data-last-name="<?= htmlspecialchars($agent['last_name']); ?>"
                             data-email="<?= htmlspecialchars($agent['email']); ?>"
                             data-phone="<?= htmlspecialchars($agent['phone']); ?>"
                             data-address="<?= htmlspecialchars($agent['address']); ?>"
                             data-user-type="Direct Agent"
                             data-profile-image-path="<?= htmlspecialchars($agent['profile_image_path']); ?>"
                             data-experience-years="<?= intval($agent['experience_years'] ?? 0); ?>"
                             data-specialization="<?= htmlspecialchars($agent['specialization'] ?? ''); ?>"
                             data-company-name="<?= htmlspecialchars($agent['company_id'] ?? 'N/A'); ?>"
                             data-education="<?= htmlspecialchars($agent['education'] ?? ''); ?>"
                             data-school="<?= htmlspecialchars($agent['school'] ?? ''); ?>"
                             data-course="<?= htmlspecialchars($agent['course'] ?? ''); ?>"
                             data-graduation-year="<?= htmlspecialchars($agent['graduation_year'] ?? ''); ?>"
                             data-status="<?= htmlspecialchars($agent['status'] ?? 'Active'); ?>"
                             data-account-created="<?= htmlspecialchars($agent['created_at']); ?>">
                            
                            <div class="direct-agent-avatar agent-avatar">
                                <?php if (!empty($agent['profile_image_path'])): ?>
                                    <img src="<?= htmlspecialchars($agent['profile_image_path']); ?>" alt="Profile" class="agent-profile-img" />
                                <?php else: ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                            </div>

                            <div class="direct-agent-info agent-info">
                                <div class="direct-agent-name agent-name"><?= htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></div>
                                <div class="direct-agent-details agent-details"><?= htmlspecialchars($agent['address'] ?? 'Unknown'); ?></div>
                            </div>

                            <div class="direct-agent-actions agent-actions">
                                <button class="btn btn-view">Details</button>
                                <button class="btn btn-remove" data-agent-id="<?= $agent['id']; ?>">Remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Associate Agents -->
        <div class="tab-panel" id="tab-associate" style="display: none;">
            <?php if (empty($associate_agents)): ?>
                <div class="no-agents">No approved associate agents found.</div>
            <?php else: ?>
                <div class="direct-agent-list"><!-- use same class as direct for styling -->
                    <?php foreach ($associate_agents as $agent): ?>
                        <div class="direct-agent-card agent-card"
                             data-first-name="<?= htmlspecialchars($agent['first_name']); ?>"
                             data-last-name="<?= htmlspecialchars($agent['last_name']); ?>"
                             data-email="<?= htmlspecialchars($agent['email']); ?>"
                             data-phone="<?= htmlspecialchars($agent['phone']); ?>"
                             data-address="<?= htmlspecialchars($agent['address']); ?>"
                             data-user-type="Associate Agent"
                             data-profile-image-path="<?= htmlspecialchars($agent['profile_image_path']); ?>"
                             data-experience-years="<?= intval($agent['experience_years'] ?? 0); ?>"
                             data-specialization="<?= htmlspecialchars($agent['specialization'] ?? ''); ?>"
                             data-company-name="<?= htmlspecialchars($agent['company_id'] ?? 'N/A'); ?>"
                             data-education="<?= htmlspecialchars($agent['education'] ?? ''); ?>"
                             data-school="<?= htmlspecialchars($agent['school'] ?? ''); ?>"
                             data-course="<?= htmlspecialchars($agent['course'] ?? ''); ?>"
                             data-graduation-year="<?= htmlspecialchars($agent['graduation_year'] ?? ''); ?>"
                             data-status="<?= htmlspecialchars($agent['status'] ?? 'Active'); ?>"
                             data-account-created="<?= htmlspecialchars($agent['created_at']); ?>">
                            
                            <div class="direct-agent-avatar agent-avatar">
                                <?php if (!empty($agent['profile_image_path'])): ?>
                                    <img src="<?= htmlspecialchars($agent['profile_image_path']); ?>" alt="Profile" class="agent-profile-img" />
                                <?php else: ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                            </div>

                            <div class="direct-agent-info agent-info">
                                <div class="direct-agent-name agent-name"><?= htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></div>
                                <div class="direct-agent-details agent-details"><?= htmlspecialchars($agent['address'] ?? 'Unknown'); ?></div>
                            </div>

                            <div class="direct-agent-actions agent-actions">
                                <button class="btn btn-view">Details</button>
                                <button class="btn btn-remove" data-agent-id="<?= $agent['id']; ?>">Remove</button>
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

    // Helper for document links
    function docLink(label, path) {
        if (!path || path.trim() === '' || path === 'null') return '';
        const filename = decodeURIComponent(path.split('/').pop() || 'Document');
        return `
            <div class="detail-row">
                <div class="detail-label">${label}:</div>
                <div class="detail-value">
                    <a href="${path}" target="_blank" class="document-link" title="${filename}">View</a>
                </div>
            </div>`;
    }

    // Event delegation for viewing agent details
    document.body.addEventListener('click', e => {
        if (!e.target.matches('.btn-view')) return;

        const card = e.target.closest('.agent-card');
        if (!card) return;

        const img = card.dataset.profileImagePath || '';
        let specialization = card.dataset.specialization || 'N/A';
        if (specialization) {
            try {
                const specArray = JSON.parse(specialization);
                if (Array.isArray(specArray) && specArray.length) specialization = specArray.join(', ');
            } catch {}
        }

        // Additional documents
        let additionalDocsHtml = '';
        if (card.dataset.additionalDocsPath) {
            const docs = card.dataset.additionalDocsPath.split(',').map(d => d.trim()).filter(Boolean);
            if (docs.length) {
                additionalDocsHtml = docs.map((doc, i) => `
                    <div class="detail-row">
                        <div class="detail-label">Additional Document ${i + 1}:</div>
                        <div class="detail-value">
                            <a href="${doc}" target="_blank" class="document-link">View</a>
                        </div>
                    </div>`).join('');
            }
        }

        // Build modal content
        modalBody.innerHTML = `
            <section class="personal-info">
                <h3>Personal Information</h3>
                <div class="agent-modal-header">
                    <div class="profile-col">
                        ${img ? `<img src="${img}" alt="Profile Picture" class="modal-profile-img" />` 
                              : `<i class="fa-solid fa-user modal-profile-icon"></i>`}
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
                <div class="detail-row"><div class="detail-label">Company:</div><div class="detail-value">${card.dataset.companyName || 'N/A'}</div></div>
                <div class="detail-row"><div class="detail-label">Experience:</div><div class="detail-value">${card.dataset.experienceYears || 0} years</div></div>
                <div class="detail-row"><div class="detail-label">Specialization:</div><div class="detail-value">${specialization}</div></div>
            </section>

            <section class="education">
                <h3>Education & Qualifications</h3>
                <div class="detail-row"><div class="detail-label">Education:</div><div class="detail-value">${card.dataset.education || 'N/A'}</div></div>
                <div class="detail-row"><div class="detail-label">School:</div><div class="detail-value">${card.dataset.school || 'N/A'}</div></div>
                <div class="detail-row"><div class="detail-label">Course:</div><div class="detail-value">${card.dataset.course || 'N/A'}</div></div>
                <div class="detail-row"><div class="detail-label">Graduation Year:</div><div class="detail-value">${card.dataset.graduationYear || 'N/A'}</div></div>
            </section>

            ${(
                docLink('Broker License', card.dataset.brokerLicensePath) +
                docLink('PRC License', card.dataset.prcLicensePath) +
                docLink('Resume/CV', card.dataset.resumePath) +
                docLink('Valid ID', card.dataset.validIdPath) +
                additionalDocsHtml
            ) ? `<section class="documents"><h3>Uploaded Documents</h3>
                ${docLink('Broker License', card.dataset.brokerLicensePath)}
                ${docLink('PRC License', card.dataset.prcLicensePath)}
                ${docLink('Resume/CV', card.dataset.resumePath)}
                ${docLink('Valid ID', card.dataset.validIdPath)}
                ${additionalDocsHtml}
            </section>` : ''}

            <section class="account">
                <h3>Account Information</h3>
                <div class="detail-row"><div class="detail-label">Account Created:</div><div class="detail-value">${card.dataset.accountCreated ? new Date(card.dataset.accountCreated).toLocaleDateString() : 'N/A'}</div></div>
                <div class="detail-row"><div class="detail-label">Status:</div><div class="detail-value">${card.dataset.status || 'N/A'}</div></div>
            </section>
        `;

        agentModal.style.display = 'block';
    });

    // ----------------------
    // Close modal
    // ----------------------
    agentModal.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => agentModal.style.display = 'none');
    });
    window.addEventListener('click', e => { 
        if (e.target === agentModal) agentModal.style.display = 'none'; 
    });
});
</script>
