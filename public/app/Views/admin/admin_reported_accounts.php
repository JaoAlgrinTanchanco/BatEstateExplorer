<?php
// --- Correct path to bootstrap ---
require_once __DIR__ . '/../../bootstrap.php';

// --- Ensure database connection exists ---
$conn = $GLOBALS['conn'] ?? null;
if (!$conn) die("Database connection not found.");

// --- Fetch reported accounts ---
$sql = "SELECT r.*, 
               reporter.first_name AS reporter_fname, reporter.last_name AS reporter_lname, reporter.email AS reporter_email,
               agent.first_name AS agent_fname, agent.last_name AS agent_lname, agent.email AS agent_email
        FROM agent_reports r
        LEFT JOIN users reporter ON r.reporter_id = reporter.id
        LEFT JOIN users agent ON r.agent_id = agent.id
        ORDER BY r.created_at DESC";

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
                <option value="reviewed">Reviewed</option>
                <option value="resolved">Resolved</option>
            </select>
        </div>
    </div>

    <div class="reported-accounts-table">
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>Reporter</th>
                    <th>Reported Agent</th>
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
                        <tr class="clickable-row" data-status="<?= htmlspecialchars($r['status']); ?>"
                            data-reporter="<?= htmlspecialchars($r['reporter_fname'] . ' ' . $r['reporter_lname'] . ' (' . $r['reporter_email'] . ')'); ?>"
                            data-agent="<?= htmlspecialchars($r['agent_fname'] . ' ' . $r['agent_lname'] . ' (' . $r['agent_email'] . ')'); ?>"
                            data-reason="<?= htmlspecialchars($r['reason']); ?>"
                            data-other-reason="<?= htmlspecialchars($r['other_reason']); ?>"
                            data-details="<?= htmlspecialchars($r['details']); ?>"
                            data-category="<?= htmlspecialchars($r['reason']); ?>"
                            data-agent-id="<?= htmlspecialchars($r['agent_id']); ?>"
                            data-created-at="<?= htmlspecialchars($r['created_at']); ?>">
                            <td><?= htmlspecialchars($r['reporter_fname'] . ' ' . $r['reporter_lname'] . ' (' . $r['reporter_email'] . ')'); ?></td>
                            <td><?= htmlspecialchars($r['agent_fname'] . ' ' . $r['agent_lname'] . ' (' . $r['agent_email'] . ')'); ?></td>
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
<div id="reportModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Report Details</h2>
      <button class="cancel-btn">&times;</button>
    </div>
    <div class="modal-body" id="modalBody">
      <!-- Dynamic content -->
    </div>
    <div class="modal-footer">
      <button id="blockUnblockBtn" class="btn btn-danger">Block Agent</button>
      <button id="deleteReportBtn" class="btn btn-warning">Delete Report</button>
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

        const penaltyNotes = {
            'fraudulent_listing': 'This account will be banned for life due to fraudulent/fake listings.',
            'harassment': 'This account may be banned for life for harassment or inappropriate behavior.',
            'misinformation': 'This account will be suspended for 7 days due to false/misleading information.',
            'spam': 'This account will be suspended for 48 hours due to spam or irrelevant contact.',
            'other': 'The account may face temporary suspension depending on severity.'
        };

        const categoryFullNames = {
            'fraudulent_listing': 'Fraudulent or fake listing',
            'harassment': 'Harassment or inappropriate behavior',
            'misinformation': 'False or misleading information',
            'spam': 'Spam or irrelevant contact',
            'other': 'Other'
        };

        let currentAgentId = null;
        let currentCategory = null;
        let currentStatus = null;

        // Filter by status
        if (statusFilter && reportsBody) {
            statusFilter.addEventListener('change', () => {
                const selected = statusFilter.value;
                reportsBody.querySelectorAll('tr.clickable-row').forEach(row => {
                    row.style.display = (selected === 'all' || row.dataset.status === selected) ? '' : 'none';
                });
            });
        }

        // Click row to open modal
        if (reportsBody) {
            reportsBody.addEventListener('click', (e) => {
                const row = e.target.closest('tr.clickable-row');
                if (!row) return;

                currentAgentId = row.dataset.agentId;
                currentCategory = row.dataset.category || 'other';
                currentStatus = row.dataset.status;

                let penaltyHtml = `<p style="color:red;"><strong>Penalty Note:</strong> ${penaltyNotes[currentCategory]}</p>`;

                // Duration selection if 'other'
                if (currentCategory === 'other') {
                    penaltyHtml += `
                        <p><strong>Set Ban Duration:</strong>
                            <select id="banDurationSelect">
                                <option value="48hrs">48 hours</option>
                                <option value="7days">7 days</option>
                                <option value="30days">30 days</option>
                                <option value="lifetime">Banned for life</option>
                            </select>
                        </p>
                    `;
                }

                modalBody.innerHTML = `
                    <p><strong>Reporter:</strong> ${row.dataset.reporter}</p>
                    <p><strong>Reported Agent:</strong> ${row.dataset.agent}</p>
                    <p><strong>Reason:</strong> ${categoryFullNames[currentCategory]}</p>
                    <p><strong>Other Reason:</strong> ${row.dataset.otherReason || 'N/A'}</p>
                    <p><strong>Details:</strong> ${row.dataset.details}</p>
                    <p><strong>Reported On:</strong> ${new Date(row.dataset.createdAt).toLocaleString()}</p>
                    ${penaltyHtml}
                `;

                // Update Block/Unblock button text based on agent's current status
                blockUnblockBtn.textContent = currentStatus === 'resolved' ? 'Unblock Agent' : 'Block Agent';

                reportModal.style.display = 'block';
            });
        }

        // Block / Unblock agent
        blockUnblockBtn.addEventListener('click', () => {
            if (!currentAgentId) return;

            const duration = document.getElementById('banDurationSelect')?.value || null;
            const action = blockUnblockBtn.textContent.includes('Unblock') ? 'unblock' : 'block';

            if (confirm(`Are you sure you want to ${action} this agent?`)) {
                fetch('/BatEstateExplorer/public/api/admin_block_agent.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ agent_id: currentAgentId, duration: duration, action: action })
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message || `Agent ${action}ed successfully.`);
                    reportModal.style.display = 'none';
                    location.reload();
                })
                .catch(err => alert(`Error trying to ${action} agent.`));
            }
        });

        // Delete report
        deleteReportBtn.addEventListener('click', () => {
            if (!currentAgentId) return;
            if (confirm("Are you sure you want to delete this report?")) {
                fetch('/BatEstateExplorer/public/admin_delete_report.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ agent_id: currentAgentId })
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message || 'Report deleted successfully.');
                    reportModal.style.display = 'none';
                    location.reload();
                })
                .catch(err => alert('Error deleting report.'));
            }
        });

        // Close modal
        reportModal.querySelectorAll('.cancel-btn').forEach(btn => btn.addEventListener('click', () => reportModal.style.display = 'none'));
        window.addEventListener('click', e => { if(e.target === reportModal) reportModal.style.display = 'none'; });
    });
</script>