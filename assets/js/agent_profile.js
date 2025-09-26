// Notification helper function
function notify(type, message) {
  const container = document.querySelector('.notification-container')
    || (() => {
      const wrap = document.createElement('div');
      wrap.className = 'notification-container';
      document.body.appendChild(wrap);
      return wrap;
    })();

  const notif = document.createElement('div');
  notif.className = `notification ${type}`;
  const icon = type === 'success'
    ? '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="#fff" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>'
    : '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="#fff" d="m13 13h-2v-6h2zm0 4h-2v-2h2zm-1-15c-1.3132 0-2.61358.25866-3.82683.7612-1.21326.50255-2.31565 1.23915-3.24424 2.16773-1.87536 1.87537-2.92893 4.41891-2.92893 7.07107 0 2.6522 1.05357 5.1957 2.92893 7.0711.92859.9286 2.03098 1.6651 3.24424 2.1677 1.21325.5025 2.51363.7612 3.82683.7612 2.6522 0 5.1957-1.0536 7.0711-2.9289 1.8753-1.8754 2.9289-4.4189 2.9289-7.0711 0-1.3132-.2587-2.61358-.7612-3.82683-.5026-1.21326-1.2391-2.31565-2.1677-3.24424-.9286-.92858-2.031-1.66518-3.2443-2.16773-1.2132-.50254-2.5136-.7612-3.8268-.7612z"/></svg>';
  notif.innerHTML = `
    <div class="notification__icon">${icon}</div>
    <div class="notification__title">${message}</div>
    <div class="notification__close">&times;</div>
  `;
  container.appendChild(notif);
  notif.querySelector('.notification__close').addEventListener('click', () => notif.remove());
  setTimeout(() => notif.remove(), 5000);
}

