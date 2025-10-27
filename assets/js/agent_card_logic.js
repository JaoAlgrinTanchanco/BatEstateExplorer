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

  // Global function to show/hide SOLD overlay
  window.updateSoldOverlay = function(status) {
    const modal = document.getElementById('propertyModal');
    if (!modal) return;

    const mainImage = modal.querySelector('.property-main-image');
    if (!mainImage) return;

    // Remove existing overlay
    let overlay = mainImage.querySelector('.sold-overlay');
    if (overlay) overlay.remove();

    // Add SOLD overlay if status is 'sold'
    if (status === 'sold') {
      overlay = document.createElement('div');
      overlay.className = 'sold-overlay';
      overlay.textContent = 'SOLD';
      mainImage.appendChild(overlay);
    }
  };

  // Open Property Modal
  async function openPropertyModal(propertyId) {
    currentPropertyId = propertyId;
    const modal = document.getElementById("propertyModal");
    if (!modal) return;

    const reviewContainer = modal.querySelector("#modalPastReviews");
    const reviewBtn = modal.querySelector("#leaveReviewBtn");
    const optionsBtn = document.getElementById('propertyOptionsBtn');
    const dropdown = document.getElementById('propertyOptionsDropdown');

    try {
      // Fetch property details
      const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(propertyId)}`);
      const data = await res.json();
      if (!data.success) return;

      const prop = data.property;
      const images = prop.images.length ? prop.images : ["/BatEstateExplorer/assets/images/bg4.jpg"];
      modal.dataset.id = prop.id;

      // Map property.agent_id to user_id
      let listedAgentUserId = null;
      try {
        const agentRes = await fetch(`/BatEstateExplorer/public/api/get_agent_user_id.php?agent_id=${prop.agent_id}`);
        const agentData = await agentRes.json();
        if (agentData.success) {
          listedAgentUserId = agentData.user_id;
          modal.dataset.listedAgentId = listedAgentUserId;
        }
      } catch (err) {
        console.error("Failed to map agent_id to user_id:", err);
      }

      const loggedInUserId = parseInt(modal.dataset.loggedInAgentId);

      // Show or hide options button
      if (loggedInUserId && listedAgentUserId && loggedInUserId === listedAgentUserId) {
        optionsBtn.style.display = 'flex';
      } else {
        optionsBtn.style.display = 'none';
      }

      // Set main image and thumbnails
      const mainImage = modal.querySelector(".property-main-image");
      mainImage.style.backgroundImage = `url('${images[0]}')`;
      modal.querySelector(".property-name").textContent = prop.title || "No title";

      const thumbs = modal.querySelector(".property-images");
      thumbs.innerHTML = images.map((img, i) =>
        `<img src="${img}" alt="Property image" ${i === 0 ? "class='active'" : ""}>`
      ).join("");

      thumbs.querySelectorAll("img").forEach(imgEl => {
        imgEl.addEventListener("click", () => {
          mainImage.style.backgroundImage = `url('${imgEl.src}')`;
          thumbs.querySelectorAll("img").forEach(i => i.classList.remove("active"));
          imgEl.classList.add("active");
        });
      });

      // Set property details
      modal.querySelector(".location").textContent = prop.location || "-";
      modal.querySelector(".price").textContent = `₱${parseFloat(prop.price || 0).toLocaleString()}`;
      modal.querySelector(".property-type").textContent = prop.property_type || "-";
      modal.querySelector(".bedrooms").textContent = prop.bedrooms ?? "-";
      modal.querySelector(".bathrooms").textContent = prop.bathrooms ?? "-";
      modal.querySelector(".lot_size").textContent = prop.lot_size ?? "-";
      modal.querySelector(".date_uploaded").textContent = prop.created_at ? new Date(prop.created_at).toLocaleDateString() : "-";
      modal.querySelector(".property-description").textContent = prop.description || "No description available.";

      // Review button visibility
      if (reviewBtn) {
        if (data.has_privilege) {
          reviewBtn.style.display = "inline-flex";
          reviewBtn.dataset.propertyId = prop.id;
        } else {
          reviewBtn.style.display = "none";
          reviewBtn.dataset.propertyId = "";
        }
      }

      // Highlight current status in dropdown
      if (dropdown) {
        dropdown.querySelectorAll('.status-option').forEach(btn => {
          btn.style.background = (btn.dataset.status === prop.status)
            ? (prop.status === 'available' ? '#d0f0c0' : '#f8d0d0')
            : '';
        });
      }

      // Show modal
      modal.hidden = false;
      modal.style.display = 'flex';

      // Update SOLD overlay based on current status
      window.updateSoldOverlay(prop.status);

      // Fetch past reviews
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
      console.error("Failed to open property modal:", err);
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

  // =============================
  // Agent Property Card Switcher
  // =============================
  document.addEventListener('click', async (e) => {
    const card = e.target.closest('.agent-property-card');
    if (!card) return;

    const propertyId = card.dataset.propertyId;
    if (!propertyId) return;

    try {
      const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${propertyId}`);
      const data = await res.json();

      if (data.success) {
        updatePropertyModal(data.property);
      } else {
        console.error('Failed to load property details');
      }
    } catch (err) {
      console.error('Error fetching property details:', err);
    }
  });

  function updatePropertyModal(property) {
    const modal = document.getElementById('propertyModal');
    if (!modal) return;

    modal.dataset.id = property.id;
    modal.querySelector('.property-name').textContent = property.title;
    modal.querySelector('.price').textContent = '₱' + parseFloat(property.price).toLocaleString();
    modal.querySelector('.location').textContent = property.location;
    modal.querySelector('.property-type').textContent = property.property_type;
    modal.querySelector('.bedrooms').textContent = property.bedrooms;
    modal.querySelector('.bathrooms').textContent = property.bathrooms;
    modal.querySelector('.lot_size').textContent = property.lot_size;
    modal.querySelector('.status').textContent = property.status;
    modal.querySelector('.date_uploaded').textContent = new Date(property.created_at).toLocaleDateString();
    modal.querySelector('.property-description').textContent = property.description;

    // --- Update Images ---
    const imagesContainer = modal.querySelector('.property-images');
    const mainImage = modal.querySelector('.property-main-image');
    if (property.images && property.images.length > 0) {
      const imgPaths = property.images.map(img => `/BatEstateExplorer/${img}`);
      mainImage.style.backgroundImage = `url('${imgPaths[0]}')`;
      imagesContainer.innerHTML = imgPaths.map(img => `<img src="${img}" alt="">`).join('');
    } else {
      mainImage.style.backgroundImage = '';
      imagesContainer.innerHTML = '<p>No images available.</p>';
    }

    // --- Update Reviews ---
    const reviewContainer = modal.querySelector("#modalPastReviews");
    if (reviewContainer) {
      // 🔹 Immediately clear and show loading
      reviewContainer.innerHTML = `<p style="opacity:0.6;">Loading reviews...</p>`;

      fetch(`/BatEstateExplorer/public/api/get_reviews.php?property_id=${property.id}`)
        .then(res => res.json())
        .then(reviewData => {
          if (reviewData.success && Array.isArray(reviewData.reviews) && reviewData.reviews.length > 0) {
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
        })
        .catch(err => {
          console.error('Error loading reviews:', err);
          reviewContainer.innerHTML = `<p style="color:red;">Failed to load reviews.</p>`;
        });
    }
  }
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

// Property Status Dropdown Logic
(() => {
  const modal = document.getElementById('propertyModal');
  if (!modal) return;

  const optionsBtn = document.getElementById('propertyOptionsBtn');
  const dropdown = document.getElementById('propertyOptionsDropdown');
  const mainImageWrapper = document.querySelector('.property-main-image-wrapper');
  let overlay = null;

  // Call this whenever modal opens
  function initStatusDropdown(currentStatus) {
    const loggedInAgentId = parseInt(modal.dataset.loggedInAgentId);
    const listedAgentId = parseInt(modal.dataset.listedAgentId);

    console.log("DEBUG: loggedInAgentId =", loggedInAgentId);
    console.log("DEBUG: listedAgentId =", listedAgentId);
    console.log("DEBUG: comparison =", loggedInAgentId === listedAgentId);

    // Show options button only if logged-in agent is the listing agent
    if (loggedInAgentId && listedAgentId && loggedInAgentId === listedAgentId) {
      console.log("DEBUG: Showing options button");
      optionsBtn.style.display = 'flex';
    } else {
      console.log("DEBUG: Hiding options button");
      optionsBtn.style.display = 'none';
    }

    // Reset dropdown display
    dropdown.style.display = 'none';

    // Highlight current status automatically
    dropdown.querySelectorAll('.status-option').forEach(btn => {
      if (btn.dataset.status === currentStatus) {
        btn.style.background = currentStatus === 'available' ? '#d0f0c0' : '#f8d0d0';
      } else {
        btn.style.background = '';
      }
    });
  }

  // Toggle dropdown on click
  optionsBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdown.style.display = dropdown.style.display === 'flex' ? 'none' : 'flex';
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', () => {
    dropdown.style.display = 'none';
  });

  // Handle status change and pastel highlighting
  dropdown.querySelectorAll('.status-option').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();
      const newStatus = btn.dataset.status;
      const propertyId = modal.dataset.id;

      // Highlight selected option
      dropdown.querySelectorAll('.status-option').forEach(b => b.style.background = '');
      btn.style.background = newStatus === 'available' ? '#d0f0c0' : '#f8d0d0';

      try {
        const res = await fetch(`/BatEstateExplorer/public/api/update_property_status.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ property_id: propertyId, status: newStatus })
        });
        const data = await res.json();

        if (data.success) {
          updateSoldOverlay(newStatus);
          dropdown.style.display = 'none';
        } else {
          alert(data.error || 'Failed to update status');
        }
      } catch (err) {
        console.error("Status update failed:", err);
      }
    });
  });

  // Expose function to call on modal open
  window.initStatusDropdown = initStatusDropdown;
})();
