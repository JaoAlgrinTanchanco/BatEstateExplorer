<?php
// MySQLi query to fetch direct agents from 'users' table
$sql = "SELECT * FROM users WHERE user_type = 'direct_agent'";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();

$direct_agents = [];
while ($row = $result->fetch_assoc()) {
    $direct_agents[] = $row;
}

$stmt->close();
?>

<header class="content-header">
  <h1>Direct Agents</h1>
  <div class="user-info">
    <span>Welcome, <?php echo htmlspecialchars($current_user['email']); ?></span>
  </div>
</header>

<div class="content-body">

  <div class="sort-row">
    <div class="sort-by">
      <label for="sort">Sort By:</label>
      <select id="sort">
        <option value="name">Name</option>
        <option value="date">Date Added</option>
        <option value="experience">Experience</option>
      </select>
    </div>
  </div>

  <div class="direct-agent-list" id="agentList">
    <?php if (empty($direct_agents)): ?>
      <div class="no-agents">No approved direct agents found.</div>
    <?php else: ?>
      <?php foreach ($direct_agents as $agent): ?>
        <div class="direct-agent-card"
             data-name="<?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?>"
             data-date="<?php echo $agent['created_at']; ?>"
             data-experience="<?php echo $agent['experience_years'] ?? 0; ?>">
          <div class="direct-agent-avatar">
            <i class="fa-solid fa-user"></i>
          </div>
          <div class="direct-agent-info">
            <div class="direct-agent-name"><?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></div>
            <div class="direct-agent-details">
              <?php echo htmlspecialchars($agent['address'] ?: 'Unknown'); ?>
            </div>
          </div>
          <div class="direct-agent-actions">
            <button class="btn btn-view"
            data-agent-id="<?php echo $agent['id']; ?>"
            data-first-name="<?php echo htmlspecialchars($agent['first_name']); ?>"
            data-last-name="<?php echo htmlspecialchars($agent['last_name']); ?>"
            data-email="<?php echo htmlspecialchars($agent['email']); ?>"
            data-phone="<?php echo htmlspecialchars($agent['phone']); ?>"
            data-address="<?php echo htmlspecialchars($agent['address']); ?>"
            data-status="<?php echo htmlspecialchars($agent['status']); ?>"
            data-created-at="<?php echo $agent['created_at']; ?>"
            >View Details
          </button>

            <button class="btn btn-remove" data-agent-id="<?php echo $agent['id']; ?>">Remove Account</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<!-- Agent Details Modal -->
<div id="agentModal" class="modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Agent Details</h2>
      <span class="close">&times;</span>
    </div>
    <div class="modal-body" id="modalBody">
      <!-- Content loaded via JS -->
    </div>
    <div class="modal-footer">
      <button class="cancel-btn">Close</button>
    </div>
  </div>
</div>

