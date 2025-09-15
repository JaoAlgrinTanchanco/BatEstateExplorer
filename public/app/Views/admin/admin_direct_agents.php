<?php
// Helper function to build full URL path based on the type of file
function buildUploadUrl($filePath) {
    if (empty($filePath)) return '';

    // If already a URL or starts with /, return as is
    if (str_starts_with($filePath, 'http') || str_starts_with($filePath, '/')) {
        return $filePath;
    }

    $filename = basename($filePath);

    // Decide folder based on filename keywords
    if (strpos($filePath, 'broker_license') !== false || strpos($filePath, 'prc_license') !== false) {
        // Image files folder
        $baseURL = '/storage/uploads/images/';
    } elseif (
        strpos($filePath, 'resume') !== false ||
        strpos($filePath, 'valid_id') !== false
    ) {
        // Documents folder
        $baseURL = '/storage/uploads/images/';
    } else {
        // fallback folder - adjust if you want
        $baseURL = '/storage/uploads/images/';
    }

    return $baseURL . $filename;
}

// Fetch direct agents with application & company info using JOIN
$sql = "SELECT
  u.id, u.email, u.first_name, u.last_name, u.phone, u.address, u.user_type, u.status AS user_status, u.created_at AS user_created_at,
  a.education, a.school, a.course, a.graduation_year, a.certifications, a.training,
  a.broker_license_path, a.prc_license_path, a.resume_path, a.valid_id_path, a.additional_docs_path,
  a.broker_id, a.license_number, a.experience_years, a.specialization, a.status AS app_status,
  c.name AS company_name
FROM users u
LEFT JOIN applications a ON u.id = a.user_id
LEFT JOIN companies c ON a.company_id = c.id
WHERE u.user_type = 'direct_agent'";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();

$direct_agents = [];
while ($row = $result->fetch_assoc()) {
    // Adjust paths for each document/image field
    $row['broker_license_path'] = buildUploadUrl($row['broker_license_path']);
    $row['prc_license_path'] = buildUploadUrl($row['prc_license_path']);
    $row['resume_path'] = buildUploadUrl($row['resume_path']);
    $row['valid_id_path'] = buildUploadUrl($row['valid_id_path']);

    // Additional docs might be multiple paths separated by commas
    if (!empty($row['additional_docs_path'])) {
        $docs = array_filter(array_map('trim', explode(',', $row['additional_docs_path'])));
        $docs = array_map('buildUploadUrl', $docs);
        $row['additional_docs_path'] = implode(',', $docs);
    } else {
        $row['additional_docs_path'] = '';
    }

    $direct_agents[] = $row;
}

$stmt->close();
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_agents.css" />

<header class="content-header">
  <h1>Direct Agents</h1>
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
             data-date="<?php echo $agent['user_created_at']; ?>"
             data-experience="<?php echo intval($agent['experience_years'] ?? 0); ?>">
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
            <button
              class="btn btn-view"
              data-agent-id="<?php echo $agent['id']; ?>"
              data-first-name="<?php echo htmlspecialchars($agent['first_name'] ?? ''); ?>"
              data-last-name="<?php echo htmlspecialchars($agent['last_name'] ?? ''); ?>"
              data-email="<?php echo htmlspecialchars($agent['email'] ?? ''); ?>"
              data-phone="<?php echo htmlspecialchars($agent['phone'] ?? ''); ?>"
              data-address="<?php echo htmlspecialchars($agent['address'] ?? ''); ?>"
              data-user-type="<?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $agent['user_type'] ?? 'direct_agent'))); ?>"
              data-broker-id="<?php echo htmlspecialchars($agent['broker_id'] ?? ''); ?>"
              data-license-number="<?php echo htmlspecialchars($agent['license_number'] ?? ''); ?>"
              data-experience-years="<?php echo intval($agent['experience_years'] ?? 0); ?>"
              data-specialization="<?php echo htmlspecialchars($agent['specialization'] ?? ''); ?>"
              data-company-name="<?php echo htmlspecialchars($agent['company_name'] ?? 'N/A'); ?>"
              data-education="<?php echo htmlspecialchars($agent['education'] ?? ''); ?>"
              data-school="<?php echo htmlspecialchars($agent['school'] ?? ''); ?>"
              data-course="<?php echo htmlspecialchars($agent['course'] ?? ''); ?>"
              data-graduation-year="<?php echo htmlspecialchars($agent['graduation_year'] ?? ''); ?>"
              data-certifications="<?php echo htmlspecialchars($agent['certifications'] ?? ''); ?>"
              data-training="<?php echo htmlspecialchars($agent['training'] ?? ''); ?>"
              data-broker-license-path="<?php echo htmlspecialchars($agent['broker_license_path'] ?? ''); ?>"
              data-prc-license-path="<?php echo htmlspecialchars($agent['prc_license_path'] ?? ''); ?>"
              data-resume-path="<?php echo htmlspecialchars($agent['resume_path'] ?? ''); ?>"
              data-valid-id-path="<?php echo htmlspecialchars($agent['valid_id_path'] ?? ''); ?>"
              data-additional-docs-path="<?php echo htmlspecialchars($agent['additional_docs_path'] ?? ''); ?>"
              data-account-created="<?php echo htmlspecialchars($agent['user_created_at'] ?? ''); ?>"
              data-status="<?php echo htmlspecialchars(strtoupper($agent['user_status'] ?? '')); ?>">
              Details
            </button>

            <button class="btn btn-remove" data-agent-id="<?php echo $agent['id']; ?>">Remove</button>
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
    </div>
    <div class="modal-body" id="modalBody">
      <!-- Content loaded via JS -->
    </div>
    <div class="modal-footer">
      <button class="cancel-btn">Close</button>
    </div>
  </div>
</div>
