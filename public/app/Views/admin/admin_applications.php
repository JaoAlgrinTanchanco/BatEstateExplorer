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

