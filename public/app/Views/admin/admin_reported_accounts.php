<?php
    // --- Correct path to bootstrap ---
    require_once __DIR__ . '/../../bootstrap.php';

    // --- Ensure database connection exists ---
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) die("Database connection not found.");

    // --- Fetch reported accounts ---
    $sql = "
        SELECT 
            ar.id,
            'agent' AS report_type,
            ar.reporter_id,
            ar.agent_id AS reported_id,
            ar.reason,
            ar.other_reason,
            ar.details,
            ar.status,
            ar.created_at,
            reporter.first_name AS reporter_fname, reporter.last_name AS reporter_lname, reporter.email AS reporter_email,
            reported.first_name AS reported_fname, reported.last_name AS reported_lname, reported.email AS reported_email
        FROM agent_reports ar
        LEFT JOIN users reporter ON ar.reporter_id = reporter.id
        LEFT JOIN users reported ON ar.agent_id = reported.id

        UNION ALL

        SELECT 
            ur.id,
            'user' AS report_type,
            ur.reporter_id,
            ur.reported_user_id AS reported_id,
            ur.reason,
            ur.other_reason,
            ur.details,
            ur.status,
            ur.created_at,
            reporter.first_name AS reporter_fname, reporter.last_name AS reporter_lname, reporter.email AS reporter_email,
            reported.first_name AS reported_fname, reported.last_name AS reported_lname, reported.email AS reported_email
        FROM user_reports ur
        LEFT JOIN users reporter ON ur.reporter_id = reporter.id
        LEFT JOIN users reported ON ur.reported_user_id = reported.id

        ORDER BY created_at DESC
    ";

    $result = $conn->query($sql);
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_reported_accounts.css" />

<header class="content-header">
    <h1>Reported Accounts</h1>
</header>

