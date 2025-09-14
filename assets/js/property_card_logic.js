(() => {
  let modalSwiper = null;
  let currentPropertyId = null;
  let saveBtn = null;

  const userToken = window.AppConfig?.userToken || "";
  const currentUserId = window.AppConfig?.userId || 0;

  // ===== Notification helper =====
  function notify(type, message) {
    let container = document.querySelector('.notification-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'notification-container';
      document.body.appendChild(container);
    }

    const notif = document.createElement('div');
    notif.className = `notification ${type}`;
    const icon = type === 'success'
      ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path fill="#fff" d="M9 16.17 4.83 12l-1.42 1.41L9 19l12-12-1.41-1.41z"/></svg>'
      : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path fill="#fff" d="m13 13h-2v-6h2zm0 4h-2v-2h2z"/></svg>';

    notif.innerHTML = `
      <div class="notification__icon">${icon}</div>
      <div class="notification__title">${message}</div>
      <div class="notification__close">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20">
          <path fill="#fff" d="m15.8 5.3-1.2-1.2-4.6 4.7-4.6-4.7-1.2 1.2 4.7 4.7-4.7 4.6 1.2 1.2 4.6-4.6 4.6 4.6 1.2-1.2-4.6-4.6z"/>
        </svg>
      </div>
    `;
    container.appendChild(notif);

    notif.querySelector('.notification__close').addEventListener('click', () => fadeOut(notif));
    setTimeout(() => fadeOut(notif), 5000);

    function fadeOut(el) {
      el.classList.add('fade-out');
      setTimeout(() => el.remove(), 500);
    }
  }

  // ===== Card hover auto-swipe =====
  document.querySelectorAll('.property-card').forEach(card => {
    const images = JSON.parse(card.dataset.images || '[]');
    if (images.length < 2) return;

    const imgEl = card.querySelector('.property-image img');
    let index = 0, interval = null;

    card.addEventListener('mouseenter', () => {
      interval = setInterval(() => {
        index = (index + 1) % images.length;
        imgEl.src = images[index];
      }, 1500);
    });

    card.addEventListener('mouseleave', () => {
      clearInterval(interval);
      imgEl.src = images[0];
      index = 0;
    });
  });

  // ===== Open Property Modal =====
  async function openPropertyModal(propertyId) {
    currentPropertyId = propertyId;
    const wrapper = document.getElementById("modalImageWrapper");
    const reviewContainer = document.getElementById("modalPastReviews");

    try {
      const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(propertyId)}`);
      const data = await res.json();
      if (!data.success) return notify("error", data.error || "Failed to fetch property.");

      const prop = data.property;
      const images = prop.images.length ? prop.images : ["/BatEstateExplorer/assets/images/bg4.jpg"];

      // Load Swiper slides
      wrapper.innerHTML = images.map(img => `<div class="swiper-slide"><img src="${img}" style="width:100%;border-radius:8px;"></div>`).join('');

      // Destroy old Swiper
      if (modalSwiper) { modalSwiper.destroy(true, true); modalSwiper = null; }

      // Initialize modal Swiper (unique selectors)
      modalSwiper = new Swiper(".modal-swiper-container", {
        loop: images.length > 1,
        navigation: {
          nextEl: ".modal-swiper-next",
          prevEl: ".modal-swiper-prev"
        },
        pagination: { el: ".modal-swiper-pagination", clickable: true },
        autoplay: { delay: 4000, disableOnInteraction: false },
      });

      // Update modal details
      document.getElementById("modalTitle").textContent = prop.title;
      document.getElementById("modalLocation").textContent = `📍 ${prop.location}`;
      document.getElementById("modalPrice").textContent = `₱${parseFloat(prop.price).toLocaleString()}`;
      document.getElementById("modalBedrooms").textContent = prop.bedrooms;
      document.getElementById("modalBathrooms").textContent = prop.bathrooms;
      document.getElementById("modalDescription").textContent = prop.description || "No description available.";

      // Past reviews
      reviewContainer.innerHTML = (prop.past_reviews?.length)
        ? prop.past_reviews.map(r => `<div class="review-card" style="margin-bottom:10px;">
            <strong>${r.first_name} ${r.last_name}</strong>
            <span style="float:right;">${r.rating}⭐</span>
            <p>${r.review_text}</p>
            <small>${new Date(r.created_at).toLocaleDateString()}</small>
          </div>`).join('')
        : `<p>No reviews yet.</p>`;

      // Leave review button
      const reviewBtn = document.getElementById("leaveReviewBtn");
      if (data.has_privilege) {
        reviewBtn.style.display = "inline-block";
        reviewBtn.onclick = () => openReviewModal(prop.id);
      } else {
        reviewBtn.style.display = "none";
        reviewBtn.onclick = null;
      }

      // Message agent
      const messageBtn = document.querySelector(".message-agent-btn");
      messageBtn.dataset.agentId = prop.agent_id || "";

      // Show modal
      document.getElementById("propertyModal").style.display = "flex";

      // Save button
      initSaveButton();
      checkIfSaved(currentPropertyId);

    } catch (err) {
      console.error(err);
      notify("error", "Failed to load property details.");
    }
  }

  // ===== Review modal =====
  function openReviewModal(propertyId) {
    document.getElementById("reviewPropertyId").value = propertyId;
    document.getElementById("reviewModal").style.display = "flex";
  }

  // ===== Save / Unsave =====
  function initSaveButton() {
    saveBtn = document.getElementById("saveFavoriteBtn");
    if (!saveBtn) return;
    saveBtn.replaceWith(saveBtn.cloneNode(true));
    saveBtn = document.getElementById("saveFavoriteBtn");
    saveBtn.addEventListener("click", handleSaveClick);
  }

  async function checkIfSaved(propertyId) {
    if (!saveBtn) return;
    try {
      const res = await fetch("/BatEstateExplorer/public/api/save_property.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: `property_id=${propertyId}&action=check&user_token=${encodeURIComponent(userToken)}`
      });
      const data = await res.json();
      if (data.success) updateSaveButton(data.saved);
    } catch (err) { console.error(err); }
  }

  function updateSaveButton(isSaved) {
    if (!saveBtn) return;
    saveBtn.innerHTML = isSaved ? '<i class="fas fa-heart"></i> Unsave' : '<i class="fas fa-heart"></i> Save to Favorites';
    saveBtn.dataset.saved = isSaved ? "true" : "false";
  }

  async function handleSaveClick() {
    if (!currentPropertyId || !saveBtn) return;
    const action = saveBtn.dataset.saved === "true" ? "unsave" : "save";
    try {
      const res = await fetch("/BatEstateExplorer/public/api/save_property.php", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`property_id=${currentPropertyId}&action=${action}&user_token=${encodeURIComponent(userToken)}`
      });
      const data = await res.json();
      if (data.success) {
        updateSaveButton(data.saved);
        if (data.message && data.message !== "Property already saved.") notify("success", data.message);
      } else notify("error", data.error || "Failed to update saved status.");
    } catch (err) { console.error(err); notify("error", "Error updating saved status."); }
  }

  // ===== Submit review =====
  document.getElementById("reviewForm")?.addEventListener("submit", async e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
      const res = await fetch("/BatEstateExplorer/public/api/submit_review.php", { method:"POST", body: formData });
      const data = await res.json();
      if (data.success) {
        notify("success", "Review submitted successfully!");
        document.getElementById("reviewModal").style.display = "none";
        e.target.reset();
      } else notify("error", data.error || "Failed to submit review.");
    } catch (err) { console.error(err); notify("error", "Error submitting review."); }
  });

  // ===== Message agent =====
  document.addEventListener("click", e => {
    const btn = e.target.closest(".message-agent-btn");
    if (btn) {
      const agentId = btn.dataset.agentId;
      if (!agentId) return notify("error", "Agent not found.");
      window.open(`/BatEstateExplorer/public/message.php?agent_id=${agentId}`, "_blank");
    }
  });

  // ===== Open property modal from card =====
  document.addEventListener("click", e => {
    const btn = e.target.closest(".view-details-btn");
    if (btn) openPropertyModal(btn.dataset.id);
  });

  // ===== Close modal =====
  document.querySelectorAll(".modal-close").forEach(btn => {
    btn.addEventListener("click", () => btn.closest(".modal").style.display = "none");
  });

  window.openPropertyModal = openPropertyModal;
})();
