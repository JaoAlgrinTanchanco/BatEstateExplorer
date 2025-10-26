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
  // --- Profile Picture Remove Logic ---
  const removeBtn = document.getElementById("removeProfilePicBtn");
  const removeInput = document.getElementById("remove_picture");
  const input = document.getElementById("edit_profile_picture");
  const preview = document.getElementById("editProfilePicPreview");

  if (preview && removeBtn && removeInput && input) {
    // Show remove button if an image is already present
    if (preview.querySelector("img")) {
      removeBtn.style.display = "flex";
    }

    // Handle remove button click
    removeBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      // Reset to "Upload Here" text
      preview.innerHTML = `<span class="edit-upload-text">Upload Here</span>`;

      // Hide remove button
      removeBtn.style.display = "none";

      // Clear file input
      input.value = "";

      // Mark for removal on backend
      removeInput.value = "1";
    });

    // When selecting a new image, show remove button again and reset removal flag
    input.addEventListener("change", () => {
      if (input.files.length > 0) {
        removeBtn.style.display = "flex";
        removeInput.value = "0";
      }
    });
  }

  if (input && preview) {
    // Hide file input fully
    input.style.display = "none";

    // Trigger input manually
    preview.addEventListener("click", (e) => {
      e.preventDefault();
      input.click();
    });

    // Handle preview change
    input.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (!file) {
        preview.innerHTML = `<span class="edit-upload-text">Upload Here</span>`;
        return;
      }

      const reader = new FileReader();
      reader.onload = (event) => {
        preview.innerHTML = `<img src="${event.target.result}" alt="Profile Picture">`;
      };
      reader.readAsDataURL(file);
    });
  }

  //edit modal
  const openBtn = document.getElementById("editProfileBtn");
  const editModal = document.getElementById("editModal");

  if (openBtn && editModal) {
    openBtn.addEventListener("click", function () {
      editModal.style.display = "flex";
    });
  }
    //property type
    const propertyType = document.getElementById('property_type');
    const bedrooms = document.getElementById('bedrooms');
    const bathrooms = document.getElementById('bathrooms');

    if (!propertyType || !bedrooms || !bathrooms) return;

    const toggleBedroomsBathrooms = () => {
        if (propertyType.value === 'Lot') {
            bedrooms.value = '';
            bathrooms.value = '';
            bedrooms.disabled = true;
            bathrooms.disabled = true;
        } else {
            bedrooms.disabled = false;
            bathrooms.disabled = false;
        }
    };

    // Initial check
    toggleBedroomsBathrooms();

    // Listen for changes
    propertyType.addEventListener('change', toggleBedroomsBathrooms);

  // -------------------------
  // Image Upload Initialization
  // -------------------------
  window.selectedFiles = [];

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
        wrap.style.position = 'relative';

        const img = document.createElement('img');
        img.className = 'thumb';
        wrap.appendChild(img);

        const reader = new FileReader();
        reader.onload = e => img.src = e.target.result;
        reader.readAsDataURL(file);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerHTML = '&times;';
        Object.assign(removeBtn.style, {
          position: 'absolute', top: '4px', right: '4px',
          background: '#000', color: '#fff',
          border: 'none', borderRadius: '50%',
          width: '24px', height: '24px', fontSize: '16px',
          cursor: 'pointer', display: 'flex',
          alignItems: 'center', justifyContent: 'center',
          padding: '0', zIndex: '10', transition: 'background 0.2s ease'
        });

        removeBtn.addEventListener('mouseenter', () => removeBtn.style.background = 'rgba(255,77,79,0.9)');
        removeBtn.addEventListener('mouseleave', () => removeBtn.style.background = '#000');
        removeBtn.addEventListener('click', () => {
          window.selectedFiles.splice(idx, 1);
          renderPreviews();
        });

        wrap.appendChild(removeBtn);
        preview.appendChild(wrap);
      });
    };

    const addFiles = files => {
      const incoming = Array.from(files).filter(f => f instanceof File && f.type.startsWith('image/'));
      const existingSigs = new Set(window.selectedFiles.map(f => `${f.name}|${f.size}|${f.lastModified}`));
      for (const f of incoming) {
        if (window.selectedFiles.length >= maxFiles) break;
        const sig = `${f.name}|${f.size}|${f.lastModified}`;
        if (!existingSigs.has(sig)) window.selectedFiles.push(f);
      }
      renderPreviews();
    };

    // Drag & drop
    ['dragenter','dragover','dragleave','drop'].forEach(evt => 
      dropArea.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); })
    );
    dropArea.addEventListener('dragover', () => dropArea.classList.add('drag-over'));
    dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));
    dropArea.addEventListener('drop', e => {
      dropArea.classList.remove('drag-over');
      addFiles(e.dataTransfer.files);
    });

    // Click to pick files
    dropArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
      addFiles(fileInput.files);
      fileInput.value = '';
    });

    window.resetImageUpload = () => { window.selectedFiles = []; renderPreviews(); };
    renderPreviews();
  };

  // Initialize image upload
  initImageUpload({ dropAreaId: 'imageUploadArea', fileInputId: 'images', previewId: 'imagePreview', maxFiles: 10 });
  
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
  // Unified Save / Update Draft
  // -------------------------
  document.getElementById('saveDraftBtn')?.addEventListener('click', async e => {
    e.preventDefault();
    const form = document.getElementById('addListingForm');
    if (!form) return;

    const fd = new FormData(form);

    // -------------------------
    // Determine mode (create or update)
    // -------------------------
    const isUpdate = !!window.currentDraftId;
    if (isUpdate) fd.append('id', window.currentDraftId);

    // -------------------------
    // Handle Images
    // -------------------------
    // window.selectedFiles = current files in form
    // window.existingImages = loaded from DB (for update)
    const existingImages = window.existingImages || [];
    const currentImages = window.selectedFiles.map(f => f.name || f); // keep names as signature

    // Compute removed images (in DB but not in current form)
    const removedImages = isUpdate
      ? existingImages.filter(img => !currentImages.includes(img.split('/').pop()))
      : [];

    removedImages.forEach(img => fd.append('remove_images[]', img));

    // Append new images (not in DB)
    window.selectedFiles.forEach(f => fd.append('images[]', f));

    // -------------------------
    // Handle Documents
    // -------------------------
    const existingDocs = window.existingDocs || [];
    const docItems = Array.from(document.querySelectorAll('#documentPreview .doc-item'));
    const currentDocs = docItems.map(item => item.dataset.path || item.file?.name);
    const removedDocs = isUpdate
      ? existingDocs.filter(doc => !currentDocs.includes(doc.split('/').pop()))
      : [];
    removedDocs.forEach(doc => fd.append('remove_docs[]', doc));

    // Append new docs
    docItems.forEach(item => {
      if (item.file) fd.append('property_document[]', item.file);
    });

    console.group("FormData Before Upload");
    for (let [key, val] of fd.entries()) console.log(key, val);
    console.groupEnd();

    try {
      const url = isUpdate
        ? '/BatEstateExplorer/public/api/update_draft.php'
        : '/BatEstateExplorer/public/api/save_draft.php';

      const res = await fetch(url, { method: 'POST', body: fd });
      if (!res.ok) throw new Error(`HTTP error ${res.status}`);
      const data = await res.json();
      console.log('Draft Save/Update Response:', data);

      if (data.success) {
        notify(isUpdate ? 'Draft updated successfully!' : 'Draft saved successfully!');
        form.reset();
        window.resetImageUpload?.();
        window.resetDocumentUpload?.();
        window.currentDraftId = null;
        window.existingImages = [];
        window.existingDocs = [];
        loadDrafts?.();
      } else {
        notify('error', data.error || 'Failed to save draft.');
      }
    } catch (err) {
      console.error('Draft save/update error:', err);
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

    // Accept both new files and draft images
    const totalImages = (window.selectedFiles?.length || 0) + (window.draftImages?.length || 0);
    if (totalImages === 0) return notify('error', 'Please upload at least one image.');

    const walletBalance = parseFloat(walletBalanceEl.innerText.replace(/,/g, ''));
    if (walletBalance < listingFee) return notify('error', 'Insufficient wallet balance.');

    // Open listing fee modal
    document.getElementById('listingFeeModal').style.display = 'flex';
  });

  function closeListingFeeModal() {
    document.getElementById('listingFeeModal').style.display = 'none';
  }
  window.closeListingFeeModal = closeListingFeeModal;

  // -------------------------
  // Pay Listing Fee & Submit
  // -------------------------
  document.getElementById('payListingFeeBtn')?.addEventListener('click', async () => {
      const form = document.getElementById('addListingForm');
      const walletBalanceEl = document.getElementById('agentWalletBalance');
      const fd = new FormData(form);

      // Append images
      window.selectedFiles.forEach(f => {
          if (f instanceof File) {
              fd.append('images[]', f);
          } else if (typeof f === 'string') {
              fd.append('existing_images[]', f);
          }
      });

      // -------------------------
      // Append property documents
      // -------------------------
      window.selectedDocuments?.forEach(f => {
          if (f instanceof File) {
              fd.append('property_documents[]', f);
          } else if (typeof f === 'string') {
              fd.append('existing_property_documents[]', f);
          }
      });

      // If editing a draft, send the draft ID
      if (window.currentDraftId) {
          fd.append('draft_id', window.currentDraftId);
      }

      try {
          const feeRes = await fetch('/BatEstateExplorer/public/api/listing_fee.php', { method: 'POST', body: fd });
          const feeData = await feeRes.json();
          if (!feeData.success) throw new Error(feeData.error || 'Failed to process fee');
          walletBalanceEl.innerText = feeData.new_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 });

          const saveRes = await fetch('/BatEstateExplorer/public/api/save_listing.php', { method: 'POST', body: fd });
          const saveData = await saveRes.json();

          if (saveData.success) {
              notify('success', 'Listing submitted! Awaiting admin approval.');
              window.closeListingFeeModal?.();
              form.reset();
              window.resetImageUpload?.();

              // Remove draft card if it exists
              if (window.currentDraftId) {
                  const draftCard = document.querySelector(`.draft-card[data-id="${window.currentDraftId}"]`);
                  draftCard?.remove();
                  window.currentDraftId = null;
              }

              // Optionally reload drafts to refresh UI completely
              loadDrafts?.();
          } else {
              notify('error', 'Listing fee paid but failed to save listing: ' + (saveData.error || 'Unknown error'));
          }

      } catch (err) {
          notify('error', err.message || 'An error occurred.');
      }
  });

  initDraftCards();

  // -------------------------
  // Property Document Upload (multiple files)
  // -------------------------
  window.selectedDocuments = window.selectedDocuments || [];

  const initDocumentUpload = ({ dropAreaId, fileInputId, previewId, maxFiles = 10 }) => {
    const dropArea = document.getElementById(dropAreaId);
    const fileInput = document.getElementById(fileInputId);
    const preview = document.getElementById(previewId);

    if (!dropArea || !fileInput || !preview) return;

    const renderPreviews = () => {
      preview.innerHTML = '';

      window.selectedDocuments.forEach((file, idx) => {
        const wrap = document.createElement('div');
        wrap.className = 'doc-wrap';

        const nameEl = document.createElement('span');
        nameEl.className = 'doc-name';
        nameEl.innerText = file.name;
        wrap.appendChild(nameEl);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-doc';
        removeBtn.innerHTML = '&times;';
        removeBtn.addEventListener('click', () => {
          window.selectedDocuments.splice(idx, 1);
          renderPreviews();
        });

        wrap.appendChild(removeBtn);
        preview.appendChild(wrap);
      });
    };

    const addFiles = (files) => {
      const incoming = Array.from(files);
      const existingSigs = new Set(window.selectedDocuments.map(f => `${f.name}|${f.size}|${f.lastModified}`));
      for (const f of incoming) {
        if (window.selectedDocuments.length >= maxFiles) break;
        const sig = `${f.name}|${f.size}|${f.lastModified}`;
        if (!existingSigs.has(sig)) {
          window.selectedDocuments.push(f);
          existingSigs.add(sig);
        }
      }
      renderPreviews();
    };

    // Drag & Drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt =>
      dropArea.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); })
    );
    dropArea.addEventListener('dragover', () => dropArea.classList.add('drag-over'));
    dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));
    dropArea.addEventListener('drop', e => {
      dropArea.classList.remove('drag-over');
      addFiles(e.dataTransfer.files);
    });

    // Click to open file picker
    dropArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
      addFiles(fileInput.files);
      fileInput.value = ''; // reset input
    });

    // Reset helper
    window.resetDocumentUpload = () => {
      window.selectedDocuments.length = 0;
      renderPreviews();
    };

    // Initial render
    renderPreviews();
  };

  // Initialize
  initDocumentUpload({
    dropAreaId: 'documentUploadArea',
    fileInputId: 'property_document',
    previewId: 'documentPreview',
    maxFiles: 10
  });

  // -------------------------
  // Document preview styling + inline remove button
  // -------------------------
  const docInput = document.getElementById('property_document');
  const docArea = document.getElementById('documentUploadArea');

  // Function to update the document preview (always visible)
  function updateDocumentPreview() {
    const preview = document.getElementById('documentPreview');
    if (!preview) return;

    preview.innerHTML = '';

    window.selectedDocuments.forEach((file, index) => {
      const item = document.createElement('div');
      item.className = 'doc-item';
      item.style.position = 'relative';
      item.style.padding = '12px 16px';
      item.style.border = '1px solid #ddd';
      item.style.borderRadius = '8px';
      item.style.background = '#f8f8f8';
      item.style.display = 'flex';
      item.style.alignItems = 'center';
      item.style.justifyContent = 'center';
      item.style.minWidth = '180px';
      item.style.wordBreak = 'break-word';

      // ✅ Support both existing URLs and new File objects
      const link = document.createElement('a');
      if (typeof file === 'string') {
        link.href = file.startsWith('/') ? file : '/' + file;
        link.textContent = file.split('/').pop();
      } else {
        link.href = URL.createObjectURL(file);
        link.textContent = file.name;
      }

      link.target = '_blank';
      link.style.color = '#007bff';
      link.style.textDecoration = 'none';
      link.style.textAlign = 'center';
      link.style.fontSize = '14px';
      link.style.maxWidth = '160px';
      link.style.overflow = 'hidden';
      link.style.textOverflow = 'ellipsis';
      link.style.whiteSpace = 'nowrap';

      // ❌ remove button
      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.innerHTML = '&times;';
      removeBtn.style.position = 'absolute';
      removeBtn.style.top = '4px';
      removeBtn.style.right = '4px';
      removeBtn.style.background = '#000';
      removeBtn.style.color = '#fff';
      removeBtn.style.border = 'none';
      removeBtn.style.borderRadius = '50%';
      removeBtn.style.width = '22px';
      removeBtn.style.height = '22px';
      removeBtn.style.fontSize = '16px';
      removeBtn.style.cursor = 'pointer';
      removeBtn.style.display = 'flex';
      removeBtn.style.alignItems = 'center';
      removeBtn.style.justifyContent = 'center';
      removeBtn.style.padding = '0';
      removeBtn.style.transition = 'background 0.2s ease';

      removeBtn.addEventListener('mouseenter', () => {
        removeBtn.style.background = 'rgba(255, 77, 79, 0.9)';
      });
      removeBtn.addEventListener('mouseleave', () => {
        removeBtn.style.background = '#000';
      });

      removeBtn.addEventListener('click', () => {
        window.selectedDocuments.splice(index, 1);
        updateDocumentPreview();
      });

      item.appendChild(link);
      item.appendChild(removeBtn);
      preview.appendChild(item);
    });
  }

  function syncDocumentInput() {
    const docInput = document.getElementById('property_document');
    const dataTransfer = new DataTransfer();

    window.selectedDocuments.forEach(file => {
      if (file instanceof File) dataTransfer.items.add(file);
    });

    docInput.files = dataTransfer.files;
  }

  // Handle file selection via input
  docInput.addEventListener('change', (e) => {
    const files = Array.from(e.target.files);
    window.selectedDocuments.push(...files);
    updateDocumentPreview();
    syncDocumentInput();
  });

  // Handle drag & drop
  docArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    docArea.classList.add('drag-over');
  });
  docArea.addEventListener('dragleave', () => docArea.classList.remove('drag-over'));
  docArea.addEventListener('drop', (e) => {
    e.preventDefault();
    docArea.classList.remove('drag-over');
    const files = Array.from(e.dataTransfer.files);
    window.selectedDocuments.push(...files);
    updateDocumentPreview();
    syncDocumentInput();
  });

  // Always visible preview (no hide/show)
  updateDocumentPreview();

}); //END OF DOM

