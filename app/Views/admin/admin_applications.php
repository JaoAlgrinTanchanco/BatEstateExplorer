<?php
// Inside main-content only

// Use global mysqli connection if available
$conn = $GLOBALS['conn'] ?? null;

// Safe current user email (controller should set $current_user)
$current_user_email = isset($current_user['email']) ? $current_user['email'] : '';

// Fetch pending applications (if DB connection exists)
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
        <span>Welcome, <?php echo htmlspecialchars($current_user_email); ?></span>
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
                    </div>

                    <div class="application-actions">
                        <button class="view-btn" data-id="<?php echo (int)$app['id']; ?>">View Details</button>
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

<!-- Modal: keep this in the view so it appears once -->
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
