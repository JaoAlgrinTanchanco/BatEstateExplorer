document.addEventListener("DOMContentLoaded", () => {
  const propertiesGrid = document.getElementById('propertiesGrid');
  const filters = ['location', 'property_type', 'price_range', 'bedrooms', 'bathrooms', 'size'];
  const searchBtn = document.getElementById('searchForm1');

  // Helper: get filter values
  function getFilterValues() {
    const data = {};
    filters.forEach(f => {
      const el = document.getElementById(f);
      if (el) data[f] = el.value;
    });
    data['ajax'] = 1;
    return data;
  }

  // Helper: update label
  function updateLabel(el) {
    const valueSpan = el.parentElement.querySelector('.value');
    const defaultText = valueSpan.dataset.default;
    const val = el.value;
    valueSpan.textContent = val === '' ? defaultText : val;
  }

  // --- Fetch properties via AJAX ---
  async function fetchProperties() {
    try {
      const formData = new FormData();
      const data = getFilterValues();
      for (const key in data) formData.append(key, data[key]);

      const res = await fetch(window.location.href, { method: 'POST', body: formData });
      const html = await res.text();
      propertiesGrid.innerHTML = html;

      // Attach modal open listeners after new content
      attachPropertyModalListeners();
    } catch (err) {
      console.error('Failed to fetch properties', err);
    }
  }

  // --- Attach modal listeners (open & close) ---
  function attachPropertyModalListeners() {
    const openButtons = propertiesGrid.querySelectorAll('.view-details-btn');
    openButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const modalId = btn.dataset.modal; // Assume each button has data-modal="#propertyModal"
        const modal = document.querySelector(modalId);
        if (!modal) return;
        modal.style.display = 'flex';
        console.log(`Modal ${modalId} opened`);
      });
    });
  }

  // --- Global modal close listener (delegation) ---
  document.addEventListener('click', (e) => {
    // Close via X button
    const closeBtn = e.target.closest('.modal-close');
    if (closeBtn) {
      const modal = closeBtn.closest('.modal');
      if (modal) {
        modal.style.display = 'none';
        console.log(`Modal ${modal.id} closed via X button`);
      }
    }

    // Close by clicking outside content
    const modalOverlay = e.target.closest('.modal');
    if (modalOverlay && e.target === modalOverlay) {
      modalOverlay.style.display = 'none';
      console.log(`Modal ${modalOverlay.id} closed by clicking outside`);
    }
  });

  // --- Escape key closes modals ---
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal').forEach(modal => {
        if (modal.style.display === 'flex') {
          modal.style.display = 'none';
          console.log(`Modal ${modal.id} closed via Escape key`);
        }
      });
    }
  });

  // --- Filters change ---
  filters.forEach(f => {
    const el = document.getElementById(f);
    if (!el) return;
    el.addEventListener('change', () => {
      updateLabel(el);
      fetchProperties();
    });
  });

  // --- Reset button ---
  if (searchBtn) {
    searchBtn.innerHTML = '<i class="fa-solid fa-arrows-rotate"></i>';
    searchBtn.addEventListener('click', () => {
      filters.forEach(f => {
        const el = document.getElementById(f);
        if (el) el.value = '';
        if (el) updateLabel(el);
      });
      fetchProperties();
    });
  }

  // --- Initial fetch ---
  fetchProperties();
});
