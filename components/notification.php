<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$notif = null;

// Case 1: Notification from redirect/session
if (!empty($_SESSION['notification'])) {
    $notif = $_SESSION['notification'];
    unset($_SESSION['notification']);
}
// Case 2: Inline $error / $success variables
elseif (!empty($error)) {
    $notif = ['type' => 'error', 'message' => $error];
} elseif (!empty($success)) {
    $notif = ['type' => 'success', 'message' => $success];
}

if ($notif): 
    $type = htmlspecialchars($notif['type']); // success | error
    $message = htmlspecialchars($notif['message']);

    // Decide icon and background based on type
    $icon = $type === 'success'
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill="#fff" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill="#fff" d="m13 13h-2v-6h2zm0 4h-2v-2h2zm-1-15c-1.3132 0-2.61358.25866-3.82683.7612-1.21326.50255-2.31565 1.23915-3.24424 2.16773-1.87536 1.87537-2.92893 4.41891-2.92893 7.07107 0 2.6522 1.05357 5.1957 2.92893 7.0711.92859.9286 2.03098 1.6651 3.24424 2.1677 1.21325.5025 2.51363.7612 3.82683.7612 2.6522 0 5.1957-1.0536 7.0711-2.9289 1.8753-1.8754 2.9289-4.4189 2.9289-7.0711 0-1.3132-.2587-2.61358-.7612-3.82683-.5026-1.21326-1.2391-2.31565-2.1677-3.24424-.9286-.92858-2.031-1.66518-3.2443-2.16773-1.2132-.50254-2.5136-.7612-3.8268-.7612z"/></svg>';
?>
<div class="notification-container">
    <div class="notification <?= $type ?>">
        <div class="notification__icon"><?= $icon ?></div>
        <div class="notification__title"><?= $message ?></div>
        <div class="notification__close">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                <path fill="#fff" d="m15.8333 5.34166-1.175-1.175-4.6583 4.65834-4.65833-4.65834-1.175 1.175 4.65833 4.65834-4.65833 4.6583 1.175 1.175 4.65833-4.6583 4.6583 4.6583 1.175-1.175-4.6583-4.6583z"/>
            </svg>
        </div>
    </div>
</div>
<?php endif; ?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/notification.css">

<script>
document.addEventListener('DOMContentLoaded', () => {
    const notifications = document.querySelectorAll('.notification');
    notifications.forEach(notif => {
        const closeBtn = notif.querySelector('.notification__close');
        if(closeBtn){
            closeBtn.addEventListener('click', () => fadeOutNotif(notif));
        }

        // Auto-dismiss
        setTimeout(() => fadeOutNotif(notif), 5000);
    });

    function fadeOutNotif(notif){
        notif.classList.add('fade-out');
        setTimeout(() => notif.remove(), 500); // remove after fade
    }
});
</script>
