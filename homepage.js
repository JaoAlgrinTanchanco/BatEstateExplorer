// BatEstate Homepage JavaScript
// Handles authentication modals, search functionality, and property display

document.addEventListener('DOMContentLoaded', function() {
    // Authentication Modal Management
    const authModal = document.getElementById('authModal');
    const loginTrigger = document.getElementById('loginTrigger');
    const signupTrigger = document.getElementById('signupTrigger');
    const loginTab = document.getElementById('loginTab');
    const signupTab = document.getElementById('signupTab');
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');

    // Show/hide auth modal
    if (loginTrigger) {
        loginTrigger.addEventListener('click', () => {
            authModal.classList.remove('hidden');
            showLoginTab();
        });
    }

    if (signupTrigger) {
        signupTrigger.addEventListener('click', () => {
            authModal.classList.remove('hidden');
            showSignupTab();
        });
    }

    // Tab switching
    if (loginTab) {
        loginTab.addEventListener('click', showLoginTab);
    }

    if (signupTab) {
        signupTab.addEventListener('click', showSignupTab);
    }

    // Close modal when clicking backdrop
    const authBackdrop = document.querySelector('.auth-backdrop');
    if (authBackdrop) {
        authBackdrop.addEventListener('click', () => {
            authModal.classList.add('hidden');
        });
    }

    function showLoginTab() {
        loginTab.classList.add('active');
        signupTab.classList.remove('active');
        loginForm.classList.add('active');
        signupForm.classList.remove('active');
    }

    function showSignupTab() {
        signupTab.classList.add('active');
        loginTab.classList.remove('active');
        signupForm.classList.add('active');
        loginForm.classList.remove('active');
    }

    // Property Search Functionality
    const searchBtn = document.getElementById('searchBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', performSearch);
    }

    function performSearch() {
        const filters = {
            location: document.getElementById('location')?.value || '',
            category: document.getElementById('category')?.value || '',
            price: document.getElementById('price')?.value || '',
            bedrooms: document.getElementById('bedrooms')?.value || '',
            bathrooms: document.getElementById('bathrooms')?.value || '',
            size: document.getElementById('size')?.value || ''
        };

        console.log('Searching with filters:', filters);
        
        // For now, just log the search. In a real implementation,
        // this would make an AJAX call to the backend
        alert('Search functionality would filter properties based on your criteria.');
    }

    // Property Listings Display
    const listingsContainer = document.getElementById('listings');
    if (listingsContainer) {
        displayFeaturedProperties();
    }

    function displayFeaturedProperties() {
        const featuredProperties = [
            {
                id: 1,
                title: "Modern Family Home",
                location: "Batangas City",
                type: "Property",
                price: "₱3,500,000",
                bedrooms: 3,
                bathrooms: 2,
                sqm: 180,
                image: "Pictures/bg4.jpg",
                features: ["3 BR", "2 BA", "180 sqm"]
            },
            {
                id: 2,
                title: "Luxury Condo Unit",
                location: "Lipa City",
                type: "Property",
                price: "₱2,800,000",
                bedrooms: 2,
                bathrooms: 2,
                sqm: 85,
                image: "Pictures/bg4.jpg",
                features: ["2 BR", "2 BA", "85 sqm"]
            },
            {
                id: 3,
                title: "Premium Lot",
                location: "Tanauan",
                type: "Lot",
                price: "₱1,200,000",
                bedrooms: 0,
                bathrooms: 0,
                sqm: 300,
                image: "Pictures/bg4.jpg",
                features: ["300 sqm", "Residential", "Ready for Construction"]
            },
            {
                id: 4,
                title: "Spacious Villa",
                location: "Nasugbu",
                type: "Property",
                price: "₱5,200,000",
                bedrooms: 4,
                bathrooms: 3,
                sqm: 250,
                image: "Pictures/bg4.jpg",
                features: ["4 BR", "3 BA", "250 sqm"]
            }
        ];

        listingsContainer.innerHTML = featuredProperties.map(property => `
            <div class="property-card">
                <div class="property-image">
                    <div class="property-badge">${property.type}</div>
                    <img src="${property.image}" alt="${property.title}" style="width:100%;height:140px;object-fit:cover;border-radius:12px 12px 30px 30px;">
                </div>
                <div class="property-name">${property.title}</div>
                <div class="property-meta">${property.location}</div>
                <div class="property-price">${property.price}</div>
                <div class="property-features">${property.features ? property.features.join(' | ') : ''}</div>
                <div class="property-actions">
                    <button class="view-details-btn" onclick="viewPropertyDetails(${property.id})">View Details</button>
                </div>
            </div>
        `).join('');
    }

    // Property Details Modal
    window.viewPropertyDetails = function(propertyId) {
        const properties = [
            {
                id: 1,
                title: "Modern Family Home",
                location: "Batangas City",
                type: "Property",
                price: "₱3,500,000",
                bedrooms: 3,
                bathrooms: 2,
                sqm: 180,
                image: "Pictures/bg4.jpg",
                description: "This beautiful property offers modern amenities and is located in a prime area. Perfect for families looking for comfort and convenience."
            },
            {
                id: 2,
                title: "Luxury Condo Unit",
                location: "Lipa City",
                type: "Property",
                price: "₱2,800,000",
                bedrooms: 2,
                bathrooms: 2,
                sqm: 85,
                image: "Pictures/bg4.jpg",
                description: "Elegant condo unit with premium finishes and amenities. Ideal for young professionals or small families."
            },
            {
                id: 3,
                title: "Premium Lot",
                location: "Tanauan",
                type: "Lot",
                price: "₱1,200,000",
                bedrooms: 0,
                bathrooms: 0,
                sqm: 300,
                image: "Pictures/bg4.jpg",
                description: "Prime residential lot ready for construction. Excellent location with good accessibility and utilities."
            },
            {
                id: 4,
                title: "Spacious Villa",
                location: "Nasugbu",
                type: "Property",
                price: "₱5,200,000",
                bedrooms: 4,
                bathrooms: 3,
                sqm: 250,
                image: "Pictures/bg4.jpg",
                description: "Luxurious villa with stunning ocean views. Features high-end finishes and spacious living areas."
            }
        ];

        const property = properties.find(p => p.id === propertyId);
        if (!property) return;

        const modalContent = `
            <div style="background: white; padding: 30px; border-radius: 15px; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0; color: #222;">${property.title}</h2>
                    <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <img src="${property.image}" alt="${property.title}" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">
                    </div>
                    <div>
                        <h3 style="color: #333; margin-bottom: 10px;">Property Details</h3>
                        <p><strong>Location:</strong> ${property.location}</p>
                        <p><strong>Type:</strong> ${property.type}</p>
                        <p><strong>Price:</strong> ${property.price}</p>
                        <p><strong>Bedrooms:</strong> ${property.bedrooms}</p>
                        <p><strong>Bathrooms:</strong> ${property.bathrooms}</p>
                        <p><strong>Size:</strong> ${property.sqm} sqm</p>
                    </div>
                </div>
                <div style="margin-bottom: 20px;">
                    <h3 style="color: #333; margin-bottom: 10px;">Description</h3>
                    <p>${property.description}</p>
                </div>
                <div style="text-align: center;">
                    <button onclick="closeModal()" style="background: #666; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer;">Close</button>
                </div>
            </div>
        `;
        showModal(modalContent);
    };

    window.showModal = function(content) {
        const modal = document.createElement('div');
        modal.id = 'modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        `;
        modal.innerHTML = content;
        document.body.appendChild(modal);
    };

    window.closeModal = function() {
        const modal = document.getElementById('modal');
        if (modal) {
            modal.remove();
        }
    };

    // Password visibility toggles
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                this.classList.remove('fa-eye-slash');
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#e74c3c';
                } else {
                    field.style.borderColor = '#e1e8ed';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });

    // Smooth scrolling for navigation links
    const navLinks = document.querySelectorAll('nav a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    console.log('BatEstate homepage JavaScript loaded successfully!');
}); 