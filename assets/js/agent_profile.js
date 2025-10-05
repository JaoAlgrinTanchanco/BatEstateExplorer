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

window.currentDraftId = null;

document.addEventListener('DOMContentLoaded', () => {
  // -------------------------
  // Image Upload Initialization
  // -------------------------
  window.selectedFiles = window.selectedFiles || [];

  const initImageUpload = ({ dropAreaId, fileInputId, previewId, maxFiles = 10 }) => {
    const dropArea = document.getElementById(dropAreaId);
    const fileInput = document.getElementById(fileInputId);
    const preview = document.getElementById(previewId);

    if (!dropArea || !fileInput || !preview) return;

    const renderPreviews = () => {
      preview.innerHTML = '';
      window.selectedFiles.forEach((file, idx) => {
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
          window.selectedFiles.splice(idx, 1);
          renderPreviews();
        });
        wrap.appendChild(removeBtn);

        const reader = new FileReader();
        reader.onload = e => img.src = e.target.result;
        reader.readAsDataURL(file);

        preview.appendChild(wrap);
      });
    };

    const addFiles = files => {
      const incoming = Array.from(files).filter(f => f.type.startsWith('image/'));
      const existingSigs = new Set(window.selectedFiles.map(f => `${f.name}|${f.size}|${f.lastModified}`));
      for (const f of incoming) {
        if (window.selectedFiles.length >= maxFiles) break;
        const sig = `${f.name}|${f.size}|${f.lastModified}`;
        if (!existingSigs.has(sig)) {
          window.selectedFiles.push(f);
          existingSigs.add(sig);
        }
      }
      renderPreviews();
    };

    // Drag & Drop
    ['dragenter','dragover','dragleave','drop'].forEach(evt =>
      dropArea.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); })
    );
    dropArea.addEventListener('dragover', () => dropArea.classList.add('drag-over'));
    dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));
    dropArea.addEventListener('drop', e => {
      dropArea.classList.remove('drag-over');
      addFiles(e.dataTransfer.files);
    });
    dropArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
      addFiles(fileInput.files);
      fileInput.value = '';
    });

    // Reset helper
    window.resetImageUpload = () => {
      window.selectedFiles.length = 0;
      renderPreviews();
    };
  };

  // Initialize main image upload
  initImageUpload({
    dropAreaId: 'imageUploadArea',
    fileInputId: 'images',
    previewId: 'imagePreview',
    maxFiles: 10
  });

  // -------------------------
  // Save Draft Handler
  // -------------------------
  function loadDrafts() {
    const container = document.getElementById('draftContainer');
    if (!container) return;

    fetch('/BatEstateExplorer/public/api/get_drafts.php') // make sure this returns drafts JSON
      .then(res => res.json())
      .then(data => {
        container.innerHTML = ''; // clear existing drafts
        data.forEach(draft => {
          const div = document.createElement('div');
          div.className = 'draft-card';
          div.dataset.id = draft.id;
          div.innerHTML = `
            <span class="delete-draft">&times;</span>
            ${draft.title}
          `;
          container.appendChild(div);
        });
      })
      .catch(err => console.error('Failed to load drafts:', err));
  }

  // -------------------------
  // Save Draft Handler
  // -------------------------
  document.getElementById('saveDraftBtn')?.addEventListener('click', async (e) => {
    e.preventDefault();
    const form = document.getElementById('addListingForm');
    if (!form) return;

    const fd = new FormData(form);
    window.selectedFiles.forEach(f => fd.append('images[]', f));

    // If editing an existing draft, append the ID
    if (window.currentDraftId) fd.append('id', window.currentDraftId);

    try {
      const res = await fetch('/BatEstateExplorer/public/api/save_draft.php', { method: 'POST', body: fd });
      if (!res.ok) throw new Error(`HTTP error ${res.status}`);

      const data = await res.json();
      if (data.success) {
        notify('success', window.currentDraftId ? 'Draft updated successfully!' : 'Draft saved successfully!');
        form.reset();
        window.resetImageUpload?.();
        loadDrafts?.();
        window.currentDraftId = null; // reset after save
      } else {
        notify('error', data.error || 'Failed to save draft.');
      }
    } catch (err) {
      console.error('Draft save error:', err);
      notify('error', err.message || 'Network error while saving draft.');
    }
  });

  // -------------------------
  // Save Listing Handler
  // -------------------------
  document.getElementById('openListingModalBtn')?.addEventListener('click', () => {
    const form = document.getElementById('addListingForm');
    const walletBalanceEl = document.getElementById('agentWalletBalance');
    const listingFee = 20;

    if (!form.checkValidity()) return form.reportValidity();
    if (!window.selectedFiles.length) return notify('error', 'Please upload at least one image.');

    const walletBalance = parseFloat(walletBalanceEl.innerText.replace(/,/g, ''));
    if (walletBalance < listingFee) return notify('error', 'Insufficient wallet balance.');

    // Open listing fee modal
    window.openListingFeeModal?.();
  });

  // Pay Listing Fee & Submit
  document.getElementById('payListingFeeBtn')?.addEventListener('click', async () => {
    const form = document.getElementById('addListingForm');
    const walletBalanceEl = document.getElementById('agentWalletBalance');
    const fd = new FormData(form);
    window.selectedFiles.forEach(f => fd.append('images[]', f));

    try {
      // Step 1: Charge listing fee
      const feeRes = await fetch('/BatEstateExplorer/public/api/listing_fee.php', { method: 'POST', body: fd });
      const feeData = await feeRes.json();
      if (!feeData.success) throw new Error(feeData.error || 'Failed to process fee');
      walletBalanceEl.innerText = feeData.new_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 });

      // Step 2: Save listing
      const saveRes = await fetch('/BatEstateExplorer/public/api/save_listing.php', { method: 'POST', body: fd });
      const saveData = await saveRes.json();
      if (saveData.success) {
        notify('success', 'Listing submitted! Awaiting admin approval.');
        window.closeListingFeeModal?.();
        form.reset();
        window.resetImageUpload?.();
      } else notify('error', 'Listing fee paid but failed to save listing: ' + (saveData.error || 'Unknown error'));
    } catch (err) {
      notify('error', err.message || 'An error occurred.');
    }
  });

