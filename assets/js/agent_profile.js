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
  window.selectedFiles = []; // Initialize globally

  const initImageUpload = ({ dropAreaId, fileInputId, previewId, maxFiles = 10 }) => {
      const dropArea = document.getElementById(dropAreaId);
      const fileInput = document.getElementById(fileInputId);
      const preview = document.getElementById(previewId);
      if (!dropArea || !fileInput || !preview) return;

      // 🛑 FIX: This consolidated function handles both File objects and strings (URLs)
      const renderPreviews = () => {
          preview.innerHTML = '';
          window.selectedFiles.forEach((file, idx) => {
              const wrap = document.createElement('div');
              wrap.className = 'img-wrap';
              wrap.style.position = 'relative';

              const img = document.createElement('img');
              img.className = 'thumb';
              wrap.appendChild(img);

              let isFileObject = file instanceof File;
              
              if (isFileObject) {
                  // Handle new File object (read locally)
                  const reader = new FileReader();
                  reader.onload = e => img.src = e.target.result;
                  reader.readAsDataURL(file);
              } else if (typeof file === 'string') {
                  // Handle existing URL string from the database
                  img.src = file.startsWith('/') ? file : '/' + file;
              } else {
                  return; // Skip invalid entries
              }

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
                  renderPreviews(); // Re-render after removal
              });

              wrap.appendChild(removeBtn);
              preview.appendChild(wrap);
          });
      };

      // ... (rest of addFiles function remains the same) ...
      const addFiles = files => {
          const incoming = Array.from(files).filter(f => f instanceof File && f.type.startsWith('image/'));
          // NOTE: Signatures only need to be checked against other File objects.
          const existingSigs = new Set(window.selectedFiles
              .filter(f => f instanceof File)
              .map(f => `${f.name}|${f.size}|${f.lastModified}`)
          );
          
          for (const f of incoming) {
              if (window.selectedFiles.length >= maxFiles) break;
              const sig = `${f.name}|${f.size}|${f.lastModified}`;
              if (!existingSigs.has(sig)) window.selectedFiles.push(f);
          }
          renderPreviews();
      };

      // ... (Drag & drop and click listeners remain the same) ...

      dropArea.addEventListener('click', () => fileInput.click());
      fileInput.addEventListener('change', () => {
          addFiles(fileInput.files);
          fileInput.value = '';
      });

      // 🛑 FIX: Expose the renderer so loadDraftIntoForm can use it
      window.renderImageUploads = renderPreviews;
      window.resetImageUpload = () => { 
          window.selectedFiles = []; 
          renderPreviews(); 
      };
      
      // Initial render
      renderPreviews();
  };

  // Initialize image upload
  initImageUpload({ dropAreaId: 'imageUploadArea', fileInputId: 'images', previewId: 'imagePreview', maxFiles: 10 });
  
  // -------------------------
  // Save Draft Handler
  // -------------------------
  window.loadDrafts = function() {
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
  // Unified Save / Update Draft (With Title Validation)
  // -------------------------
  document.getElementById('saveDraftBtn')?.addEventListener('click', async e => {
      e.preventDefault();

      const form = document.getElementById('addListingForm');
      if (!form) return;

      // 🛑 VALIDATION: Drafts must have a title
      const titleInput = form.title; // Assumes the input element has name="title"
      const titleValue = titleInput?.value.trim();

      if (!titleValue) {
          notify('error', 'The draft must have a title to be saved as draft.');
          titleInput?.focus();
          return; // Stop the process
      }

      // Initialize FormData empty. We append fields manually to prevent file duplication.
      const fd = new FormData();
      const isUpdate = !!window.currentDraftId;

      if (isUpdate) fd.append('id', window.currentDraftId);
      
      // Manually append all non-file form fields
      for (const [key, value] of new FormData(form).entries()) {
          // Exclude file input fields as they are handled manually via selectedFiles/Documents.
          if (key !== 'images[]' && key !== 'property_document[]') {
              fd.append(key, value);
          }
      }

      // -------------------------
      // Handle Images
      // -------------------------
      const existingImages = window.existingImages || []; // Existing URLs from DB
      const newImages = window.selectedFiles.filter(f => f instanceof File); // New File objects
      // URLs currently in selectedFiles (must be preserved)
      const remainingImages = existingImages.filter(url => window.selectedFiles.includes(url));
      // URLs that were present but are now missing (must be removed)
      const removedImages = existingImages.filter(url => !window.selectedFiles.includes(url));
      
      // 1. Mark images for removal
      removedImages.forEach(img => fd.append('remove_images[]', img));

      // 2. Append new image files
      newImages.forEach(file => fd.append('images[]', file));

      // 3. Include remaining existing images
      remainingImages.forEach(img => fd.append('existing_images[]', img));


      // -------------------------
      // Handle Documents
      // -------------------------
      window.deduplicateSelectedDocuments?.(); // Ensure this is available and runs
      
      const existingDocs = window.existingDocs || [];
      const newDocs = window.selectedDocuments.filter(f => f instanceof File);
      const remainingDocs = existingDocs.filter(doc => window.selectedDocuments.includes(doc));
      const removedDocs = existingDocs.filter(doc => !window.selectedDocuments.includes(doc));
      
      // 1. Mark documents for removal
      removedDocs.forEach(doc => fd.append('remove_docs[]', doc));
      
      // 2. Append new document files
      newDocs.forEach(file => fd.append('property_document[]', file));
      
      // 3. Include remaining existing documents
      remainingDocs.forEach(doc => fd.append('existing_property_documents[]', doc));
      
      
      // -------------------------
      // Debug FormData
      // -------------------------
      console.group("FormData Before Upload (Post-Validation)");
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
              notify('success', isUpdate ? 'Draft updated successfully!' : 'Draft saved successfully!');
              
              // Reset form and global state variables on success
              form.reset();
              window.resetImageUpload?.();
              window.resetDocumentUpload?.();
              window.currentDraftId = null;
              window.existingImages = [];
              window.existingDocs = [];
              window.selectedFiles = [];
              window.selectedDocuments = [];

              // Reload the list of drafts
              window.loadDrafts?.();
          } else {
              notify('error', data.error || 'Failed to save draft.');
          }

      } catch (err) {
          console.error('Draft save/update error:', err);
          notify('error', err.message || 'Network error while saving draft.');
      }
  });

  // -------------------------
  // Show Listing Fee & Open Modal
  // -------------------------
  document.getElementById('openListingModalBtn')?.addEventListener('click', async () => {
      const form = document.getElementById('addListingForm');

      // Validate form first
      if (!validateForm(form, false)) return;
      if (!form.checkValidity()) return form.reportValidity();

      // Check for at least one image
      const totalImages = (window.selectedFiles?.length || 0) + (window.draftImages?.length || 0);
      if (totalImages === 0) return notify('error', 'Please upload at least one image.');

      // Get property type
      const propertyType = document.getElementById('property_type')?.value || 'Lot';

      try {
          // Fetch listing fee info without deducting
          const res = await fetch('/BatEstateExplorer/public/api/show_fee.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: `property_type=${encodeURIComponent(propertyType)}`
          });
          const data = await res.json();

          if (!data.success) throw new Error(data.error || 'Failed to fetch listing fee.');

          // Update modal with fee details
          document.getElementById('listingBaseFee').innerText = data.base_fee.toLocaleString('en-PH', { minimumFractionDigits: 2 });
          document.getElementById('listingVAT').innerText = data.vat.toLocaleString('en-PH', { minimumFractionDigits: 2 });
          document.getElementById('listingTotal').innerText = data.total_deduction.toLocaleString('en-PH', { minimumFractionDigits: 2 });

          // Open modal
          document.getElementById('listingFeeModal').style.display = 'flex';

      } catch (err) {
          notify('error', err.message || 'Failed to load listing fee.');
      }
  });

  function closeListingFeeModal() {
    document.getElementById('listingFeeModal').style.display = 'none';
  }
  window.closeListingFeeModal = closeListingFeeModal;

  // ------------------------------------------
  // Pay Listing Fee & Submit with Inline Confirmation
  // ------------------------------------------
  document.getElementById('payListingFeeBtn')?.addEventListener('click', () => {
      // Show confirmation overlay instead of immediately paying
      const modal = document.getElementById('listingFeeModal');
      let overlay = modal.querySelector('.confirmation-overlay');

      // Create overlay if it doesn't exist
      if (!overlay) {
          overlay = document.createElement('div');
          overlay.className = 'confirmation-overlay';
          overlay.innerHTML = `
              <div class="overlay-content">
                  <p>Are you sure you want to pay the listing fee?</p>
                  <button id="confirmPayBtn" class="btn btn-success">Confirm</button>
                  <button id="cancelPayBtn" class="btn btn-secondary">Cancel</button>
              </div>
          `;
          modal.appendChild(overlay);
      }

      // Show overlay
      overlay.style.display = 'flex';
      overlay.style.opacity = 1;

      // Cancel button hides overlay
      overlay.querySelector('#cancelPayBtn').onclick = () => {
          overlay.style.opacity = 0;
          setTimeout(() => overlay.style.display = 'none', 200);
      };

      // Confirm button executes original pay logic
      overlay.querySelector('#confirmPayBtn').onclick = async () => {
          overlay.style.opacity = 0;
          setTimeout(() => overlay.style.display = 'none', 200);

          const form = document.getElementById('addListingForm');
          const walletBalanceEl = document.getElementById('agentWalletBalance');
          const fd = new FormData(form);

          // Deduplicate and append files
          deduplicateSelectedDocuments();

          window.selectedFiles.forEach(f => {
              if (f instanceof File) fd.append('images[]', f);
              else if (typeof f === 'string') fd.append('existing_images[]', f);
          });

          window.selectedDocuments?.forEach(f => {
              if (f instanceof File) fd.append('property_documents[]', f);
              else if (typeof f === 'string') fd.append('existing_property_documents[]', f);
          });

          if (window.currentDraftId) fd.append('draft_id', window.currentDraftId);

          try {
              // Step 1: Save listing first
              const saveRes = await fetch('/BatEstateExplorer/public/api/save_listing.php', { method: 'POST', body: fd });
              const saveData = await saveRes.json();
              if (!saveData.success) {
                  notify('error', 'Failed to save listing data or files: ' + (saveData.error || 'Unknown server error.'));
                  return;
              }

              fd.append('listing_id', saveData.listing_id);

              // Step 2: Pay listing fee
              const propertyType = document.getElementById('property_type')?.value || 'Lot';
              fd.append('property_type', propertyType);

              const feeRes = await fetch('/BatEstateExplorer/public/api/listing_fee.php', { method: 'POST', body: fd });
              const feeData = await feeRes.json();

              if (!feeData.success) {
                  notify('error', 'Listing saved but fee payment failed: ' + (feeData.error || 'Payment failed.'));
                  if (feeData.current_balance !== undefined) {
                      walletBalanceEl.innerText = feeData.current_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                  }
                  return;
              }

              // Success
              notify('success', `Listing submitted! Fee: PHP ${feeData.total_deduction.toLocaleString('en-PH', { minimumFractionDigits:2 })}. Awaiting admin approval.`);
              window.closeListingFeeModal?.();
              walletBalanceEl.innerText = feeData.new_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 });

              const mainSubmitBtn = document.getElementById('openListingModalBtn');
              if (mainSubmitBtn && feeData.new_balance !== undefined) {
                  mainSubmitBtn.innerHTML = `Save Listing (Balance: ₱${feeData.new_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 })})`;
              }

              // Clear form and reset state
              form.reset();
              window.resetImageUpload?.();
              window.resetDocumentUpload?.();
              window.currentDraftId = null;
              window.existingImages = [];
              window.existingDocs = [];
              window.selectedFiles = [];
              window.selectedDocuments = [];

              const draftCard = document.querySelector(`.draft-card[data-id="${window.currentDraftId}"]`);
              draftCard?.remove();
              window.loadDrafts?.();

          } catch (err) {
              notify('error', err.message || 'A critical network error occurred.');
          }
      };
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

      const form = document.getElementById('addListingForm');
      if (!form) return;

      // -------------------------
      // Fill form fields
      // -------------------------
      ['title','location','price','lot_size','property_type','bedrooms','bathrooms','description']
        .forEach(id => form[id].value = data[id] || '');

      // -------------------------
      // Reset previous images/docs
      // -------------------------
      window.resetImageUpload?.();
      window.resetDocumentUpload?.();
      window.selectedFiles = [];
      window.selectedDocuments = [];
      window.existingImages = [];
      window.existingDocs = [];

      const imgPreview = document.getElementById('imagePreview');
      const docPreview = document.getElementById('documentPreview');
      imgPreview.innerHTML = '';
      docPreview.innerHTML = '';

      // -------------------------
      // Render Images
      // -------------------------
      if (Array.isArray(data.images)) {
          data.images.forEach(src => {
              const wrap = document.createElement('div');
              wrap.className = 'img-wrap';
              wrap.style.position = 'relative';

              const img = document.createElement('img');
              img.className = 'thumb';
              img.src = src;
              wrap.appendChild(img);

              const removeBtn = document.createElement('button');
              removeBtn.type = 'button';
              removeBtn.innerHTML = '&times;';
              
              // 🚀 FIX: Apply IDENTICAL styling from initImageUpload
              Object.assign(removeBtn.style, {
                  position: 'absolute', top: '4px', right: '4px',
                  background: '#000', color: '#fff',
                  border: 'none', borderRadius: '50%',
                  width: '24px', height: '24px', fontSize: '16px',
                  cursor: 'pointer', display: 'flex',
                  alignItems: 'center', justifyContent: 'center',
                  padding: '0', zIndex: '10', transition: 'background 0.2s ease'
              });
              
              // 🚀 FIX: Apply IDENTICAL hover effects from initImageUpload
              removeBtn.addEventListener('mouseenter', () => removeBtn.style.background = 'rgba(255,77,79,0.9)');
              removeBtn.addEventListener('mouseleave', () => removeBtn.style.background = '#000');


              removeBtn.addEventListener('click', () => {
                  wrap.remove();
                  // Remove the image path from state arrays
                  window.selectedFiles = window.selectedFiles.filter(f => f !== src);
                  window.existingImages = window.existingImages.filter(f => f !== src);
              });
              wrap.appendChild(removeBtn);

              imgPreview.appendChild(wrap);

              // Add to both selectedFiles and existingImages
              window.selectedFiles.push(src);
              window.existingImages.push(src);
          });
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
              
              // 🚀 FIX: Apply IDENTICAL container styling from updateDocumentPreview
              Object.assign(item.style, {
                  padding: '12px 16px',
                  border: '1px solid #ddd',
                  borderRadius: '8px',
                  background: '#f8f8f8',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  minWidth: '180px',
                  wordBreak: 'break-word'
              });

              const link = document.createElement('a');
              link.href = fullPath;
              link.target = '_blank';
              link.textContent = fileName;
              // Apply link styling
              Object.assign(link.style, {
                  color: '#007bff',
                  textDecoration: 'none',
                  textAlign: 'center',
                  fontSize: '14px',
                  maxWidth: '160px',
                  overflow: 'hidden',
                  textOverflow: 'ellipsis',
                  whiteSpace: 'nowrap'
              });
              item.appendChild(link);

              const removeBtn = document.createElement('button');
              removeBtn.type = 'button';
              removeBtn.innerHTML = '&times;';
              
              // 🚀 FIX: Apply IDENTICAL removal button styling from updateDocumentPreview
              Object.assign(removeBtn.style, { 
                  position: 'absolute', top: '4px', right: '4px', 
                  background: '#000', color: '#fff', 
                  border: 'none', borderRadius: '50%', 
                  width: '22px', height: '22px', fontSize: '16px', 
                  cursor: 'pointer', display: 'flex', 
                  alignItems: 'center', justifyContent: 'center', 
                  padding: '0', transition: 'background 0.2s ease' 
              });
              
              // 🚀 FIX: Apply IDENTICAL hover effects from updateDocumentPreview
              removeBtn.addEventListener('mouseenter', () => removeBtn.style.background = 'rgba(255, 77, 79, 0.9)');
              removeBtn.addEventListener('mouseleave', () => removeBtn.style.background = '#000');


              removeBtn.addEventListener('click', () => {
                  item.remove();
                  // Remove the document path from state arrays
                  window.selectedDocuments = window.selectedDocuments.filter(f => f !== fullPath);
                  window.existingDocs = window.existingDocs.filter(f => f !== fullPath);
                  
                  // Re-render the overall document preview if the function exists
                  window.updateDocumentPreview?.(); 
              });
              item.appendChild(removeBtn);

              docPreview.appendChild(item);

              // Add to both selectedDocuments and existingDocs
              window.selectedDocuments.push(fullPath);
              window.existingDocs.push(fullPath);
          });
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

