document.addEventListener('DOMContentLoaded', () => {
    // =========================
    // Notification Helper
    // =========================
    function notify(type, message) {
        const container = document.getElementById('notificationContainer') 
            || (() => {
                const div = document.createElement('div');
                div.id = 'notificationContainer';
                div.style.position = 'fixed';
                div.style.top = '20px';
                div.style.right = '20px';
                div.style.zIndex = '9999';
                document.body.appendChild(div);
                return div;
            })();

        const notif = document.createElement('div');
        notif.className = `notification ${type}`;
        notif.style.padding = '10px 20px';
        notif.style.marginBottom = '10px';
        notif.style.borderRadius = '6px';
        notif.style.color = '#fff';
        notif.style.fontWeight = '500';
        notif.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
        notif.style.cursor = 'pointer';
        notif.style.opacity = '1';
        notif.style.transition = 'opacity 0.5s';
        notif.style.backgroundColor =
            type === 'success' ? '#28a745'
            : type === 'error' ? '#dc3545'
            : '#333';

        notif.textContent = message;
        notif.addEventListener('click', () => fadeOut(notif));
        container.appendChild(notif);
        setTimeout(() => fadeOut(notif), 5000);

        function fadeOut(el) {
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }
    }

    // =========================
    // Image Upload (Drag & Drop + Preview)
    // =========================
    const dropArea  = document.getElementById('imageUploadArea');
    const fileInput = document.getElementById('images');
    const preview   = document.getElementById('imagePreview');
    const form      = document.getElementById('addListingForm');
    const MAX_FILES = 10;

    if (dropArea && fileInput && form) {
        let selectedFiles = [];

        const fileSignature = f => `${f.name}|${f.size}|${f.lastModified}`;

        function renderPreviews() {
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
                wrap.appendChild(removeBtn);

                removeBtn.addEventListener('click', () => {
                    selectedFiles.splice(index, 1);
                    renderPreviews();
                });

                const reader = new FileReader();
                reader.onload = e => img.src = e.target.result;
                reader.readAsDataURL(file);

                preview.appendChild(wrap);
            });
        }

        function addFiles(fileList) {
            if (!fileList) return;
            const incoming = Array.from(fileList).filter(f => f.type.startsWith('image/'));
            const existingSigs = new Set(selectedFiles.map(fileSignature));
            for (const f of incoming) {
                if (selectedFiles.length >= MAX_FILES) break;
                if (!existingSigs.has(fileSignature(f))) {
                    selectedFiles.push(f);
                    existingSigs.add(fileSignature(f));
                }
            }
            renderPreviews();
        }

        // Drag & drop events
        ['dragenter','dragover','dragleave','drop'].forEach(evt => {
            dropArea.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); });
        });
        dropArea.addEventListener('dragover', () => dropArea.classList.add('drag-over'));
        dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));
        dropArea.addEventListener('drop', e => {
            dropArea.classList.remove('drag-over');
            addFiles(e.dataTransfer.files);
        });

        // File input
        dropArea.addEventListener('click', () => fileInput.click());
        dropArea.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                fileInput.click();
            }
        });
        fileInput.addEventListener('change', () => {
            addFiles(fileInput.files);
            fileInput.value = '';
        });

        // Form submit
        form.addEventListener('submit', e => {
            e.preventDefault();
            const fd = new FormData(form);
            selectedFiles.forEach(f => fd.append('images[]', f));

            fetch(form.action, { method: 'POST', body: fd })
                .then(res => res.text())
                .then(data => {
                    console.log('Server response:', data);
                    notify('success', 'Listing saved successfully!');
                    form.reset();
                    selectedFiles = [];
                    renderPreviews();
                })
                .catch(err => {
                    console.error('Upload error:', err);
                    notify('error', 'Failed to save listing.');
                });
        });
    }

    // =========================
    // Modal Handling
    // =========================
    window.openModal = id => {
        const modal = document.getElementById(`editModal-${id}`);
        if (modal) modal.style.display = 'block';
    };

    window.closeModal = id => {
        const modal = document.getElementById(`editModal-${id}`);
        if (modal) modal.style.display = 'none';
    };

    window.onclick = event => {
        document.querySelectorAll('.edit-modal').forEach(modal => {
            if (event.target === modal) modal.style.display = 'none';
        });
    };

    // =========================
    // Remove Image (Slider)
    // =========================
    window.removeImage = btn => {
        const sliderItem = btn.closest('.slider-item');
        if (sliderItem) sliderItem.remove();
    };

    // =========================
    // Disable Bedrooms/Bathrooms for "Lot"
    // =========================
    const propertyTypeMain = document.getElementById('property_type');
    const bedroomsMain = document.getElementById('bedrooms');
    const bathroomsMain = document.getElementById('bathrooms');

    function toggleRooms() {
        if (!propertyTypeMain || !bedroomsMain || !bathroomsMain) return;
        const isLot = propertyTypeMain.value === 'Lot';
        bedroomsMain.disabled = isLot;
        bathroomsMain.disabled = isLot;
        bedroomsMain.value = isLot ? 0 : '';
        bathroomsMain.value = isLot ? 0 : '';
    }

    if (propertyTypeMain) {
        propertyTypeMain.addEventListener('change', toggleRooms);
        toggleRooms();
    }

    // =========================
    // Delete Modal
    // =========================
    const openDeleteModal = document.getElementById('openDeleteModal');
    if (openDeleteModal) {
        openDeleteModal.addEventListener('click', () => {
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) deleteModal.style.display = 'flex';
        });
    }

    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', () => {
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) deleteModal.style.display = 'none';
        });
    }

    const deleteAgentForm = document.getElementById('deleteAgentForm');
    if (deleteAgentForm) {
        deleteAgentForm.addEventListener('submit', () => {
            const deleteSpinner = document.getElementById('deleteSpinner');
            if (deleteSpinner) deleteSpinner.style.display = 'flex';
        });
    }

    // =========================
    // Direct: Listing Fee Modal & Payment Flow
    // =========================
    const directListingForm = document.getElementById('addListingForm');
    const directListingModal = document.getElementById('listingFeeModal');
    const directWalletBalanceEl = document.getElementById('agentWalletBalance');
    const directPayBtn = document.getElementById('payListingFeeBtn');
    const directListingFee = 20;

    // Dedicated "Save Listing" button that opens modal
    const directOpenListingBtn = document.getElementById('openListingModalBtn');

    if(directListingForm && directListingModal && directWalletBalanceEl && directPayBtn && directOpenListingBtn){
        
        // Open / Close modal helpers
        window.openListingFeeModal = () => directListingModal.style.display = 'flex';
        window.closeListingFeeModal = (e) => { 
            if(!e || e.target === directListingModal) directListingModal.style.display = 'none'; 
        };

        // Click "Save Listing" -> validate form -> show modal
        directOpenListingBtn.addEventListener('click', () => {
            if(!directListingForm.checkValidity()){
                directListingForm.reportValidity();
                return;
            }
            const walletBalance = parseFloat(directWalletBalanceEl.innerText.replace(/,/g,''));
            if(walletBalance < directListingFee){
                notify('error','Insufficient wallet balance. Please deposit first.');
                return;
            }
            openListingFeeModal();
        });

        // Click "Pay Listing Fee & Submit" -> charge fee -> save listing
        directPayBtn.addEventListener('click', () => {
            const walletBalance = parseFloat(directWalletBalanceEl.innerText.replace(/,/g,''));
            if(walletBalance < directListingFee){
                notify('error','Insufficient wallet balance. Please deposit first.');
                return;
            }

            const formData = new FormData(directListingForm);

            // Step 1: Process listing fee
            fetch('/BatEstateExplorer/public/api/listing_fee.php', { method:'POST', body: formData })
            .then(res => res.json())
            .then(feeData => {
                if(!feeData.success) throw new Error(feeData.error || 'Failed to process listing fee.');

                // Update wallet balance on success
                directWalletBalanceEl.innerText = feeData.new_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 });

                // Step 2: Save listing after fee (Direct API)
                return fetch('/BatEstateExplorer/public/api/direct_save_listing.php', { method:'POST', body: formData });
            })
            .then(res => res.json())
            .then(saveData => {
                if(saveData.success){
                    notify('success','Direct listing submitted! Awaiting admin approval.');
                    closeListingFeeModal();
                    directListingForm.reset();
                    if(typeof resetImageUpload === "function"){ resetImageUpload(); }
                } else {
                    notify('error','Listing fee paid but failed to save listing: ' + (saveData.error || 'Unknown error'));
                }
            })
            .catch(err => {
                notify('error', err.message || 'An error occurred. Please try again.');
            });
        });
    }

});

