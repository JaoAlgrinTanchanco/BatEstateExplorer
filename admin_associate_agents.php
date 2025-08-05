<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
$is_logged_in = is_logged_in();
$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_admin = ($current_user && $current_user['user_type'] === 'admin');
}

// Redirect if not logged in or not admin
if (!$is_logged_in || !$is_admin) {
    header('Location: login.php');
    exit;
}

// Get approved associate agents only (not pending applications)
$query = "SELECT u.*, a.broker_id, a.license_number, a.experience_years, a.specialization, c.name as company_name 
          FROM users u 
          LEFT JOIN agents a ON u.id = a.user_id 
          LEFT JOIN companies c ON a.company_id = c.id 
          WHERE u.user_type = 'associate_agent' AND u.status = 'active'
          ORDER BY u.created_at DESC";
$result = mysqli_query($conn, $query);
$associate_agents = [];
while ($row = mysqli_fetch_assoc($result)) {
    $associate_agents[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Associate Agents | BatEstateExplorer</title>
  <link rel="stylesheet" href="admin_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    .sort-by {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .sort-by label {
      font-weight: 500;
      color: #555;
    }
    .sort-by select {
      padding: 7px 15px;
      border-radius: 20px;
      border: 1px solid #dcdfe3;
      font-size: 0.95rem;
      background: #fff;
      width: 160px;
    }
    .associate-agents-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    .sort-row {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 30px;
    }
    .associate-agent-list {
      display: flex;
      flex-direction: column;
      gap: 25px;
    }
    .associate-agent-card {
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      padding: 20px 30px;
      gap: 25px;
      border: 1px solid #e7e7e7;
    }
    .associate-agent-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #eee;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: #888;
    }
    .associate-agent-info {
      flex: 1;
    }
    .associate-agent-name {
      font-weight: 600;
      font-size: 1.1rem;
      margin-bottom: 4px;
    }
    .associate-agent-details {
      color: #666;
      font-size: 0.95rem;
    }
    .associate-agent-actions {
      display: flex;
      gap: 10px;
    }
    .btn {
      padding: 8px 18px;
      border-radius: 20px;
      border: none;
      cursor: pointer;
      font-size: 0.95rem;
      font-weight: 500;
      text-decoration: none;
      display: inline-block;
      text-align: center;
      transition: background 0.2s, color 0.2s;
    }
    .btn-view {
      background: #f4f7fa;
      color: #222;
      border: 1px solid #e7e7e7;
    }
    .btn-view:hover {
      background: #000;
      color: #fff;
    }
    .btn-remove {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    .btn-remove:hover {
      background: #dc3545;
      color: #fff;
    }
    .no-agents {
      text-align: center;
      padding: 40px;
      color: #666;
      font-style: italic;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">BatEstateExplorer</div>
        <h3>Hi, <?php echo htmlspecialchars($current_user['first_name']); ?>!</h3>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li><a href="admin_dashboard_new.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
          <li class="nav-dropdown">
            <a href="#" class="dropdown-toggle"><i class="fa-solid fa-users"></i> Manage Accounts <i class="fa-solid fa-chevron-down dropdown-icon"></i></a>
            <ul class="dropdown-menu">
              <li><a href="admin_direct_agents.php">Direct Agents</a></li>
              <li><a href="admin_associate_agents.php" class="active">Associate Agents</a></li>
            </ul>
          </li>
          <li><a href="admin_property_listings.php"><i class="fa-solid fa-house-chimney"></i> Property Listings</a></li>
          <li><a href="admin_applications.php"><i class="fa-solid fa-file-lines"></i> Applications</a></li>
          <li><a href="admin_reports.php"><i class="fa-solid fa-flag"></i> Reports</a></li>
          <li><a href="admin_performance.php"><i class="fa-solid fa-chart-bar"></i> Performance</a></li>
        </ul>
      </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
      <header class="main-header">
        <h1>Associate Agents</h1>
        <div class="header-actions">
          <a href="logout.php"><button class="logout-btn">Log Out</button></a>
          <div class="profile-btn">
            <i class="fa-solid fa-user"></i>
          </div>
        </div>
      </header>
      
      <div class="sort-row">
        <div class="sort-by">
          <label for="sort">Sort By:</label>
          <select id="sort">
            <option value="name">Name</option>
            <option value="date">Date Added</option>
            <option value="experience">Experience</option>
            <option value="company">Company</option>
          </select>
        </div>
      </div>
      
      <div class="associate-agent-list" id="agentList">
        <?php if (empty($associate_agents)): ?>
          <div class="no-agents">No approved associate agents found.</div>
        <?php else: ?>
          <?php foreach ($associate_agents as $agent): ?>
            <div class="associate-agent-card" data-name="<?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?>" data-date="<?php echo $agent['created_at']; ?>" data-experience="<?php echo $agent['experience_years'] ?? 0; ?>" data-company="<?php echo htmlspecialchars($agent['company_name'] ?? ''); ?>">
              <div class="associate-agent-avatar">
                <i class="fa-solid fa-user"></i>
              </div>
              <div class="associate-agent-info">
                <div class="associate-agent-name"><?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></div>
                <div class="associate-agent-details">
                  <?php echo htmlspecialchars($agent['address'] ?: 'Unknown'); ?>
                  <?php if ($agent['company_name']): ?>
                    <br><strong>Company:</strong> <?php echo htmlspecialchars($agent['company_name']); ?>
                  <?php endif; ?>
                </div>
              </div>
              <div class="associate-agent-actions">
                <button class="btn btn-view" onclick="viewAgent(<?php echo $agent['id']; ?>)">View Details</button>
                <button class="btn btn-remove" onclick="removeAgent(<?php echo $agent['id']; ?>)">Remove Account</button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- Agent Details Modal -->
  <div id="agentModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Associate Agent Details</h2>
        <span class="close" onclick="closeModal()">&times;</span>
      </div>
      <div class="modal-body" id="modalBody">
        <!-- Content will be loaded here -->
      </div>
      <div class="modal-footer">
        <button class="cancel-btn" onclick="closeModal()">Close</button>
      </div>
    </div>
  </div>

  <script>
    // View agent details
    function viewAgent(agentId) {
      // Fetch agent details via AJAX
      fetch('get_agent_details.php?id=' + agentId)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const agent = data.agent;
            const modalBody = document.getElementById('modalBody');
            
            modalBody.innerHTML = `
              <div class="detail-section">
                <h3>Personal Information</h3>
                <div class="detail-row">
                  <div class="detail-label">Full Name:</div>
                  <div class="detail-value">${agent.first_name} ${agent.last_name}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Email:</div>
                  <div class="detail-value">${agent.email}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Phone:</div>
                  <div class="detail-value">${agent.phone || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Address:</div>
                  <div class="detail-value">${agent.address || 'N/A'}</div>
                </div>
              </div>
              
              <div class="detail-section">
                <h3>Agent Information</h3>
                <div class="detail-row">
                  <div class="detail-label">Agent Type:</div>
                  <div class="detail-value">${(agent.user_type || 'associate_agent').replace('_', ' ').toUpperCase()}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Broker ID:</div>
                  <div class="detail-value">${agent.broker_id || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">License Number:</div>
                  <div class="detail-value">${agent.license_number || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Experience:</div>
                  <div class="detail-value">${agent.experience_years || 'N/A'} years</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Specialization:</div>
                  <div class="detail-value">${agent.specialization || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Company:</div>
                  <div class="detail-value">${agent.company_name || 'N/A'}</div>
                </div>
              </div>
              
              <div class="detail-section">
                <h3>Education & Qualifications</h3>
                <div class="detail-row">
                  <div class="detail-label">Education:</div>
                  <div class="detail-value">${agent.education || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">School:</div>
                  <div class="detail-value">${agent.school || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Course:</div>
                  <div class="detail-value">${agent.course || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Graduation Year:</div>
                  <div class="detail-value">${agent.graduation_year || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Certifications:</div>
                  <div class="detail-value">${agent.certifications || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Training:</div>
                  <div class="detail-value">${agent.training || 'N/A'}</div>
                </div>
              </div>
              
              <div class="detail-section">
                <h3>Uploaded Documents</h3>
                ${agent.broker_license_path ? 
                  `<div class="detail-row">
                    <div class="detail-label">Broker License:</div>
                    <div class="detail-value"><a href="${agent.broker_license_path}" target="_blank" class="document-link">View Document</a></div>
                  </div>` : ''
                }
                ${agent.prc_license_path ? 
                  `<div class="detail-row">
                    <div class="detail-label">PRC License:</div>
                    <div class="detail-value"><a href="${agent.prc_license_path}" target="_blank" class="document-link">View Document</a></div>
                  </div>` : ''
                }
                ${agent.resume_path ? 
                  `<div class="detail-row">
                    <div class="detail-label">Resume/CV:</div>
                    <div class="detail-value"><a href="${agent.resume_path}" target="_blank" class="document-link">View Document</a></div>
                  </div>` : ''
                }
                ${agent.valid_id_path ? 
                  `<div class="detail-row">
                    <div class="detail-label">Valid ID:</div>
                    <div class="detail-value"><a href="${agent.valid_id_path}" target="_blank" class="document-link">View Document</a></div>
                  </div>` : ''
                }
                ${agent.additional_docs_path ? 
                  `<div class="detail-row">
                    <div class="detail-label">Additional Documents:</div>
                    <div class="detail-value">
                      ${agent.additional_docs_path.split(',').map(doc => 
                        `<a href="${doc.trim()}" target="_blank" class="document-link">View Document</a>`
                      ).join('<br>')}
                    </div>
                  </div>` : ''
                }
                ${!agent.broker_license_path && !agent.prc_license_path && !agent.resume_path && !agent.valid_id_path && !agent.additional_docs_path ? 
                  '<div class="detail-row"><div class="detail-value">No documents uploaded</div></div>' : ''
                }
              </div>
              
              <div class="detail-section">
                <h3>Account Information</h3>
                <div class="detail-row">
                  <div class="detail-label">Account Created:</div>
                  <div class="detail-value">${new Date(agent.created_at).toLocaleDateString()}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Status:</div>
                  <div class="detail-value">${(agent.status || 'active').toUpperCase()}</div>
                </div>
              </div>
            `;
            
            document.getElementById('agentModal').style.display = 'block';
          } else {
            alert('Error loading agent details');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error loading agent details');
        });
    }
    
    // Close modal
    function closeModal() {
      document.getElementById('agentModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('agentModal');
      if (event.target === modal) {
        closeModal();
      }
    }

    function removeAgent(agentId) {
      if (confirm('Are you sure you want to remove this agent? This action cannot be undone.')) {
        // TODO: Implement remove agent functionality
        alert('Remove agent functionality would be implemented here');
      }
    }

    // Sort functionality
    document.getElementById('sort').addEventListener('change', function() {
      const sortBy = this.value;
      const cards = Array.from(document.querySelectorAll('.associate-agent-card'));
      
      let sorted;
      switch(sortBy) {
        case 'date':
          sorted = cards.sort((a, b) => new Date(b.dataset.date) - new Date(a.dataset.date));
          break;
        case 'name':
          sorted = cards.sort((a, b) => a.dataset.name.localeCompare(b.dataset.name));
          break;
        case 'experience':
          sorted = cards.sort((a, b) => parseInt(b.dataset.experience) - parseInt(a.dataset.experience));
          break;
        case 'company':
          sorted = cards.sort((a, b) => a.dataset.company.localeCompare(b.dataset.company));
          break;
      }
      
      const container = document.getElementById('agentList');
      sorted.forEach(card => container.appendChild(card));
    });

    // Sidebar functionality
    document.addEventListener('DOMContentLoaded', function() {
      const currentPage = window.location.pathname.split('/').pop();
      const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
      
      // Reset all active states
      sidebarLinks.forEach(link => {
        link.classList.remove('active');
      });
      
      // Set active state for current page
      sidebarLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
          link.classList.add('active');
          // Only open dropdown if the active link is within that dropdown
          const dropdown = link.closest('.nav-dropdown');
          if (dropdown) {
            dropdown.classList.add('open');
          }
        }
      });
    });

    // Dropdown toggle - works from any page
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        const dropdown = this.parentElement;
        
        // Toggle current dropdown
        dropdown.classList.toggle('open');
      });
    });
  </script>
</body>
</html> 