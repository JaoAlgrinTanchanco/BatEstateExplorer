// Admin Dashboard interactions
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      const dropdown = this.parentElement;
      dropdown.classList.toggle('open');
    });
  });

  // Optional: auto-refresh, comment out if not desired
  setInterval(function() {
    if (document.visibilityState === 'visible') {
      location.reload();
    }
  }, 30000);
});


