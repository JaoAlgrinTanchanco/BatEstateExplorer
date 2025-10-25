<?php
    // ==============================
    // Helper: Normalize upload URLs
    // ==============================
    function buildUploadUrl(string $filePath): string {
        if (empty($filePath)) return '';

        // Already full URL or starts with /BatEstateExplorer/storage
        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://') || str_starts_with($filePath, 'BatEstateExplorer/storage')) {
            return '/' . ltrim($filePath, '/');
        }

        $filePath = ltrim($filePath, '/');
        $filename = basename($filePath);
        $lowerFile = strtolower($filePath);

        if (str_contains($lowerFile, 'profile') || str_contains($lowerFile, 'pfp')) {
            $baseURL = '/BatEstateExplorer/storage/uploads/profile_images/';
        } elseif (preg_match('/\.(jpg|jpeg|png|gif|bmp|webp)$/i', $filename)) {
            $baseURL = '/BatEstateExplorer/storage/uploads/images/';
        } elseif (preg_match('/\.(pdf|doc|docx)$/i', $filename)) {
            $baseURL = '/BatEstateExplorer/storage/uploads/documents/';
        } else {
            $baseURL = '/BatEstateExplorer/storage/uploads/misc/';
        }

        return rtrim($baseURL, '/') . '/' . $filename;
    }

    // ==============================
    // Fetch agents by type
    // ==============================
    function fetchAgents(string $type, $conn): array {
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_type = ?");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();

        $agents = [];
        while ($row = $result->fetch_assoc()) {
            $paths = [
                'profile_image_path', 'broker_license_path', 'prc_license_path', 
                'resume_path', 'valid_id_path', 
                'property_location_path', 'property_image_path', 'property_document_path'
            ];
            foreach ($paths as $p) {
                if (isset($row[$p])) {
                    $row[$p] = buildUploadUrl($row[$p]);
                }
            }

            // Additional docs
            if (!empty($row['additional_docs_path'])) {
                $docs = array_filter(array_map('trim', explode(',', $row['additional_docs_path'])));
                $docs = array_map('buildUploadUrl', $docs);
                $row['additional_docs_path'] = implode(',', $docs);
            } else {
                $row['additional_docs_path'] = '';
            }

            $agents[] = $row;
        }
        $stmt->close();
        return $agents;
    }

    // ==============================
    // Fetch direct and associate agents
    // ==============================
    $associate_agents = fetchAgents('associate_agent', $conn);
    $direct_agents    = fetchAgents('direct_agent', $conn);
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
                            data-broker-license-path="<?= htmlspecialchars($agent['broker_license_path'] ?? '') ?>"
                            data-prc-license-path="<?= htmlspecialchars($agent['prc_license_path'] ?? '') ?>"
                            data-resume-path="<?= htmlspecialchars($agent['resume_path'] ?? '') ?>"
                            data-valid-id-path="<?= htmlspecialchars($agent['valid_id_path'] ?? '') ?>"
                            data-property-location="<?= htmlspecialchars($agent['property_location'] ?? '') ?>"
                            data-property-image-path="<?= htmlspecialchars($agent['property_image_path'] ?? '') ?>"
                            data-property-document-path="<?= htmlspecialchars($agent['property_document_path'] ?? '') ?>"
                            data-additional-docs-path="<?= htmlspecialchars($agent['additional_docs_path'] ?? '') ?>"
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
                <div class="direct-agent-list"><!-- reuse class for styling -->
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
                            data-broker-license-path="<?= htmlspecialchars($agent['broker_license_path'] ?? ''); ?>"
                            data-prc-license-path="<?= htmlspecialchars($agent['prc_license_path'] ?? ''); ?>"
                            data-resume-path="<?= htmlspecialchars($agent['resume_path'] ?? ''); ?>"
                            data-valid-id-path="<?= htmlspecialchars($agent['valid_id_path'] ?? ''); ?>"
                            data-additional-docs-path="<?= htmlspecialchars($agent['additional_docs_path'] ?? ''); ?>"
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
                const panel = document.getElementById('tab-' + tab.dataset.tab);
                if (panel) panel.style.display = 'block';
            });
        });

        // ----------------------
        // Modal Elements
        // ----------------------
        const agentModal = document.getElementById('agentModal');
        const modalBody = document.getElementById('modalBody');
        const modalCloseBtns = agentModal.querySelectorAll('.cancel-btn');

        // ----------------------
        // Helper to render document links
        // ----------------------
        const docLink = (label, path, required = false) => {
            if (!path || path.trim() === '' || path === 'null') {
                if (required) {
                    return `<div class="detail-row"><div class="detail-label">${label}:</div><div class="detail-value">Not available</div></div>`;
                }
                return '';
            }
            const filename = decodeURIComponent(path.split('/').pop() || 'Document');
            return `<div class="detail-row"><div class="detail-label">${label}:</div><div class="detail-value"><a href="${path}" target="_blank" class="document-link" title="${filename}">View</a></div></div>`;
        };

        // ----------------------
        // Show agent modal on "Details" click
        // ----------------------
        document.body.addEventListener('click', e => {
            if (!e.target.matches('.btn-view')) return;

            const card = e.target.closest('.agent-card');
            if (!card) return;

            const img = card.dataset.profileImagePath || '';
            const userType = card.dataset.userType || 'Direct Agent';

            // Parse specialization if JSON
            let specialization = card.dataset.specialization || 'N/A';
            if (specialization.trim()) {
                try {
                    const parsed = JSON.parse(specialization);
                    if (Array.isArray(parsed) && parsed.length) specialization = parsed.join(', ');
                } catch {}
            }

            // Additional documents
            let additionalDocsHtml = '';
            if (card.dataset.additionalDocsPath) {
                const docs = card.dataset.additionalDocsPath.split(',').map(d => d.trim()).filter(Boolean);
                additionalDocsHtml = docs.map((d, i) => docLink(`Additional Document ${i+1}`, d)).join('');
            }

            // Build modal HTML
            modalBody.innerHTML = `
                <section class="personal-info">
                    <h3>Personal Information</h3>
                    <div class="agent-modal-header">
                        <div class="profile-col">
                            ${img ? `<img src="${img}" alt="Profile Picture" class="modal-profile-img"/>` : `<i class="fa-solid fa-user modal-profile-icon"></i>`}
                        </div>
                        <div class="info-col">
                            <h3 class="agent-fullname">${card.dataset.firstName || ''} ${card.dataset.lastName || ''}</h3>
                            <p class="agent-email">${card.dataset.email || 'N/A'}</p>
                            <p class="agent-type">Type: ${userType}</p>
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

                <section class="documents">
                    <h3>Uploaded Documents</h3>
                    ${
                        // normalize userType: lowercase, remove spaces/underscores
                        (() => {
                            const type = userType.toLowerCase().replace(/\s|_/g, '');
                            if (type === 'associateagent') {
                                return `
                                    ${docLink('Broker’s License', card.dataset.brokerLicensePath, true)}
                                    ${docLink('PRC License', card.dataset.prcLicensePath, true)}
                                    ${docLink('Resume/CV', card.dataset.resumePath, true)}
                                    ${docLink('Valid ID', card.dataset.validIdPath, true)}
                                `;
                            } else { // direct agent
                                return `
                                    ${docLink('Valid ID', card.dataset.validIdPath, true)}
                                    <div class="detail-row">
                                        <div class="detail-label">Property Location:</div>
                                        <div class="detail-value">${card.dataset.propertyLocation || 'Not available'}</div>
                                    </div>
                                    ${docLink('Property Image', card.dataset.propertyImagePath, true)}
                                    ${docLink('Property Document', card.dataset.propertyDocumentPath, true)}
                                `;
                            }
                        })()
                    }
                    ${additionalDocsHtml}
                </section>

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
        modalCloseBtns.forEach(btn => btn.addEventListener('click', () => agentModal.style.display = 'none'));
        window.addEventListener('click', e => {
            if (e.target === agentModal) agentModal.style.display = 'none';
        });
    });
</script>