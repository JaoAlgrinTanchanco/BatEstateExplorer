(() => {
  let modalSwiper = null;
  let currentPropertyId = null;
  let saveBtn = null;

  const userToken = window.AppConfig?.userToken || "";
  const currentUserId = window.AppConfig?.userId || 0;

  // --- Centralized notification helper ---
  function notify(type, message) {
    // remove previous notifications
    const existing = document.querySelector(".central-notification");
    if (existing) existing.remove();

    const div = document.createElement("div");
    div.className = `central-notification ${type}`;
    div.innerHTML = message;
    div.style.position = "fixed";
    div.style.top = "20px";
    div.style.right = "20px";
    div.style.padding = "15px 20px";
    div.style.borderRadius = "8px";
    div.style.zIndex = 9999;
    div.style.color = "#fff";
    div.style.background = type === "success" ? "#28a745" : "#dc3545";
    document.body.appendChild(div);

    setTimeout(() => div.remove(), 4000);
  }

  async function openPropertyModal(propertyId) {
    currentPropertyId = propertyId;
    try {
      const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(propertyId)}`);
      const data = await res.json();
      if (!data.success) return notify("error", data.error || "Failed to fetch property.");

      const prop = data.property;
      const wrapper = document.getElementById("modalImageWrapper");
      wrapper.innerHTML = "";
      const images = (prop.images && prop.images.length) ? prop.images : ["/BatEstateExplorer/assets/images/bg4.jpg"];
      images.forEach(img => {
        wrapper.innerHTML += `<div class="swiper-slide"><img src="${img}" style="width:100%;border-radius:8px;"></div>`;
      });

      if (modalSwiper) modalSwiper.update();
      else modalSwiper = new Swiper(".modal-swiper", {
        loop: images.length > 1,
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
        pagination: { el: ".swiper-pagination", clickable: true }
      });

      document.getElementById("modalTitle").textContent = prop.title;
      document.getElementById("modalLocation").textContent = `📍 ${prop.location}`;
      document.getElementById("modalPrice").textContent = `₱${parseFloat(prop.price).toLocaleString()}`;
      document.getElementById("modalBedrooms").textContent = prop.bedrooms;
      document.getElementById("modalBathrooms").textContent = prop.bathrooms;
      document.getElementById("modalDescription").textContent = prop.description || "No description available.";

      const reviewBtn = document.getElementById("leaveReviewBtn");
      if (data.has_privilege) {
        reviewBtn.style.display = "inline-block";
        reviewBtn.onclick = () => openReviewModal(prop.id);
      } else {
        reviewBtn.style.display = "none";
        reviewBtn.onclick = null;
      }

      const messageBtn = document.querySelector(".message-agent-btn");
      messageBtn.dataset.agentId = prop.agent_id || "";

      try {
        const agentRes = await fetch(`/BatEstateExplorer/public/api/get_property_agent.php?property_id=${encodeURIComponent(prop.id)}`);
        const agentData = await agentRes.json();
        if (!agentData.error && agentData.agent_id) messageBtn.dataset.agentId = agentData.agent_id;
      } catch (err) {
        console.warn("Failed to fetch agent ID", err);
      }

      document.getElementById("propertyModal").style.display = "flex";
      initSaveButton();
      checkIfSaved(currentPropertyId);

    } catch (err) {
      console.error(err);
      notify("error", "Failed to load property details.");
    }
  }

  document.querySelectorAll(".view-details-btn").forEach(btn => {
    btn.addEventListener("click", () => openPropertyModal(btn.dataset.id));
  });

  document.querySelectorAll(".modal-close").forEach(btn => {
    btn.addEventListener("click", () => document.getElementById("propertyModal").style.display = "none");
  });

  document.getElementById("propertyModal")?.addEventListener("click", e => {
    if (e.target === e.currentTarget) e.currentTarget.style.display = "none";
  });

  function openReviewModal(propertyId) {
    document.getElementById("reviewPropertyId").value = propertyId;
    document.getElementById("reviewModal").style.display = "flex";
  }

  document.getElementById("reviewForm")?.addEventListener("submit", async e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
      const res = await fetch("/BatEstateExplorer/public/api/submit_review.php", { method: "POST", body: formData });
      const data = await res.json();
      if (data.success) {
        notify("success", "Review submitted successfully!");
        document.getElementById("reviewModal").style.display = "none";
        e.target.reset();
      } else {
        notify("error", data.error || "Failed to submit review.");
      }
    } catch (err) {
      console.error(err);
      notify("error", "Error submitting review.");
    }
  });

  document.querySelectorAll(".message-agent-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const agentId = btn.dataset.agentId;
      if (!agentId) return notify("error", "Agent not found.");
      window.open(`/BatEstateExplorer/public/message.php?agent_id=${agentId}`, "_blank");
    });
  });

  function initSaveButton() {
    saveBtn = document.querySelector(".modal-actions .btn-outline");
    saveBtn?.addEventListener("click", handleSaveClick);
  }

  function updateSaveButton(isSaved) {
    if (!saveBtn) return;
    saveBtn.innerHTML = isSaved ? '<i class="fas fa-heart"></i> Unsave' : '<i class="fas fa-heart"></i> Save to Favorites';
    saveBtn.dataset.saved = isSaved ? "true" : "false";
  }

  async function checkIfSaved(propertyId) {
    if (!saveBtn) return;
    try {
      const res = await fetch("/BatEstateExplorer/public/api/save_property.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `property_id=${encodeURIComponent(propertyId)}&action=check&user_token=${encodeURIComponent(userToken)}`
      });
      const data = await res.json();
      if (data.success) updateSaveButton(data.saved);
    } catch (err) {
      console.error("Error checking saved status", err);
    }
  }

  async function handleSaveClick() {
    if (!currentPropertyId || !saveBtn) return;
    const action = saveBtn.dataset.saved === "true" ? "unsave" : "save";

    try {
      const res = await fetch("/BatEstateExplorer/public/api/save_property.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `property_id=${encodeURIComponent(currentPropertyId)}&action=${action}&user_token=${encodeURIComponent(userToken)}`
      });

      const data = await res.json();

      if (data.success) {
        updateSaveButton(data.saved);

        // Only notify if it's a real change (skip "already saved")
        if (data.message && data.message !== "Property already saved.") {
          notify("success", data.message);
        }
      } else {
        notify("error", data.error || "Failed to update saved status.");
      }
    } catch (err) {
      console.error("Error updating saved status", err);
      notify("error", "Error updating saved status.");
    }
  }
  window.openPropertyModal = openPropertyModal;
})();
