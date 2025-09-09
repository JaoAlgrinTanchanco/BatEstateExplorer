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
  const profileModal = document.getElementById('profileModal');
  const modalCloseBtn = document.getElementById('modalCloseBtn');

  if (dotsBtn && dropdownMenu) {
    // Toggle menu
    dotsBtn.addEventListener('click', (e) => {
      e.stopPropagation(); // prevent closing immediately
      dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
    });

    // Close menu when clicking outside
    document.addEventListener('click', () => {
      dropdownMenu.style.display = 'none';
    });

    // Prevent closing when clicking inside menu
    dropdownMenu.addEventListener('click', (e) => {
      e.stopPropagation();
    });

    // Menu item actions
    document.getElementById('editProfileBtn').addEventListener('click', () => {
      dropdownMenu.style.display = 'none';
      profileModal.style.display = 'block';
    });

    const directBtn = document.getElementById('becomeDirectAgent');
    if (directBtn) {
      directBtn.addEventListener('click', () => {
        dropdownMenu.style.display = 'none';
      });
    }

    const associateBtn = document.getElementById('becomeAssociateAgent');
    if (associateBtn) {
      associateBtn.addEventListener('click', () => {
        dropdownMenu.style.display = 'none';
      });
    }
  }

  /** ----------------------
   * Profile Modal Close
   * ---------------------- */
  if (profileModal && modalCloseBtn) {
    // Close when clicking "X"
    modalCloseBtn.addEventListener('click', () => {
      profileModal.style.display = 'none';
    });

    // Close when clicking outside modal content
    profileModal.addEventListener('click', (e) => {
      if (e.target === profileModal) {
        profileModal.style.display = 'none';
      }
    });
  }
});


// Delete Account Modal
const deleteAccountBtn = document.getElementById('deleteAccount');
const deleteModal = document.getElementById('deleteAccountModal');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const deleteLoading = document.getElementById('deleteLoading');
const deleteModalActions = document.getElementById('deleteModalActions');
const deleteModalMessage = document.getElementById('deleteModalMessage');

if (deleteAccountBtn && deleteModal) {
  // Show modal
  deleteAccountBtn.addEventListener('click', () => {
    const dropdownMenu = document.getElementById('profileDropdownMenu');
    if (dropdownMenu) dropdownMenu.style.display = 'none';
    deleteModal.style.display = 'flex';
  });

  // Cancel
  cancelDeleteBtn.addEventListener('click', () => {
    deleteModal.style.display = 'none';
  });

  // Confirm deletion
  confirmDeleteBtn.addEventListener('click', async () => {
    // Switch to loading state
    deleteModalMessage.textContent = "Please wait while we delete your account...";
    deleteModalActions.style.display = 'none';
    deleteLoading.style.display = 'block';

    try {
      const res = await fetch('/BatEstateExplorer/public/api/delete_account.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({})
      });

      const data = await res.json();

      if (data.success) {
        // Redirect after a short delay
        setTimeout(() => {
          window.location.href = '/BatEstateExplorer/auth/login.php';
        }, 1000);
      } else {
        deleteModalMessage.textContent = "Error deleting account: " + data.message;
        deleteLoading.style.display = 'none';
      }
    } catch (err) {
      console.error('❌ Delete account error:', err);
      deleteModalMessage.textContent = "Something went wrong. Please try again.";
      deleteLoading.style.display = 'none';
    }
  });
}
