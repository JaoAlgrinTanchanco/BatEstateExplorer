let modalSwiper;
let currentPropertyId = null;

// ✅ Use token from global config
const userToken = window.AppConfig?.userToken || "";

if (!userToken) {
    console.error("⚠️ User token missing! Save feature will not work.");
}

// Property details modal
document.querySelectorAll(".view-details-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
        const propertyId = btn.dataset.id;
        currentPropertyId = propertyId;

        try {
            const res = await fetch(
                `/BatEstateExplorer/public/api/get_property_details.php?id=${encodeURIComponent(propertyId)}`
            );
            const data = await res.json();
            if (!data.success) {
                alert(data.error || "Failed to fetch property.");
                return;
            }
            const prop = data.property;

            // Populate images
            const wrapper = document.getElementById("modalImageWrapper");
            wrapper.innerHTML = "";
            const images =
                prop.images && prop.images.length
                    ? prop.images
                    : ["/BatEstateExplorer/assets/images/bg4.jpg"];
            images.forEach((img) => {
                wrapper.innerHTML += `<div class="swiper-slide"><img src="${img}" style="width:100%;border-radius:8px;"></div>`;
            });

            // Init/update Swiper
            if (modalSwiper) {
                modalSwiper.update();
            } else {
                modalSwiper = new Swiper(".modal-swiper", {
                    loop: images.length > 1,
                    navigation: {
                        nextEl: ".swiper-button-next",
                        prevEl: ".swiper-button-prev",
                    },
                    pagination: { el: ".swiper-pagination", clickable: true },
                });
            }

            // Fill details
            document.getElementById("modalTitle").textContent = prop.title;
            document.getElementById("modalLocation").textContent = `📍 ${prop.location}`;
            document.getElementById("modalPrice").textContent = `₱${parseFloat(
                prop.price
            ).toLocaleString()}`;
            document.getElementById("modalBedrooms").textContent = prop.bedrooms;
            document.getElementById("modalBathrooms").textContent = prop.bathrooms;
            document.getElementById("modalDescription").textContent =
                prop.description || "No description available.";

            // Review button
            const reviewBtn = document.getElementById("leaveReviewBtn");
            if (data.has_privilege) {
                reviewBtn.style.display = "inline-block";
                reviewBtn.onclick = () => openReviewModal(prop.id);
            } else {
                reviewBtn.style.display = "none";
                reviewBtn.onclick = null;
            }

            // Agent button
            const messageBtn = document.querySelector(".message-agent-btn");
            messageBtn.dataset.agentId = prop.agent_id;
            try {
                const agentRes = await fetch(
                    `/BatEstateExplorer/public/api/get_property_agent.php?property_id=${encodeURIComponent(
                        prop.id
                    )}`
                );
                const agentData = await agentRes.json();
                if (!agentData.error && agentData.agent_id)
                    messageBtn.dataset.agentId = agentData.agent_id;
            } catch (err) {
                console.warn(
                    "Failed to fetch corrected agent ID, using legacy one.",
                    err
                );
            }

            // Show modal
            document.getElementById("propertyModal").style.display = "flex";

            // Check if saved
            checkIfSaved(currentPropertyId);
        } catch (err) {
            console.error(err);
            alert("Failed to load property details.");
        }
    });
});

// Close modal
document.querySelectorAll(".modal-close").forEach((btn) => {
    btn.addEventListener("click", () => {
        document.getElementById("propertyModal").style.display = "none";
    });
});
document
    .getElementById("propertyModal")
    .addEventListener("click", (e) => {
        if (e.target === e.currentTarget) e.currentTarget.style.display = "none";
    });

// Review modal
function openReviewModal(propertyId) {
    document.getElementById("reviewPropertyId").value = propertyId;
    document.getElementById("reviewModal").style.display = "flex";
}

// Submit review
document
    .getElementById("reviewForm")
    .addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const res = await fetch(
                "/BatEstateExplorer/public/api/submit_review.php",
                { method: "POST", body: formData }
            );
            const data = await res.json();
            if (data.success) {
                alert("Review submitted successfully!");
                document.getElementById("reviewModal").style.display = "none";
                e.target.reset();
            } else {
                alert(data.error || "Failed to submit review.");
            }
        } catch (err) {
            console.error(err);
            alert("Error submitting review.");
        }
    });

// Message Agent
document.querySelectorAll(".message-agent-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
        const agentId = btn.dataset.agentId;
        if (!agentId) return alert("Agent not found.");
        window.open(
            `/BatEstateExplorer/public/message.php?agent_id=${agentId}`,
            "_blank"
        );
    });
});

// Save/Unsave
const saveBtn = document.querySelector(".modal-actions .btn-outline");

function updateSaveButton(isSaved) {
    if (isSaved) {
        saveBtn.innerHTML = '<i class="fas fa-heart"></i> Unsave';
        saveBtn.dataset.saved = "true";
    } else {
        saveBtn.innerHTML =
            '<i class="fas fa-heart"></i> Save to Favorites';
        saveBtn.dataset.saved = "false";
    }
}

// Check saved status
async function checkIfSaved(propertyId) {
    try {
        const res = await fetch(
            "/BatEstateExplorer/public/api/save_property.php",
            {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `property_id=${encodeURIComponent(
                    propertyId
                )}&action=check&user_token=${encodeURIComponent(userToken)}`,
            }
        );
        const data = await res.json();
        if (data.success) updateSaveButton(data.saved);
        else console.warn("Failed to get saved status:", data);
    } catch (err) {
        console.error("Error checking saved status", err);
    }
}

// Handle save/unsave click
saveBtn.addEventListener("click", async () => {
    if (!currentPropertyId) return alert("Property not selected.");
    const action = saveBtn.dataset.saved === "true" ? "unsave" : "save";

    try {
        const res = await fetch(
            "/BatEstateExplorer/public/api/save_property.php",
            {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `property_id=${encodeURIComponent(
                    currentPropertyId
                )}&action=${action}&user_token=${encodeURIComponent(userToken)}`,
            }
        );
        const data = await res.json();
        if (data.success) {
            updateSaveButton(data.saved);
            alert(data.message);
        } else {
            alert(data.error || "Failed to update saved status.");
        }
    } catch (err) {
        console.error("Error updating saved status", err);
        alert("Error updating saved status.");
    }
});
