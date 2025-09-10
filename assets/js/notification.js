// notification.js
document.addEventListener('DOMContentLoaded', () => {
  const notifications = document.querySelectorAll('.notification');
  notifications.forEach(notif => {
    notif.classList.add('show');
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
      notif.remove();
    }, 5000);
    
    // Close button
    const closeBtn = notif.querySelector('.close-btn');
    if(closeBtn){
      closeBtn.addEventListener('click', () => {
        notif.remove();
      });
    }
  });
});
