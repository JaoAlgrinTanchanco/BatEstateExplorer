<?php
if (!isset($user)) {
    die('Access denied.');
}
?>
<div class="dashboard-container">
    <header class="dashboard-header">
        <h1>Search Properties</h1>
    </header>

    <form class="search-form" method="GET" action="">
        <input type="hidden" name="view" value="associate_search">

        <div class="search-field">
            <label for="location">Location</label>
            <select id="location" name="location">
                <option value="">All Locations</option>
                <option value="Lipa City">Lipa City</option>
                <option value="Batangas City">Batangas City</option>
            </select>
        </div>

        <div class="search-field">
            <label for="type">Property Type</label>
            <select id="type" name="type">
                <option value="">All Types</option>
                <option value="House">House</option>
                <option value="Lot">Lot</option>
                <option value="Condo">Condo</option>
            </select>
        </div>

        <div class="search-field">
            <label for="min_price">Min Price</label>
            <input type="number" name="min_price" id="min_price" placeholder="0">
        </div>

        <div class="search-field">
            <label for="max_price">Max Price</label>
            <input type="number" name="max_price" id="max_price" placeholder="0">
        </div>

        <button type="submit">Search</button>
    </form>

    <section class="search-results">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && count($_GET) > 1) {
            echo "<h2>Search Results</h2>";
            echo "<p>Show matching properties here...</p>";
            // TODO: Fetch from DB using filters
        }
        ?>
    </section>
</div>
