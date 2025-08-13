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

<style>
/* --- Search Container Layout --- */
.search-container {
  position: relative;
  width: 100%;
  min-height: calc(100vh - 120px); /* subtract header/footer if any */
  display: grid;
  grid-template-rows: auto 1fr; /* search bar then results */
  align-items: start;
  padding: 20px;
  box-sizing: border-box;
}

.search {
  padding-top: 60px;
  padding-bottom: 60px;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* --- Search Form --- */
.search-form {
  width: 100%;
  max-width: 60rem;
  background-color: #fff;
  display: flex;
  align-items: center;
  padding: 8px 12px;
  gap: 0;
  border: 1px solid #dfdfdf;
  border-radius: 40px;
  overflow: hidden;
}

/* Search Fields */
.search-field {
  flex: 1 1 0;
  border: none;
  background: transparent;
  cursor: pointer;
  padding: 12px 24px;
  text-align: left;
  display: flex;
  flex-direction: column;
  justify-content: center;
  border-right: 1px solid #eee;
  position: relative;
  font-size: 14px;
  color: #222;
  transition: background-color 0.3s ease;
  user-select: none;
}

.search-field:last-of-type {
  border-right: none;
}

.search-field .label {
  font-weight: 600;
  font-size: 10px;
  text-transform: uppercase;
  color: #717171;
  letter-spacing: 0.05em;
  margin-bottom: 4px;
}

.search-field .value {
  font-weight: 700;
  font-size: 14px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Hide native select */
.search-field select {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.search-field:hover {
  background-color: #f7f7f7;
}

/* Submit Button */
.user-search-submit {
  background-color: #ff385c;
  border: none;
  border-radius: 50%;
  width: 55px;
  height: 55px;
  margin-left: 12px;
  color: white;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: background-color 0.3s ease;
  flex-shrink: 0;
}

.search-submit:hover {
  background-color: #e03150;
}

.search-submit i {
  font-size: 18px;
}

/* --- Results Grid --- */
.result {
  display: flex;  
  flex-wrap: wrap; 
  gap: 20px;
  width: 100%;
  height: auto;
  box-sizing: border-box;
}

/* --- Modal Styles --- */
.modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999999;
  animation: fadeIn 0.3s ease;
  padding: 10px;
}

.modal-content {
  background: #fff;
  max-width: 900px;
  width: 90%;
  border-radius: 12px;
  padding: 20px;
  position: relative;
  display: flex;
  flex-direction: column;
}

.modal-body {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.modal-image {
  flex: 1 1 45%;
}

.modal-image img {
  width: 100%;
  height: auto;
  border-radius: 8px;
  object-fit: cover;
}

.modal-details {
  flex: 1 1 55%;
}

.modal-close {
  font-size: 26px;
  cursor: pointer;
  position: absolute;
  top: 12px;
  right: 16px;
  background: none;
  border: none;
  color: #666;
}

.modal-close:hover {
  color: #000;
}

.features span {
  display: inline-block;
  margin-right: 12px;
  font-size: 14px;
}

.price {
  font-size: 20px;
  color: #28a745;
  margin: 8px 0;
  font-weight: bold;
}

.modal-actions {
  margin-top: 15px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn {
  padding: 8px 14px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
}

.btn-primary {
  background: #007bff;
  color: #fff;
  border: none;
}

.btn-outline {
  border: 1px solid #ccc;
  background: white;
}

/* --- Animations --- */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* --- Responsive --- */
@media (max-width: 600px) {
  .search-form {
    flex-direction: column;
    gap: 8px;
    padding: 12px;
  }

  .search-field {
    border-right: none !important;
    border-radius: 12px;
    padding: 14px 16px;
  }

  .search-submit {
    width: 100%;
    border-radius: 12px;
  }

  .modal-body {
    flex-direction: column;
  }
}
</style>
