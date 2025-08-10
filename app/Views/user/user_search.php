<?php
// user_search.php

ob_start();
?>

<div class="search-card">
    <h2>Property Search</h2>
    <form class="search-form" method="GET" action="user_dashboard.php?view=search_results">
        <div class="form-group">
            <label for="location">Location</label>
            <select id="location" name="location">
                <option value="">All Locations</option>
                <option value="Batangas City">Batangas City</option>
                <option value="Lipa City">Lipa City</option>
                <option value="Tanauan City">Tanauan City</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="property_type">Property Type</label>
            <select id="property_type" name="property_type">
                <option value="">All Types</option>
                <option value="house">House</option>
                <option value="condo">Condominium</option>
                <option value="land">Land</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="price_range">Price Range</label>
            <select id="price_range" name="price_range">
                <option value="">Any Price</option>
                <option value="0-1000000">Under ₱1M</option>
                <option value="1000000-5000000">₱1M - ₱5M</option>
                <option value="5000000+">₱5M+</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-search"></i> Search Properties
        </button>
    </form>
</div>

<div class="results-section">
    <h3>Search Results</h3>
    <p>Use the search form above to find properties.</p>
</div>

