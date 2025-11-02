<?php
    // --- Correct path to bootstrap ---
    require_once __DIR__ . '/../../bootstrap.php';

    // --- Ensure database connection exists ---
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) die("Database connection not found.");

    // ===============================
    // Fetch reported accounts
    // ===============================
    $sqlAccounts = "
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

    $resultAccounts = $conn->query($sqlAccounts);
    $reportsAccounts = [];
    while ($row = $resultAccounts->fetch_assoc()) {
        $reportsAccounts[] = $row;
    }

    // ===============================
    // Fetch reported properties
    // ===============================
    $sqlProperties = "
        SELECT 
            pr.id,
            pr.property_id,
            pr.reporter_id,
            u.first_name AS reporter_fname, u.last_name AS reporter_lname,
            CONCAT(u.first_name, ' ', u.last_name, ' (', u.email, ')') AS reporter_name,
            p.title AS property_title,
            pr.reason,
            pr.other_reason,
            pr.details,
            pr.status,
            pr.created_at
        FROM property_reports pr
        LEFT JOIN users u ON pr.reporter_id = u.id
        LEFT JOIN properties p ON pr.property_id = p.id
        ORDER BY pr.created_at DESC
    ";

    $resultProperties = $conn->query($sqlProperties);
    $reportsProperties = [];
    while ($row = $resultProperties->fetch_assoc()) {
        $reportsProperties[] = $row;
    }
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_reported_accounts.css" />

<header class="content-header">
    <h1>Reports Management</h1>
</header>

