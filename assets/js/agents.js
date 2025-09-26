document.addEventListener('DOMContentLoaded', () => {

  // Property Edit Buttons: Populate modal and show it
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

  // Property Delete Buttons: Set confirmation handler and show modal
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

  // Burger Menu toggle
  const burger = document.querySelector('.nav-burger');
  const dropdown = document.querySelector('.nav-dropdown');

  if (burger && dropdown) {
    burger.addEventListener('click', () => {
      // Toggle visibility between 'flex' and 'none'
      dropdown.style.display = dropdown.style.display === 'flex' ? 'none' : 'flex';
    });
  }

  // Footer Sections toggle (mobile/tablet accordion)
  document.querySelectorAll('.footer-section').forEach(section => {
    const header = section.querySelector('h3, h4');
    if (header) {
      header.addEventListener('click', () => {
        section.classList.toggle('active');
      });
    }
  });

});