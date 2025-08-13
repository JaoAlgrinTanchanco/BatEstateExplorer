<!-- app/Views/agent/partials/agent_property_scripts.php -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Open edit modal
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

    // Open delete modal
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
});
</script>