document.addEventListener('DOMContentLoaded', () => {

  // Toggle Bedrooms/Bathrooms based on property type
  const toggleRooms = (typeSelect, bedroomsInput, bathroomsInput) => {
    const isLot = typeSelect.value === 'Lot';
    bedroomsInput.disabled = isLot;
    bathroomsInput.disabled = isLot;
    if (isLot) { bedroomsInput.value = 0; bathroomsInput.value = 0; }
  };

  // Drag & Drop Image Upload Initialization
  window.selectedFiles = window.selectedFiles || [];
  const initImageUpload = ({ dropAreaId, fileInputId, previewId, formId, maxFiles = 10 }) => {
    const dropArea = document.getElementById(dropAreaId);
    const fileInput = document.getElementById(fileInputId);
    const preview = document.getElementById(previewId);
    const form = document.getElementById(formId);
    if (!dropArea || !fileInput || !form) return;

    let selectedFiles = window.selectedFiles;
    const fileSignature = f => `${f.name}|${f.size}|${f.lastModified}`;

    const renderPreviews = () => {
      preview.innerHTML = '';
      selectedFiles.forEach((file, index) => {
        const wrap = document.createElement('div');
        wrap.className = 'img-wrap';
        const img = document.createElement('img');
        img.className = 'thumb';
        wrap.appendChild(img);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-img';
        removeBtn.innerHTML = '&times;';
        removeBtn.addEventListener('click', () => {
          selectedFiles.splice(index, 1);
          renderPreviews();
        });
        wrap.appendChild(removeBtn);

        const reader = new FileReader();
        reader.onload = e => (img.src = e.target.result);
        reader.readAsDataURL(file);
        preview.appendChild(wrap);
      });
    };

    const addFiles = fileList => {
      if (!fileList) return;
      const incoming = Array.from(fileList).filter(f => f.type.startsWith('image/'));
      const existingSigs = new Set(selectedFiles.map(fileSignature));
      for (const f of incoming) {
        if (selectedFiles.length >= maxFiles) break;
        if (!existingSigs.has(fileSignature(f))) {
          selectedFiles.push(f);
          existingSigs.add(fileSignature(f));
        }
      }
      renderPreviews();
    };

    // Set up drag and drop and file input event listeners
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt =>
      dropArea.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); })
    );
    dropArea.addEventListener('dragover', () => dropArea.classList.add('drag-over'));
    dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));
    dropArea.addEventListener('drop', e => { dropArea.classList.remove('drag-over'); addFiles(e.dataTransfer.files); });
    dropArea.addEventListener('click', () => fileInput.click());
    dropArea.addEventListener('keydown', e => { if (['Enter', ' '].includes(e.key)) { e.preventDefault(); fileInput.click(); } });
    fileInput.addEventListener('change', () => { addFiles(fileInput.files); fileInput.value = ''; });

    // Handle standard form submission (used by Direct Agents)
    if (form.id === 'addListingForm') {
      form.addEventListener('submit', e => {
        e.preventDefault();
        const fd = new FormData(form);
        selectedFiles.forEach(f => fd.append('images[]', f));
        fetch(form.action, { method: 'POST', body: fd })
          .then(res => res.text())
          .then(() => {
            notify('success', 'Listing saved!');
            form.reset();
            selectedFiles = [];
            renderPreviews();
          })
          .catch(() => notify('error', 'Upload failed!'));
      });
    }

    // Expose reset function globally
    window.resetImageUpload = () => {
      selectedFiles = [];
      renderPreviews();
    };
  };

  // Initialize image upload for the main form
  initImageUpload({
    dropAreaId: 'imageUploadArea',
    fileInputId: 'images',
    previewId: 'imagePreview',
    formId: 'addListingForm',
    maxFiles: 10
  });

  // Edit modals functions
  window.openModal = id => document.getElementById(`editModal-${id}`).style.display = 'block';
  window.closeModal = id => document.getElementById(`editModal-${id}`).style.display = 'none';
  window.onclick = e => { document.querySelectorAll('.edit-modal').forEach(m => { if (e.target === m) m.style.display = 'none'; }); };
  window.removeImage = btn => btn.closest('.slider-item').remove();

  // Bedrooms/Bathrooms initial setup for edit modals
  document.querySelectorAll('.edit-modal').forEach(modal => {
    const typeSelect = modal.querySelector('select[name="property_type"]');
    const bedrooms = modal.querySelector('input[name="bedrooms"]');
    const bathrooms = modal.querySelector('input[name="bathrooms"]');
    if (!typeSelect || !bedrooms || !bathrooms) return;
    toggleRooms(typeSelect, bedrooms, bathrooms);
    typeSelect.addEventListener('change', () => toggleRooms(typeSelect, bedrooms, bathrooms));
  });

  // Bedrooms/Bathrooms initial setup for Add Listing form
  const addType = document.getElementById('property_type');
  const addBeds = document.getElementById('bedrooms');
  const addBaths = document.getElementById('bathrooms');
  if (addType && addBeds && addBaths) {
    toggleRooms(addType, addBeds, addBaths);
    addType.addEventListener('change', () => toggleRooms(addType, addBeds, addBaths));
  }

  // Company listing modal details fetch
  document.querySelectorAll('.view-details').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const propertyId = link.dataset.id;
      fetch(`/BatEstateExplorer/public/api/get_company_listings.php?id=${propertyId}`)
        .then(res => res.json())
        .then(data => {
          if (data.error) return notify('error', data.error);
          document.getElementById('propertyTitle').textContent = data.title || 'N/A';
          const carousel = document.getElementById('carouselImages');
          carousel.innerHTML = '';
          if (data.images?.length) {
            data.images.forEach((img, idx) => {
              carousel.innerHTML += `<div class="carousel-item ${idx === 0 ? 'active' : ''}"><img src="storage/uploads/property_images/${img}" class="d-block w-100"></div>`;
            });
          } else carousel.innerHTML = '<div class="carousel-item active"><p>No images</p></div>';
          const details = document.getElementById('propertyDetails');
          details.innerHTML = `
            <li class="list-group-item"><b>Location:</b> ${data.location}</li>
            <li class="list-group-item"><b>Price:</b> ₱${parseFloat(data.price).toLocaleString()}</li>
            <li class="list-group-item"><b>Bedrooms:</b> ${data.bedrooms}</li>
            <li class="list-group-item"><b>Bathrooms:</b> ${data.bathrooms}</li>
            <li class="list-group-item"><b>Size:</b> ${data.sqm} sqm</li>
            <li class="list-group-item"><b>Status:</b> ${data.status}</li>
            <li class="list-group-item"><b>Created By:</b> ${data.created_by ?? 'N/A'}</li>
            <li class="list-group-item"><b>Sold By:</b> ${data.sold_by ?? 'N/A'}</li>`;
        })
        .catch(() => notify('error', 'Error fetching property details.'));
    });
  });

  // Edit profile modal logic
  const editBtn = document.getElementById("editProfileBtn");
  const editModal = document.getElementById("editModal");
  const cancelEditBtn = document.getElementById("cancelEditBtn");

  if (editBtn && editModal && cancelEditBtn) {
    editBtn.addEventListener("click", () => {
      editModal.style.display = "flex";
    });

    cancelEditBtn.addEventListener("click", () => {
      editModal.style.display = "none";
    });

    window.addEventListener("click", e => {
      if (e.target === editModal) editModal.style.display = "none";
    });
  }

  // Delete agent modal logic (Direct Agent) - Redundant code below was removed, consolidating the logic here.
  const openDeleteModalBtn = document.getElementById("openDeleteModal");
  const deleteModal = document.getElementById("deleteModal");
  const cancelDeleteBtnEl = document.getElementById("cancelDeleteBtn");
  const deleteForm = document.getElementById("deleteAgentForm");
  const confirmBtn = document.getElementById("confirmDeleteBtn");
  const spinner = document.getElementById("deleteSpinner");

  if (openDeleteModalBtn) openDeleteModalBtn.addEventListener("click", () => deleteModal.style.display = "flex");
  if (cancelDeleteBtnEl) cancelDeleteBtnEl.addEventListener("click", () => deleteModal.style.display = "none");
  if (deleteForm) deleteForm.addEventListener("submit", () => {
    // Only hide button and show spinner on submit
    if (confirmBtn) confirmBtn.style.display = "none";
    if (spinner) spinner.style.display = "flex";
  });

  // Listing Fee Modal & Submit Flow (Associate Agent)
  const listingForm = document.getElementById('addListingForm');
  const listingModal = document.getElementById('listingFeeModal');
  const walletBalanceEl = document.getElementById('agentWalletBalance');
  const payBtn = document.getElementById('payListingFeeBtn');
  const listingFee = 20;
  const openListingBtn = document.getElementById('openListingModalBtn');

  if (listingForm && listingModal && walletBalanceEl && payBtn && openListingBtn) {

    // Open / Close modal helpers
    const openListingFeeModal = () => listingModal.style.display = 'flex';
    const closeListingFeeModal = (e) => {
      if (!e || e.target === listingModal) listingModal.style.display = 'none';
    };

    // Click "Save Listing" -> validate form + images -> show modal
    openListingBtn.addEventListener('click', () => {
      if (!listingForm.checkValidity()) {
        listingForm.reportValidity();
        return;
      }
      if (!window.selectedFiles || window.selectedFiles.length === 0) {
        notify('error', 'Please upload at least one property image.');
        return;
      }

      // Wallet balance check
      const walletBalance = parseFloat(walletBalanceEl.innerText.replace(/,/g, ''));
      if (walletBalance < listingFee) {
        notify('error', 'Insufficient wallet balance. Please deposit first.');
        return;
      }

      openListingFeeModal();
    });

    // Click "Pay Listing Fee & Submit" -> process fee -> save listing
    payBtn.addEventListener('click', () => {
      const walletBalance = parseFloat(walletBalanceEl.innerText.replace(/,/g, ''));
      if (walletBalance < listingFee) {
        notify('error', 'Insufficient wallet balance. Please deposit first.');
        return;
      }
      if (!window.selectedFiles || window.selectedFiles.length === 0) {
        notify('error', 'Please upload at least one property image.');
        return;
      }

      const formData = new FormData(listingForm);
      window.selectedFiles.forEach(f => formData.append('images[]', f));

      // Step 1: Charge listing fee
      fetch('/BatEstateExplorer/public/api/listing_fee.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(feeData => {
          if (!feeData.success) throw new Error(feeData.error || 'Failed to process listing fee.');
          walletBalanceEl.innerText = feeData.new_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 });

          // Step 2: Save listing
          return fetch('/BatEstateExplorer/public/api/save_listing.php', { method: 'POST', body: formData });
        })
        .then(res => res.json())
        .then(saveData => {
          if (saveData.success) {
            notify('success', 'Listing submitted! Awaiting admin approval.');
            closeListingFeeModal();
            listingForm.reset();
            window.resetImageUpload(); // clears previews & selectedFiles
          } else {
            notify('error', 'Listing fee paid but failed to save listing: ' + (saveData.error || 'Unknown error'));
          }
        })
        .catch(err => notify('error', err.message || 'An error occurred.'));
    });

    // Expose modal close globally
    window.closeListingFeeModal = closeListingFeeModal;
    window.openListingFeeModal = openListingFeeModal;
  }

  // Trim old agent transactions
  const TRIM_AGENT_API = '/BatEstateExplorer/public/api/agent_trim_transact.php';

  async function trimAgentTransactions() {
    try {
      const res = await fetch(TRIM_AGENT_API, { method: 'POST', credentials: 'same-origin' });
      const data = await res.json();
      // Remove debugging lines
    } catch (err) {
      // Remove debugging lines
    }
  }

  trimAgentTransactions(); // Call immediately
  document.addEventListener('visibilitychange', () => { // Also refresh on tab visibility change
    if (document.visibilityState === 'visible') {
      trimAgentTransactions();
    }
  });

}); // End DOMContentLoaded