// =========================
// Delete Account Modal Logic
// =========================
function openDeleteModal() {
  const modal = document.getElementById('deleteModal');
  if (!modal) return;
  modal.style.display = 'flex'; // matches your CSS
}

function closeDeleteModal() {
  const modal = document.getElementById('deleteModal');
  if (!modal) return;
  modal.style.display = 'none';
}

function openEditProfileModal() {
  const modal = document.getElementById('editModal');
  if (!modal) return;
  modal.style.display = 'flex'; // matches your modal CSS style
}

function closeEditProfileModal() {
  const modal = document.getElementById('editModal');
  if (!modal) return;
  modal.style.display = 'none';
}

// Open edit modal
function openModal(propertyId) {
    const modal = document.getElementById(`editModal-${propertyId}`);
    if (!modal) return;
    modal.style.display = 'flex'; // or 'block', depending on your CSS
}

// Close edit modal
function closeModal(propertyId) {
    const modal = document.getElementById(`editModal-${propertyId}`);
    if (!modal) return;
    modal.style.display = 'none';
}

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
  const emailInput = document.getElementById('searchEmail');
  const email = emailInput.value.trim();
  if (!email) return notify('error', 'Please enter an email');

  fetch(`/BatEstateExplorer/public/api/give_privilege.php?email=${encodeURIComponent(email)}`)
    .then(res => res.json())
    .then(data => {
      const section = document.getElementById('grantPrivilegeSection');
      const giveBtn = document.getElementById('givePrivilegeBtn');
      const userNameEmail = document.getElementById('userNameEmail');

      // Clear previous selection if client not found
      if (data.error || !data.email) {
        window.currentEmail = null;
        userNameEmail.innerHTML = '';
        section.style.opacity = '0.5';
        section.style.pointerEvents = 'none';
        giveBtn.disabled = true;
        return notify('error', data.error || 'Client not found.');
      }

      // Set current email for givePrivilege
      window.currentEmail = data.email;

      // Render client card
      userNameEmail.innerHTML = `
        <div class="client-card">
          <img src="${data.profile_image}" alt="${data.name}" class="client-pfp">
          <div class="client-info">
            <div class="name-email">${data.name} &middot; ${data.email}</div>
            <div class="joined">Joined ${data.joined}</div>
          </div>
        </div>
      `;

      // Enable grant privilege section
      section.style.opacity = '1';
      section.style.pointerEvents = 'auto';
      giveBtn.disabled = false;

      notify('success', 'Client found! Select a property to grant privilege.');
    })
    .catch(() => {
      notify('error', 'Search client error.');
    });
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
        
        // Reset selection
        selectedPropertyId = null;
        document.querySelectorAll('.property-card').forEach(c => c.classList.remove('selected'));

        // Reload page after short delay to show the success notification
        setTimeout(() => {
          window.location.reload();
        }, 500); // 0.5s delay
      } else {
        notify('error', data.error || 'Something went wrong.');
      }
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

