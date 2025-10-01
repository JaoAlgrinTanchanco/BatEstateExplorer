// Notification helper
function notify(type, message) {
  let container = document.querySelector(".notification-container");
  if (!container) {
    container = document.createElement("div");
    container.className = "notification-container";
    document.body.appendChild(container);
  }

  const notif = document.createElement("div");
  notif.className = `notification ${type}`;
  notif.innerHTML = `
    <div class="notification__title">${message}</div>
    <div class="notification__close">&times;</div>
  `;
  container.appendChild(notif);

  notif.querySelector(".notification__close").addEventListener("click", () => notif.remove());
  setTimeout(() => notif.remove(), 5000);
}

// Renders the star rating HTML
function renderStars(rating) {
  let stars = "";
  for (let i = 1; i <= 5; i++) {
    stars += `<i class="fas fa-star ${i <= rating ? "filled" : ""}"></i>`;
  }
  return `<div class="review-stars">${stars}</div>`;
}

// Property and Review Modals Logic
(() => {
  let currentPropertyId = null;
  let selectedRating = 0;

  // Open Property Modal
  async function openPropertyModal(propertyId) {
    currentPropertyId = propertyId;
    const modal = document.getElementById("propertyModal");
    if (!modal) return;

    const reviewContainer = modal.querySelector("#modalPastReviews");
    const reviewBtn = modal.querySelector("#leaveReviewBtn");

    try {
      // Fetch property details
      const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(propertyId)}`);
      const data = await res.json();
      if (!data.success) {
        // Remove debugging line
        return;
      }

      const prop = data.property;
      const images = prop.images.length ? prop.images : ["/BatEstateExplorer/assets/images/bg4.jpg"];
      modal.dataset.id = prop.id;

      // Set Left Column content
      modal.querySelector(".property-main-image").style.backgroundImage = `url('${images[0]}')`;
      modal.querySelector(".property-name").textContent = prop.title || "No title";

      const thumbs = modal.querySelector(".property-images");
      thumbs.innerHTML = images.map((img, i) =>
        `<img src="${img}" alt="Property image" ${i === 0 ? "class='active'" : ""}>`
      ).join("");

      // Image gallery click handler
      thumbs.querySelectorAll("img").forEach(imgEl => {
        imgEl.addEventListener("click", () => {
          modal.querySelector(".property-main-image").style.backgroundImage = `url('${imgEl.src}')`;
          thumbs.querySelectorAll("img").forEach(i => i.classList.remove("active"));
          imgEl.classList.add("active");
        });
      });

      // Set Right Column content
      modal.querySelector(".location").textContent = prop.location || "-";
      modal.querySelector(".price").textContent = `₱${parseFloat(prop.price || 0).toLocaleString()}`;
      modal.querySelector(".property-type").textContent = prop.property_type || "-";
      modal.querySelector(".bedrooms").textContent = prop.bedrooms ?? "-";
      modal.querySelector(".bathrooms").textContent = prop.bathrooms ?? "-";
      modal.querySelector(".sqm").textContent = prop.sqm ?? "-";
      modal.querySelector(".lot_size").textContent = prop.lot_size ?? "-";
      modal.querySelector(".status").textContent = prop.status || "-";
      modal.querySelector(".date_uploaded").textContent = prop.created_at ? new Date(prop.created_at).toLocaleDateString() : "-";
      modal.querySelector(".property-description").textContent = prop.description || "No description available.";

      // Review Button visibility
      if (reviewBtn) {
        if (data.has_privilege) {
          reviewBtn.style.display = "inline-flex";
          reviewBtn.dataset.propertyId = prop.id;
        } else {
          reviewBtn.style.display = "none";
          reviewBtn.dataset.propertyId = "";
        }
      }

      // Show modal
      modal.hidden = false;
      modal.style.display = "flex";

      // Fetch Past Reviews
      if (reviewContainer) {
        const reviewRes = await fetch(`/BatEstateExplorer/public/api/get_reviews.php?property_id=${prop.id}`);
        const reviewData = await reviewRes.json();

        if (reviewData.success && reviewData.reviews.length > 0) {
          reviewContainer.innerHTML = reviewData.reviews.map(r => `
            <div class="review-card" style="margin-bottom:10px;">
              <strong>${r.user_name}</strong>
              <div style="float:right;">${renderStars(r.rating)}</div>
              <p>${r.review_text}</p>
              <small>${new Date(r.created_at).toLocaleDateString()}</small>
            </div>
          `).join("");
        } else {
          reviewContainer.innerHTML = `<p>No reviews yet.</p>`;
        }
      }

    } catch (err) {
      // Remove debugging line
    }
  }

  // Open Review Modal
  function openReviewModal(propertyId) {
    const reviewModal = document.getElementById("reviewModal");
    if (!reviewModal) return;
    document.getElementById("reviewPropertyId").value = propertyId;
    selectedRating = 0;
    reviewModal.hidden = false;
    reviewModal.style.display = "flex";

    // Reset stars and textarea
    reviewModal.querySelectorAll(".rating-stars span").forEach(star => star.classList.remove("selected"));
    reviewModal.querySelector("textarea[name='review_text']").value = "";
  }

  // Close Modal Helper
  function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    modal.style.display = "none";
  }

  // Star Rating Interaction (Mouseover/Out/Click)
  document.addEventListener("mouseover", e => {
    if (!e.target.matches(".rating-stars span")) return;
    const stars = Array.from(e.target.parentNode.children);
    const idx = stars.indexOf(e.target);
    stars.forEach((s, i) => s.classList.toggle("selected", i <= idx));
  });

  document.addEventListener("click", e => {
    if (!e.target.matches(".rating-stars span")) return;
    const stars = Array.from(e.target.parentNode.children);
    const idx = stars.indexOf(e.target);
    selectedRating = idx + 1;
    stars.forEach((s, i) => s.classList.toggle("selected", i <= idx));
  });

  document.addEventListener("mouseout", e => {
    if (!e.target.matches(".rating-stars span")) return;
    const stars = Array.from(e.target.parentNode.children);
    stars.forEach((s, i) => s.classList.toggle("selected", i < selectedRating));
  });

  // Submit Review Form
  const reviewForm = document.getElementById("postReviewForm");

  if (reviewForm) {
    reviewForm.addEventListener("submit", async e => {
      e.preventDefault();
      const propertyId = parseInt(document.getElementById("reviewPropertyId").value);
      const reviewText = reviewForm.review_text.value.trim();

      if (!selectedRating) return alert("Please select a rating.");
      if (!reviewText) return alert("Please write a review.");

      try {
        const res = await fetch("/BatEstateExplorer/public/api/submit_review.php", {
          method: "POST",
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            property_id: propertyId,
            rating: selectedRating,
            review_text: reviewText
          })
        });
        const data = await res.json();

        if (data.error) return alert(data.error);

        // Fetch all reviews again to update property modal
        const reviewRes = await fetch(`/BatEstateExplorer/public/api/get_reviews.php?property_id=${propertyId}`);
        const reviewData = await reviewRes.json();

        const reviewContainer = document.querySelector("#modalPastReviews");
        if (reviewContainer) {
          if (reviewData.success && reviewData.reviews.length > 0) {
            reviewContainer.innerHTML = reviewData.reviews.map(r => `
              <div class="review-card" style="margin-bottom:10px;">
                <strong>${r.user_name}</strong>
                <div style="float:right;">${renderStars(r.rating)}</div>
                <p>${r.review_text}</p>
                <small>${new Date(r.created_at).toLocaleDateString()}</small>
              </div>
            `).join("");
          } else {
            reviewContainer.innerHTML = `<p>No reviews yet.</p>`;
          }
        }

        alert("Review submitted successfully!");
        closeModal(document.getElementById("reviewModal"));
        selectedRating = 0;
        reviewForm.review_text.value = "";

      } catch (err) {
        // Remove debugging line
        alert("Failed to submit review.");
      }
    });
  }

  // Central Event Delegation for Modals and Buttons
  document.addEventListener("click", e => {
    const card = e.target.closest(".property-card");
    const reviewBtn = e.target.closest("[id='leaveReviewBtn']");
    const closeBtn = e.target.closest(".custom-modal .close");

    // Open property modal from card click
    if (card && !e.target.closest(".modal")) return openPropertyModal(card.dataset.id);

    // Open review modal
    if (reviewBtn) {
      const propertyId = reviewBtn.dataset.propertyId || currentPropertyId;
      return openReviewModal(propertyId);
    }

    // Close modal via 'x' button
    if (closeBtn) return closeModal(closeBtn.closest(".custom-modal"));

    // Close modal by clicking outside content (backdrop)
    if (e.target.id === "propertyModal") closeModal(e.target);
    if (e.target.id === "reviewModal") closeModal(e.target);
  });

  // Close Review Modal via 'modal-close' button
  document.addEventListener("click", e => {
    if (e.target.classList.contains("modal-close")) {
      const modal = e.target.closest(".modal");
      if (modal) closeModal(modal);
    }
  });

  // Expose functions globally for external calls
  window.openPropertyModal = openPropertyModal;
  window.openReviewModal = openReviewModal;

})();

// Favorite Property Logic
(() => {
  let currentPropertyId = null;
  const saveBtn = document.getElementById("saveFavoriteBtn");

  if (!saveBtn) return;

  // Update button appearance (Saved/Save)
  function updateSaveButton(saved) {
    if (saved) {
      saveBtn.classList.add("saved");
      saveBtn.innerHTML = `<i class="fas fa-heart"></i> Saved`;
    } else {
      saveBtn.classList.remove("saved");
      saveBtn.innerHTML = `<i class="fas fa-heart"></i> Save`;
    }
  }

  // Check if property is already saved
  async function checkSaved(propertyId) {
    try {
      const formData = new FormData();
      formData.append("property_id", propertyId);
      formData.append("action", "check");

      const res = await fetch("/BatEstateExplorer/public/api/save_property.php", {
        method: "POST",
        body: formData,
        credentials: "include"
      });
      const data = await res.json();
      if (data.success) updateSaveButton(data.saved);
    } catch (err) {
      // Remove debugging line
    }
  }

  // Toggle save/unsave status
  async function toggleSave() {
    if (!currentPropertyId) return;

    try {
      const action = saveBtn.classList.contains("saved") ? "unsave" : "save";
      const formData = new FormData();
      formData.append("property_id", currentPropertyId);
      formData.append("action", action);

      const res = await fetch("/BatEstateExplorer/public/api/save_property.php", {
        method: "POST",
        body: formData,
        credentials: "include"
      });
      const data = await res.json();

      if (data.success) {
        updateSaveButton(data.saved);
        notify("success", data.message || (data.saved ? "Property saved!" : "Removed from saved list."));
      } else {
        notify("error", data.error || "Failed to update saved status.");
      }
    } catch (err) {
      // Remove debugging line
      notify("error", "Failed to update saved status.");
    }
  }

  saveBtn.addEventListener("click", toggleSave);

  // Integrate with property modal (check saved status when modal opens)
  const observer = new MutationObserver(() => {
    const modal = document.getElementById("propertyModal");
    if (modal && modal.style.display !== "none") {
      const propId = modal.querySelector(".property-card, .modal-right")?.dataset?.id
                     || window.currentPropertyId; // Fallback to global exposed ID
      if (propId) {
        currentPropertyId = parseInt(propId);
        checkSaved(currentPropertyId);
      }
    }
  });

  observer.observe(document.body, { childList: true, subtree: true });

  // Update button when opening property card from main list
  document.addEventListener("click", e => {
    const card = e.target.closest(".property-card");
    if (card) {
      currentPropertyId = parseInt(card.dataset.id);
      checkSaved(currentPropertyId);
    }
  });
})();

// Message Agent Logic
(() => {
  document.addEventListener("click", async (e) => {
    const messageBtn = e.target.closest(".message-agent-btn");
    if (!messageBtn) return;

    e.preventDefault();

    const modal = document.getElementById("propertyModal");
    const propertyId = modal?.dataset.id;
    if (!propertyId) {
      notify("error", "Property ID not found.");
      return;
    }

    try {
      const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(propertyId)}`);
      const data = await res.json();

      if (!data.success) {
        notify("error", data.error || "Failed to fetch property details.");
        return;
      }

      const agentId = data.property?.agent_id;
      if (!agentId) {
        notify("error", "Agent not found for this property.");
        return;
      }

      // Open the message page in a new tab
      const url = `/BatEstateExplorer/public/message.php?agent_id=${agentId}`;
      window.open(url, "_blank");

    } catch (err) {
      // Remove debugging line
      notify("error", "Failed to fetch property details.");
    }
  });

})();