// Global functions
function toggleSoldBy(select, propertyId) {
  const container = document.getElementById('soldByContainer-' + propertyId);
  if (!container) return;
  if (select.value === 'sold_by') container.style.display = 'block';
  else { container.style.display = 'none'; container.querySelector('input').value = ''; }
}

function confirmEdit(propertyId) {
  const form = document.getElementById('editForm-' + propertyId);
  if (!form) return;
  if (confirm("Are you sure you want to update this listing?")) form.submit();
}

let selectedPropertyId = null;
window.currentEmail = null;

// Search client logic
window.searchClient = function() {
  const email = document.getElementById('searchEmail').value.trim();
  if (!email) return notify('error', 'Please enter an email');

  fetch(`/BatEstateExplorer/public/api/give_privilege.php?email=${encodeURIComponent(email)}`)
    .then(res => res.json())
    .then(data => {
      if (data.error || !data.email) {
        window.currentEmail = null;
        document.getElementById('userNameEmail').textContent = '';
        document.getElementById('grantPrivilegeSection').style.opacity = '0.5';
        document.getElementById('grantPrivilegeSection').style.pointerEvents = 'none';
        document.getElementById('givePrivilegeBtn').disabled = true;
        return notify('error', data.error || 'Client not found.');
      }

      window.currentEmail = data.email;
      document.getElementById('userNameEmail').textContent = `${data.name || ''} (${data.email})`;
      const section = document.getElementById('grantPrivilegeSection');
      const giveBtn = document.getElementById('givePrivilegeBtn');
      section.style.opacity = '1';
      section.style.pointerEvents = 'auto';
      giveBtn.disabled = false;

      notify('success', 'Client found! Select a property to grant privilege.');
    })
    .catch(() => notify('error', 'Search client error.'));
};

