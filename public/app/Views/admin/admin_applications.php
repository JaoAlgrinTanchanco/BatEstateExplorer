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

    // Fetch pending applications
    $applications = [];
    if ($conn) {
        $query = "SELECT a.*, c.name AS company_name
            FROM applications a
            LEFT JOIN companies c ON a.company_id = c.id
            WHERE a.status = 'pending'
            ORDER BY a.created_at DESC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {

                // Decode specialization JSON string to array
                $row['specializations'] = [];
                if (!empty($row['specialization'])) {
                    $decoded = json_decode($row['specialization'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $row['specializations'] = $decoded;
                    } else {
                        // fallback: comma-separated string
                        $row['specializations'] = array_map('trim', explode(',', $row['specialization']));
                    }
                }
                unset($row['specialization']); // remove raw DB string

                $applications[] = $row;
            }
        }
    }
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_applications.css" />

<header class="content-header">
    <h1>Agent Registrations</h1>
</header>

<div class="content-body">
    <div class="applications-header">
        <h2>Pending Applications</h2>
        <div class="sort-row">
            <label for="sort">Sort By:</label>
            <select id="sort">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="name">Applicant Name</option>
                <option value="type">Agent Type</option>
            </select>
        </div>
    </div>

    <div class="application-list" id="applicationList">
        <?php if (empty($applications)): ?>
            <div class="no-applications">No pending applications.</div>
        <?php else: ?>
            <?php foreach ($applications as $app): ?>
                <div class="application-card"
                    data-name="<?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>"
                    data-type="<?php echo htmlspecialchars($app['agent_type']); ?>"
                    data-date="<?php echo htmlspecialchars($app['created_at']); ?>">
                    
                    <div class="application-header">
                        <?php
                        $profileImageUrl = !empty($app['profile_image_path'])
                            ? '/BatEstateExplorer/storage/uploads/profile_images/' . basename($app['profile_image_path'])
                            : '';
                        ?>
                        <!-- LEFT: Profile Image -->
                        <div class="applicant-avatar">
                            <?php if (!empty($profileImageUrl)): ?>
                                <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profile" class="avatar-img">
                            <?php else: ?>
                                <i class="fa-solid fa-user default-avatar"></i>
                            <?php endif; ?>
                        </div>

                        <!-- RIGHT: Name, Email, Applied Date -->
                        <div class="applicant-info">
                            <div class="applicant-name"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                            <div class="applicant-details"><?php echo htmlspecialchars($app['email']); ?> • <?php echo htmlspecialchars(str_replace('_', ' ', $app['agent_type'])); ?></div>
                            <div class="applicant-details">Applied: <?php echo date('M d, Y', strtotime($app['created_at'])); ?></div>
                        </div>

                        <!-- Status Badge -->
                        <div class="application-status status-<?php echo htmlspecialchars($app['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($app['status'])); ?>
                        </div>
                    </div>

                    <!-- Card content and actions remain the same -->
                    <div class="application-content">
                        <div class="application-field"><span class="field-label">Broker ID:</span><span class="field-value"><?php echo htmlspecialchars($app['broker_id'] ?: 'N/A'); ?></span></div>
                        <div class="application-field"><span class="field-label">Experience:</span><span class="field-value"><?php echo htmlspecialchars($app['experience_years'] ?: 'N/A'); ?> years</span></div>
                        <div class="application-field"><span class="field-label">Address:</span><span class="field-value"><?php echo htmlspecialchars($app['address'] ?: 'N/A'); ?></span></div>
                        <?php if ($app['company_name']): ?>
                        <div class="application-field"><span class="field-label">Company:</span><span class="field-value"><?php echo htmlspecialchars($app['company_name']); ?></span></div>
                        <?php endif; ?>
                    </div>

                    <div class="application-actions">
                        <button class="view-btn" id="openModalBtn" data-id="<?php echo (int)$app['id']; ?>">View Details</button>
                        <?php if ($app['status'] === 'pending'): ?>
                            <button class="approve-btn" data-id="<?php echo (int)$app['id']; ?>">Approve</button>
                            <button class="reject-btn" data-id="<?php echo (int)$app['id']; ?>">Reject</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div id="applicationModal" class="modal" aria-hidden="true" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Application Details</h2>
      <button class="close" aria-label="Close">&times;</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
    <div class="modal-footer">
      <button class="cancel-btn">Close</button>
    </div>
  </div>
</div>