<div class="content-body">

    <div class="sort-row">
        <div class="sort-by">
            <label for="statusFilter">Filter by Status:</label>
            <select id="statusFilter">
                <option value="all">All</option>
                <option value="pending">Pending</option>
                <option value="blocked">Blocked</option>
                <option value="unblocked">Unblocked</option>
            </select>
        </div>
    </div>

    <div class="reported-accounts-table">
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>Reporter</th>
                    <th>Reported Account</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Other Reason</th>
                    <th>Details</th>
                    <th>Status</th>
                    <th>Date Reported</th>
                </tr>
            </thead>
            <tbody id="reportsBody">
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">No reported accounts found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $r): ?>
                        <tr class="clickable-row" 
                            data-status="<?= htmlspecialchars($r['status']); ?>"
                            data-reporter="<?= htmlspecialchars($r['reporter_fname'] . ' ' . $r['reporter_lname'] . ' (' . $r['reporter_email'] . ')'); ?>"
                            data-reported="<?= htmlspecialchars($r['reported_fname'] . ' ' . $r['reported_lname'] . ' (' . $r['reported_email'] . ')'); ?>"
                            data-reason="<?= htmlspecialchars($r['reason']); ?>"
                            data-other-reason="<?= htmlspecialchars($r['other_reason']); ?>"
                            data-details="<?= htmlspecialchars($r['details']); ?>"
                            data-category="<?= htmlspecialchars($r['reason']); ?>"
                            data-reported-id="<?= htmlspecialchars($r['reported_id']); ?>"
                            data-report-id="<?= htmlspecialchars($r['id']); ?>"
                            data-type="<?= htmlspecialchars($r['report_type']); ?>"
                            data-created-at="<?= htmlspecialchars($r['created_at']); ?>"
                        >
                            <td><?= htmlspecialchars($r['reporter_fname'] . ' ' . $r['reporter_lname'] . ' (' . $r['reporter_email'] . ')'); ?></td>
                            <td><?= htmlspecialchars($r['reported_fname'] . ' ' . $r['reported_lname'] . ' (' . $r['reported_email'] . ')'); ?></td>
                            <td><?= ucfirst(htmlspecialchars($r['report_type'])); ?></td>
                            <td><?= htmlspecialchars($r['reason']); ?></td>
                            <td><?= htmlspecialchars($r['other_reason']); ?></td>
                            <td><?= htmlspecialchars($r['details']); ?></td>
                            <td><?= htmlspecialchars(ucfirst($r['status'])); ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($r['created_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Modal -->
<div id="reportModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Report Details</h2>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Dynamic content -->
        </div>
        <div class="modal-footer">
            <button id="blockUnblockBtn" class="btn btn-danger">Block Agent</button>
            <button id="deleteReportBtn" class="btn btn-secondary">Delete Report</button>
            <button class="cancel-btn">Close</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const statusFilter = document.getElementById('statusFilter');
        const reportsBody = document.getElementById('reportsBody');
        const reportModal = document.getElementById('reportModal');
        const modalBody = document.getElementById('modalBody');
        const blockUnblockBtn = document.getElementById('blockUnblockBtn');
        const deleteReportBtn = document.getElementById('deleteReportBtn');

        /* =====================================================
        PENALTIES & CATEGORY DEFINITIONS
        ===================================================== */

        const penaltyNotes = {
            // --- User-specific ---
            'harassment': 'User temporarily suspended for 7 days due to harassment or inappropriate behavior.',
            'spam': 'User messaging privileges restricted for 48 hours due to spam or irrelevant contact.',
            'fake_review': 'User banned from posting reviews for 30 days due to fake feedback.',
            'misinformation': 'User restricted for 7 days for spreading misinformation.',
            'false_report': 'User temporarily restricted from reporting agents for 7 days.',
            'fraudulent_activity': 'User permanently banned for fraudulent or impersonation activity.',
            'impersonation': 'User permanently banned for impersonating another person.',
            'other': 'Admin may assign a custom temporary penalty depending on severity.',

            // --- Agent-specific (for completeness) ---
            'fraudulent_listing': 'Agent banned for life due to fraudulent or fake listings.',
            'harassment_agent': 'Agent permanently banned for harassment or unprofessional conduct.',
            'misinformation_agent': 'Agent suspended for 7 days due to false or misleading information.',
            'spam_agent': 'Agent suspended for 48 hours for excessive or irrelevant contact.',
        };

        const categoryFullNames = {
            'harassment': 'Harassment or inappropriate behavior',
            'spam': 'Spam or irrelevant contact',
            'fake_review': 'Fake or manipulated review',
            'misinformation': 'False or misleading information',
            'false_report': 'False or malicious report',
            'fraudulent_activity': 'Fraudulent or deceptive activity',
            'impersonation': 'Impersonation or identity misuse',
            'other': 'Other (Custom penalty)',
            // For agents:
            'fraudulent_listing': 'Fraudulent or fake listing',
            'harassment_agent': 'Harassment or inappropriate behavior (Agent)',
            'misinformation_agent': 'False or misleading information (Agent)',
            'spam_agent': 'Spam or irrelevant contact (Agent)',
        };

        let currentReportedId = null;
        let currentReportId = null;
        let currentCategory = null;
        let currentStatus = null;
        let currentType = null; // 'agent' or 'user'

        /* =====================================================
        Helper: Update row color and label based on status
        ===================================================== */
        function updateStatusCell(row, status) {
            const statusCell = row.querySelector('td:nth-child(7)');
            if (!statusCell) return;
            statusCell.textContent = status.charAt(0).toUpperCase() + status.slice(1);

            switch (status) {
                case 'blocked':
                    statusCell.style.color = '#ff0000';
                    break;
                case 'unblocked':
                    statusCell.style.color = '#008dff';
                    break;
                default:
                    statusCell.style.color = '#12d800';
                    break;
            }
        }

        reportsBody.querySelectorAll('tr.clickable-row').forEach(row => {
            updateStatusCell(row, row.dataset.status);
        });

        /* =====================================================
        Filter by status
        ===================================================== */
        statusFilter?.addEventListener('change', () => {
            const selected = statusFilter.value;
            reportsBody.querySelectorAll('tr.clickable-row').forEach(row => {
                row.style.display = (selected === 'all' || row.dataset.status === selected) ? '' : 'none';
            });
        });

        /* =====================================================
        Modal open on row click
        ===================================================== */
        reportsBody?.addEventListener('click', e => {
            const row = e.target.closest('tr.clickable-row');
            if (!row) return;

            currentReportedId = row.dataset.reportedId;
            currentReportId = row.dataset.reportId;
            currentCategory = row.dataset.category || 'other';
            currentStatus = row.dataset.status;
            currentType = row.dataset.type; // 'agent' or 'user'

            const isUser = currentType === 'user';
            const categoryKey = currentCategory;
            const fullReason = categoryFullNames[categoryKey] || 'Other';

            let penaltyHtml = `<p style="color:red;"><strong>Penalty Note:</strong> ${
                penaltyNotes[categoryKey] || penaltyNotes['other']
            }</p>`;

            // If custom penalty duration is needed
            if (currentCategory === 'other') {
                penaltyHtml += `
                    <p><strong>Set Penalty Duration:</strong>
                        <select id="banDurationSelect">
                            <option value="48hrs">48 hours</option>
                            <option value="7days">7 days</option>
                            <option value="30days">30 days</option>
                            <option value="lifetime">Permanent Ban</option>
                        </select>
                    </p>`;
            }

            modalBody.innerHTML = `
                <p><strong>Reporter:</strong> ${row.dataset.reporter}</p>
                <p><strong>Reported ${isUser ? 'User' : 'Agent'}:</strong> ${row.dataset.reported}</p>
                <p><strong>Reason:</strong> ${fullReason}</p>
                <p><strong>Other Reason:</strong> ${row.dataset.otherReason || 'N/A'}</p>
                <p><strong>Details:</strong> ${row.dataset.details}</p>
                <p><strong>Reported On:</strong> ${new Date(row.dataset.createdAt).toLocaleString()}</p>
                ${penaltyHtml}
            `;

            blockUnblockBtn.textContent =
                currentStatus === 'blocked'
                    ? `Unblock ${isUser ? 'User' : 'Agent'}`
                    : `Block ${isUser ? 'User' : 'Agent'}`;

            reportModal.style.display = 'block';
        });

        /* =====================================================
        Block / Unblock logic
        ===================================================== */
        blockUnblockBtn.addEventListener('click', () => {
            if (!currentReportedId || !currentType) return;

            const duration = document.getElementById('banDurationSelect')?.value || null;
            const action = blockUnblockBtn.textContent.toLowerCase().includes('unblock') ? 'unblock' : 'block';
            const displayType = currentType.charAt(0).toUpperCase() + currentType.slice(1);

            if (!confirm(`Are you sure you want to ${action} this ${displayType}?`)) return;

            const endpoint =
                currentType === 'agent'
                    ? '/BatEstateExplorer/public/api/admin_block_agent.php'
                    : '/BatEstateExplorer/public/api/admin_block_user.php';

            // --- Send correct ID key based on type ---
            const payload = {
                action,
                category: currentCategory,
                duration: action === 'block' ? duration : null
            };
            if (currentType === 'agent') payload.agent_id = currentReportedId;
            else payload.user_id = currentReportedId;

            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        const row = reportsBody.querySelector(`tr[data-report-id="${currentReportId}"]`);
                        if (row) {
                            row.dataset.status = data.status; // use backend response
                            updateStatusCell(row, row.dataset.status);
                        }
                        reportModal.style.display = 'none';
                    } else {
                        alert(data.error || `Failed to ${action} ${currentType}.`);
                    }
                })
                .catch(() => alert(`Error trying to ${action} ${currentType}.`));
        });

        /* =====================================================
        Delete report
        ===================================================== */
        deleteReportBtn.addEventListener('click', () => {
            if (!currentReportId) return;
            if (!confirm('Are you sure you want to delete this report?')) return;

            fetch('/BatEstateExplorer/public/api/admin_delete_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ report_id: currentReportId, type: currentType }),
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        const row = reportsBody.querySelector(`tr[data-report-id="${currentReportId}"]`);
                        if (row) row.remove();
                        reportModal.style.display = 'none';
                    } else {
                        alert(data.error || 'Failed to delete report.');
                    }
                })
                .catch(() => alert('Error deleting report.'));
        });

        /* =====================================================
        Close modal
        ===================================================== */
        reportModal.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.addEventListener('click', () => (reportModal.style.display = 'none'));
        });
        window.addEventListener('click', e => {
            if (e.target === reportModal) reportModal.style.display = 'none';
        });
    });
</script>
