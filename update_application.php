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

// Handle POST request to update application status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $admin_notes = isset($_POST['admin_notes']) ? sanitize_input($conn, $_POST['admin_notes']) : '';
    
    if ($application_id && in_array($action, ['approve', 'reject'])) {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        
        // Update application status
        $query = "UPDATE applications SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $status, $admin_notes, $application_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // If approved, create user account and agent record
            if ($action === 'approve') {
                // Get application details
                $query = "SELECT * FROM applications WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $application_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $application = mysqli_fetch_assoc($result);
                
                if ($application) {
                    // Start transaction
                    mysqli_begin_transaction($conn);
                    
                    try {
                        // Determine agent type (default to direct_agent if empty)
                        $agent_type = !empty($application['agent_type']) ? $application['agent_type'] : 'direct_agent';
                        
                        // Create user account
                        $query = "INSERT INTO users (email, password_hash, first_name, last_name, phone, address, user_type, status) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, "sssssss", 
                            $application['email'],
                            $application['password_hash'],
                            $application['first_name'],
                            $application['last_name'],
                            $application['phone'],
                            $application['address'],
                            $agent_type
                        );
                        
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Failed to create user account: " . mysqli_stmt_error($stmt));
                        }
                        
                        $user_id = mysqli_insert_id($conn);
                        
                        // Create agent record
                        $query = "INSERT INTO agents (user_id, company_id, broker_id, license_number, experience_years, specialization, bio) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, "iisssss", 
                            $user_id,
                            $application['company_id'], 
                            $application['broker_id'], 
                            $application['license_number'], 
                            $application['experience_years'], 
                            $application['specialization'], 
                            $application['bio']
                        );
                        
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Failed to create agent record: " . mysqli_stmt_error($stmt));
                        }
                        
                        // Update application with user_id and agent_type
                        $query = "UPDATE applications SET user_id = ?, agent_type = ? WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, "isi", $user_id, $agent_type, $application_id);
                        
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Failed to update application: " . mysqli_stmt_error($stmt));
                        }
                        
                        // Commit transaction
                        mysqli_commit($conn);
                        
                    } catch (Exception $e) {
                        // Rollback transaction
                        mysqli_rollback($conn);
                        error_log("Approval error: " . $e->getMessage());
                        header('Location: admin_applications.php?error=3');
                        exit;
                    }
                }
            }
            
            header('Location: admin_applications.php?success=1');
            exit;
        } else {
            header('Location: admin_applications.php?error=1');
            exit;
        }
    } else {
        header('Location: admin_applications.php?error=2');
        exit;
    }
} else {
    // If not POST request, redirect back
    header('Location: admin_applications.php');
    exit;
}
?> 