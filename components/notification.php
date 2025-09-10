<?php
if(session_status() === PHP_SESSION_NONE) session_start();
if(isset($_SESSION['notification'])):
    $notif = $_SESSION['notification'];
    $type = $notif['type'] ?? 'success'; // success or error
    $message = $notif['message'] ?? '';
?>
<div class="notification-container">
    <div class="notification <?= htmlspecialchars($type) ?>">
        <span><?= htmlspecialchars($message) ?></span>
        <span class="close-btn">&times;</span>
    </div>
</div>
<?php
    unset($_SESSION['notification']); // remove after displaying
endif;
?>
