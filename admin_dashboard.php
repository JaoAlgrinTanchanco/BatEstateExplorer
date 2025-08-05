<?php
session_start();
require_once 'config/database.php';

// Redirect to the new admin dashboard
header('Location: admin_dashboard_new.php');
exit;

$current_user = get_logged_in_user($conn);
$message = '';
$error = '';

// Handle application approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $application_id = (int)$_POST['application_id'];
    $action = $_POST['action'];
    $admin_notes = sanitize_input($conn, $_POST['admin_notes']);
    
    if ($action === 'approve' || $action === 'reject') {
        mysqli_begin_transaction($conn);
        
        try {
            // Update application status
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            $update_query = "UPDATE applications SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ssi", $status, $admin_notes, $application_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // If approved, create agent record
                if ($action === 'approve') {
                    // Get application details
                    $app_query = "SELECT user_id, broker_id, company_id FROM applications WHERE id = ?";
                    $app_stmt = mysqli_prepare($conn, $app_query);
                    mysqli_stmt_bind_param($app_stmt, "i", $application_id);
                    mysqli_stmt_execute($app_stmt);
                    $app_result = mysqli_stmt_get_result($app_stmt);
                    $application = mysqli_fetch_assoc($app_result);
                    
                    if ($application) {
                        // Create agent record
                        $agent_query = "INSERT INTO agents (user_id, broker_id, company_id, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
                        $agent_stmt = mysqli_prepare($conn, $agent_query);
                        mysqli_stmt_bind_param($agent_stmt, "isi", $application['user_id'], $application['broker_id'], $application['company_id']);
                        
                        if (!mysqli_stmt_execute($agent_stmt)) {
                            throw new Exception("Error creating agent record");
                        }
                        
                        // Update user type to direct_agent
                        $user_query = "UPDATE users SET user_type = 'direct_agent' WHERE id = ?";
                        $user_stmt = mysqli_prepare($conn, $user_query);
                        mysqli_stmt_bind_param($user_stmt, "i", $application['user_id']);
                        
                        if (!mysqli_stmt_execute($user_stmt)) {
                            throw new Exception("Error updating user type");
                        }
                    }
                }
                
                mysqli_commit($conn);
                $message = "Application " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully!";
            } else {
                throw new Exception("Error updating application");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get pending applications
$pending_query = "
    SELECT 
        a.*, 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.user_type,
        u.phone,
        u.address,
        a.experience_years,
        a.specialization,
        a.bio,
        a.broker_id,
        a.license_number,
        c.name as company_name
    FROM applications a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN companies c ON a.company_id = c.id
    WHERE a.status = 'pending'
    ORDER BY a.created_at DESC
";
$pending_result = mysqli_query($conn, $pending_query);
$pending_applications = [];
while ($row = mysqli_fetch_assoc($pending_result)) {
    $pending_applications[] = $row;
}

// Get approved applications
$approved_query = "
    SELECT 
        a.*, 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.user_type,
        u.phone,
        u.address,
        a.experience_years,
        a.specialization,
        a.bio,
        a.broker_id,
        a.license_number,
        c.name as company_name
    FROM applications a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN companies c ON a.company_id = c.id
    WHERE a.status = 'approved'
    ORDER BY a.updated_at DESC
    LIMIT 10
";
$approved_result = mysqli_query($conn, $approved_query);
$approved_applications = [];
while ($row = mysqli_fetch_assoc($approved_result)) {
    $approved_applications[] = $row;
}

// Get rejected applications
$rejected_query = "
    SELECT 
        a.*, 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.user_type,
        u.phone,
        u.address,
        a.experience_years,
        a.specialization,
        a.bio,
        a.broker_id,
        a.license_number,
        c.name as company_name
    FROM applications a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN companies c ON a.company_id = c.id
    WHERE a.status = 'rejected'
    ORDER BY a.updated_at DESC
    LIMIT 10
";
$rejected_result = mysqli_query($conn, $rejected_query);
$rejected_applications = [];
while ($row = mysqli_fetch_assoc($rejected_result)) {
    $rejected_applications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BatEstate</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 2em;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1em;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .section-title {
            font-size: 1.5em;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .application-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin: 15px;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .applicant-name {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .application-date {
            color: #666;
            font-size: 0.9em;
        }
        
        .application-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #2c3e50;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-reject:hover {
            background: #c82333;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background: #138496;
        }
        
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
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            min-height: 100px;
        }
        
        .no-applications {
            padding: 40px;
            text-align: center;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏠 BatEstate Admin Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($pending_applications); ?></div>
                <div class="stat-label">Pending Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($approved_applications); ?></div>
                <div class="stat-label">Approved Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($rejected_applications); ?></div>
                <div class="stat-label">Rejected Applications</div>
            </div>
        </div>
        
        <!-- Pending Applications -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Pending Applications</h2>
            </div>
            
            <?php if (empty($pending_applications)): ?>
                <div class="no-applications">No pending applications at this time.</div>
            <?php else: ?>
                <?php foreach ($pending_applications as $app): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="applicant-name"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                            <div class="application-date">Applied: <?php echo date('M j, Y', strtotime($app['created_at'])); ?></div>
                        </div>
                        
                        <div class="application-details">
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div class="detail-value"><?php echo htmlspecialchars($app['email']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Phone</div>
                                <div class="detail-value"><?php echo htmlspecialchars($app['phone'] ?: 'N/A'); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Company</div>
                                <div class="detail-value"><?php echo htmlspecialchars($app['company_name'] ?: 'N/A'); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Experience</div>
                                <div class="detail-value"><?php echo htmlspecialchars($app['experience_years'] ?: 'N/A'); ?> years</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Education</div>
                                <div class="detail-value"><?php echo htmlspecialchars($app['education'] ?: 'N/A'); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">License Number</div>
                                <div class="detail-value"><?php echo htmlspecialchars($app['license_number'] ?: 'N/A'); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($app['specialization']): ?>
                            <div class="detail-item" style="grid-column: 1 / -1;">
                                <div class="detail-label">Specialization</div>
                                <div class="detail-value"><?php echo htmlspecialchars($app['specialization']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="action-buttons">
                            <button class="btn btn-approve" onclick="openModal('approve', <?php echo $app['id']; ?>)">Approve</button>
                            <button class="btn btn-reject" onclick="openModal('reject', <?php echo $app['id']; ?>)">Reject</button>
                            <button class="btn btn-view" onclick="viewDetails(<?php echo $app['id']; ?>)">View Details</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Recently Approved -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recently Approved</h2>
            </div>
            
            <?php if (empty($approved_applications)): ?>
                <div class="no-applications">No approved applications to display.</div>
            <?php else: ?>
                <?php foreach ($approved_applications as $app): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="applicant-name"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                            <div class="application-date">Approved: <?php echo date('M j, Y', strtotime($app['updated_at'])); ?></div>
                        </div>
                        
                        <div class="application-details">
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div class="detail-value"><?php echo htmlspecialchars($app['email']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Company</div>
                                <div class="detail-value"><?php echo htmlspecialchars($app['company_name'] ?: 'N/A'); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Admin Notes</div>
                                <div class="detail-value"><?php echo htmlspecialchars($app['admin_notes'] ?: 'No notes'); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal for approval/rejection -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Review Application</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="application_id" name="application_id">
                <input type="hidden" id="action" name="action">
                
                <div class="form-group">
                    <label for="admin_notes">Admin Notes:</label>
                    <textarea id="admin_notes" name="admin_notes" placeholder="Enter your review notes here..."></textarea>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn" id="submitBtn">Submit</button>
                    <button type="button" class="btn btn-view" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(action, applicationId) {
            document.getElementById('application_id').value = applicationId;
            document.getElementById('action').value = action;
            document.getElementById('modalTitle').textContent = action === 'approve' ? 'Approve Application' : 'Reject Application';
            document.getElementById('submitBtn').textContent = action === 'approve' ? 'Approve' : 'Reject';
            document.getElementById('submitBtn').className = action === 'approve' ? 'btn btn-approve' : 'btn btn-reject';
            document.getElementById('actionModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('actionModal').style.display = 'none';
        }
        
        function viewDetails(applicationId) {
            // You can implement a detailed view modal here
            alert('Detailed view for application ' + applicationId + ' - This feature can be expanded');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('actionModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 