let modalSwiper;
let currentPropertyId = null;

// ✅ Use token & userId from global config
const userToken = window.AppConfig?.userToken || "";
const currentUserId = window.AppConfig?.userId || 0;

// --- Helper to open property modal ---
async function openPropertyModal(propertyId) {
  currentPropertyId = propertyId;
  try {
    const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(propertyId)}`);
    const data = await res.json();
    if (!data.success) return alert(data.error || "Failed to fetch property.");

    const prop = data.property;

    // Populate images
    const wrapper = document.getElementById("modalImageWrapper");
    wrapper.innerHTML = "";
    const images = (prop.images && prop.images.length) ? prop.images : ["/BatEstateExplorer/assets/images/bg4.jpg"];
    images.forEach(img => {
      wrapper.innerHTML += `<div class="swiper-slide"><img src="${img}" style="width:100%;border-radius:8px;"></div>`;
    });

    // Init/update Swiper
    if (modalSwiper) modalSwiper.update();
    else modalSwiper = new Swiper(".modal-swiper", {
      loop: images.length > 1,
      navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
      pagination: { el: ".swiper-pagination", clickable: true },
    });

    // Fill details
    document.getElementById("modalTitle").textContent = prop.title;
    document.getElementById("modalLocation").textContent = `📍 ${prop.location}`;
    document.getElementById("modalPrice").textContent = `₱${parseFloat(prop.price).toLocaleString()}`;
    document.getElementById("modalBedrooms").textContent = prop.bedrooms;
    document.getElementById("modalBathrooms").textContent = prop.bathrooms;
    document.getElementById("modalDescription").textContent = prop.description || "No description available.";

    // Review button
    const reviewBtn = document.getElementById("leaveReviewBtn");
    if (data.has_privilege) {
      reviewBtn.style.display = "inline-block";
      reviewBtn.onclick = () => openReviewModal(prop.id);
    } else {
      reviewBtn.style.display = "none";
      reviewBtn.onclick = null;
    }

    // Agent button
    const messageBtn = document.querySelector(".message-agent-btn");
    messageBtn.dataset.agentId = prop.agent_id;
    try {
      const agentRes = await fetch(`/BatEstateExplorer/public/api/get_property_agent.php?property_id=${encodeURIComponent(prop.id)}`);
      const agentData = await agentRes.json();
      if (!agentData.error && agentData.agent_id) messageBtn.dataset.agentId = agentData.agent_id;
    } catch (err) {
      console.warn("Failed to fetch corrected agent ID, using legacy one.", err);
    }

    // Show modal
    document.getElementById("propertyModal").style.display = "flex";

    // Check if saved
    checkIfSaved(currentPropertyId);

  } catch (err) {
    console.error(err);
    alert("Failed to load property details.");
  }
}

// --- Event Delegation for View Details Buttons ---
document.querySelector("#saved-list .property-grid")?.addEventListener("click", e => {
  const btn = e.target.closest(".view-details-btn");
  if (!btn) return;
  openPropertyModal(btn.dataset.id);
});

// --- Modal close ---
document.querySelectorAll(".modal-close").forEach(btn => {
  btn.addEventListener("click", () => document.getElementById("propertyModal").style.display = "none");
});
document.getElementById("propertyModal")?.addEventListener("click", e => {
  if (e.target === e.currentTarget) e.currentTarget.style.display = "none";
});

// --- Review Modal ---
function openReviewModal(propertyId) {
  document.getElementById("reviewPropertyId").value = propertyId;
  document.getElementById("reviewModal").style.display = "flex";
}

// --- Submit review ---
document.getElementById("reviewForm")?.addEventListener("submit", async e => {
  e.preventDefault();
  const formData = new FormData(e.target);
  try {
    const res = await fetch("/BatEstateExplorer/public/api/submit_review.php", { method: "POST", body: formData });
    const data = await res.json();
    if (data.success) {
      alert("Review submitted successfully!");
      document.getElementById("reviewModal").style.display = "none";
      e.target.reset();
    } else alert(data.error || "Failed to submit review.");
  } catch (err) {
    console.error(err);
    alert("Error submitting review.");
  }
});

// --- Message Agent ---
document.querySelector(".message-agent-btn")?.addEventListener("click", e => {
  const agentId = e.currentTarget.dataset.agentId;
  if (!agentId) return alert("Agent not found.");
  window.open(`/BatEstateExplorer/public/message.php?agent_id=${agentId}`, "_blank");
});

// --- Save / Unsave ---
const saveBtn = document.querySelector(".modal-actions .btn-outline");
function updateSaveButton(isSaved) {
  if (isSaved) {
    saveBtn.innerHTML = '<i class="fas fa-heart"></i> Unsave';
    saveBtn.dataset.saved = "true";
  } else {
    saveBtn.innerHTML = '<i class="fas fa-heart"></i> Save to Favorites';
    saveBtn.dataset.saved = "false";
  }
}

async function checkIfSaved(propertyId) {
  try {
    const res = await fetch("/BatEstateExplorer/public/api/save_property.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `property_id=${encodeURIComponent(propertyId)}&action=check&user_token=${encodeURIComponent(userToken)}`
    });
    const data = await res.json();
    if (data.success) updateSaveButton(data.saved);
  } catch (err) { console.error("Error checking saved status", err); }
}

// Handle save/unsave click
saveBtn?.addEventListener("click", async () => {
  if (!currentPropertyId) return alert("Property not selected.");
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
      alert(data.message);

      // 🔔 Dispatch event for other parts of the app
      window.dispatchEvent(new CustomEvent("favorites:changed", { detail: { propertyId: String(currentPropertyId), action } }));

      // ✅ Handle adding the property back to the list if saved
      const grid = document.querySelector("#saved-list .property-grid");
      if (action === "save" && grid) {
        // Fetch property details to render new card
        const propRes = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(currentPropertyId)}`);
        const propData = await propRes.json();
        if (propData.success) {
          const prop = propData.property;
          const cardHTML = `
            <div class="property-card" data-price="${prop.price}" data-date="${new Date(prop.created_at).getTime()}">
              <div class="property-image">
                <img src="${prop.images?.[0] || '/BatEstateExplorer/assets/images/bg4.jpg'}" alt="Property Image">
              </div>
              <div class="property-info">
                <h3>${prop.title}</h3>
                <p>${prop.location}</p>
                <p>₱${parseFloat(prop.price).toLocaleString()}</p>
                <button class="view-details-btn" data-id="${prop.id}">View Details</button>
              </div>
            </div>
          `;
          grid.insertAdjacentHTML("afterbegin", cardHTML);
        }
      }

    } else {
      alert(data.error || "Failed to update saved status.");
    }
  } catch (err) {
    console.error("Error updating saved status", err);
    alert("Error updating saved status.");
  }
});
