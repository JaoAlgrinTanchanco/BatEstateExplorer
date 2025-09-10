(() => {
  const propertiesGrid = document.getElementById('propertiesGrid');
  const filters = ['location', 'property_type', 'price_range', 'bedrooms', 'bathrooms', 'size'];
  const resetBtn = document.getElementById('searchForm1');

  // Helper: get current filter values
  function getFilterValues() {
    const data = {};
    filters.forEach(f => {
      const el = document.getElementById(f);
      if (el) data[f] = el.value;
    });
    data['ajax'] = 1; // indicate AJAX request
    return data;
  }

  // Helper: update the dropdown label (button span.value)
  function updateLabel(el) {
    const valueSpan = el.parentElement.querySelector('.value');
    const defaultText = valueSpan.dataset.default;
    const val = el.value;
    if (val === '' || val === null) valueSpan.textContent = defaultText;
    else valueSpan.textContent = val.includes('+') ? val : val;
  }

  // --- Fetch properties via AJAX ---
  async function fetchProperties() {
    try {
      const formData = new FormData();
      const data = getFilterValues();
      for (const key in data) formData.append(key, data[key]);

      const res = await fetch(window.location.href, {
        method: 'POST',
        body: formData,
      });
      const html = await res.text();
      propertiesGrid.innerHTML = html;

      // Re-attach property modal listeners if needed
      if (window.openPropertyModal) {
        const cards = propertiesGrid.querySelectorAll('.view-details-btn');
        cards.forEach(btn => {
          btn.addEventListener('click', () => openPropertyModal(btn.dataset.id));
        });
      }
    } catch (err) {
      console.error('Failed to fetch properties', err);
    }
  }

  // --- Event listeners: update on filter change ---
  filters.forEach(f => {
    const el = document.getElementById(f);
    if (!el) return;
    el.addEventListener('change', () => {
      updateLabel(el);
      fetchProperties();
    });
  });

  // --- Reset filters on button click ---
  if (resetBtn) {
    // change icon to "reset" (example: refresh icon)
    resetBtn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i>';
    resetBtn.addEventListener('click', () => {
      filters.forEach(f => {
        const el = document.getElementById(f);
        if (el) el.value = '';
        if (el) updateLabel(el);
      });
      fetchProperties();
    });
  }

  // Optional: trigger fetch on page load to ensure correct filter state
  fetchProperties();

})();