initDraftCards();
}); //END OF DOM

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

// -------------------------
// Draft card click & delete
// -------------------------
// Call this whenever draft cards exist (after loadDrafts or on page load)
function initDraftCards() {
  const container = document.getElementById('draftContainer');
  if (!container) return;

  // Use event delegation so dynamically added cards work
  container.addEventListener('click', e => {
    const card = e.target.closest('.draft-card');
    if (!card) return;

    const draftId = card.dataset.id;
    if (e.target.classList.contains('delete-draft')) {
      // Delete draft
      deleteDraft(draftId, card);
    } else {
      // Load draft
      loadDraftIntoForm(draftId);
    }
  });
}

// Load draft into form
function loadDraftIntoForm(draftId) {
  if (!draftId) return notify('error', 'Invalid draft ID');

  fetch(`/BatEstateExplorer/public/api/get_draft.php?id=${draftId}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) return notify('error', data.error);

      document.getElementById('title').value = data.title || '';
      document.getElementById('location').value = data.location || '';
      document.getElementById('price').value = data.price || '';
      document.getElementById('lot_size').value = data.lot_size || '';
      document.getElementById('property_type').value = data.property_type || '';
      document.getElementById('bedrooms').value = data.bedrooms || '';
      document.getElementById('bathrooms').value = data.bathrooms || '';
      document.getElementById('description').value = data.description || '';

      // Reset images first
      window.resetImageUpload?.();

      // Load existing images into preview
      if (Array.isArray(data.images)) {
        data.images.forEach(src => {
          const wrap = document.createElement('div');
          wrap.className = 'img-wrap';

          const img = document.createElement('img');
          img.className = 'thumb';
          img.src = src;
          wrap.appendChild(img);

          const removeBtn = document.createElement('button');
          removeBtn.type = 'button';
          removeBtn.className = 'remove-img';
          removeBtn.innerHTML = '&times;';
          removeBtn.addEventListener('click', () => wrap.remove()); // optional: mark for deletion in update
          wrap.appendChild(removeBtn);

          document.getElementById('imagePreview').appendChild(wrap);
        });
      }
    })
    .catch(err => notify('error', 'Failed to load draft'));
}

// Delete draft
function deleteDraft(draftId, cardEl) {
  if (!draftId) return notify('error', 'Invalid draft ID');
  if (!confirm('Are you sure you want to delete this draft?')) return;

  fetch('/BatEstateExplorer/public/api/delete_draft.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: draftId })
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        notify('success', 'Draft deleted successfully!');
        cardEl.remove();
      } else {
        notify('error', data.error || 'Failed to delete draft');
      }
    })
    .catch(() => notify('error', 'Network error while deleting draft'));
}