//deduplication
function deduplicateSelectedDocuments() {
    console.groupCollapsed('Deduplication Check: window.selectedDocuments');
    console.log('Array BEFORE deduplication:', window.selectedDocuments);
    console.log('Total items BEFORE:', window.selectedDocuments.length);

    const uniqueDocuments = [];
    const fileSignatures = new Set();
    let duplicatesRemoved = 0;

    for (const doc of window.selectedDocuments) {
        if (typeof doc === 'string') {
            // Keep existing document paths (strings)
            uniqueDocuments.push(doc);
            console.log('Kept existing path (string):', doc);
        } else if (doc instanceof File) {
            // Create a unique signature for the File object
            const sig = `${doc.name}|${doc.size}|${doc.lastModified}`;
            
            if (!fileSignatures.has(sig)) {
                uniqueDocuments.push(doc);
                fileSignatures.add(sig);
                console.log('Kept unique file:', doc.name, '| Sig:', sig);
            } else {
                duplicatesRemoved++;
                console.warn('Removed duplicate file:', doc.name, '| Sig:', sig);
            }
        } else {
             // Catch unexpected items (shouldn't happen, but good for debugging)
             console.error('Skipped unexpected item type:', doc);
        }
    }
    
    // Replace the global array with the deduplicated array
    window.selectedDocuments = uniqueDocuments;

    console.log('Total duplicates removed:', duplicatesRemoved);
    console.log('Array AFTER deduplication:', window.selectedDocuments);
    console.log('Total items AFTER:', window.selectedDocuments.length);
    console.groupEnd();
}