// Select property for privilege
window.selectProperty = function(card, propertyId) {
  if (!window.currentEmail) return;
  document.querySelectorAll('.property-card').forEach(c => c.classList.remove('selected'));
  card.classList.add('selected');
  selectedPropertyId = propertyId;
};

// Grant privilege
window.givePrivilege = function() {
  if (!selectedPropertyId) return notify('error', 'Please select a property first.');
  if (!window.currentEmail) return notify('error', 'No client selected.');

  if (!confirm('Are you sure you want to grant this privilege?')) return;

  fetch('/BatEstateExplorer/public/api/give_privilege.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `email=${encodeURIComponent(window.currentEmail)}&property_id=${encodeURIComponent(selectedPropertyId)}`
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        notify('success', 'Privilege granted successfully!');
        selectedPropertyId = null;
        document.querySelectorAll('.property-card').forEach(c => c.classList.remove('selected'));
      } else notify('error', data.error || 'Something went wrong.');
    })
    .catch(() => notify('error', 'Give privilege error.'));
};

// Mark image for removal in edit forms
function markImageForRemoval(button, imagePath) {
  const imgItem = button.closest('.image-item, .image-item2');
  if (!imgItem) return;
  imgItem.style.opacity = '0.5';

  const form = button.closest('form');
  if (!form) return;

  let container = form.querySelector('[id^="removeImages-"]');
  if (!container) {
    container = document.createElement('div');
    container.id = `removeImages-${form.id}`;
    form.appendChild(container);
  }

  const hiddenInput = document.createElement('input');
  hiddenInput.type = 'hidden';
  hiddenInput.name = 'remove_images[]';
  hiddenInput.value = imagePath;
  container.appendChild(hiddenInput);

  button.disabled = true;
}

// Sidebar toggle animation
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarWrapper = document.querySelector('.agent-sidebar-wrapper');

sidebarToggle.addEventListener('click', () => {
  sidebarWrapper.classList.toggle('open');
  const icon = sidebarToggle.querySelector('i');

  if (sidebarWrapper.classList.contains('open')) {
    sidebarToggle.style.left = '16rem';
    icon.classList.replace('fa-arrow-right', 'fa-arrow-left');
  } else {
    sidebarToggle.style.left = '1rem';
    icon.classList.replace('fa-arrow-left', 'fa-arrow-right');
  }
});

// Format wallet balance display
const balanceEl = document.getElementById('walletBalance');
balanceEl.innerText = parseFloat(balanceEl.innerText.replace(/,/g, '')).toLocaleString('en-PH', { minimumFractionDigits: 2 });

// Delete listing function
function deleteListing(id) {
  if (confirm("Are you sure you want to delete this listing?")) {
    document.getElementById(`deleteForm-${id}`).submit();
  }
}