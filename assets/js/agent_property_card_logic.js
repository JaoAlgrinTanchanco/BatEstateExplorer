(() => {
  let modalSwiper = null;
  let currentPropertyId = null;

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
      if (modalSwiper) {
        modalSwiper.update();
      } else {
        modalSwiper = new Swiper(".modal-swiper", {
          loop: images.length > 1,
          navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
          pagination: { el: ".swiper-pagination", clickable: true }
        });
      }

      // Fill details
      document.getElementById("modalTitle").textContent = prop.title;
      document.getElementById("modalLocation").textContent = `📍 ${prop.location}`;
      document.getElementById("modalPrice").textContent = `₱${parseFloat(prop.price).toLocaleString()}`;
      document.getElementById("modalBedrooms").textContent = prop.bedrooms;
      document.getElementById("modalBathrooms").textContent = prop.bathrooms;
      document.getElementById("modalDescription").textContent = prop.description || "No description available.";

      // ===== Leave Review Button per Property Privilege =====
      const reviewBtn = document.getElementById("leaveReviewBtn");
      if (reviewBtn) {
        if (data.has_privilege) {
          reviewBtn.style.display = "inline-block"; // Show button
          reviewBtn.onclick = () => openReviewModal(prop.id); // Assign click handler
        } else {
          reviewBtn.style.display = "none"; // Hide button
          reviewBtn.onclick = null; // Remove click handler
        }
      }

      // Show modal
      document.getElementById("propertyModal").style.display = "flex";

    } catch (err) {
      console.error(err);
      alert("Failed to load property details.");
    }
  }

  // ===== Review modal helper =====
  function openReviewModal(propertyId) {
    const reviewModal = document.getElementById("reviewModal");
    if (!reviewModal) return;
    document.getElementById("reviewPropertyId").value = propertyId;
    reviewModal.style.display = "flex";
  }

  // Use event delegation for clicks
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.view-details-btn');
    if (btn) openPropertyModal(btn.dataset.id);

    const closeBtn = e.target.closest('.modal-close');
    if (closeBtn) {
      closeBtn.closest('.modal').style.display = 'none';
    }

    if (e.target.id === 'propertyModal') {
      e.target.style.display = 'none';
    }
  });

  // Expose functions for debugging or external use
  window.openPropertyModal = openPropertyModal;
  window.openReviewModal = openReviewModal;

})();
