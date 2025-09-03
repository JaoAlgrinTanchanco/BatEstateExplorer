
document.addEventListener('DOMContentLoaded', () => {
  /** -------------------------------
   * 🔍 SEARCH PROPERTIES (AJAX)
   * ------------------------------- */
  const searchBtn = document.getElementById('searchForm1');
  if (searchBtn) {
    searchBtn.addEventListener('click', async (e) => {
      e.preventDefault();

      // Collect filters
      const params = ['location','property_type','price_range','bedrooms','bathrooms','size']
        .reduce((obj, id) => {
          const el = document.getElementById(id);
          obj[id] = el ? el.value : '';
          return obj;
        }, {});

      const query = new URLSearchParams(params).toString();

      try {
        const res = await fetch(`/BatEstateExplorer/public/api/get_properties.php?${query}`);
        if (!res.ok) throw new Error('Network response was not OK');
        const data = await res.json();

        const grid = document.getElementById('propertiesGrid');
        if (!grid) {
          console.error("❌ propertiesGrid not found");
          return;
        }

        if (!data.properties?.length) {
          grid.innerHTML = '<p>No properties available at the moment.</p>';
          return;
        }

        // Render property cards
        grid.innerHTML = data.properties.map(p => `
          <div class="property-card">
            <div class="property-image">
              <img src="${p.image_path || '/BatEstateExplorer/assets/images/bg4.jpg'}" alt="${p.title}">
            </div>
            <div class="property-content">
              <h3>${p.title}</h3>
              <p class="property-location"><i class="fas fa-map-marker-alt"></i> ${p.location}</p>
              <p class="property-price">₱${Number(p.price).toLocaleString()}</p>
              <div class="property-features">
                <span><i class="fas fa-bed"></i> ${p.bedrooms} Beds</span>
                <span><i class="fas fa-bath"></i> ${p.bathrooms} Baths</span>
              </div>
              <button class="btn btn-outline view-details-btn" data-id="${p.id}">View Details</button>
            </div>
          </div>
        `).join('');

        // Re-bind modal handling from external JS
        if (window.AppHandlers?.attachViewDetailHandlers) {
          window.AppHandlers.attachViewDetailHandlers();
        }

      } catch (err) {
        console.error('❌ Fetch error:', err);
      }
    });
  }

  /** -------------------------------
   * 🎛️ DROPDOWN LABEL UPDATER
   * ------------------------------- */
  document.querySelectorAll('.search-field select').forEach(selectEl => {
    const valueSpan = selectEl.closest('.search-field').querySelector('.value');
    const updateValue = () => {
      valueSpan.textContent = selectEl.value === ""
        ? valueSpan.dataset.default
        : selectEl.options[selectEl.selectedIndex].text;
    };
    updateValue();
    selectEl.addEventListener('change', updateValue);
  });
});
