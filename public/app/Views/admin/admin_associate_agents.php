<?php
// Fetch associate agents from 'users' table
$sql = "SELECT * FROM users WHERE user_type = 'associate_agent'";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();

$associate_agents = [];
while ($row = $result->fetch_assoc()) {
    $associate_agents[] = $row;
}

$stmt->close();
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_agents.css" />

<header class="content-header">
  <h1>Associate Agents</h1>
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

  <!-- 👇 Still using "direct-agent-list" so JS works -->
  <div class="direct-agent-list" id="agentList">
    <?php if (empty($associate_agents)): ?>
      <div class="no-agents">No approved associate agents found.</div>
    <?php else: ?>
      <?php foreach ($associate_agents as $agent): ?>
        <div class="direct-agent-card"
             data-name="<?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?>"
             data-date="<?php echo $agent['created_at']; ?>"
             data-experience="<?php echo $agent['experience_years'] ?? 0; ?>">
          <div class="direct-agent-avatar">
            <i class="fa-solid fa-user"></i>
          </div>
          <div class="direct-agent-info">
            <div class="direct-agent-name">
              <?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?>
            </div>
            <div class="direct-agent-details">
              <?php echo htmlspecialchars($agent['address'] ?: 'Unknown'); ?>
            </div>
          </div>
          <div class="direct-agent-actions">
            <button class="btn btn-view"
              data-agent-id="<?= $agent['id']; ?>"
              data-first-name="<?= htmlspecialchars($agent['first_name']); ?>"
              data-last-name="<?= htmlspecialchars($agent['last_name']); ?>"
              data-email="<?= htmlspecialchars($agent['email']); ?>"
              data-phone="<?= htmlspecialchars($agent['phone'] ?? ''); ?>"
              data-address="<?= htmlspecialchars($agent['address'] ?? ''); ?>"
              data-account-created="<?= $agent['created_at']; ?>"
              data-status="<?= htmlspecialchars($agent['status'] ?? 'active'); ?>"
              data-user-type="Associate"
              data-company="<?= htmlspecialchars($agent['company_id'] ?? 'N/A'); ?>"
              data-broker-id="<?= htmlspecialchars($agent['broker_id'] ?? 'N/A'); ?>"
              data-license-number="<?= htmlspecialchars($agent['license_number'] ?? 'N/A'); ?>"
              data-experience-years="<?= htmlspecialchars($agent['experience_years'] ?? 0); ?>"
              data-specialization="<?= htmlspecialchars($agent['specialization'] ?? 'N/A'); ?>"
              data-education="<?= htmlspecialchars($agent['education'] ?? 'N/A'); ?>"
              data-school="<?= htmlspecialchars($agent['school'] ?? 'N/A'); ?>"
              data-course="<?= htmlspecialchars($agent['course'] ?? 'N/A'); ?>"
              data-graduation-year="<?= htmlspecialchars($agent['graduation_year'] ?? 'N/A'); ?>"
            >
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

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const agentList = document.getElementById('agentList');
    const agentModal = document.getElementById('agentModal');
    const modalBody = document.getElementById('modalBody');
    const modalCloseBtns = agentModal.querySelectorAll('.cancel-btn');

    if (agentList) {
      agentList.addEventListener('click', async (e) => {
        // View Button Logic
        if (e.target.matches('.btn-view')) {
          const btn = e.target;

          // Parse specialization (convert JSON array to string)
          let specialization = 'N/A';

          if (btn.dataset.specialization && btn.dataset.specialization.trim() !== '') {
            try {
              const specData = JSON.parse(btn.dataset.specialization);
              if (Array.isArray(specData) && specData.length > 0) {
                specialization = specData.join(', ');
              } else if (typeof specData === 'string' && specData.trim() !== '') {
                specialization = specData;
              }
            } catch (err) {
              // Not valid JSON, fallback to raw string
              specialization = btn.dataset.specialization;
            }
          }

          modalBody.innerHTML = `
            <div class="col-left">
              <section class="personal-info">
                <h3>Personal Information</h3>
                <div class="detail-row">
                  <div class="detail-label">Full Name:</div>
                  <div class="detail-value">${btn.dataset.firstName || ''} ${btn.dataset.lastName || ''}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Email:</div>
                  <div class="detail-value">${btn.dataset.email || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Phone:</div>
                  <div class="detail-value">${btn.dataset.phone || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Address:</div>
                  <div class="detail-value">${btn.dataset.address || 'N/A'}</div>
                </div>
              </section>

              <section class="agent-info">
                <h3>Agent Information</h3>
                <div class="detail-row">
                  <div class="detail-label">Agent Type:</div>
                  <div class="detail-value">${btn.dataset.userType || 'Associate'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Company:</div>
                  <div class="detail-value">${btn.dataset.company || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Broker ID:</div>
                  <div class="detail-value">${btn.dataset.brokerId || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">License Number:</div>
                  <div class="detail-value">${btn.dataset.licenseNumber || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Experience:</div>
                  <div class="detail-value">${btn.dataset.experienceYears || '0'} years</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Specialization:</div>
                  <div class="detail-value">${specialization}</div>
                </div>
              </section>
            </div>

            <div class="col-right">
              <section class="education">
                <h3>Education & Qualifications</h3>
                <div class="detail-row">
                  <div class="detail-label">Education:</div>
                  <div class="detail-value">${btn.dataset.education || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">School:</div>
                  <div class="detail-value">${btn.dataset.school || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Course:</div>
                  <div class="detail-value">${btn.dataset.course || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Graduation Year:</div>
                  <div class="detail-value">${btn.dataset.graduationYear || 'N/A'}</div>
                </div>
              </section>

              <section class="account">
                <h3>Account Information</h3>
                <div class="detail-row">
                  <div class="detail-label">Account Created:</div>
                  <div class="detail-value">
                    ${btn.dataset.accountCreated 
                      ? new Date(btn.dataset.accountCreated).toLocaleDateString() 
                      : 'N/A'}
                  </div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Status:</div>
                  <div class="detail-value">${btn.dataset.status || 'N/A'}</div>
                </div>
              </section>
            </div>
          `;

          agentModal.style.display = 'block';
        }
      });
    }

    // Close modal
    modalCloseBtns.forEach(btn => btn.addEventListener('click', () => agentModal.style.display = 'none'));
    window.addEventListener('click', e => { if (e.target === agentModal) agentModal.style.display = 'none'; });
  });
</script>
