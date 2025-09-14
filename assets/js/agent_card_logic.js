(() => {
  let modalSwiper = null;
  let currentPropertyId = null;

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
    notif.innerHTML = `
      <div class="notification__title">${message}</div>
      <div class="notification__close">&times;</div>
    `;
    container.appendChild(notif);

    notif.querySelector('.notification__close').addEventListener('click', () => notif.remove());
    setTimeout(() => notif.remove(), 5000);
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

      // Initialize Swiper
      modalSwiper = new Swiper(".modal-swiper-container", {
        loop: images.length > 1,
        navigation: { nextEl: ".modal-swiper-button-next", prevEl: ".modal-swiper-button-prev" },
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

      // Show modal
      document.getElementById("propertyModal").style.display = "flex";

    } catch (err) {
      console.error(err);
      notify("error", "Failed to load property details.");
    }
  }

  // ===== Open modal from card click =====
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
