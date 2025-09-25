document.addEventListener("DOMContentLoaded", () => {

    // ===================================
    //  GLOBAL CONSTANTS
    // ===================================
    // This custom event is dispatched by any modal that closes and might require 
    // the saved properties list to be refreshed (e.g., saving/unsaving a property).
    const PROPERTY_RELOAD_EVENT = 'propertyModalClosed';
    
    // List of modal IDs that, when closed, require the properties list to reload.
    // NOTE: You must update 'propertyModalId' and 'reviewModalId' to match the actual IDs in your modular files.
    const MODALS_AFFECTING_PROPERTIES = ['propertyModalId', 'reviewModalId'];

    // ===================================
    //  DOM ELEMENT REFERENCES
    // ===================================

    // Modals
    const profileModal = document.getElementById("profileModal");
    const deleteModal = document.getElementById("deleteAccountModal");

    // Modal Triggers/Controls
    const editProfileBtn = document.getElementById("editProfileBtn");
    const deleteAccountBtn = document.getElementById("deleteAccount");
    const modalCloseBtn = document.getElementById("modalCloseBtn"); // Profile Modal 'X'
    const cancelEditBtn = document.getElementById("cancelEditBtn"); // Profile Modal 'Cancel'
    const cancelDeleteBtn = document.getElementById("cancelDeleteBtn"); 
    const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

    // Dropdown
    const profileDotsBtn = document.getElementById("profileDotsBtn");
    const profileDropdownMenu = document.getElementById("profileDropdownMenu");

    // Form
    const profileForm = document.getElementById("profileForm");

    // Saved Properties Section
    const savedPropertiesSection = document.querySelector(".saved-properties-section");

    // Sorting/Tabs (Kept for compatibility)
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
    //  RELOAD FUNCTIONALITY
    // ===================================

    /**
     * Fetches and replaces the content of the Saved Properties section using AJAX.
     */
    async function refreshSavedProperties() {
        if (!savedPropertiesSection) return;

        savedPropertiesSection.style.opacity = '0.5';

        try {
            const response = await fetch(window.location.href, {
                method: 'GET',
                cache: 'no-cache' // Ensure fresh data
            });
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newSavedSection = doc.querySelector(".saved-properties-section");

            if (newSavedSection) {
                savedPropertiesSection.innerHTML = newSavedSection.innerHTML;
            }
        } catch (error) {
            console.error("Failed to refresh saved properties:", error);
            notify("error", "Failed to reload properties content.");
        } finally {
            savedPropertiesSection.style.opacity = '1';
        }
    }
    
    // LISTENER: Reloads properties when the global event is fired
    document.addEventListener(PROPERTY_RELOAD_EVENT, refreshSavedProperties);


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

    document.addEventListener("click", () => {
        profileDropdownMenu?.classList.remove("active");
    });

    profileDropdownMenu?.addEventListener("click", (e) => e.stopPropagation());

    // ===================================
    //  PROFILE MODAL (EDIT) LOGIC
    // ===================================

    // Open modal
    editProfileBtn?.addEventListener("click", () => {
        profileDropdownMenu?.classList.remove("active");
        profileModal?.classList.add("active");
    });

    // Close modal using 'X' or 'Cancel' button
    [modalCloseBtn, cancelEditBtn].forEach(btn => {
        btn?.addEventListener("click", () => {
            profileModal?.classList.remove("active");
        });
    });

    // Handle form submission via AJAX (using full page reload on success to update header)
    profileForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(profileForm);
        
        try {
            const response = await fetch(profileForm.action, {
                method: 'POST',
                body: formData
            });
            const result = await response.json(); 

            if (result.success) {
                notify("success", result.message || "Profile updated successfully!");
                // Force a full page reload to refresh user details in the profile header
                // and clear notification messages from the form action.
                setTimeout(() => window.location.reload(), 500); 
            } else {
                notify("error", result.message || "Failed to update profile.");
            }
        } catch (error) {
            console.error("Profile update failed:", error);
            notify("error", "An error occurred during profile update.");
        }
    });

    // ===================================
    //  DELETE ACCOUNT MODAL LOGIC
    // ===================================

    deleteAccountBtn?.addEventListener("click", () => {
        profileDropdownMenu?.classList.remove("active");
        deleteModal.classList.add("active");
    });

    cancelDeleteBtn?.addEventListener("click", () => {
        deleteModal.classList.remove("active");
    });
    
    // ... (confirmDeleteBtn async logic remains the same)

    // ===================================
    //  GLOBAL MODAL CLOSING LOGIC (DISPATCHER)
    // ===================================

    /**
     * Handles modal closing from outside click, escape key, or internal buttons
     * for all modals, including modular ones, and dispatches the reload event 
     * if the modal affects properties.
     */
    const closeModalAndDispatch = (modal) => {
        if (!modal) return;

        // 1. Close the modal (Class-based first, then fallback to inline style)
        if (modal.classList.contains('active')) {
            modal.classList.remove('active');
        } else if (modal.style.display !== 'none') {
            modal.style.display = 'none'; // For legacy/hack modals
        }

        // 2. Dispatch event if required
        if (MODALS_AFFECTING_PROPERTIES.includes(modal.id)) {
            document.dispatchEvent(new CustomEvent(PROPERTY_RELOAD_EVENT));
        }
    }

    // Global Click Listener: Handles outside modal clicks and specific close buttons
    document.addEventListener("click", (e) => {
        const modalClicked = e.target.closest(".modal, .modal-agent");
        
        // Check for clicks on the modal background itself (for class-based modals)
        if (e.target.classList.contains('modal') || e.target.classList.contains('modal-agent')) {
            closeModalAndDispatch(e.target);
        }

        // Check for clicks on the general close button (used by all modals)
        const closeBtn = e.target.closest(".modal-close, .cancel-btn-agent");
        if (closeBtn) {
            const closingModal = closeBtn.closest(".modal, .modal-agent");
            closeModalAndDispatch(closingModal);
        }
    });

    // Global Keydown Listener: Handles Escape key press
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            // Find any currently open modal
            const activeModal = document.querySelector(".modal.active, .modal-agent.active");
            if (activeModal) {
                closeModalAndDispatch(activeModal);
            } else {
                // Check legacy modals using inline display
                document.querySelectorAll("[style*='display: flex']").forEach(modal => {
                    closeModalAndDispatch(modal);
                });
            }
        }
    });

    // ===================================
    //  INITIALIZATION
    // ===================================

    // Server-side error display
    const profileError = document.getElementById("profileError");
    if (profileError) {
        notify("error", profileError.textContent);
        profileError.remove();
    }
});