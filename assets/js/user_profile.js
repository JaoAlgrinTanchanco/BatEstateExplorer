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

  profileDotsBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    profileDropdownMenu.classList.toggle("active");
  });

  document.addEventListener("click", (e) => {
    // Exclude profile dropdown, its button, and review cards
    if (!profileDropdownMenu.contains(e.target) &&
        e.target !== profileDotsBtn &&
        !e.target.closest(".user-review-card")) {
        profileDropdownMenu.classList.remove("active");
      }
  });

  // --- Modal ---
  const editProfileBtn = document.getElementById("editProfileBtn");
  const profileModal = document.getElementById("profileModal");
  const modalCloseBtn = document.getElementById("modalCloseBtn");

  if (editProfileBtn) {
    editProfileBtn.addEventListener("click", () => {
      profileDropdownMenu.classList.remove("active");
      profileModal.classList.add("active");
    });
  }

  if (modalCloseBtn) {
    modalCloseBtn.addEventListener("click", () => {
      profileModal.classList.remove("active");
    });
  }

  if (profileModal) {
    profileModal.addEventListener("click", (e) => {
      if (e.target === profileModal) {
        profileModal.classList.remove("active");
      }
    });
  }

  // --- Logout ---
  window.logout = function () {
    if (confirm("Are you sure you want to logout?")) {
      window.location.href = "logout.php";
    }
  };
});

// Keep Saved List in sync when a property is unsaved from the modal
window.addEventListener("favorites:changed", (e) => {
  const { propertyId, action } = e.detail || {};
  if (action !== "unsave") return;

  const grid = document.querySelector("#saved-list .property-grid");
  if (!grid) return;

  // Find the card by the button inside it
  const btn = grid.querySelector(`.view-details-btn[data-id="${propertyId}"]`);
  const card = btn ? btn.closest(".property-card") : null;

  if (card) {
    card.remove();
  }

  // If nothing left, show empty state
  if (!grid.querySelector(".property-card")) {
    grid.innerHTML = "<p>You have no saved properties yet.</p>";
  }
});

// Attach click handlers to user review cards
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".user-review-card").forEach(card => {
        card.addEventListener("click", () => {
            const propertyId = card.dataset.propertyId;
            if (!propertyId) return;
            openPropertyModal(propertyId);
        });
    });
});
