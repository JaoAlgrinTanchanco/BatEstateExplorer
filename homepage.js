const authModal = document.getElementById('authModal');
const loginTrigger = document.getElementById('loginTrigger');
const signupTrigger = document.getElementById('signupTrigger');
const loginTab = document.getElementById('loginTab');
const signupTab = document.getElementById('signupTab');
const loginForm = document.getElementById('loginForm');
const signupForm = document.getElementById('signupForm');

// Show modal
loginTrigger.onclick = () => showForm('login');
signupTrigger.onclick = () => showForm('signup');

function showForm(mode) {
  authModal.classList.remove('hidden');
  if (mode === 'login') {
    loginTab.classList.add('active');
    signupTab.classList.remove('active');
    loginForm.classList.add('active');
    signupForm.classList.remove('active');
  } else {
    signupTab.classList.add('active');
    loginTab.classList.remove('active');
    signupForm.classList.add('active');
    loginForm.classList.remove('active');
  }
}

// Tab switch within modal
loginTab.onclick = () => showForm('login');
signupTab.onclick = () => showForm('signup');

// Close when clicking outside card
document.querySelector('.auth-backdrop').onclick = () =>
  authModal.classList.add('hidden');

const categoryInput = document.getElementById('category');
const typeInput = document.getElementById('type');
const typesDatalist = document.getElementById('types');
const priceInput = document.getElementById('price');
const pricesDatalist = document.getElementById('prices');

const propertyTypes = [
  'Agricultural Land',
  'Apartment',
  'Brownfield / Infill Parcel',
  'Condominium',
  'Data Center',
  'Detached House',
  'Duplex / Triplex / Fourplex',
  'Factory / Manufacturing Plant',
  'Hotel',
  'Life Estate',
  'Luxury Estate',
  'Mixed-use Development',
  'Office Building',
  'Raw Land',
  'Ranch / Equestrian Property',
  'Resort',
  'Self-storage Facility',
  'Special-purpose Property',
  'Timeshare',
  'Townhouse',
  'Vacation / Second Home',
  'Warehouse / Distribution Center'
];

const lotTypes = [
  'Corner Lot',
  'Cul-de-sac Lot',
  'Flag Lot',
  'Interior Lot',
  'T-Intersection Lot',
  'Through / Double-Frontage Lot',
  'Key Lot',
  'Tertiary Lot'
];

const propertyPrice = [
  'Below ₱100,000',
  '₱100,000 - ₱500,000',
  '₱500,000 - ₱1,000,000',
  '₱1,000,000 - ₱10,000,000',
  '₱10,000,000 - ₱20,000,000',
  '₱20,000,000 - ₱30,000,000',
  '₱30,000,000 - ₱40,000,000',
  '₱40,000,000 - ₱50,000,000',
  '₱50,000,000 - ₱100,000,000',
  '₱100,000,000 and Above',
];

const lotPrice = [
  'Below ₱100,000',
  '₱100,000 - ₱500,000',
  '₱500,000 - ₱1,000,000',
  '₱1,000,000 - ₱10,000,000',
  '₱10,000,000 - ₱20,000,000',
  '₱20,000,000 - ₱30,000,000',
  '₱30,000,000 - ₱40,000,000',
  '₱40,000,000 - ₱50,000,000',
  '₱50,000,000 and Above',
];

function updateTypeOptions() {
  const selected = categoryInput.value;
  let arr = (selected === 'Property')
    ? propertyTypes
    : (selected === 'Lot')
      ? lotTypes
      : [];
  typesDatalist.innerHTML = '';
  arr.forEach(v => {
    const o = document.createElement('option');
    o.value = v;
    typesDatalist.appendChild(o);
  });
  typeInput.disabled = arr.length === 0;
}

function updatePriceRangeOptions() {
  const selected = categoryInput.value;
  let arr = (selected === 'Property')
    ? propertyPrice
    : (selected === 'Lot')
      ? lotPrice
      : [];
  pricesDatalist.innerHTML = '';
  arr.forEach(v => {
    const o = document.createElement('option');
    o.value = v;
    pricesDatalist.appendChild(o);
  });
  priceInput.disabled = arr.length === 0;
}

// Hook up events
categoryInput.addEventListener('change', () => {
  updateTypeOptions();
  updatePriceRangeOptions();
});

// Initialize on load
updateTypeOptions();
updatePriceRangeOptions();

const inputs = [
  categoryInput,
  typeInput,
  priceInput,
  document.getElementById('searchBar'),
  document.getElementById('location'),
];

// Enter Key
inputs.forEach((input, idx) => {
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      input.blur();

      if (input === categoryInput) {
        updateTypeOptions();
        updatePriceRangeOptions();
      }

      const next = inputs[idx + 1];
      if (next) next.focus();
      else document.getElementById('searchBtn').focus();
    }
  });
});

// Updated search button logic
document.getElementById('searchBtn').addEventListener('click', async () => {
  const filters = {
    location: document.getElementById('location').value,
    category: document.getElementById('category').value,
    type: document.getElementById('type').value,
    price: document.getElementById('price').value,
  };

  try {
    const response = await fetch('http://localhost:5000/api/properties', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });
    const data = await response.json();
    console.log('Data from backend:', data);
    // Here you can update the listings with the fetched data
  } catch (error) {
    console.error('Error fetching data:', error);
  }
});

// Favorites
function saveFavorite(title) {
  let favorites = JSON.parse(localStorage.getItem("favorites")) || [];
  if (!favorites.includes(title)) {
    favorites.push(title);
    localStorage.setItem("favorites", JSON.stringify(favorites));
    alert(`${title} has been added to your favorites!`);
  } else {
    alert(`${title} is already in your favorites.`);
  }
}
