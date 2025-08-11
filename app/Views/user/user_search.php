<?php
// user_search.php

$locations = [
    '' => 'All Locations',
    'Batangas City' => 'Batangas City',
    'Lipa City' => 'Lipa City',
    'Tanauan City' => 'Tanauan City',
];

$property_types = [
    '' => 'All Types',
    'house' => 'House',
    'condo' => 'Condominium',
    'land' => 'Land',
];

$price_ranges = [
    '' => 'Any Price',
    '0-1000000' => 'Under ₱1M',
    '1000000-5000000' => '₱1M - ₱5M',
    '5000000+' => '₱5M+',
];
?>
<div class="search-container">
    <div class="search">

        <form class="search-form" method="GET" action="user_dashboard.php?view=search_results">

            <button type="button" class="search-field" data-field="location">
            <span class="label">Location</span>
            <span class="value">All Locations</span>
            <select name="location" id="location">
                <option value="" selected>All Locations</option>
                <option value="Batangas City">Batangas City</option>
                <option value="Lipa City">Lipa City</option>
                <option value="Tanauan City">Tanauan City</option>
            </select>
            </button>

            <button type="button" class="search-field" data-field="property_type">
            <span class="label">Property Type</span>
            <span class="value">All Types</span>
            <select name="property_type" id="property_type">
                <option value="" selected>All Types</option>
                <option value="house">House</option>
                <option value="condo">Condominium</option>
                <option value="land">Land</option>
            </select>
            </button>

            <button type="button" class="search-field" data-field="price_range">
            <span class="label">Price Range</span>
            <span class="value">Any Price</span>
            <select name="price_range" id="price_range">
                <option value="" selected>Any Price</option>
                <option value="0-1000000">Under ₱1M</option>
                <option value="1000000-5000000">₱1M - ₱5M</option>
                <option value="5000000+">₱5M+</option>
            </select>
            </button>

            <button type="submit" class="search-submit" aria-label="Search">
            <i class="fa-solid fa-magnifying-glass"></i>
            </button>

        </form>
  
    </div>
    

<div class="result">

</div>
</div>

<style>
    .search-container {
  position: relative;
  width: 100%;
  min-height: calc(100vh - 120px); /* subtract header/footer if any */
  display: grid;
  grid-template-rows: auto 1fr; /* search bar then results */
  align-items: start;
  background-color: blue; /* debug */
  padding: 20px;
  box-sizing: border-box;
  background-color: blue;
  
}
.search {
    padding-top: 60px;
    padding-bottom: 60px;
  display: flex;
  justify-content: center;
  align-items: center;
}

</style>