// -------------------------
// Load Draft into Form (Update Mode)
// -------------------------
function loadDraftIntoForm(draftId) {
  if (!draftId) return notify('error', 'Invalid draft ID');

  fetch(`/BatEstateExplorer/public/api/get_draft.php?id=${draftId}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) return notify('error', data.error);

      // -------------------------
      // Fill form fields
      // -------------------------
      document.getElementById('title').value         = data.title || '';
      document.getElementById('location').value      = data.location || '';
      document.getElementById('price').value         = data.price || '';
      document.getElementById('lot_size').value      = data.lot_size || '';
      document.getElementById('property_type').value = data.property_type || '';
      document.getElementById('bedrooms').value      = data.bedrooms || '';
      document.getElementById('bathrooms').value     = data.bathrooms || '';
      document.getElementById('description').value   = data.description || '';

      // -------------------------
      // Reset previous images/docs
      // -------------------------
      window.resetImageUpload?.();
      const docPreview = document.getElementById('documentPreview');
      docPreview.innerHTML = '';
      window.selectedFiles = [];
      window.selectedDocuments = [];

      // -------------------------
      // Render Images
      // -------------------------
      if (Array.isArray(data.images)) {
        const preview = document.getElementById('imagePreview');
        data.images.forEach(src => {
          const wrap = document.createElement('div');
          wrap.className = 'img-wrap';

          const img = document.createElement('img');
          img.className = 'thumb';
          img.src = src;
          wrap.appendChild(img);

          const removeBtn = document.createElement('button');
          removeBtn.type = 'button';
          removeBtn.innerHTML = '&times;';
          removeBtn.addEventListener('click', () => {
            wrap.remove();
            window.selectedFiles = window.selectedFiles.filter(f => f !== src);
          });
          wrap.appendChild(removeBtn);

          preview.appendChild(wrap);

          // Add to both "selected" and "existing" for update logic
          window.selectedFiles.push(src);
        });

        // Keep track of existing images for update comparison
        window.existingImages = [...window.selectedFiles];
      }

      // -------------------------
      // Render Documents
      // -------------------------
      if (data.property_document_path) {
        const docs = data.property_document_path.split(',').filter(Boolean);

        docs.forEach(path => {
          const fileName = path.split('/').pop();
          const fullPath = '/' + path.replace(/^\/?/, '');

          const item = document.createElement('div');
          item.className = 'doc-item';
          item.style.position = 'relative';

          const link = document.createElement('a');
          link.textContent = fileName;
          link.href = fullPath;
          link.target = '_blank';
          item.appendChild(link);

          const removeBtn = document.createElement('button');
          removeBtn.type = 'button';
          removeBtn.innerHTML = '&times;';
          removeBtn.style.position = 'absolute';
          removeBtn.style.top = '4px';
          removeBtn.style.right = '4px';
          removeBtn.addEventListener('click', () => {
            item.remove();
            // mark for removal in FormData
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'remove_existing_docs[]';
            hidden.value = path;
            docPreview.closest('form').appendChild(hidden);

            // Remove from selectedDocuments
            window.selectedDocuments = window.selectedDocuments.filter(f => f !== fullPath);
          });

          item.appendChild(removeBtn);
          docPreview.appendChild(item);

          window.selectedDocuments.push(fullPath);
        });

        // Keep track of existing docs for update comparison
        window.existingDocs = [...window.selectedDocuments];
      }

      // -------------------------
      // Set draft ID
      // -------------------------
      window.currentDraftId = draftId;
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