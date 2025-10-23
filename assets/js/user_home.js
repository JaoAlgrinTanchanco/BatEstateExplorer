// user_home.js
(() => {
  // Quick stats interactivity
  const statItems = document.querySelectorAll(".quick-stats .stat-item");
  statItems.forEach(item => {
    item.addEventListener("click", () => {
      item.classList.toggle("active");
    });
  });

  // Home page buttons
  const searchBtn = document.querySelector(".action-buttons .btn-primary");
  if (searchBtn) {
    searchBtn.addEventListener("click", () => {
      window.location.href = "/BatEstateExplorer/public/user_dashboard.php?view=search";
    });
  }

  const profileBtn = document.querySelector(".action-buttons .btn-secondary");
  if (profileBtn) {
    profileBtn.addEventListener("click", () => {
      window.location.href = "/BatEstateExplorer/public/user_dashboard.php?view=profile";
    });
  }

  // Optional: delegation for property grid click events (for other purposes)
  const propertiesGrid = document.querySelector(".properties-grid");
  if (propertiesGrid) {
    propertiesGrid.addEventListener("click", e => {
      // This no longer opens modal; just dispatch event if needed
      const btn = e.target.closest(".view-details-btn");
      if (!btn) return;
      const propertyId = btn.dataset.id;
      window.dispatchEvent(new CustomEvent("property:clicked", { detail: { propertyId } }));
    });
  }
})();
