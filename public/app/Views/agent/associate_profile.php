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
                echo "<h2>Profile Overview</h2>";
                echo "<p>Name: {$user['name']}</p>";
                echo "<p>Email: {$user['email']}</p>";
                echo "<p>User Type: {$user['user_type']}</p>";
        }
        ?>
    </section>
</div>
