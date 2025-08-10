<?php
// user_layout.php
// include header, footer, CSS, JS

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>BatEstateExplorer User Dashboard</title>
  <link rel="stylesheet" href="/app/Views/user/css/main.css" />
  <!-- Add more CSS as needed -->
</head>
<body>
  <header>
    <!-- your nav bar or header here -->
  </header>

  <main>
    <?php include $contentView; ?>
  </main>

  <footer>
    <!-- footer here -->
  </footer>

  <script src="/app/Views/user/js/login.js"></script>
  <script src="/app/Views/user/js/property_slider.js"></script>
  <script src="/app/Views/user/js/search.js"></script>
</body>
</html>
