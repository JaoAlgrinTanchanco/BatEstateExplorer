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

// Get only pending applications from database
$applications = [];
$query = "SELECT a.*, c.name as company_name 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Applications | BatEstateExplorer</title>
  <link rel="stylesheet" href="admin_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    .applications-header {
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
    .application-list {
      display: flex;
      flex-direction: column;
      gap: 25px;
    }
    .application-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      padding: 25px 30px;
      border: 1px solid #e7e7e7;
      transition: box-shadow 0.2s;
    }
    
    .application-card:hover {
      box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    }
    .application-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
    }
    .applicant-info {
      flex: 1;
    }
    .applicant-name {
      font-weight: 600;
      font-size: 1.2rem;
      margin-bottom: 8px;
      color: #333;
    }
    .applicant-details {
      color: #666;
      font-size: 0.95rem;
      margin-bottom: 6px;
      line-height: 1.4;
    }
    .application-status {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
    }
    .status-pending {
      background: #fff3cd;
      color: #856404;
    }
    .status-approved {
      background: #d4edda;
      color: #155724;
    }
    .status-rejected {
      background: #f8d7da;
      color: #721c24;
    }
    .application-content {
      margin-bottom: 20px;
    }
    .application-field {
      display: flex;
      margin-bottom: 8px;
    }
    .field-label {
      font-weight: 500;
      color: #555;
      width: 120px;
      flex-shrink: 0;
    }
    .field-value {
      color: #333;
    }
    .application-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }
    .application-actions button {
      padding: 8px 18px;
      border-radius: 20px;
      border: none;
      font-weight: 500;
      font-size: 0.95rem;
      cursor: pointer;
      transition: background 0.2s, color 0.2s;
    }
    .approve-btn {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .approve-btn:hover {
      background: #155724;
      color: #fff;
    }
    .reject-btn {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    .reject-btn:hover {
      background: #721c24;
      color: #fff;
    }
    .view-btn {
      background: #f4f7fa;
      color: #222;
    }
    .view-btn:hover {
      background: #000;
      color: #fff;
    }
    .no-applications {
      text-align: center;
      padding: 40px;
      color: #666;
    }
    
    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
      background-color: #ffffff;
      margin: 3% auto;
      padding: 0;
      border-radius: 15px;
      width: 90%;
      max-width: 700px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
      border: 1px solid #e7e7e7;
    }
    
    .modal-header {
      padding: 25px 30px;
      border-bottom: 1px solid #e7e7e7;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #f8f9fa;
      border-radius: 15px 15px 0 0;
    }
    
    .modal-header h2 {
      margin: 0;
      color: #333;
      font-size: 1.5rem;
      font-weight: 600;
    }
    
    .close {
      color: #666;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.2s;
    }
    
    .close:hover {
      color: #000;
      background: #e9ecef;
    }
    
    .modal-body {
      padding: 30px;
      max-height: 65vh;
      overflow-y: auto;
    }
    
    .modal-footer {
      padding: 20px 30px;
      border-top: 1px solid #e7e7e7;
      text-align: right;
      background: #f8f9fa;
      border-radius: 0 0 15px 15px;
    }
    
    .modal-footer .cancel-btn {
      padding: 10px 20px;
      border: none;
      background: #6c757d;
      color: white;
      border-radius: 20px;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
    }
    
    .modal-footer .cancel-btn:hover {
      background: #5a6268;
    }
    
    .detail-row {
      display: flex;
      margin-bottom: 15px;
      border-bottom: 1px solid #f0f0f0;
      padding-bottom: 15px;
    }
    
    .detail-row:last-child {
      border-bottom: none;
      margin-bottom: 0;
    }
    
    .detail-label {
      font-weight: 600;
      color: #555;
      width: 180px;
      flex-shrink: 0;
      font-size: 0.95rem;
    }
    
    .detail-value {
      color: #333;
      flex: 1;
      font-size: 0.95rem;
    }
    
    .detail-section {
      margin-bottom: 30px;
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      border: 1px solid #e9ecef;
    }
    
    .detail-section h3 {
      color: #333;
      margin-bottom: 20px;
      font-size: 1.2rem;
      font-weight: 600;
      border-bottom: 2px solid #007bff;
      padding-bottom: 8px;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">BatEstateExplorer</div>
        <h3>Hi, Admin!</h3>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
          <li class="nav-dropdown">
            <a href="#" class="dropdown-toggle"><i class="fa-solid fa-users"></i> Manage Accounts <i class="fa-solid fa-chevron-down dropdown-icon"></i></a>
            <ul class="dropdown-menu">
              <li><a href="admin_direct_agents.php">Direct Agents</a></li>
              <li><a href="admin_associate_agents.php">Associate Agents</a></li>
            </ul>
          </li>
          <li><a href="admin_property_listings.php"><i class="fa-solid fa-house-chimney"></i> Property Listings</a></li>
          <li><a href="admin_applications.php" class="active"><i class="fa-solid fa-file-lines"></i> Applications</a></li>
          <li><a href="admin_reports.php"><i class="fa-solid fa-flag"></i> Reports</a></li>
          <li><a href="admin_performance.php"><i class="fa-solid fa-chart-bar"></i> Performance</a></li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <header class="main-header applications-header">
        <h1>Agent Applications</h1>
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
            <option value="newest">Newest First</option>
            <option value="oldest">Oldest First</option>
            <option value="name">Applicant Name</option>
            <option value="type">Agent Type</option>
          </select>
        </div>
      </div>
      <div class="application-list" id="applicationList">
        <?php if (empty($applications)): ?>
          <div class="no-applications">No pending applications</div>
        <?php else: ?>
          <?php foreach ($applications as $app): ?>
            <div class="application-card" data-name="<?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>" data-type="<?php echo htmlspecialchars($app['agent_type']); ?>" data-date="<?php echo $app['created_at']; ?>">
              <div class="application-header">
                <div class="applicant-info">
                  <div class="applicant-name"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                  <div class="applicant-details"><?php echo htmlspecialchars($app['email']); ?> • <?php echo str_replace('_', ' ', htmlspecialchars($app['agent_type'])); ?></div>
                  <div class="applicant-details">Applied: <?php echo date('M d, Y', strtotime($app['created_at'])); ?></div>
                </div>
                <div class="application-status status-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></div>
              </div>
              <div class="application-content">
                <div class="application-field">
                  <span class="field-label">Broker ID:</span>
                  <span class="field-value"><?php echo htmlspecialchars($app['broker_id'] ?: 'N/A'); ?></span>
                </div>
                <div class="application-field">
                  <span class="field-label">Experience:</span>
                  <span class="field-value"><?php echo htmlspecialchars($app['experience_years'] ?: 'N/A'); ?> years</span>
                </div>
                <div class="application-field">
                  <span class="field-label">Address:</span>
                  <span class="field-value"><?php echo htmlspecialchars($app['address'] ?: 'N/A'); ?></span>
                </div>
              </div>
              <div class="application-actions">
                <button class="view-btn" onclick="viewApplication(<?php echo $app['id']; ?>)">View Details</button>
                <?php if ($app['status'] === 'pending'): ?>
                  <button class="approve-btn" onclick="reviewApplication(<?php echo $app['id']; ?>, 'approve')">Approve</button>
                  <button class="reject-btn" onclick="reviewApplication(<?php echo $app['id']; ?>, 'reject')">Reject</button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
            </div>

  <!-- Application Details Modal -->
  <div id="applicationModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Application Details</h2>
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
    let currentApplicationId = null;

    // View application details
    function viewApplication(id) {
      // Fetch application details via AJAX
      fetch('get_application_details.php?id=' + id)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const application = data.application;
            const modalBody = document.getElementById('modalBody');
            
            modalBody.innerHTML = `
              <div class="detail-section">
                <h3>Personal Information</h3>
                <div class="detail-row">
                  <div class="detail-label">Full Name:</div>
                  <div class="detail-value">${application.first_name} ${application.last_name}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Email:</div>
                  <div class="detail-value">${application.email}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Phone:</div>
                  <div class="detail-value">${application.phone || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Address:</div>
                  <div class="detail-value">${application.address || 'N/A'}</div>
                </div>
              </div>
              
              <div class="detail-section">
                <h3>Agent Information</h3>
                <div class="detail-row">
                  <div class="detail-label">Agent Type:</div>
                  <div class="detail-value">${application.agent_type.replace('_', ' ').toUpperCase()}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Broker ID:</div>
                  <div class="detail-value">${application.broker_id || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">License Number:</div>
                  <div class="detail-value">${application.license_number || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Experience:</div>
                  <div class="detail-value">${application.experience_years || 'N/A'} years</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Specialization:</div>
                  <div class="detail-value">${application.specialization || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Company:</div>
                  <div class="detail-value">${application.company_name || 'N/A'}</div>
                </div>
              </div>
              
              <div class="detail-section">
                <h3>Education & Qualifications</h3>
                <div class="detail-row">
                  <div class="detail-label">Education:</div>
                  <div class="detail-value">${application.education || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">School:</div>
                  <div class="detail-value">${application.school || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Course:</div>
                  <div class="detail-value">${application.course || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Graduation Year:</div>
                  <div class="detail-value">${application.graduation_year || 'N/A'}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Certifications:</div>
                  <div class="detail-value">${application.certifications || 'N/A'}</div>
            </div>
                <div class="detail-row">
                  <div class="detail-label">Training:</div>
                  <div class="detail-value">${application.training || 'N/A'}</div>
          </div>
          </div>

                      <div class="detail-section">
                        <h3>Uploaded Documents</h3>
                        ${application.broker_license_path ? 
                          `<div class="detail-row">
                            <div class="detail-label">Broker License:</div>
                            <div class="detail-value"><a href="${application.broker_license_path}" target="_blank" class="document-link">View Document</a></div>
                          </div>` : ''
                        }
                        ${application.prc_license_path ? 
                          `<div class="detail-row">
                            <div class="detail-label">PRC License:</div>
                            <div class="detail-value"><a href="${application.prc_license_path}" target="_blank" class="document-link">View Document</a></div>
                          </div>` : ''
                        }
                        ${application.resume_path ? 
                          `<div class="detail-row">
                            <div class="detail-label">Resume/CV:</div>
                            <div class="detail-value"><a href="${application.resume_path}" target="_blank" class="document-link">View Document</a></div>
                          </div>` : ''
                        }
                        ${application.valid_id_path ? 
                          `<div class="detail-row">
                            <div class="detail-label">Valid ID:</div>
                            <div class="detail-value"><a href="${application.valid_id_path}" target="_blank" class="document-link">View Document</a></div>
                          </div>` : ''
                        }
                        ${application.additional_docs_path ? 
                          `<div class="detail-row">
                            <div class="detail-label">Additional Documents:</div>
                            <div class="detail-value">
                              ${application.additional_docs_path.split(',').map(doc => 
                                `<a href="${doc.trim()}" target="_blank" class="document-link">View Document</a>`
                              ).join('<br>')}
                            </div>
                          </div>` : ''
                        }
                        ${!application.broker_license_path && !application.prc_license_path && !application.resume_path && !application.valid_id_path && !application.additional_docs_path ? 
                          '<div class="detail-row"><div class="detail-value">No documents uploaded</div></div>' : ''
                        }
          </div>
          
          <div class="detail-section">
                        <h3>Additional Information</h3>
                        <div class="detail-row">
                          <div class="detail-label">Applied Date:</div>
                          <div class="detail-value">${new Date(application.created_at).toLocaleDateString()}</div>
        </div>
                        <div class="detail-row">
                          <div class="detail-label">Status:</div>
                          <div class="detail-value">${application.status.toUpperCase()}</div>
            </div>
          </div>
        `;
        
        
            document.getElementById('applicationModal').style.display = 'block';
          } else {
            alert('Error loading application details');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error loading application details');
        });
    }
    
    // Close modal
    function closeModal() {
      document.getElementById('applicationModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('applicationModal');
      if (event.target === modal) {
        closeModal();
      }
    }

    // Review application (approve/reject)
    function reviewApplication(id, action) {
      const formData = new FormData();
      formData.append('application_id', id);
      formData.append('action', action);
      formData.append('admin_notes', ''); // Removed prompt, sending empty notes
      
      fetch('update_application.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (response.ok) {
          window.location.reload();
        } else {
          alert('Error updating application');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error updating application');
      });
    }

    // Sort functionality
    document.getElementById('sort').addEventListener('change', function() {
      const sortBy = this.value;
      const cards = Array.from(document.querySelectorAll('.application-card'));
      
      let sorted;
      switch(sortBy) {
        case 'newest':
          sorted = cards.sort((a, b) => new Date(b.dataset.date) - new Date(a.dataset.date));
          break;
        case 'oldest':
          sorted = cards.sort((a, b) => new Date(a.dataset.date) - new Date(b.dataset.date));
          break;
        case 'name':
          sorted = cards.sort((a, b) => a.dataset.name.localeCompare(b.dataset.name));
          break;
        case 'type':
          sorted = cards.sort((a, b) => a.dataset.type.localeCompare(b.dataset.type));
          break;
      }
      
      const container = document.getElementById('applicationList');
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