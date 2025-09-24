(() => {
  let currentPropertyId = null;

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
    const reviewContainer = modal.querySelector(".property-reviews") || null;
    const reviewBtn = modal.querySelector("#leaveReviewBtn");

    try {
      const res = await fetch(`/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(propertyId)}`);
      const data = await res.json();
      if (!data.success) return notify("error", data.error || "Failed to fetch property.");

      const prop = data.property;
      const images = prop.images && prop.images.length ? prop.images : ["/BatEstateExplorer/assets/images/bg4.jpg"];

      // === Left Column ===
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

      // === Right Column ===
      modal.querySelector(".location").textContent      = prop.location || "-";
      modal.querySelector(".price").textContent         = `₱${parseFloat(prop.price || 0).toLocaleString()}`;
      modal.querySelector(".property-type").textContent = prop.property_type || "-";
      modal.querySelector(".bedrooms").textContent      = prop.bedrooms ?? "-";
      modal.querySelector(".bathrooms").textContent     = prop.bathrooms ?? "-";
      modal.querySelector(".sqm").textContent           = prop.sqm ?? "-";
      modal.querySelector(".lot_size").textContent      = prop.lot_size ?? "-";
      modal.querySelector(".status").textContent        = prop.status || "-";
      modal.querySelector(".date_uploaded").textContent = prop.created_at
        ? new Date(prop.created_at).toLocaleDateString()
        : "-";
      modal.querySelector(".property-description").textContent = prop.description || "No description available.";

      // === Past Reviews ===
      if (reviewContainer) {
        reviewContainer.innerHTML = (prop.past_reviews && prop.past_reviews.length)
          ? prop.past_reviews.map(r => `
              <div class="review-card" style="margin-bottom:10px;">
                <strong>${r.first_name} ${r.last_name}</strong>
                <span style="float:right;">${r.rating}⭐</span>
                <p>${r.review_text}</p>
                <small>${new Date(r.created_at).toLocaleDateString()}</small>
              </div>
            `).join("")
          : `<p>No reviews yet.</p>`;
      }

      // === Review Button Visibility ===
      if (reviewBtn) {
        if (data.has_privilege) {
          reviewBtn.style.display = "inline-block";
          reviewBtn.onclick = () => openReviewModal(prop.id);
        } else {
          reviewBtn.style.display = "none";
          reviewBtn.onclick = null;
        }
      }

      // === Show modal ===
      modal.style.display = "flex";

    } catch (err) {
      console.error(err);
      notify("error", "Failed to load property details.");
    }
  }

  // ===== Open Review Modal =====
  function openReviewModal(propertyId) {
    const reviewModal = document.getElementById("reviewModal");
    if (!reviewModal) return;
    document.getElementById("reviewPropertyId").value = propertyId;
    reviewModal.style.display = "flex";
  }

  // ===== Open modal from card click =====
  document.addEventListener("click", e => {
    const card = e.target.closest(".property-card");
    if (card && !e.target.closest(".modal")) {
      openPropertyModal(card.dataset.id);
    }
  });

  // ===== Close modal =====
  document.querySelectorAll("#propertyModal .close").forEach(btn => {
    btn.addEventListener("click", () => btn.closest(".modal").style.display = "none");
  });

  // Expose for external use
  window.openPropertyModal = openPropertyModal;
  window.openReviewModal = openReviewModal;

})();