<style>
    /* Overlay background */
    .confirm-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
    }

    /* Modal card */
    .confirm-modal .modal-content {
        background: #fff;
        color: #333;
        padding: 20px 25px;
        border-radius: 12px;
        width: 320px;
        max-width: 90%;
        box-shadow: 0 8px 25px rgba(0,0,0,0.25);
        font-family: sans-serif;
        text-align: center;
        animation: scaleIn 0.2s ease;
    }

    /* Buttons */
    .confirm-modal .btn {
        padding: 8px 18px;
        border-radius: 6px;
        border: none;
        font-size: 14px;
        cursor: pointer;
        margin: 8px;
        transition: background 0.2s ease, transform 0.15s ease;
    }

    .confirm-modal .btn:hover {
        transform: translateY(-1px);
    }

    .confirm-modal .btn-primary {
        background: #007bff;
        color: #fff;
    }

    .confirm-modal .btn-primary:hover {
        background: #0062cc;
    }

    .confirm-modal .btn-secondary {
        background: #e0e0e0;
        color: #333;
    }

    .confirm-modal .btn-secondary:hover {
        background: #cfcfcf;
    }

    /* Animation */
    @keyframes scaleIn {
        from { transform: scale(0.9); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>

<script>
    // === UTILITY: ESCAPE HTML ===
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // === RENDER SPECIALIZATIONS ===
    function renderSpecializations(specs) {
        if (!specs || (Array.isArray(specs) && specs.length === 0)) return 'N/A';
        if (typeof specs === 'string') {
            try { specs = JSON.parse(specs); }
            catch { specs = specs.split(',').map(s => s.trim()); }
        }
        if (Array.isArray(specs)) return specs.map(s => escapeHtml(s)).join(', ');
        return escapeHtml(String(specs));
    }

    // === DETAIL ROW HELPER ===
    function detailRow(label, value) {
        return `
            <div class="detail-row">
                <div class="detail-label">${label}:</div>
                <div class="detail-value">${value}</div>
            </div>
        `;
    }

    // === BUILD PUBLIC URL FROM STORAGE PATH ===
    function buildFileUrl(path) {
        if (!path) return null;
        const filename = path.split(/[/\\]/).pop();

        if (path.includes('profile_images')) return `/BatEstateExplorer/storage/uploads/profile_images/${filename}`;
        if (path.includes('documents')) return `/BatEstateExplorer/storage/uploads/documents/${filename}`;
        if (path.includes('images')) return `/BatEstateExplorer/storage/uploads/images/${filename}`;
        if (path.includes('property_images')) return `/BatEstateExplorer/storage/uploads/property_images/${filename}`;
        
        return '';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const applicationList = document.getElementById('applicationList');
        const modal = document.getElementById('applicationModal');
        const modalBody = document.getElementById('modalBody');
        const sortSelect = document.getElementById('sort');

        if (applicationList) applicationList.addEventListener('click', handleApplicationClick);
        if (sortSelect) sortSelect.addEventListener('change', handleSortChange);
        initModalCloseEvents();

        // === CLICK HANDLER ===
        function handleApplicationClick(e) {
            const btn = e.target.closest('.view-btn, .approve-btn, .reject-btn');
            if (!btn) return;
            const id = btn.dataset.id;
            if (!id) return;

            if (btn.classList.contains('view-btn')) fetchApplicationDetails(id);
            else if (btn.classList.contains('approve-btn')) reviewApplication(id, 'approve', btn);
            else if (btn.classList.contains('reject-btn')) reviewApplication(id, 'reject', btn);
        }

        // === FETCH DETAILS ===
        function fetchApplicationDetails(id) {
            fetch(`/BatEstateExplorer/public/api/get_application_details.php?id=${encodeURIComponent(id)}`)
                .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                .then(data => {
                    if (!data || !data.success) { alert('Failed to load application details'); return; }
                    renderApplicationDetails(data.application);
                    showModal();
                })
                .catch(err => { console.error(err); alert('Error loading application details'); });
        }

        // === RENDER DETAILS ===
        function renderApplicationDetails(app) {
            let docSection = '';
            const profileImageUrl = app.profile_image_path ? buildFileUrl(app.profile_image_path) : '';

            if (app.agent_type === 'associate_agent') {
                docSection = `
                    <h3>Documents Submitted</h3>
                    ${app.broker_license_path ? detailRow('Broker License', `<a href="${escapeHtml(buildFileUrl(app.broker_license_path))}" target="_blank">View</a>`) : ''}
                    ${app.prc_license_path ? detailRow('PRC License', `<a href="${escapeHtml(buildFileUrl(app.prc_license_path))}" target="_blank">View</a>`) : ''}
                    ${app.resume_path ? detailRow('Resume / CV', `<a href="${escapeHtml(buildFileUrl(app.resume_path))}" target="_blank">View</a>`) : ''}
                    ${app.valid_id_path ? detailRow('Valid ID', `<a href="${escapeHtml(buildFileUrl(app.valid_id_path))}" target="_blank">View</a>`) : ''}
                `;
            } else if (app.agent_type === 'direct_agent') {
                docSection = `
                    <h3>Documents Submitted</h3>
                    ${app.valid_id_path ? detailRow('Valid ID', `<a href="${escapeHtml(buildFileUrl(app.valid_id_path))}" target="_blank">View</a>`) : ''}
                    ${detailRow('Property Location', escapeHtml(app.property_location || 'N/A'))}
                    ${app.property_image_path ? detailRow('Property Image', `<a href="${escapeHtml(buildFileUrl(app.property_image_path))}" target="_blank">View</a>`) : ''}
                    ${app.property_document_path ? detailRow('Property Document', `<a href="${escapeHtml(buildFileUrl(app.property_document_path))}" target="_blank">View</a>`) : ''}
                `;
            }

            modalBody.innerHTML = `
                <div class="detail-section">
                    ${profileImageUrl
                        ? `<div class="modal-avatar"><img src="${escapeHtml(profileImageUrl)}" alt="Profile" class="avatar-img" style="width:100px;height:100px;border-radius:50%;margin-bottom:15px;"></div>`
                        : `<i class="fa-solid fa-user default-avatar" style="font-size:80px; display:block; margin-bottom:15px;"></i>`}
                    
                    <h3>Applicant Info</h3>
                    ${detailRow('Full Name', `${escapeHtml(app.first_name)} ${escapeHtml(app.last_name)}`)}
                    ${detailRow('Email', escapeHtml(app.email))}
                    ${detailRow('Phone', escapeHtml(app.phone || 'N/A'))}
                    ${detailRow('Address', escapeHtml(app.address || 'N/A'))}
                    ${detailRow('Agent Type', escapeHtml(app.agent_type))}
                    ${app.company_name ? detailRow('Company', escapeHtml(app.company_name)) : ''}
                    ${detailRow('Broker ID', escapeHtml(app.broker_id || 'N/A'))}
                    ${detailRow('License Number', escapeHtml(app.license_number || 'N/A'))}
                    ${detailRow('Experience Years', escapeHtml(app.experience_years || 'N/A'))}
                    ${detailRow('Specializations', renderSpecializations(app.specializations))}
                    ${detailRow('Bio', escapeHtml(app.bio || 'N/A'))}
                    ${detailRow('Education', escapeHtml(app.education || 'N/A'))}
                    ${detailRow('School', escapeHtml(app.school || 'N/A'))}
                    ${detailRow('Course', escapeHtml(app.course || 'N/A'))}
                    ${detailRow('Graduation Year', escapeHtml(app.graduation_year || 'N/A'))}
                    ${detailRow('Certifications', escapeHtml(app.certifications || 'N/A'))}
                    ${detailRow('Training', escapeHtml(app.training || 'N/A'))}
                </div>
                ${docSection}
            `;
        }

        // === CONFIRM MODAL ===
        function showConfirm(msg) {
            return new Promise(resolve => {
                const confirmModal = document.createElement('div');
                confirmModal.className = 'confirm-modal';
                confirmModal.innerHTML = `
                    <div class="modal-content">
                        <p style="margin-bottom:15px; font-size:15px;">${msg}</p>
                        <button class="btn btn-primary" id="confirmYes">Yes</button>
                        <button class="btn btn-secondary" id="confirmNo">No</button>
                    </div>
                `;
                document.body.appendChild(confirmModal);
                confirmModal.querySelector('#confirmYes').addEventListener('click', () => { confirmModal.remove(); resolve(true); });
                confirmModal.querySelector('#confirmNo').addEventListener('click', () => { confirmModal.remove(); resolve(false); });
            });
        }

        // === APPROVE / REJECT ===
        async function reviewApplication(id, action, btn) {
            const confirmed = await showConfirm(`Are you sure you want to ${action} this application?`);
            if (!confirmed) return;

            fetch('/BatEstateExplorer/public/api/admin_application_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, action })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(`Application ${action}d`);
                    btn.closest('.application-card')?.remove();
                } else {
                    alert(`Error: ${data.message}`);
                }
            })
            .catch(() => alert('Network error'));
        }

        // === SORT ===
        function handleSortChange() {
            const sortBy = sortSelect.value;
            const cards = Array.from(applicationList.querySelectorAll('.application-card'));
            cards.sort((a, b) => {
                switch (sortBy) {
                    case 'newest': return new Date(b.dataset.date) - new Date(a.dataset.date);
                    case 'oldest': return new Date(a.dataset.date) - new Date(b.dataset.date);
                    case 'name': return a.dataset.name.localeCompare(b.dataset.name);
                    case 'type': return a.dataset.type.localeCompare(b.dataset.type);
                    default: return 0;
                }
            });
            cards.forEach(c => applicationList.appendChild(c));
        }

        // === MODAL HANDLING ===
        function initModalCloseEvents() {
            modal.querySelectorAll('.close, .cancel-btn').forEach(btn => btn.addEventListener('click', closeModal));
            window.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        }

        function showModal() { modal.style.display = 'flex'; modal.setAttribute('aria-hidden', 'false'); }
        function closeModal() { modal.style.display = 'none'; modal.setAttribute('aria-hidden', 'true'); document.querySelector('#openModalBtn')?.focus(); }
    });
</script>
