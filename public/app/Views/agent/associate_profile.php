<?php
if (!isset($user)) die('Access denied.');

// Detect active tab
$tab = $_GET['tab'] ?? 'overview';
?>

<link rel="stylesheet" href="assets/css/agent_profile_tab.css">

<div class="dashboard-container">
    <!-- Header -->
    <header class="dashboard-header">
        <h1>Associate Agent Profile</h1>
    </header>

    <!-- Navigation Tabs -->
    <nav class="dashboard-tabs">
        <a href="?view=associate_profile" class="tab <?= ($tab === 'overview') ? 'active' : '' ?>">Overview</a>
        <a href="?view=associate_profile&tab=my_listings" class="tab <?= ($tab === 'my_listings') ? 'active' : '' ?>">My Listings</a>
        <a href="?view=associate_profile&tab=add_listing" class="tab <?= ($tab === 'add_listing') ? 'active' : '' ?>">Add Listing</a>
        <a href="?view=associate_profile&tab=analytics" class="tab <?= ($tab === 'analytics') ? 'active' : '' ?>">Analytics</a>
        <a href="?view=associate_profile&tab=company_listings" class="tab <?= ($tab === 'company_listings') ? 'active' : '' ?>">Company Listings</a>
    </nav>

    <!-- Content Section -->
    <section class="dashboard-content">
        <?php switch ($tab):
            case 'my_listings': ?>
                <h2>My Listings</h2>
                <p>Display list of properties posted by this agent.</p>
        <?php break; ?>

        <?php case 'add_listing': ?>
                <h2>Add New Listing</h2>
                <p>Form to create a new property listing.</p>
        <?php break; ?>

        <?php case 'analytics': ?>
                <h2>Performance Analytics</h2>
                <p>Charts, leads, and sales data here.</p>
        <?php break; ?>

        <?php case 'company_listings': ?>
                <h2>Company Listings</h2>
                <p>List of all properties from your company.</p>
        <?php break; ?>

        <?php default:
            // Overview Tab
            $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            if ($fullName === '') $fullName = 'Agent';
        ?>
            <h2>Profile Overview</h2>
            <div class="overview-container">

                <!-- Basic Info -->
                <div class="overview-card">
                    <h3>Basic Information</h3>
                    <div class="info-row"><strong>Name:</strong> <span><?= htmlspecialchars($fullName) ?></span></div>
                    <div class="info-row"><strong>Email:</strong> <span><?= htmlspecialchars($user['email'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Phone:</strong> <span><?= htmlspecialchars($user['phone'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Address:</strong> <span><?= htmlspecialchars($user['address'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>User Type:</strong> <span><?= htmlspecialchars($user['user_type'] ?? '-') ?></span></div>
                </div>

                <!-- Education & Training -->
                <div class="overview-card">
                    <h3>Education & Training</h3>
                    <div class="info-row"><strong>Education:</strong> <span><?= htmlspecialchars($user['education'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>School:</strong> <span><?= htmlspecialchars($user['school'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Course:</strong> <span><?= htmlspecialchars($user['course'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Graduation Year:</strong> <span><?= htmlspecialchars($user['graduation_year'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Certifications:</strong> <span><?= htmlspecialchars($user['certifications'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Training:</strong> <span><?= htmlspecialchars($user['training'] ?? '-') ?></span></div>
                </div>

                <!-- Professional Details -->
                <div class="overview-card">
                    <h3>Professional Details</h3>
                    <div class="info-row"><strong>Agent Type:</strong> <span><?= htmlspecialchars($user['agent_type'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Broker ID:</strong> <span><?= htmlspecialchars($user['broker_id'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>License Number:</strong> <span><?= htmlspecialchars($user['license_number'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Years of Experience:</strong> <span><?= htmlspecialchars($user['experience_years'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Specialization:</strong> <span><?= htmlspecialchars($user['specialization'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Bio:</strong> <span><?= nl2br(htmlspecialchars($user['bio'] ?? '-')) ?></span></div>
                </div>

                <!-- Documents -->
                <div class="overview-card">
                    <h3>Documents</h3>
                    <?php
                    $docs = [
                        'Broker License' => 'broker_license_path',
                        'PRC License' => 'prc_license_path',
                        'Resume' => 'resume_path',
                        'Valid ID' => 'valid_id_path',
                        'Additional Docs' => 'additional_docs_path'
                    ];
                    foreach ($docs as $label => $field):
                    ?>
                        <div class="info-row"><strong><?= $label ?>:</strong>
                            <?php if (!empty($user[$field])): ?>
                                <a href="<?= htmlspecialchars($user[$field]) ?>" target="_blank">View</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Account Status -->
                <div class="overview-card">
                    <h3>Account Status</h3>
                    <div class="info-row"><strong>Status:</strong> <span><?= htmlspecialchars($user['status'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Admin Notes:</strong> <span><?= nl2br(htmlspecialchars($user['admin_notes'] ?? '-')) ?></span></div>
                    <div class="info-row"><strong>Created At:</strong> <span><?= htmlspecialchars($user['created_at'] ?? '-') ?></span></div>
                    <div class="info-row"><strong>Last Updated:</strong> <span><?= htmlspecialchars($user['updated_at'] ?? '-') ?></span></div>
                </div>

            </div>
        <?php endswitch; ?>
    </section>
</div>

<style>
/* ===== Profile Page Layout ===== */
.dashboard-container {
    padding: 20px;
    max-width: 1100px;
    margin: auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* ===== Header ===== */
.dashboard-header h1 {
    font-size: 1.8rem;
    margin-bottom: 15px;
    color: #222;
}

/* ===== Tabs ===== */
.dashboard-tabs {
    display: flex;
    flex-wrap: wrap;
    border-bottom: 2px solid #ddd;
    margin-bottom: 20px;
}
.dashboard-tabs .tab {
    padding: 10px 18px;
    text-decoration: none;
    color: #555;
    border-bottom: 3px solid transparent;
    transition: all 0.2s ease;
    font-weight: 500;
}
.dashboard-tabs .tab:hover {
    background: #f8f8f8;
    color: #222;
}
.dashboard-tabs .tab.active {
    border-bottom-color: #3498db;
    color: #3498db;
}

/* ===== Content Section ===== */
.dashboard-content h2 {
    margin-bottom: 10px;
    font-size: 1.4rem;
    color: #333;
}
.dashboard-content p {
    font-size: 1rem;
    line-height: 1.6;
    color: #555;
}

/* ===== Table Styling (Future Tabs) ===== */
.dashboard-content table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
.dashboard-content table th,
.dashboard-content table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}
.dashboard-content table th {
    background: #f3f3f3;
    font-weight: bold;
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
    .dashboard-tabs {
        flex-direction: column;
    }
    .dashboard-tabs .tab {
        border-bottom: none;
        border-left: 3px solid transparent;
    }
    .dashboard-tabs .tab.active {
        border-left-color: #3498db;
        border-bottom: none;
    }
}
</style>
