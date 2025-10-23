document.addEventListener("DOMContentLoaded", () => {

    // ===================================
    //  DOM ELEMENT REFERENCES
    // ===================================

    // Modals
    const profileModal = document.getElementById("profileModal");
    const deleteModal = document.getElementById("deleteAccountModal");

    // Modal Triggers/Controls
    const editProfileBtn = document.getElementById("editProfileBtn");
    const deleteAccountBtn = document.getElementById("deleteAccount");
    const modalCloseBtn = document.getElementById("modalCloseBtn");
    const cancelEditBtn = document.getElementById("cancelEditBtn");
    const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");
    const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

    // Dropdown
    const profileDotsBtn = document.getElementById("profileDotsBtn");
    const profileDropdownMenu = document.getElementById("profileDropdownMenu");

    // Sorting/Tabs
    const tabButtons = document.querySelectorAll(".tab-btn");
    const tabContents = document.querySelectorAll(".tab-content");
    const sortSelect = document.getElementById("sortSelect");

    // Delete Modal Actions
    const deleteLoading = document.getElementById("deleteLoading");
    const deleteModalActions = document.getElementById("deleteModalActions");
    const deleteModalMessage = document.getElementById("deleteModalMessage");

    // ===================================
    //  NOTIFICATION HELPER
    // ===================================

    function notify(type, message) {
        let container = document.querySelector(".notification-container");
        if (!container) {
            container = document.createElement("div");
            container.className = "notification-container";
            document.body.appendChild(container);
        }

        const notif = document.createElement("div");
        notif.className = `notification ${type}`;
        notif.textContent = message;
        container.appendChild(notif);

        setTimeout(() => notif.remove(), 4000);
    }

    // ===================================
    //  TABS & SORTING
    // ===================================

    tabButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
            tabButtons.forEach((b) => b.classList.remove("active"));
            btn.classList.add("active");

            const tab = btn.dataset.tab;
            tabContents.forEach((tc) => tc.classList.toggle("active", tc.id === tab));
            if (sortSelect) sortSelect.value = "date";
        });
    });

    sortSelect?.addEventListener("change", () => {
        const sortBy = sortSelect.value;
        const container = document.querySelector("#saved-list .property-grid");
        if (!container) return;

        const cards = Array.from(container.querySelectorAll(".property-card"));
        cards.sort((a, b) => {
            if (sortBy === "price") return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            return parseInt(b.dataset.date) - parseInt(a.dataset.date);
        });
        cards.forEach((card) => container.appendChild(card));
    });

    // ===================================
    //  PROFILE DROPDOWN
    // ===================================

    profileDotsBtn?.addEventListener("click", (e) => {
        e.stopPropagation();
        profileDropdownMenu?.classList.toggle("active");
    });

    // Close dropdown when clicking anywhere else
    document.addEventListener("click", () => {
        profileDropdownMenu?.classList.remove("active");
    });

    // Prevent dropdown from closing when clicking inside it
    profileDropdownMenu?.addEventListener("click", (e) => e.stopPropagation());

    // ===================================
    //  PROFILE MODAL (EDIT) LOGIC
    // ===================================

    // Open modal from dropdown menu item
    editProfileBtn?.addEventListener("click", () => {
        profileDropdownMenu?.classList.remove("active");
        profileModal?.classList.add("active");
    });

    // Close modal using the 'X' button
    modalCloseBtn?.addEventListener("click", () => profileModal?.classList.remove("active"));

    // Close modal using the 'Cancel' button
    cancelEditBtn?.addEventListener("click", () => profileModal?.classList.remove("active"));


    // ===================================
    //  DELETE ACCOUNT MODAL LOGIC (FIXED: CONSISTENT CLASS USE)
    // ===================================

    deleteAccountBtn?.addEventListener("click", () => {
        profileDropdownMenu?.classList.remove("active");
        // FIX: Use classList.add('active') instead of style.display = "flex"
        deleteModal.classList.add("active");
    });

    cancelDeleteBtn?.addEventListener("click", () => {
        // FIX: Use classList.remove('active') instead of style.display = "none"
        deleteModal.classList.remove("active");
    });

    confirmDeleteBtn?.addEventListener("click", async () => {
        deleteModalMessage.textContent = "Deleting your account...";
        deleteModalActions.style.display = "none";
        deleteLoading.style.display = "block";

        try {
            const res = await fetch("/BatEstateExplorer/public/api/delete_account.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({}),
            });
            const data = await res.json();
            
            // Re-show buttons on failure, then notify
            const resetDeleteModal = (message) => {
                notify("error", message);
                deleteLoading.style.display = "none";
                deleteModalActions.style.display = "flex";
                deleteModalMessage.textContent = "Are you sure you want to delete your account? This action cannot be undone.";
            };

            if (data.success) {
                notify("success", "Account deleted successfully. Redirecting...");
                setTimeout(() => (window.location.href = "/BatEstateExplorer/auth/login.php"), 1200);
            } else {
                resetDeleteModal(data.message || "Failed to delete account.");
            }
        } catch (err) {
            console.error(err);
            resetDeleteModal("Something went wrong. Please try again.");
        }
    });

    // ===================================
    //  GLOBAL HANDLERS
    // ===================================

    // Server-side error display
    const profileError = document.getElementById("profileError");
    if (profileError) {
        notify("error", profileError.textContent);
        profileError.remove();
    }

    // Close modals on Escape key
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            // Remove 'active' class from modals using it
            if (profileModal) profileModal.classList.remove("active");
            if (deleteModal) deleteModal.classList.remove("active");
            
            // HACK: Logic to close other modals that might still rely on inline styles
            ["propertyModal", "reviewModal"].forEach((id) => {
                const modal = document.getElementById(id);
                if (modal) modal.style.display = "none";
            });
        }
    });
    
    // HACK: Logic to handle modals still using .modal-close and style.display
    document.addEventListener("click", (e) => {
        const closeBtn = e.target.closest(".modal-close");
        if (closeBtn) {
            const modal = closeBtn.closest(".modal");
            // If the modal exists and is NOT the profileModal (which uses active class)
            // AND the modal still relies on inline style (which is inconsistent), hide it.
            if (modal && modal.id !== 'profileModal') {
                modal.style.display = "none";
            }
        }
    });
});