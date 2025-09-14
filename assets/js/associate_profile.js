document.addEventListener('DOMContentLoaded', () => {

    // ===== Helper: Toggle Bedrooms/Bathrooms for "Lot" =====
    const toggleRooms = (typeSelect, bedroomsInput, bathroomsInput) => {
        const isLot = typeSelect.value === 'Lot';
        bedroomsInput.disabled = isLot;
        bathroomsInput.disabled = isLot;
        if (isLot) {
            bedroomsInput.value = 0;
            bathroomsInput.value = 0;
        }
    };

    // ===== Drag & Drop Image Upload =====
    const initImageUpload = ({ dropAreaId, fileInputId, previewId, formId, maxFiles = 10 }) => {
        const dropArea  = document.getElementById(dropAreaId);
        const fileInput = document.getElementById(fileInputId);
        const preview   = document.getElementById(previewId);
        const form      = document.getElementById(formId);
        if (!dropArea || !fileInput || !form) return;

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
                removeBtn.addEventListener('click', () => {
                    selectedFiles.splice(index, 1);
                    renderPreviews();
                });
                wrap.appendChild(removeBtn);

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
                if (selectedFiles.length >= maxFiles) break;
                if (!existingSigs.has(fileSignature(f))) {
                    selectedFiles.push(f);
                    existingSigs.add(fileSignature(f));
                }
            }
            renderPreviews();
        };

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
        dropArea.addEventListener('keydown', e => {
            if (['Enter',' '].includes(e.key)) {
                e.preventDefault();
                fileInput.click();
            }
        });
        fileInput.addEventListener('change', () => {
            addFiles(fileInput.files);
            fileInput.value = '';
        });

        form.addEventListener('submit', e => {
            e.preventDefault();
            const fd = new FormData(form);
            selectedFiles.forEach(f => fd.append('images[]', f));

            fetch(form.action, { method: 'POST', body: fd })
                .then(res => res.text())
                .then(data => {
                    console.log('Server response:', data);
                    alert('Listing saved!');
                    form.reset();
                    selectedFiles = [];
                    renderPreviews();
                })
                .catch(err => console.error('Upload error:', err));
        });
    };

    initImageUpload({
        dropAreaId: 'imageUploadArea',
        fileInputId: 'images',
        previewId: 'imagePreview',
        formId: 'addListingForm',
        maxFiles: 10
    });

    // ===== Modal Handling =====
    window.openModal = id => document.getElementById(`editModal-${id}`).style.display = 'block';
    window.closeModal = id => document.getElementById(`editModal-${id}`).style.display = 'none';
    window.onclick = event => {
        document.querySelectorAll('.edit-modal').forEach(modal => {
            if (event.target === modal) modal.style.display = 'none';
        });
    };

    // ===== Remove Image from Slider =====
    window.removeImage = btn => btn.closest('.slider-item').remove();

    // ===== Init Bedrooms/Bathrooms Toggle for Edit Modals =====
    document.querySelectorAll('.edit-modal').forEach(modal => {
        const typeSelect = modal.querySelector('select[name="property_type"]');
        const bedrooms = modal.querySelector('input[name="bedrooms"]');
        const bathrooms = modal.querySelector('input[name="bathrooms"]');
        if (!typeSelect || !bedrooms || !bathrooms) return;
        toggleRooms(typeSelect, bedrooms, bathrooms);
        typeSelect.addEventListener('change', () => toggleRooms(typeSelect, bedrooms, bathrooms));
    });

    // ===== Init Bedrooms/Bathrooms Toggle for Add Listing =====
    const addType = document.getElementById('property_type');
    const addBeds = document.getElementById('bedrooms');
    const addBaths = document.getElementById('bathrooms');
    if (addType && addBeds && addBaths) {
        toggleRooms(addType, addBeds, addBaths);
        addType.addEventListener('change', () => toggleRooms(addType, addBeds, addBaths));
    }

    // ===== Company Listing Modal =====
    const propertyModal = document.getElementById('propertyModal');
    document.querySelectorAll('.view-details').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const propertyId = link.dataset.id;
            fetch(`/BatEstateExplorer/public/api/get_company_listings.php?id=${propertyId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) { alert(data.error); return; }

                    document.getElementById('propertyTitle').textContent = data.title || 'N/A';

                    const carousel = document.getElementById('carouselImages');
                    carousel.innerHTML = '';
                    if (data.images?.length) {
                        data.images.forEach((img, idx) => {
                            carousel.innerHTML += `
                                <div class="carousel-item ${idx===0?'active':''}">
                                    <img src="storage/uploads/property_images/${img}" class="d-block w-100">
                                </div>`;
                        });
                    } else {
                        carousel.innerHTML = '<div class="carousel-item active"><p>No images</p></div>';
                    }

                    const details = document.getElementById('propertyDetails');
                    details.innerHTML = `
                        <li class="list-group-item"><b>Location:</b> ${data.location}</li>
                        <li class="list-group-item"><b>Price:</b> ₱${parseFloat(data.price).toLocaleString()}</li>
                        <li class="list-group-item"><b>Bedrooms:</b> ${data.bedrooms}</li>
                        <li class="list-group-item"><b>Bathrooms:</b> ${data.bathrooms}</li>
                        <li class="list-group-item"><b>Size:</b> ${data.sqm} sqm</li>
                        <li class="list-group-item"><b>Status:</b> ${data.status}</li>
                        <li class="list-group-item"><b>Created By:</b> ${data.created_by ?? 'N/A'}</li>
                        <li class="list-group-item"><b>Sold By:</b> ${data.sold_by ?? 'N/A'}</li>
                    `;
                })
                .catch(err => console.error(err));
        });
    });

    const openBtn = document.getElementById("openDeleteModal");
    const modal = document.getElementById("deleteModal");
    const cancelBtn = document.getElementById("cancelDeleteBtn");
    const deleteForm = document.getElementById("deleteAgentForm");
    const confirmBtn = document.getElementById("confirmDeleteBtn");
    const spinner = document.getElementById("deleteSpinner");

    openBtn.addEventListener("click", () => {
        modal.style.display = "flex";
    });

    cancelBtn.addEventListener("click", () => {
        modal.style.display = "none";
    });

    deleteForm.addEventListener("submit", function() {
        confirmBtn.style.display = "none";
        spinner.style.display = "flex";
    });

});

function toggleSoldBy(select, propertyId) {
    const container = document.getElementById('soldByContainer-' + propertyId);
    if (!container) return; // safety
    if (select.value === 'sold_by') {
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
        container.querySelector('input').value = '';
    }
}
function confirmEdit(propertyId) {
    const form = document.getElementById('editForm-' + propertyId);
    if (!form) return;

    // Show a browser confirmation
    const confirmed = confirm("Are you sure you want to update this listing?");
    if (confirmed) {
        form.submit();
    }
}

// Global variables
                    let selectedPropertyId = null;
                    window.currentEmail = null;

                    // Search client by email
                    window.searchClient = function() {
                        const email = document.getElementById('searchEmail').value.trim();
                        if (!email) return alert('Please enter an email');

                        fetch(`/BatEstateExplorer/public/api/give_privilege.php?email=${encodeURIComponent(email)}`)
                            .then(res => res.json())
                            .then(data => {
                                if (data.error) return alert(data.error);

                                document.getElementById('userNameEmail').textContent =
                                    `${data.name || ''} (${data.email})`;

                                document.getElementById('privilegeModal').style.display = 'block';
                                window.currentEmail = data.email;
                            })
                            .catch(err => console.error('Search client error:', err));
                    };

                    // Select a property card
                    window.selectProperty = function(card, propertyId) {
                        document.querySelectorAll('.property-card').forEach(c => c.classList.remove('selected'));
                        card.classList.add('selected');
                        selectedPropertyId = propertyId;
                    };

                    // Close privilege modal
                    window.closePrivilegeModal = function() {
                        document.getElementById('privilegeModal').style.display = 'none';
                        selectedPropertyId = null;
                    };

                    // Give privilege to selected client for selected property
                    window.givePrivilege = function() {
                        if (!selectedPropertyId) return alert('Please select a property first.');
                        if (!window.currentEmail) return alert('No client selected.');

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
                    };