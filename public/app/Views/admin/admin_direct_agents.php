<?php
  function buildUploadUrl($filePath) {
      if (empty($filePath)) return '';

      // ✅ Return full path if already absolute
      if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
          return $filePath;
      }

      // ✅ Remove any leading/trailing slashes
      $filePath = ltrim($filePath, '/');

      // ✅ Extract filename and lowercase
      $filename = basename($filePath);
      $lowerFile = strtolower($filePath);

      // ✅ Auto-detect folder
      if (str_contains($lowerFile, 'profile') || str_contains($lowerFile, 'pfp')) {
          $baseURL = '/BatEstateExplorer/storage/uploads/profile_images/';
      } elseif (preg_match('/\.(jpg|jpeg|png|gif|bmp|webp)$/i', $filename)) {
          $baseURL = '/BatEstateExplorer/storage/uploads/images/';
      } elseif (preg_match('/\.(pdf|doc|docx)$/i', $filename)) {
          $baseURL = '/BatEstateExplorer/storage/uploads/documents/';
      } else {
          $baseURL = '/BatEstateExplorer/storage/uploads/misc/';
      }

      // ✅ Ensure no double slashes
      return rtrim($baseURL, '/') . '/' . $filename;
  }

  // Fetch direct agents directly from users table
  $sql = "SELECT 
      u.id, u.email, u.first_name, u.last_name, u.phone, u.address,
      u.user_type, u.status AS user_status, u.created_at, u.updated_at,
      u.education, u.school, u.course, u.graduation_year, 
      u.certifications, u.training,
      u.profile_image_path,
      u.broker_license_path, u.prc_license_path, u.resume_path, u.valid_id_path, u.additional_docs_path,
      u.company_id, u.broker_id, u.license_number, u.experience_years, u.specialization, u.bio,
      u.wallet_balance, u.privileges,
      c.name AS company_name
  FROM users u
  LEFT JOIN companies c ON u.company_id = c.id
  WHERE u.user_type = 'direct_agent'";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
      die("Prepare failed: " . $conn->error);
  }

  $stmt->execute();
  $result = $stmt->get_result();

  $direct_agents = [];
  while ($row = $result->fetch_assoc()) {
      // Normalize file paths
      foreach (['broker_license_path','prc_license_path','resume_path','valid_id_path'] as $field) {
          $row[$field] = buildUploadUrl($row[$field]);
      }
      $row['profile_image_path'] = buildUploadUrl($row['profile_image_path']);
      // Handle additional docs
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
             data-date="<?php echo $agent['created_at']; ?>"
             data-experience="<?php echo intval($agent['experience_years'] ?? 0); ?>">
            <div class="direct-agent-avatar">
              <?php if (!empty($agent['profile_image_path'])): ?>
                <img src="<?= htmlspecialchars($agent['profile_image_path']); ?>" alt="Profile" class="agent-profile-img" />
              <?php else: ?>
                <i class="fa-solid fa-user"></i>
              <?php endif; ?>
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
              data-profile-image-path="<?php echo htmlspecialchars($agent['profile_image_path'] ?? ''); ?>"
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
              data-account-created="<?php echo htmlspecialchars($agent['created_at'] ?? ''); ?>"
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

<script>  
  document.addEventListener('DOMContentLoaded', () => {
    const agentList = document.getElementById('agentList');
    const agentModal = document.getElementById('agentModal');
    const modalBody = document.getElementById('modalBody');
    const modalCloseBtns = agentModal.querySelectorAll('.cancel-btn');

    // ✅ Simplified document link generator — trust PHP URLs
    function docLink(label, path) {
      if (!path || path.trim() === '' || path === 'null') {
        return `
          <div class="detail-row">
            <div class="detail-label">${label}:</div>
            <div class="detail-value">No file uploaded</div>
          </div>`;
      }

      const filename = decodeURIComponent(path.split('/').pop() || 'Document');
      return `
        <div class="detail-row">
          <div class="detail-label">${label}:</div>
          <div class="detail-value">
            <a href="${path}" target="_blank" class="document-link" title="${filename}">View</a>
          </div>
        </div>`;
    }

    // ✅ Event delegation for viewing agent details
    if (agentList) {
      agentList.addEventListener('click', (e) => {
        if (!e.target.matches('.btn-view')) return;
        const btn = e.target;

        // Profile image (already built by PHP)
        const profileImg = btn.dataset.profileImagePath?.trim() || null;

        // Specialization (array or string)
        let specialization = 'N/A';
        if (btn.dataset.specialization) {
          try {
            const specArray = JSON.parse(btn.dataset.specialization);
            if (Array.isArray(specArray) && specArray.length > 0) {
              specialization = specArray.join(', ');
            }
          } catch {
            specialization = btn.dataset.specialization;
          }
        }

        // Additional docs (comma-separated)
        let additionalDocsHtml = '';
        if (btn.dataset.additionalDocsPath) {
          const docs = btn.dataset.additionalDocsPath
            .split(',')
            .map(d => d.trim())
            .filter(Boolean);

          if (docs.length) {
            additionalDocsHtml = docs.map((doc, i) => `
              <div class="detail-row">
                <div class="detail-label">Additional Document ${i + 1}:</div>
                <div class="detail-value">
                  <a href="${doc}" target="_blank" class="document-link">View</a>
                </div>
              </div>
            `).join('');
          }
        }

        // ✅ Build modal content
        modalBody.innerHTML = `
          <section class="personal-info">
            <h3>Personal Information</h3>
            <div class="agent-modal-header">
              <div class="profile-col">
                ${
                  profileImg
                    ? `<img src="${profileImg}" alt="Profile Picture" class="modal-profile-img" />`
                    : `<i class="fa-solid fa-user modal-profile-icon"></i>`
                }
              </div>
              <div class="info-col">
                <h3 class="agent-fullname">${btn.dataset.firstName || ''} ${btn.dataset.lastName || ''}</h3>
                <p class="agent-email">${btn.dataset.email || 'N/A'}</p>
                <p class="agent-type">Type: ${btn.dataset.userType || 'Direct Agent'}</p>
              </div>
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
            <div class="detail-row"><div class="detail-label">Broker ID:</div><div class="detail-value">${btn.dataset.brokerId || 'N/A'}</div></div>
            <div class="detail-row"><div class="detail-label">License Number:</div><div class="detail-value">${btn.dataset.licenseNumber || 'N/A'}</div></div>
            <div class="detail-row"><div class="detail-label">Experience:</div><div class="detail-value">${btn.dataset.experienceYears || '0'} years</div></div>
            <div class="detail-row"><div class="detail-label">Specialization:</div><div class="detail-value">${specialization}</div></div>
          </section>

          <section class="education">
            <h3>Education & Qualifications</h3>
            <div class="detail-row"><div class="detail-label">Education:</div><div class="detail-value">${btn.dataset.education || 'N/A'}</div></div>
            <div class="detail-row"><div class="detail-label">School:</div><div class="detail-value">${btn.dataset.school || 'N/A'}</div></div>
            <div class="detail-row"><div class="detail-label">Course:</div><div class="detail-value">${btn.dataset.course || 'N/A'}</div></div>
            <div class="detail-row"><div class="detail-label">Graduation Year:</div><div class="detail-value">${btn.dataset.graduationYear || 'N/A'}</div></div>
          </section>

          <section class="documents">
            <h3>Uploaded Documents</h3>
            ${docLink('Broker License', btn.dataset.brokerLicensePath)}
            ${docLink('PRC License', btn.dataset.prcLicensePath)}
            ${docLink('Resume/CV', btn.dataset.resumePath)}
            ${docLink('Valid ID', btn.dataset.validIdPath)}
            ${additionalDocsHtml || '<div class="detail-row"><div class="detail-value">No additional documents uploaded</div></div>'}
          </section>

          <section class="account">
            <h3>Account Information</h3>
            <div class="detail-row">
              <div class="detail-label">Account Created:</div>
              <div class="detail-value">${btn.dataset.accountCreated ? new Date(btn.dataset.accountCreated).toLocaleDateString() : 'N/A'}</div>
            </div>
            <div class="detail-row">
              <div class="detail-label">Status:</div>
              <div class="detail-value">${btn.dataset.status || 'N/A'}</div>
            </div>
          </section>
        `;

        agentModal.style.display = 'block';
      });
    }

    // ✅ Close modal
    modalCloseBtns.forEach(btn => btn.addEventListener('click', () => agentModal.style.display = 'none'));
    window.addEventListener('click', e => {
      if (e.target === agentModal) agentModal.style.display = 'none';
    });

    // ✅ Sorting
    const sortSelect = document.getElementById('sort');
    if (sortSelect && agentList) {
      sortSelect.addEventListener('change', () => {
        const sortBy = sortSelect.value;
        const cards = Array.from(agentList.querySelectorAll('.direct-agent-card'));
        let sorted = [];

        switch (sortBy) {
          case 'date':
            sorted = cards.sort((a, b) => new Date(b.dataset.date) - new Date(a.dataset.date));
            break;
          case 'name':
            sorted = cards.sort((a, b) => a.dataset.name.localeCompare(b.dataset.name));
            break;
          case 'experience':
            sorted = cards.sort((a, b) => parseInt(b.dataset.experience) - parseInt(a.dataset.experience));
            break;
        }
        sorted.forEach(card => agentList.appendChild(card));
      });
    }
  });
</script>