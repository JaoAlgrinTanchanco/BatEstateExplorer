<!-- app/Views/agent/partials/agent_modals.php -->

<!-- Edit Property Modal -->
<div class="modal fade" id="editPropertyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Property</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editPropertyForm">
          <input type="hidden" name="property_id" id="edit_property_id">
          <div class="mb-3">
            <label class="form-label">Property Title</label>
            <input type="text" class="form-control" name="title" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Price</label>
            <input type="number" class="form-control" name="price" required>
          </div>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePropertyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center">
        <p>Are you sure you want to delete this property?</p>
        <button type="button" class="btn btn-danger me-2" id="confirmDeleteProperty">Yes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
      </div>
    </div>
  </div>
</div>
