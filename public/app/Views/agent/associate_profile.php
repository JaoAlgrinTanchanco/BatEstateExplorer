<?php
// Security: Make sure $user is available from the controller
if (!isset($user)) {
    die('Access denied.');
}
?>
<div class="dashboard-container">
    <header class="dashboard-header">
        <h1>Associate Agent Profile</h1>
    </header>

    <nav class="dashboard-tabs">
        <a href="?view=associate_profile" class="tab active">Overview</a>
        <a href="?view=associate_profile&tab=my_listings" class="tab">My Listings</a>
        <a href="?view=associate_profile&tab=add_listing" class="tab">Add Listing</a>
        <a href="?view=associate_profile&tab=analytics" class="tab">Analytics</a>
        <a href="?view=associate_profile&tab=company_listings" class="tab">Company Listings</a>
    </nav>

    <section class="dashboard-content">
        <?php
        $tab = $_GET['tab'] ?? 'overview';
        switch ($tab) {
            case 'my_listings':
                echo "<h2>My Listings</h2>";
                echo "<p>Display list of properties posted by this agent.</p>";
                break;

            case 'add_listing':
                echo "<h2>Add New Listing</h2>";
                echo "<p>Form to create a new property listing.</p>";
                break;

            case 'analytics':
                echo "<h2>Performance Analytics</h2>";
                echo "<p>Charts, leads, and sales data here.</p>";
                break;

            case 'company_listings':
                echo "<h2>Company Listings</h2>";
                echo "<p>List of all properties from your company.</p>";
                break;

            default:
                $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                if ($fullName === '') {
                    $fullName = 'Agent';
                }

                echo "<h2>Profile Overview</h2>";
                echo "<p>Name: " . htmlspecialchars($fullName) . "</p>";
                echo "<p>Email: " . htmlspecialchars($user['email'] ?? '') . "</p>";
                echo "<p>User Type: " . htmlspecialchars($user['user_type'] ?? '') . "</p>";
                break;

        }
        ?>
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
