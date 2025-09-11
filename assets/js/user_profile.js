document.addEventListener("DOMContentLoaded", () => {
  
  // --- Tabs ---
  const tabButtons = document.querySelectorAll(".tab-btn");
  const tabContents = document.querySelectorAll(".tab-content");
  const sortSelect = document.getElementById("sortSelect");

  tabButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      tabButtons.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      const tab = btn.dataset.tab;
      tabContents.forEach((tc) => tc.classList.toggle("active", tc.id === tab));

      if (sortSelect) sortSelect.value = "date";
    });
  });

  // --- Sorting ---
  sortSelect?.addEventListener("change", () => {
    const sortBy = sortSelect.value;
    const container = document.querySelector("#saved-list .property-grid");
    if (!container) return;

    const cards = Array.from(container.querySelectorAll(".property-card"));

    cards.sort((a, b) => {
      if (sortBy === "price") {
        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
      } else {
        return parseInt(b.dataset.date) - parseInt(a.dataset.date);
      }
    });

    cards.forEach((card) => container.appendChild(card));
  });

  // --- Profile Dropdown ---
  const profileDotsBtn = document.getElementById("profileDotsBtn");
  const profileDropdownMenu = document.getElementById("profileDropdownMenu");

  profileDotsBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    profileDropdownMenu?.classList.toggle("active");
  });

  document.addEventListener("click", () => profileDropdownMenu?.classList.remove("active"));
  profileDropdownMenu?.addEventListener("click", (e) => e.stopPropagation());

  // --- Profile Modal ---
  const editProfileBtn = document.getElementById("editProfileBtn");
  const profileModal = document.getElementById("profileModal");
  const modalCloseBtn = document.getElementById("modalCloseBtn");

  editProfileBtn?.addEventListener("click", () => {
    profileDropdownMenu?.classList.remove("active");
    profileModal?.classList.add("active");
  });

  modalCloseBtn?.addEventListener("click", () => profileModal?.classList.remove("active"));
  profileModal?.addEventListener("click", (e) => {
    if (e.target === profileModal) profileModal?.classList.remove("active");
  });

  // --- Delete Account ---
  const deleteAccountBtn = document.getElementById('deleteAccount');
  const deleteModal = document.getElementById('deleteAccountModal');
  const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
  const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  const deleteLoading = document.getElementById('deleteLoading');
  const deleteModalActions = document.getElementById('deleteModalActions');
  const deleteModalMessage = document.getElementById('deleteModalMessage');

  deleteAccountBtn?.addEventListener('click', () => {
    profileDropdownMenu?.classList.remove("active");
    deleteModal.style.display = 'flex';
  });

  cancelDeleteBtn?.addEventListener('click', () => {
    deleteModal.style.display = 'none';
  });

  confirmDeleteBtn?.addEventListener('click', async () => {
    deleteModalMessage.textContent = "Deleting your account...";
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
        notify('success', "Account deleted successfully. Redirecting...");
        setTimeout(() => window.location.href = '/BatEstateExplorer/auth/login.php', 1200);
      } else {
        notify('error', data.message || "Failed to delete account.");
        deleteLoading.style.display = 'none';
        deleteModalActions.style.display = 'flex';
      }
    } catch (err) {
      console.error('❌ Delete account error:', err);
      notify('error', "Something went wrong. Please try again.");
      deleteLoading.style.display = 'none';
      deleteModalActions.style.display = 'flex';
    }
  });

// --- Show server-side error if present ---
const profileError = document.getElementById("profileError");
if (profileError) {
  notify('error', profileError.textContent);
  profileError.remove();
}

});
