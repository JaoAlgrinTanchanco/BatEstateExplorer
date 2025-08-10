<?php

?>

<!-- Property Listings Section -->
<header class="content-header">
    <h1>Property Listing</h1>
    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($current_user['email']); ?></span>
    </div>
</header>
<table>
    <thead>
        <tr>
            <th>Property Name</th>
            <th>Location</th>
            <th>Price</th>
            <th>Status</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($listings)): ?>
            <?php foreach ($listings as $listing): ?>
                <tr>
                    <td><?= htmlspecialchars($listing['property_name']) ?></td>
                    <td><?= htmlspecialchars($listing['location']) ?></td>
                    <td><?= number_format($listing['price'], 2) ?></td>
                    <td><?= htmlspecialchars($listing['status']) ?></td>
                    <td><?= htmlspecialchars($listing['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No property listings found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
