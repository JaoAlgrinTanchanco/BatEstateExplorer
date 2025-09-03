<div class="property-card">
  <div class="property-image">
    <img src="<?= htmlspecialchars($property['image_path'] ?: '/BatEstateExplorer/assets/images/bg4.jpg') ?>" alt="Property Image">
  </div>
  <div class="property-info">
    <h3><?= htmlspecialchars($property['title']) ?></h3>
    <p><?= htmlspecialchars($property['location']) ?></p>
    <p>₱<?= number_format($property['price']) ?></p>
    <button class="view-details-btn" data-id="<?= $property['id'] ?>">View Details</button>
  </div>
</div>