// =========================
// Client Search & Privilege
// =========================
let selectedPropertyId = null;

function searchClient() {
    const email = document.getElementById('searchEmail')?.value.trim();
    if (!email) return notify('error', 'Please enter an email');

    fetch(`/BatEstateExplorer/public/api/give_privilege.php?email=${encodeURIComponent(email)}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) return notify('error', data.error);

            const nameEmailEl = document.getElementById('userNameEmail');
            if (nameEmailEl) nameEmailEl.textContent = `${data.name || ''} (${data.email})`;

            const modal = document.getElementById('privilegeModal');
            if (modal) modal.style.display = 'block';

            window.currentEmail = data.email;
        })
        .catch(err => {
            console.error('Search client error:', err);
            notify('error', 'Failed to search client.');
        });
}

function selectProperty(card, propertyId) {
    document.querySelectorAll('.property-card').forEach(c => c.classList.remove('selected'));
    card?.classList.add('selected');
    selectedPropertyId = propertyId;
}

function closePrivilegeModal() {
    const modal = document.getElementById('privilegeModal');
    if (modal) modal.style.display = 'none';
    selectedPropertyId = null;
}

function givePrivilege() {
    if (!selectedPropertyId) return notify('error', 'Please select a property first.');

    fetch('/BatEstateExplorer/public/api/give_privilege.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `email=${encodeURIComponent(window.currentEmail)}&property_id=${encodeURIComponent(selectedPropertyId)}`
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                notify('success', 'Privilege granted successfully!');
                closePrivilegeModal();
            } else {
                notify('error', data.error || 'Something went wrong.');
            }
        })
        .catch(err => {
            console.error('Give privilege error:', err);
            notify('error', 'Failed to grant privilege.');
        });
}

function markImageForRemoval(button, imagePath) {
    const container = button.closest('form').querySelector('[id^="removeImages-"]');
    if (!container) return;

    // Mark image visually
    button.closest('.image-item').style.opacity = '0.5';

    // Append hidden input
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'remove_images[]';
    hiddenInput.value = imagePath;
    container.appendChild(hiddenInput);

    // Disable button to prevent duplicate marking
    button.disabled = true;
}


