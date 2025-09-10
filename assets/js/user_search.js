(() => {
  /** -------------------------------
   * 🎛️ DROPDOWN LABEL UPDATER
   * ------------------------------- */
  document.querySelectorAll('.search-field').forEach(field => {
    const selectEl = field.querySelector('select');
    const valueSpan = field.querySelector('.value');
    if (!selectEl || !valueSpan) return;

    const updateValue = () => {
      valueSpan.textContent = selectEl.value === "" 
        ? valueSpan.dataset.default 
        : selectEl.options[selectEl.selectedIndex].text;
    };

    updateValue();
    selectEl.addEventListener('change', updateValue);
  });

  /** -------------------------------
   * 🔍 FILTER PROPERTIES
   * ------------------------------- */
  function filterProperties() {
    const filters = ['location','property_type','price_range','bedrooms','bathrooms','size'].reduce((obj, id) => {
      const el = document.getElementById(id);
      obj[id] = el ? el.value : '';
      return obj;
    }, {});

    const allCards = document.querySelectorAll('#propertiesGrid .property-card');
    if (!allCards.length) return;

    allCards.forEach(card => {
      let match = true;

      const price = parseFloat(card.dataset.price || 0);
      const bedrooms = parseInt(card.dataset.bedrooms || 0);
      const bathrooms = parseInt(card.dataset.bathrooms || 0);
      const size = parseInt(card.dataset.size || 0);
      const location = (card.dataset.location || '').toLowerCase();
      const type = (card.dataset.type || '').toLowerCase();

      // Location
      if (filters.location && !location.includes(filters.location.toLowerCase())) match = false;

      // Type
      if (filters.property_type && type !== filters.property_type.toLowerCase()) match = false;

      // Price
      if (filters.price_range) {
        if (filters.price_range === "5000000+" && price < 5000000) match = false;
        else if (filters.price_range.includes("-")) {
          const [min, max] = filters.price_range.split("-").map(Number);
          if (price < min || price > max) match = false;
        }
      }

      // Bedrooms
      if (filters.bedrooms && bedrooms < parseInt(filters.bedrooms)) match = false;

      // Bathrooms
      if (filters.bathrooms && bathrooms < parseInt(filters.bathrooms)) match = false;

      // Size
      if (filters.size) {
        if (filters.size === "200+" && size < 200) match = false;
        else if (filters.size.includes("-")) {
          const [min, max] = filters.size.split("-").map(Number);
          if (size < min || size > max) match = false;
        }
      }

      card.style.display = match ? "" : "none";
    });

    // Show message if no results
    const anyVisible = [...allCards].some(c => c.style.display !== 'none');
    const grid = document.getElementById('propertiesGrid');
    if (!anyVisible) {
      if (!grid.querySelector('.no-results')) {
        grid.insertAdjacentHTML('beforeend','<p class="no-results">No properties match your filters.</p>');
      } else {
        grid.querySelector('.no-results').style.display = '';
      }
    } else {
      const msg = grid.querySelector('.no-results');
      if (msg) msg.style.display = 'none';
    }
  }

  /** -------------------------------
   * 🚀 SEARCH BUTTON
   * ------------------------------- */
  document.getElementById('searchForm1')?.addEventListener('click', (e) => {
    e.preventDefault();
    filterProperties();
  });

  /** -------------------------------
   * 🖱️ EVENT DELEGATION FOR PROPERTY MODAL
   * ------------------------------- */
  document.addEventListener('click', e => {
    const btn = e.target.closest('.view-details-btn');
    if (btn && btn.dataset.id) {
      window.openPropertyModal?.(btn.dataset.id);
    }

    const closeBtn = e.target.closest('.modal-close');
    if (closeBtn) {
      closeBtn.closest('.modal').style.display = 'none';
    }

    if (e.target.id === 'propertyModal' || e.target.id === 'reviewModal') {
      e.target.style.display = 'none';
    }
  });

})();
