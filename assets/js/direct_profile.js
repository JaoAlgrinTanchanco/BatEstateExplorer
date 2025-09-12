document.addEventListener('DOMContentLoaded', () => {
    // ===== Drag & Drop Image Upload =====
    const dropArea  = document.getElementById('imageUploadArea');
    const fileInput = document.getElementById('images');
    const preview   = document.getElementById('imagePreview');
    const form      = document.getElementById('addListingForm');
    const MAX_FILES = 10;

    if (dropArea && fileInput && form) {
        let selectedFiles = [];

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
        };

        const addFiles = fileList => {
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
        };

        // Drag/drop handlers
        ['dragenter','dragover','dragleave','drop'].forEach(evt => {
            dropArea.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); });
        });
        dropArea.addEventListener('dragover', () => dropArea.classList.add('drag-over'));
        dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));
        dropArea.addEventListener('drop', e => {
            dropArea.classList.remove('drag-over');
            addFiles(e.dataTransfer.files);
        });

        // Click & keyboard file picker
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

        // Form submission
        form.addEventListener('submit', e => {
        e.preventDefault();
        const fd = new FormData(form);
        selectedFiles.forEach(f => fd.append('images[]', f));

        fetch(form.action, { method: 'POST', body: fd })
            .then(res => res.text())
            .then(data => {
                console.log('Server response:', data);
                notify('success', 'Listing saved!');
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

    // ===== Modal Handling =====
    window.openModal = id => {
        document.getElementById(`editModal-${id}`).style.display = 'block';
    };
    window.closeModal = id => {
        document.getElementById(`editModal-${id}`).style.display = 'none';
    };
    window.onclick = event => {
        document.querySelectorAll('.edit-modal').forEach(modal => {
            if (event.target === modal) modal.style.display = 'none';
        });
    };

    // ===== Remove Image from Slider =====
    window.removeImage = btn => btn.closest('.slider-item').remove();

    // ===== Disable Bedrooms/Bathrooms for "Lot" =====
    const initPropertyTypeToggles = () => {
        document.querySelectorAll('.edit-modal').forEach(modal => {
            const propertyType = modal.querySelector('select[name="property_type"]');
            const bedrooms = modal.querySelector('input[name="bedrooms"]');
            const bathrooms = modal.querySelector('input[name="bathrooms"]');

            if (!propertyType || !bedrooms || !bathrooms) return;

            const toggleRooms = () => {
                const isLot = propertyType.value === 'Lot';
                bedrooms.disabled = isLot;
                bathrooms.disabled = isLot;
                if (isLot) {
                    bedrooms.value = 0;
                    bathrooms.value = 0;
                }
            };

            toggleRooms();
            propertyType.addEventListener('change', toggleRooms);
        });
    };

    initPropertyTypeToggles();

    // Open modal
    document.getElementById('openDeleteModal').addEventListener('click', () => {
        document.getElementById('deleteModal').style.display = 'flex';
    });

    // Cancel deletion
    document.getElementById('cancelDeleteBtn').addEventListener('click', () => {
        document.getElementById('deleteModal').style.display = 'none';
    });

    // Optional: show spinner on form submit
    document.getElementById('deleteAgentForm').addEventListener('submit', () => {
        document.getElementById('deleteSpinner').style.display = 'flex';
    });

});

let selectedPropertyId = null;

                function searchClient() {
                    const email = document.getElementById('searchEmail').value.trim();
                    if (!email) return alert('Please enter an email');

                    fetch(`/BatEstateExplorer/public/api/give_privilege.php?email=${encodeURIComponent(email)}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                                return;
                            }

                            document.getElementById('userNameEmail').textContent =
                                `${data.name || ''} (${data.email})`;

                            document.getElementById('privilegeModal').style.display = 'block';
                            window.currentEmail = data.email; // store globally
                        })
                        .catch(err => console.error('Search client error:', err));
                }

                function selectProperty(card, propertyId) {
                    document.querySelectorAll('.property-card').forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    selectedPropertyId = propertyId;
                }

                function closePrivilegeModal() {
                    document.getElementById('privilegeModal').style.display = 'none';
                    selectedPropertyId = null;
                }

                function givePrivilege() {
                    if (!selectedPropertyId) return alert('Please select a property first.');

                    fetch('/BatEstateExplorer/public/api/give_privilege.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `email=${encodeURIComponent(window.currentEmail)}&property_id=${encodeURIComponent(selectedPropertyId)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('Privilege granted successfully!');
                            closePrivilegeModal();
                        } else {
                            alert(data.error || 'Something went wrong.');
                        }
                    })
                    .catch(err => console.error('Give privilege error:', err));
                }

                const propertyType = document.getElementById('property_type');
                    const bedrooms = document.getElementById('bedrooms');
                    const bathrooms = document.getElementById('bathrooms');

                    function toggleRooms() {
                        if (propertyType.value.toLowerCase() === 'lot') {
                            bedrooms.disabled = true;
                            bathrooms.disabled = true;
                            bedrooms.value = '';
                            bathrooms.value = '';
                        } else {
                            bedrooms.disabled = false;
                            bathrooms.disabled = false;
                        }
                    }

                    // Listen for changes
                    propertyType.addEventListener('change', toggleRooms);

                    // Initialize on page load
                    toggleRooms();