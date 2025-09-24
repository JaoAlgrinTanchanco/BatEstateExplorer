  // ===== Notification helper =====
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

  function renderStars(rating) {
    let stars = "";
    for (let i = 1; i <= 5; i++) {
      stars += `<i class="fas fa-star ${i <= rating ? "filled" : ""}"></i>`;
    }
    return `<div class="review-stars">${stars}</div>`;
  }

(() => {
  let currentPropertyId = null;
  let selectedRating = 0;

  // ===== Card hover auto-swipe =====
  document.querySelectorAll(".property-card").forEach(card => {
    const images = JSON.parse(card.dataset.images || "[]");
    if (images.length < 2) return;

    const imgEl = card.querySelector(".property-image img");
    let index = 0, interval = null;

    card.addEventListener("mouseenter", () => {
      interval = setInterval(() => {
        index = (index + 1) % images.length;
        imgEl.src = images[index];
      }, 1500);
    });

    card.addEventListener("mouseleave", () => {
      clearInterval(interval);
      imgEl.src = images[0];
      index = 0;
    });
  });

  // ===== Open Property Modal =====
  async function openPropertyModal(propertyId) {
    currentPropertyId = propertyId;
    const modal = document.getElementById("propertyModal");
    if (!modal) return;

    const reviewContainer = modal.querySelector("#modalPastReviews");
    const reviewBtn = modal.querySelector("#leaveReviewBtn");

    try {
      // --- Fetch property details ---
      const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(propertyId)}`);
      const data = await res.json();
      if (!data.success) {
        console.error(data.error || "Failed to fetch property.");
        return;
      }

      const prop = data.property;
      const images = prop.images.length ? prop.images : ["/BatEstateExplorer/assets/images/bg4.jpg"];
      modal.dataset.id = prop.id;

      // --- Left Column ---
      modal.querySelector(".property-main-image").style.backgroundImage = `url('${images[0]}')`;
      modal.querySelector(".property-name").textContent = prop.title || "No title";

      const thumbs = modal.querySelector(".property-images");
      thumbs.innerHTML = images.map((img, i) =>
        `<img src="${img}" alt="Property image" ${i === 0 ? "class='active'" : ""}>`
      ).join("");

      thumbs.querySelectorAll("img").forEach(imgEl => {
        imgEl.addEventListener("click", () => {
          modal.querySelector(".property-main-image").style.backgroundImage = `url('${imgEl.src}')`;
          thumbs.querySelectorAll("img").forEach(i => i.classList.remove("active"));
          imgEl.classList.add("active");
        });
      });

      // --- Right Column ---
      modal.querySelector(".location").textContent      = prop.location || "-";
      modal.querySelector(".price").textContent         = `₱${parseFloat(prop.price || 0).toLocaleString()}`;
      modal.querySelector(".property-type").textContent = prop.property_type || "-";
      modal.querySelector(".bedrooms").textContent      = prop.bedrooms ?? "-";
      modal.querySelector(".bathrooms").textContent     = prop.bathrooms ?? "-";
      modal.querySelector(".sqm").textContent           = prop.sqm ?? "-";
      modal.querySelector(".lot_size").textContent      = prop.lot_size ?? "-";
      modal.querySelector(".status").textContent        = prop.status || "-";
      modal.querySelector(".date_uploaded").textContent = prop.created_at ? new Date(prop.created_at).toLocaleDateString() : "-";
      modal.querySelector(".property-description").textContent = prop.description || "No description available.";

      // --- Review Button Logic ---
      if (reviewBtn) {
        if (data.has_privilege) {
          reviewBtn.style.display = "inline-flex";
          reviewBtn.dataset.propertyId = prop.id;
        } else {
          reviewBtn.style.display = "none";
          reviewBtn.dataset.propertyId = "";
        }
      }

      // --- Show modal ---
      modal.hidden = false;
      modal.style.display = "flex";

      // --- Fetch Past Reviews ---
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
      console.error("Failed to load property details:", err);
    }
  }

  // ===== Open Review Modal =====
  function openReviewModal(propertyId) {
    const reviewModal = document.getElementById("reviewModal");
    if (!reviewModal) return;
    document.getElementById("reviewPropertyId").value = propertyId;
    selectedRating = 0;
    reviewModal.hidden = false;
    reviewModal.style.display = "flex";

    // Reset stars
    reviewModal.querySelectorAll(".rating-stars span").forEach(star => star.classList.remove("selected"));
    reviewModal.querySelector("textarea[name='review_text']").value = "";
  }

  // ===== Close Modal Helper =====
  function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    modal.style.display = "none";
  }

  // ===== Star Rating =====
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

  // ===== Submit Review Form =====
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

        // Fetch all reviews again to update modal
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
        console.error(err);
        alert("Failed to submit review.");
      }
    });
  }

  // ===== Event Delegation =====
  document.addEventListener("click", e => {
    const card = e.target.closest(".property-card");
    const reviewBtn = e.target.closest("[id='leaveReviewBtn']");
    const closeBtn = e.target.closest(".modal .close");

    // Open property modal
    if (card && !e.target.closest(".modal")) return openPropertyModal(card.dataset.id);

    // Open review modal
    if (reviewBtn) {
      const propertyId = reviewBtn.dataset.propertyId || currentPropertyId;
      return openReviewModal(propertyId);
    }

    // Close modal via close button
    if (closeBtn) return closeModal(closeBtn.closest(".modal"));

    // Close modal by clicking outside content
    if (e.target.id === "propertyModal") closeModal(e.target);
    if (e.target.id === "reviewModal") closeModal(e.target);
  });

  // Expose functions globally
  window.openPropertyModal = openPropertyModal;
  window.openReviewModal = openReviewModal;
})();

(() => {
  let currentPropertyId = null;
  const saveBtn = document.getElementById("saveFavoriteBtn");

  if (!saveBtn) return;

  // --- Helper to show notifications ---
  function notify(type, message) {
    let container = document.querySelector(".notification-container");
    if (!container) {
      container = document.createElement("div");
      container.className = "notification-container";
      document.body.appendChild(container);
    }

    const notif = document.createElement("div");
    notif.className = `notification ${type}`;
    notif.innerHTML = `<div class="notification__title">${message}</div><div class="notification__close">&times;</div>`;
    container.appendChild(notif);
    notif.querySelector(".notification__close").addEventListener("click", () => notif.remove());
    setTimeout(() => notif.remove(), 5000);
  }

  // --- Update button appearance ---
  function updateSaveButton(saved) {
    if (saved) {
      saveBtn.classList.add("saved");
      saveBtn.innerHTML = `<i class="fas fa-heart"></i> Saved`;
    } else {
      saveBtn.classList.remove("saved");
      saveBtn.innerHTML = `<i class="fas fa-heart"></i> Save`;
    }
  }

  // --- Check if property is already saved ---
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
      console.error("Failed to check saved property:", err);
    }
  }

  // --- Toggle save/unsave ---
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
      console.error(err);
      notify("error", "Failed to update saved status.");
    }
  }

  saveBtn.addEventListener("click", toggleSave);

  // --- Integrate with property modal ---
  const observer = new MutationObserver(() => {
    const modal = document.getElementById("propertyModal");
    if (modal && modal.style.display !== "none") {
      const propId = modal.querySelector(".property-card, .modal-right")?.dataset?.id
                     || window.currentPropertyId;
      if (propId) {
        currentPropertyId = parseInt(propId);
        checkSaved(currentPropertyId);
      }
    }
  });

  observer.observe(document.body, { childList: true, subtree: true });

  // --- Optional: Update button when opening property card from main list ---
  document.addEventListener("click", e => {
    const card = e.target.closest(".property-card");
    if (card) {
      currentPropertyId = parseInt(card.dataset.id);
      checkSaved(currentPropertyId);
    }
  });
})();

(() => {
document.addEventListener("click", async (e) => {
  const messageBtn = e.target.closest(".message-agent-btn");
  if (!messageBtn) return;

  e.preventDefault();

  const modal = document.getElementById("propertyModal");
  const propertyId = modal?.dataset.id;
  if (!propertyId) {
    notify("Property ID not found.");
    return;
  }

  try {
    const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(propertyId)}`);
    const data = await res.json();

    if (!data.success) {
      notify(data.error || "Failed to fetch property details.");
      return;
    }

    const agentId = data.property?.agent_id;
    if (!agentId) {
      notify("Agent not found for this property.");
      return;
    }

    // Open the message page in a new tab
    const url = `/BatEstateExplorer/public/message.php?agent_id=${agentId}`;
    window.open(url, "_blank");

  } catch (err) {
    console.error("Error fetching property details:", err);
    notify("Failed to fetch property details.");
  }
});

})();
