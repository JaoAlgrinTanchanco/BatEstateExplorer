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
<div id="reportModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Report Details</h2>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Dynamic content -->
        </div>
        <div class="modal-footer">
            <button id="blockAgentBtn" class="btn btn-danger">Block Agent</button>
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
    const blockBtn = document.getElementById('blockAgentBtn');

    const penaltyNotes = {
        'fraudulent_listing': '⚠️ This account will be banned for life due to fraudulent/fake listings.',
        'harassment': '⚠️ This account may be banned for life for harassment or inappropriate behavior.',
        'misinformation': '⚠️ This account will be suspended for 7 days due to false/misleading info.',
        'spam': '⚠️ This account will be suspended for 48 hours due to spam or irrelevant contact.',
        'other': '⚠️ The account may face temporary suspension depending on severity.'
    };

    // Filter by status
    if (statusFilter && reportsBody) {
        statusFilter.addEventListener('change', () => {
            const selected = statusFilter.value;
            const rows = reportsBody.querySelectorAll('tr.clickable-row');
            rows.forEach(row => {
                if (selected === 'all' || row.dataset.status === selected) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Click row to open modal
    if (reportsBody) {
        reportsBody.addEventListener('click', (e) => {
            let row = e.target.closest('tr.clickable-row');
            if (!row) return;

            const category = row.dataset.category || 'other';
            modalBody.innerHTML = `
                <p><strong>Reporter:</strong> ${row.dataset.reporter}</p>
                <p><strong>Reported Agent:</strong> ${row.dataset.agent}</p>
                <p><strong>Reason:</strong> ${row.dataset.reason}</p>
                <p><strong>Other Reason:</strong> ${row.dataset.otherReason || 'N/A'}</p>
                <p><strong>Details:</strong> ${row.dataset.details}</p>
                <p><strong>Reported On:</strong> ${new Date(row.dataset.createdAt).toLocaleString()}</p>
                <p style="color:red;"><strong>Penalty Note:</strong> ${penaltyNotes[category] || penaltyNotes['other']}</p>
            `;
            blockBtn.dataset.agentId = row.dataset.agentId;
            reportModal.style.display = 'block';
        });
    }

    // Block agent confirmation
    blockBtn.addEventListener('click', () => {
        const agentId = blockBtn.dataset.agentId;
        if (!agentId) return;
        if (confirm("Are you sure you want to block this agent?")) {
            // Make AJAX request to block agent
            fetch('/BatEstateExplorer/public/admin_block_agent.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({agent_id: agentId})
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message || 'Agent blocked successfully.');
                reportModal.style.display = 'none';
                location.reload();
            })
            .catch(err => alert('Error blocking agent.'));
        }
    });

    // Close modal
    reportModal.querySelectorAll('.cancel-btn').forEach(btn => btn.addEventListener('click', () => reportModal.style.display = 'none'));
    window.addEventListener('click', e => { if(e.target===reportModal) reportModal.style.display='none'; });
});
</script>
