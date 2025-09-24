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

      // Show modal
      document.getElementById("propertyModal").style.display = "flex";
    } catch (err) {
      console.error(err);
      alert("Failed to load property details.");
    }
  }

  // Use event delegation for view-details buttons
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.view-details-btn');
    if (btn) {
      openPropertyModal(btn.dataset.id);
    }
    // modal close
    const closeBtn = e.target.closest('.modal-close');
    if (closeBtn) {
      document.getElementById("propertyModal").style.display = "none";
    }
    // click outside modal content to close
    if (e.target.id === 'propertyModal') {
      e.target.style.display = 'none';
    }
  });

  // expose for debug if needed
  window.openPropertyModal = openPropertyModal;
})();
