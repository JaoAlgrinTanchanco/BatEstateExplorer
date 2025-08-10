<?php
// user_home.php

ob_start();
?>

<!-- user_home.php -->
<div class="welcome-card">
    <h2>Find Your Dream Property</h2>
    <p>Welcome to BatEstate! We're here to help you find the perfect property in Batangas.</p>
    
    <div class="action-buttons">
        <a href="user_dashboard.php?view=search" class="btn btn-primary">
            <i class="fa-solid fa-search"></i> Search Properties
        </a>
        <a href="user_dashboard.php?view=profile" class="btn btn-secondary">
            <i class="fa-solid fa-user"></i> View Profile
        </a>
    </div>
</div>

<div class="quick-stats">
    <h3>Quick Overview</h3>
    <div class="stats-grid">
        <div class="stat-item">
            <i class="fa-solid fa-house"></i>
            <span>Browse Properties</span>
        </div>
        <div class="stat-item">
            <i class="fa-solid fa-user-tie"></i>
            <span>Connect with Agents</span>
        </div>
        <div class="stat-item">
            <i class="fa-solid fa-heart"></i>
            <span>Save Favorites</span>
        </div>
    </div>
</div>

