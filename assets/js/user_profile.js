document.addEventListener("DOMContentLoaded", () => {
  // --- Tabs ---
  const tabButtons = document.querySelectorAll(".tab-btn");
  const tabContents = document.querySelectorAll(".tab-content");
  const sortSelect = document.getElementById("sortSelect");

  tabButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Switch active tab button
      tabButtons.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      // Show selected tab
      const tab = btn.dataset.tab;
      tabContents.forEach((tc) =>
        tc.classList.toggle("active", tc.id === tab)
      );

      // Reset sort when switching tabs
      sortSelect.value = "date";
    });
  });

  // --- Sorting ---
  sortSelect.addEventListener("change", () => {
    const sortBy = sortSelect.value;
    const container = document.querySelector("#saved-list .property-grid");
    if (!container) return;

    const cards = Array.from(container.querySelectorAll(".property-card"));

    cards.sort((a, b) => {
      if (sortBy === "price") {
        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
      } else {
        return parseInt(b.dataset.date) - parseInt(a.dataset.date); // newest first
      }
    });

    // Re-append in sorted order
    cards.forEach((card) => container.appendChild(card));
  });

  // --- Profile dropdown ---
  const profileDotsBtn = document.getElementById("profileDotsBtn");
  const profileDropdownMenu = document.getElementById("profileDropdownMenu");

  profileDotsBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    profileDropdownMenu.classList.toggle("active");
  });

  // --- Profile modal ---
  const editProfileBtn = document.getElementById("editProfileBtn");
  const profileModal = document.getElementById("profileModal");
  const modalCloseBtn = document.getElementById("modalCloseBtn");

  editProfileBtn?.addEventListener("click", () => {
    profileDropdownMenu.classList.remove("active");
    profileModal.classList.add("active");
  });

  modalCloseBtn?.addEventListener("click", () => {
    profileModal.classList.remove("active");
  });

  profileModal?.addEventListener("click", (e) => {
    if (e.target === profileModal) profileModal.classList.remove("active");
  });

  // --- Logout ---
  window.logout = function () {
    if (confirm("Are you sure you want to logout?")) {
      window.location.href = "logout.php";
    }
  };
});

document.addEventListener('DOMContentLoaded', () => {
  const dotsBtn = document.getElementById('profileDotsBtn');
  const dropdownMenu = document.getElementById('profileDropdownMenu');

  if (!dotsBtn || !dropdownMenu) return;

  // Toggle menu
  dotsBtn.addEventListener('click', (e) => {
    e.stopPropagation(); // prevent closing immediately
    dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
  });

  // Close menu when clicking outside
  document.addEventListener('click', () => {
    dropdownMenu.style.display = 'none';
  });

  // Menu item actions
  document.getElementById('editProfileBtn').addEventListener('click', () => {
    dropdownMenu.style.display = 'none';
    document.getElementById('profileModal').style.display = 'block';
  });

  document.getElementById('becomeDirectAgent').addEventListener('click', () => {
    dropdownMenu.style.display = 'none';
    alert('Direct Agent feature coming soon!');
  });

  document.getElementById('becomeAssociateAgent').addEventListener('click', () => {
    dropdownMenu.style.display = 'none';
    alert('Associate Agent feature coming soon!');
  });

  document.getElementById('deleteAccount').addEventListener('click', () => {
    dropdownMenu.style.display = 'none';
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
      // Implement deletion logic here
      alert('Account deletion not implemented yet.');
    }
  });
});

// delete account
document.getElementById('deleteAccount').addEventListener('click', async () => {
  const dropdownMenu = document.getElementById('profileDropdownMenu');
  dropdownMenu.style.display = 'none';

  if (!confirm('Are you sure you want to delete your account? This action cannot be undone.')) return;

  try {
    const res = await fetch('/BatEstateExplorer/public/api/delete_account.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({})
    });

    const data = await res.json();

    if (data.success) {
      alert(data.message);
      window.location.href = '/BatEstateExplorer/public/index.php';
    } else {
      alert('Error: ' + data.message);
    }

  } catch (err) {
    console.error('❌ Delete account error:', err);
    alert('Failed to delete account.');
  }
});
