<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['notification'])) {
    $notif = $_SESSION['notification'];
    $type = $notif['type'] ?? 'info'; // success | error
    $message = htmlspecialchars($notif['message']);

    echo "
    <div class='notification {$type}'>
        <span>{$message}</span>
        <button class='close-btn' onclick='this.parentElement.remove();'>&times;</button>
    </div>
    ";

    // Clear notification so it only shows once
    unset($_SESSION['notification']);
}
?>
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/notification.css">
<script>
    // Auto-hide after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.notification').forEach(el => el.remove());
    }, 5000);
</script>
