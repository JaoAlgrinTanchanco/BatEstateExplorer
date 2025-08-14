document.addEventListener('DOMContentLoaded', () => {
    // ===== Edit Profile Toggle =====
    const editBtn = document.getElementById('editProfileBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    const profileView = document.querySelector('.profile-view');
    const profileEdit = document.querySelector('.profile-edit');

    if (editBtn && cancelBtn && profileView && profileEdit) {
        editBtn.addEventListener('click', () => {
            profileView.style.display = 'none';
            profileEdit.style.display = 'block';
        });

        cancelBtn.addEventListener('click', () => {
            profileEdit.style.display = 'none';
            profileView.style.display = 'block';
        });
    }

    // ===== Property Edit Buttons =====
    document.querySelectorAll('.btn-edit-property').forEach(btn => {
        btn.addEventListener('click', () => {
            const propertyId = btn.dataset.id;
            const title = btn.dataset.title;
            const price = btn.dataset.price;

            document.getElementById('edit_property_id').value = propertyId;
            document.querySelector('#editPropertyForm [name="title"]').value = title;
            document.querySelector('#editPropertyForm [name="price"]').value = price;

            new bootstrap.Modal(document.getElementById('editPropertyModal')).show();
        });
    });

    // ===== Property Delete Buttons =====
    document.querySelectorAll('.btn-delete-property').forEach(btn => {
        btn.addEventListener('click', () => {
            const propertyId = btn.dataset.id;
            document.getElementById('confirmDeleteProperty').onclick = () => {
                fetch(`/controllers/delete_property.php?id=${propertyId}`, { method: 'POST' })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Failed to delete property');
                        }
                    });
            };
            new bootstrap.Modal(document.getElementById('deletePropertyModal')).show();
        });
    });

    const addBtn = document.getElementById('addListingBtn');
    const modal = document.getElementById('addListingModal');
    const closeModal = document.getElementById('closeModal');

    addBtn.addEventListener('click', () => {
        modal.style.display = 'flex';
    });

    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Close modal when clicking outside content
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });

});