<div class="content-body">

  <!-- Tab Bar -->
  <div class="tab-bar">
    <button class="tab-btn active" data-tab="accountsTab">Accounts</button>
    <button class="tab-btn" data-tab="propertiesTab">Properties</button>
  </div>

  <!-- Filter -->
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

  <!-- Accounts Reports Table -->
  <div class="tab-content active" id="accountsTab">
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
        <tbody id="accountsBody">
          <?php if (empty($reportsAccounts)): ?>
            <tr>
              <td colspan="8" style="text-align:center;">No reported accounts found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($reportsAccounts as $r): ?>
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
                  data-type="account"
                  data-created-at="<?= htmlspecialchars($r['created_at']); ?>"
              >
                <td><?= htmlspecialchars($r['reporter_fname'] . ' ' . $r['reporter_lname'] . ' (' . $r['reporter_email'] . ')'); ?></td>
                <td><?= htmlspecialchars($r['reported_fname'] . ' ' . $r['reported_lname'] . ' (' . $r['reported_email'] . ')'); ?></td>
                <td>Account</td>
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

  <!-- Properties Reports Table -->
  <div class="tab-content" id="propertiesTab">
    <div class="reported-properties-table">
      <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
          <tr>
            <th>Reporter</th>
            <th>Reported Property</th>
            <th>Reason</th>
            <th>Other Reason</th>
            <th>Details</th>
            <th>Status</th>
            <th>Date Reported</th>
          </tr>
        </thead>
        <tbody id="propertiesBody">
          <?php if (empty($reportsProperties)): ?>
            <tr>
              <td colspan="7" style="text-align:center;">No reported properties found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($reportsProperties as $r): ?>
              <tr class="clickable-row"
                  data-status="<?= htmlspecialchars($r['status']); ?>"
                  data-reporter="<?= htmlspecialchars($r['reporter_name']); ?>"
                  data-reported="<?= htmlspecialchars($r['property_title']); ?>"
                  data-reason="<?= htmlspecialchars($r['reason']); ?>"
                  data-other-reason="<?= htmlspecialchars($r['other_reason']); ?>"
                  data-details="<?= htmlspecialchars($r['details']); ?>"
                  data-category="<?= htmlspecialchars($r['reason']); ?>"
                  data-reported-id="<?= htmlspecialchars($r['property_id']); ?>"
                  data-report-id="<?= htmlspecialchars($r['id']); ?>"
                  data-type="property"
                  data-created-at="<?= htmlspecialchars($r['created_at']); ?>"
              >
                <td><?= htmlspecialchars($r['reporter_name']); ?></td>
                <td><?= htmlspecialchars($r['property_title']); ?></td>
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
        const tabs = document.querySelectorAll('.tab-btn');
        const contents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).classList.add('active');
            });
        });

        statusFilter.addEventListener('change', () => {
            const value = statusFilter.value;
            document.querySelectorAll('.tab-content.active tbody tr').forEach(row => {
            row.style.display = (value === 'all' || row.dataset.status === value) ? '' : 'none';
            });
        });

        /* =====================================================
        PENALTIES & CATEGORY DEFINITIONS (Accounts, Agents, Properties)
        ===================================================== */
        const penalties = {
            user: {
                harassment: 'User temporarily suspended for 7 days due to harassment or inappropriate behavior.',
                spam: 'User messaging privileges restricted for 48 hours due to spam or irrelevant contact.',
                fake_review: 'User banned from posting reviews for 30 days due to fake or manipulated feedback.',
                misinformation: 'User restricted for 7 days for spreading false or misleading information.',
                false_report: 'User temporarily restricted from reporting for 7 days due to false or malicious reports.',
                fraudulent_activity: 'User permanently banned for fraudulent or deceptive activity.',
                impersonation: 'User permanently banned for impersonating another person.',
                other: 'Admin may assign a custom temporary penalty depending on severity.',
            },
            agent: {
                fraudulent_listing: 'Agent permanently banned due to fraudulent or fake listings.',
                harassment: 'Agent permanently banned for harassment or unprofessional conduct.',
                misinformation: 'Agent suspended for 7 days due to false or misleading information.',
                spam: 'Agent suspended for 48 hours for excessive or irrelevant contact.',
                other: 'Admin may assign a custom temporary penalty depending on severity.',
            },
            property: {
                fraudulent_property: 'Property permanently removed due to fraudulent or deceptive listing.',
                illegal_listing: 'Property permanently taken down due to illegal or prohibited content.',
                misleading_info: 'Property permanently removed due to false or misleading information.',
                already_sold: 'Property taken down due to being already sold. Associated agent will also be blocked.',
                other: 'Admin may assign a custom penalty depending on severity.',
            }
        };

        const categoryNames = {
            user: {
                harassment: 'Harassment or inappropriate behavior',
                spam: 'Spam or irrelevant contact',
                fake_review: 'Fake or manipulated review',
                misinformation: 'False or misleading information',
                false_report: 'False or malicious report',
                fraudulent_activity: 'Fraudulent or deceptive activity',
                impersonation: 'Impersonation or identity misuse',
                other: 'Other (Custom penalty)',
            },
            agent: {
                fraudulent_listing: 'Fraudulent or fake listing (Agent)',
                harassment: 'Harassment or inappropriate behavior (Agent)',
                misinformation: 'False or misleading information (Agent)',
                spam: 'Spam or irrelevant contact (Agent)',
                other: 'Other (Custom penalty)',
            },
            property: {
                fraudulent_property: 'Fraudulent property listing (Permanent ban)',
                illegal_listing: 'Illegal or prohibited listing (Permanent ban)',
                misleading_info: 'Misleading or false property information (Permanent ban)',
                already_sold: 'Property already sold (Taken down and linked agent blocked)',
                other: 'Other (Custom penalty)',
            }
        };

        // Current state variables
        let currentReportedId = null;
        let currentReportId = null;
        let currentCategory = null;
        let currentStatus = null;
        let currentType = null; // 'user', 'account', 'agent', or 'property'

        /* Helper function to get penalty note based on type & category */
        function getPenaltyNote(type, category) {
            const key = type === 'account' ? 'user' : type; // treat account same as user
            return penalties[key]?.[category] || penalties[key]?.other || 'Admin may assign a custom penalty.';
        }

        /* Helper function to get category display name */
        function getCategoryName(type, category) {
            const key = type === 'account' ? 'user' : type;
            return categoryNames[key]?.[category] || categoryNames[key]?.other || 'Other';
        }

        // =============================
        // Helper: Update row color based on status
        // =============================
        function updateStatusCell(row) {
            // Detect whether this is in the accounts table or properties table
            const isAccountsTable = row.closest('.reported-accounts-table') !== null;

            // Accounts table: Status is 7th column
            // Properties table: Status is 6th column
            const statusCellIndex = isAccountsTable ? 7 : 6;
            const statusCell = row.querySelector(`td:nth-child(${statusCellIndex})`);
            if (!statusCell) return;

            const status = (row.dataset.status || statusCell.textContent.trim()).toLowerCase();
            statusCell.textContent = status.charAt(0).toUpperCase() + status.slice(1);

            switch (status) {
                case 'blocked':
                    statusCell.style.color = '#ff0000'; // red
                    break;
                case 'unblocked':
                    statusCell.style.color = '#008dff'; // blue
                    break;
                default:
                    statusCell.style.color = '#12d800'; // green
                    break;
            }
        }

        // =============================
        // Apply to all rows in both tables
        // =============================
        const allRows = document.querySelectorAll(
            '.reported-accounts-table tr.clickable-row, .reported-properties-table tr.clickable-row'
        );

        allRows.forEach(row => updateStatusCell(row));

        // =============================
        // Optional: Reapply when status changes dynamically
        // =============================
        function updateRowStatus(reportId, newStatus) {
            const row = document.querySelector(
                `.reported-accounts-table tr[data-report-id="${reportId}"], .reported-properties-table tr[data-report-id="${reportId}"]`
            );
            if (row) {
                row.dataset.status = newStatus;
                updateStatusCell(row);
            }
        }

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
        Row Click Handler (Modal Open) - Accounts, Agents & Properties
        ===================================================== */
        document.querySelectorAll('.tab-content tbody tr.clickable-row').forEach(row => {
            updateStatusCell(row); // Initialize status display
        });

        document.querySelectorAll('.tab-content tbody').forEach(tbody => {
            tbody.addEventListener('click', e => {
                const row = e.target.closest('tr.clickable-row');
                if (!row) return; // Ignore clicks outside clickable rows

                // --- Capture row data ---
                currentReportedId = row.dataset.reportedId;
                currentReportId = row.dataset.reportId;
                currentCategory = row.dataset.category || 'other';
                currentStatus = row.dataset.status;
                currentType = row.dataset.type;

                // --- Determine type label ---
                let typeLabel = {
                    user: 'User',
                    account: 'User',
                    agent: 'Agent',
                    property: 'Property'
                }[currentType] || 'Reported';

                // --- Safe access to category full name & penalty note ---
                const fullReason = getCategoryName(currentType, currentCategory);
                const defaultPenalty = getPenaltyNote(currentType, currentCategory);

                // --- Build penalty HTML ---
                let penaltyHtml = `<p style="color:red;"><strong>Penalty Note:</strong> ${defaultPenalty}</p>`;

                // --- Custom penalty duration select for 'other' ---
                if (currentCategory === 'other') {
                    penaltyHtml += `
                        <p><strong>Set Penalty Duration:</strong>
                            <select id="banDurationSelect">
                                <option value="48hrs">48 hours</option>
                                <option value="7days">7 days</option>
                                <option value="30days">30 days</option>
                                <option value="lifetime">Permanent</option>
                            </select>
                        </p>`;
                }

                // --- Populate modal body ---
                modalBody.innerHTML = `
                    <p><strong>Reporter:</strong> ${row.dataset.reporter}</p>
                    <p><strong>Reported ${typeLabel}:</strong> ${row.dataset.reported}</p>
                    <p><strong>Reason:</strong> ${fullReason}</p>
                    <p><strong>Other Reason:</strong> ${row.dataset.otherReason || 'N/A'}</p>
                    <p><strong>Details:</strong> ${row.dataset.details}</p>
                    <p><strong>Reported On:</strong> ${new Date(row.dataset.createdAt).toLocaleString()}</p>
                    ${penaltyHtml}
                `;

                // --- Update Block/Take Down button ---
                if (['user', 'account', 'agent'].includes(currentType)) {
                    blockUnblockBtn.textContent = currentStatus === 'blocked'
                        ? `Unblock ${typeLabel}`
                        : `Block ${typeLabel}`;
                    blockUnblockBtn.classList.remove('btn-warning', 'btn-success', 'btn-secondary');
                    blockUnblockBtn.style.display = 'inline-block';
                    blockUnblockBtn.disabled = false;
                    blockUnblockBtn.title = '';
                }
                else if (currentType === 'property') {
                    blockUnblockBtn.textContent = 'Take Down Listing';
                    blockUnblockBtn.classList.remove('btn-success', 'btn-secondary');
                    blockUnblockBtn.classList.add('btn-warning');

                    if (currentStatus === 'blocked') {
                        blockUnblockBtn.disabled = true;
                        blockUnblockBtn.classList.add('btn-secondary'); // Gray out
                        blockUnblockBtn.title = 'This property has already been taken down.';
                    } else {
                        blockUnblockBtn.disabled = false;
                        blockUnblockBtn.title = '';
                    }

                    blockUnblockBtn.style.display = 'inline-block';
                } else {
                    blockUnblockBtn.style.display = 'none';
                }

                // --- Show modal ---
                reportModal.style.display = 'block';
            });
        });

        /* =====================================================
        Block / Take Down logic (Accounts, Agents, Users, Properties)
        ===================================================== */
        blockUnblockBtn.addEventListener('click', async (event) => {
            event.preventDefault();
            if (!currentReportedId || !currentType) return;

            const isProperty = currentType === 'property';
            const displayType = currentType.charAt(0).toUpperCase() + currentType.slice(1);
            const action = isProperty
                ? 'block'
                : blockUnblockBtn.textContent.toLowerCase().includes('unblock')
                    ? 'unblock'
                    : 'block';
            const duration = 'lifetime';

            if (!confirm(isProperty
                ? `Are you sure you want to take down this property? This action is permanent and cannot be undone.`
                : `Are you sure you want to ${action} this ${displayType}?`)) return;

            // Prepare payload
            const payload = { action, category: currentCategory, duration: isProperty ? duration : null };
            let endpoint = '';
            switch (currentType) {
                case 'agent':
                    endpoint = '/BatEstateExplorer/public/api/admin_block_agent.php';
                    payload.agent_id = currentReportedId;
                    break;
                case 'user':
                case 'account':
                    endpoint = '/BatEstateExplorer/public/api/admin_block_user.php';
                    payload.user_id = currentReportedId;
                    break;
                case 'property':
                    endpoint = '/BatEstateExplorer/public/api/admin_block_property.php';
                    payload.property_id = currentReportedId;
                    break;
                default:
                    alert('Unknown type for blocking/unblocking.');
                    return;
            }

            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (!data.success) {
                    alert(`Failed to ${action} ${displayType}: ${data.error || 'Unknown error'}`);
                    return;
                }

                alert(data.message);

                // Update clicked row only
                const activeRow = document.querySelector(`.tab-content.active tr.clickable-row[data-report-id="${currentReportId}"]`);
                if (activeRow) {
                    activeRow.dataset.status = data.status;
                    updateStatusCell(activeRow);
                }

                // ===== Only auto-block agent for already_sold cases =====
                if (isProperty && data.agent_id && currentCategory === 'already_sold') {
                    await blockAgent(
                        data.agent_id,
                        currentCategory,
                        duration,
                        'Associated agent has been blocked because the property was already sold.'
                    );
                }

                reportModal.style.display = 'none';
                // setTimeout(() => location.reload(), 1000);

            } catch (err) {
                console.error('Fetch error:', err);
                alert(`Error trying to ${action} ${displayType}.`);
                reportModal.style.display = 'none';
            }
        });

        /* Helper function to block an agent */
        async function blockAgent(agentId, category, duration, successMessage) {
            const agentPayload = { action: 'block', agent_id: agentId, category, duration };
            try {
                const agentRes = await fetch('/BatEstateExplorer/public/api/admin_block_agent.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(agentPayload)
                });
                const agentData = await agentRes.json();
                if (agentData.success) alert(successMessage);
                else alert('Property taken down, but failed to block associated agent.');
            } catch (err) {
                console.error('Error blocking agent:', err);
                alert('Property taken down, but failed to block associated agent.');
            }
        }

        /* =====================================================
        Delete report (Accounts, Agents, Users, Properties)
        ===================================================== */
        deleteReportBtn.addEventListener('click', () => {
            if (!currentReportId || !currentType) return;

            if (!confirm('Are you sure you want to delete this report?')) return;

            const endpoint = '/BatEstateExplorer/public/api/admin_delete_report.php';

            // 🟢 FIX: include report_type
            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    report_id: currentReportId,
                    report_type: currentType, // <--- added this line
                }),
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);

                        const activeContent = document.querySelector('.tab-content.active');
                        if (activeContent) {
                            const row = activeContent.querySelector(`tr[data-report-id="${currentReportId}"]`);
                            if (row) row.remove();
                        }

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
