document.addEventListener('DOMContentLoaded', () => {

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
   * 🔍 SEARCH PROPERTIES (FILTER CARDS)
   * ------------------------------- */
  const searchBtn = document.getElementById('searchForm1');
  if (!searchBtn) return;

  searchBtn.addEventListener('click', async (e) => {
    e.preventDefault();

    const filters = ['location','property_type','price_range','bedrooms','bathrooms','size'].reduce((obj, id) => {
      const el = document.getElementById(id);
      obj[id] = el ? el.value : '';
      return obj;
    }, {});

    const allCards = document.querySelectorAll('#propertiesGrid .property-card');
    if (!allCards.length) return;

    allCards.forEach(card => {
      let match = true;

      // Check each filter
      const location = card.querySelector('.property-location')?.textContent || '';
      const priceText = card.querySelector('.property-price')?.textContent.replace(/[₱,]/g, '') || '0';
      const price = parseFloat(priceText) || 0;
      const bedrooms = parseInt(card.querySelector('.property-features span:first-child')?.textContent) || 0;
      const bathrooms = parseInt(card.querySelector('.property-features span:nth-child(2)')?.textContent) || 0;
      const size = parseInt(card.dataset.size) || 0; // Optional: if size stored in data-size attribute

      // Location
      if (filters.location && !location.toLowerCase().includes(filters.location.toLowerCase())) match = false;

      // Property Type
      if (filters.property_type && card.dataset.type !== filters.property_type) match = false;

      // Price Range
      if (filters.price_range) {
        if (filters.price_range === '5000000+' && price < 5000000) match = false;
        else if (filters.price_range.includes('-')) {
          const [min, max] = filters.price_range.split('-').map(Number);
          if (price < min || price > max) match = false;
        }
      }

      // Bedrooms
      if (filters.bedrooms && bedrooms < parseInt(filters.bedrooms)) match = false;

      // Bathrooms
      if (filters.bathrooms && bathrooms < parseInt(filters.bathrooms)) match = false;

      // Size (if you have data-size attribute)
      if (filters.size) {
        if (filters.size === '200+' && size < 200) match = false;
        else if (filters.size.includes('-')) {
          const [min, max] = filters.size.split('-').map(Number);
          if (size < min || size > max) match = false;
        }
      }

      card.style.display = match ? '' : 'none';
    });
  });

});
