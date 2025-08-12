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
            $applications[] = $row;
        }
    }
}

?>

<header class="content-header">
    <h1>Admin Applications</h1>
    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($current_user['email']); ?></span>
    </div>
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
                        <div class="applicant-info">
                            <div class="applicant-name"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                            <div class="applicant-details"><?php echo htmlspecialchars($app['email']); ?> • <?php echo htmlspecialchars(str_replace('_', ' ', $app['agent_type'])); ?></div>
                            <div class="applicant-details">Applied: <?php echo date('M d, Y', strtotime($app['created_at'])); ?></div>
                        </div>
                        <div class="application-status status-<?php echo htmlspecialchars($app['status']); ?>"><?php echo ucfirst(htmlspecialchars($app['status'])); ?></div>
                    </div>

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


<script>
document.addEventListener('DOMContentLoaded', () => {
    // === ELEMENT REFERENCES ===
    const applicationList = document.getElementById('applicationList');
    const modal = document.getElementById('applicationModal');
    const modalBody = document.getElementById('modalBody');
    const sortSelect = document.getElementById('sort');

    // === EVENT LISTENERS ===
    if (applicationList) {
        applicationList.addEventListener('click', handleApplicationClick);
    }
    if (sortSelect) {
        sortSelect.addEventListener('change', handleSortChange);
    }
    initModalCloseEvents();

    // === MAIN CLICK HANDLER FOR APPLICATION LIST ===
    function handleApplicationClick(e) {
        const targetBtn = e.target.closest('.view-btn, .approve-btn, .reject-btn');
        if (!targetBtn) return;

        const id = targetBtn.dataset.id;
        if (!id) return;

        if (targetBtn.classList.contains('view-btn')) {
            fetchApplicationDetails(id);
        } else if (targetBtn.classList.contains('approve-btn')) {
            reviewApplication(id, 'approve', targetBtn);
        } else if (targetBtn.classList.contains('reject-btn')) {
            reviewApplication(id, 'reject', targetBtn);
        }
    }

    // === FETCH APPLICATION DETAILS ===
    function fetchApplicationDetails(id) {
        fetch(`/BatEstateExplorer/public/api/get_application_details.php?id=${encodeURIComponent(id)}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert('Failed to load application details');
                    return;
                }
                renderApplicationDetails(data.application);
                showModal();
            })
            .catch(() => alert('Error loading application details'));
    }

    // === RENDER APPLICATION DETAILS IN MODAL ===
    function renderApplicationDetails(app) {
        modalBody.innerHTML = `
            <div class="detail-section">
                <h3>Applicant Info</h3>
                ${detailRow('Full Name', `${escapeHtml(app.first_name)} ${escapeHtml(app.last_name)}`)}
                ${detailRow('Email', escapeHtml(app.email))}
                ${detailRow('Broker ID', escapeHtml(app.broker_id || 'N/A'))}
                ${detailRow('Experience', `${escapeHtml(app.experience_years || 'N/A')} years`)}
                ${detailRow('Address', escapeHtml(app.address || 'N/A'))}
                ${detailRow('Company', escapeHtml(app.company_name || 'N/A'))}
            </div>
        `;
    }

    function detailRow(label, value) {
        return `
            <div class="detail-row">
                <div class="detail-label">${label}:</div>
                <div class="detail-value">${value}</div>
            </div>
        `;
    }

    // === APPROVE / REJECT APPLICATION ===
    function reviewApplication(id, action, button) {
        if (!confirm(`Are you sure you want to ${action} this application?`)) return;

        fetch('/api/admin_application_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(`Application ${action}d`);
                    button.closest('.application-card')?.remove();
                } else {
                    alert(`Error: ${data.message}`);
                }
            })
            .catch(() => alert('Network error'));
    }

    // === SORT APPLICATION CARDS ===
    function handleSortChange() {
        const sortBy = sortSelect.value;
        const cards = Array.from(applicationList.querySelectorAll('.application-card'));

        cards.sort((a, b) => {
            switch (sortBy) {
                case 'newest':
                    return new Date(b.dataset.date) - new Date(a.dataset.date);
                case 'oldest':
                    return new Date(a.dataset.date) - new Date(b.dataset.date);
                case 'name':
                    return a.dataset.name.localeCompare(b.dataset.name);
                case 'type':
                    return a.dataset.type.localeCompare(b.dataset.type);
                default:
                    return 0;
            }
        });

        cards.forEach(card => applicationList.appendChild(card));
    }

    // === MODAL HANDLING ===
    function initModalCloseEvents() {
        modal.querySelectorAll('.close, .cancel-btn').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });
        window.addEventListener('click', e => {
            if (e.target === modal) closeModal();
        });
    }

    function showModal() {
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        document.querySelector('#openModalBtn')?.focus();
    }

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
});
</script>