/**
 * Validates the form data based on whether it is a final listing submission or a draft save.
 * @param {HTMLFormElement} form - The listing form element.
 * @param {boolean} isDraft - True if saving as draft, false if submitting for listing.
 * @returns {boolean} True if validation passes, false otherwise.
 */
function validateForm(form, isDraft = false) {
    const propertyType = form.property_type.value;

    // --- RULE 1: Title is ALWAYS required (for drafts or final listing) ---
    if (!form.title.value.trim()) {
        notify('error', 'The Property Name (Title) is required.');
        form.title.focus();
        return false;
    }

    // --- Rules for FINAL LISTING SUBMISSION ONLY ---
    if (!isDraft) {
        // --- RULE 2: Location, Price, Lot Size, Description are MANDATORY ---
        const requiredFields = ['location', 'price', 'lot_size', 'description'];
        for (const fieldId of requiredFields) {
            if (!form[fieldId].value.trim()) {
                notify('error', `${form[fieldId].previousElementSibling.textContent.trim().replace(':', '')} is required.`);
                form[fieldId].focus();
                return false;
            }
        }
        
        // --- RULE 3: Prices can't be 0 or 0.00 ---
        const price = parseFloat(form.price.value);
        if (isNaN(price) || price <= 0) {
            notify('error', 'Price must be greater than zero.');
            form.price.focus();
            return false;
        }

        // --- RULE 4: At least ONE document is required ---
        // Checks combined list of new File objects and existing path strings
        const totalDocuments = (window.selectedDocuments?.length || 0);
        if (totalDocuments === 0) {
            notify('error', 'At least one Property Document is required for submission.');
            // Focus on the document upload area
            document.getElementById('documentUploadArea').focus();
            return false;
        }

        // --- RULE 5: Bedrooms and Bathrooms required only if type is 'Property' ---
        if (propertyType === 'Property') {
            const bedrooms = form.bedrooms.value.trim();
            const bathrooms = form.bathrooms.value.trim();

            if (!bedrooms || parseInt(bedrooms) <= 0) {
                notify('error', 'Bedrooms are required and must be greater than zero for Property listings.');
                form.bedrooms.focus();
                return false;
            }
            if (!bathrooms || parseInt(bathrooms) <= 0) {
                notify('error', 'Bathrooms are required and must be greater than zero for Property listings.');
                form.bathrooms.focus();
                return false;
            }
        }
    }

    return true; // Validation passed!
}