<?php
// User search view
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Properties | BatEstate</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" />
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🏠 BatEstate</h2>
                <p>User Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="user_dashboard.php?view=home"><i class="fa-solid fa-home"></i> Home</a></li>
                    <li><a href="user_dashboard.php?view=profile"><i class="fa-solid fa-user"></i> Profile</a></li>
                    <li><a href="user_dashboard.php?view=search" class="active"><i class="fa-solid fa-search"></i> Search Properties</a></li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../../auth/logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="content-header">
                <h1>Search Properties</h1>
                <div class="user-info">
                    <span>Find your dream property</span>
                </div>
            </header>

            <div class="content-body">
                <div class="search-card">
                    <h2>Property Search</h2>
                    <form class="search-form">
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
            </div>
        </div>
    </div>
</body>
</html>
