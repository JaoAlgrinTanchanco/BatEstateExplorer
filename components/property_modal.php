<!-- Shared Property Modal -->
<div id="propertyModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="modal-close">&times;</span>
    <div class="modal-body">
      <!-- Image Carousel -->
      <div class="modal-image">
        <div class="swiper modal-swiper">
          <div class="swiper-wrapper" id="modalImageWrapper"></div>
          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>
          <div class="swiper-pagination"></div>
        </div>
      </div>

      <!-- Details -->
      <div class="modal-details">
        <h2 id="modalTitle"></h2>
        <p id="modalLocation"></p>
        <p id="modalPrice" class="price"></p>
        <div class="features">
          <span><i class="fas fa-bed"></i> <span id="modalBedrooms"></span> Beds</span>
          <span><i class="fas fa-bath"></i> <span id="modalBathrooms"></span> Baths</span>
        </div>
        <p><strong>Description:</strong></p>
        <p id="modalDescription"></p>

        <div class="modal-actions">
          <button class="btn btn-primary message-agent-btn">
              <i class="fas fa-envelope"></i> Message Agent
          </button>

          <button id="saveFavoriteBtn" class="btn btn-outline" data-saved="false">
            <i class="fas fa-heart"></i> Save to Favorites
          </button>

          <button id="leaveReviewBtn" class="btn btn-success" style="display:none;">
            <i class="fas fa-star"></i> Leave a Review
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="modal-close" onclick="document.getElementById('reviewModal').style.display='none'">&times;</span>
    <h3>Leave a Review</h3>
    <form id="reviewForm">
      <label>Rating:</label>
      <select name="rating" required>
        <option value="">Select...</option>
        <option value="5">⭐⭐⭐⭐⭐</option>
        <option value="4">⭐⭐⭐⭐</option>
        <option value="3">⭐⭐⭐</option>
        <option value="2">⭐⭐</option>
        <option value="1">⭐</option>
      </select>
      <label>Your Review:</label>
      <textarea name="review_text" rows="4" required></textarea>
      <input type="hidden" name="property_id" id="reviewPropertyId">
      <button type="submit" class="btn btn-success">Submit</button>
    </form>
  </div>
</div>
