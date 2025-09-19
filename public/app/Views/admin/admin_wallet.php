<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_wallet.css" />

<div class="dashboard-container">
    <main class="wallet-content">
        <header class="content-header">
            <h1>Admin Wallet</h1>
        </header>

        <!-- Top Cards Section -->
        <section class="cards-section">
            <h2>Your Card</h2>
            <div class="cards-grid top-cards-grid">
                <!-- Right Column: VISA Card -->
                <div class="card visa-standard">
                    <div class="card-type">VISA Standard</div>
                    <div class="card-balance"></div>
                    <div class="card-number-wrapper">
                        <div class="card-number" id="cardNumber">•••• •••• •••• ••••</div>
                        <button class="toggle-number" id="toggleNumber" aria-label="Toggle card number">
                            <!-- Flat white eye icon SVG -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="20" height="20">
                                <path d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="card-details">
                        <span class="card-holder">MR. JOHN DOE</span>
                        <span class="card-expiry">05/23</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Transactions Section (1 Column) -->
        <section class="transactions-section">
            <h2>Recent Transactions</h2>
            <div class="transactions-list" id="listingFeeTransactions">
                <!-- JS will populate items here -->
            </div>
        </section>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    /* ================================
       CARD NUMBER TOGGLE
    ================================= */
    const cardNumberEl = document.getElementById('cardNumber');
    const toggleBtn2 = document.getElementById('toggleNumber');
    const realNumber = "4012 2312 0552 7892";
    let hidden = true; // hidden by default

    function maskNumber(num) {
        return num.replace(/\d/g, "•");
    }

    // Initialize masked card number
    cardNumberEl.textContent = maskNumber(realNumber);

    toggleBtn2.addEventListener('click', () => {
        if (hidden) {
            cardNumberEl.textContent = realNumber;
        } else {
            cardNumberEl.textContent = maskNumber(realNumber);
        }
        hidden = !hidden;
    });

    /* ================================
       UPDATE CARD BALANCE
    ================================= */
    async function updateCardBalance() {
        try {
            const response = await fetch('/BatEstateExplorer/public/api/get_listing_fee_total.php');
            const data = await response.json();

            if (data.success) {
                const cardBalanceEl = document.querySelector('.card-balance');
                cardBalanceEl.textContent = `₱${parseFloat(data.total_listing_fees).toLocaleString()}`;
            } else {
                console.error('❌ Failed to fetch listing fee total:', data.error);
            }
        } catch (err) {
            console.error('❌ Error fetching listing fee total:', err);
        }
    }

    /* ================================
       LOAD TRANSACTIONS (LISTING FEES)
    ================================= */
    async function loadListingFeeTransactions() {
        try {
            const response = await fetch('/BatEstateExplorer/public/api/get_listing_fee_transactions.php');
            const data = await response.json();

            if (!data.success) {
                console.error('❌ Failed to fetch transactions:', data.error);
                return;
            }

            const container = document.getElementById('listingFeeTransactions');
            container.innerHTML = ''; // clear old items

            data.transactions.forEach(tx => {
                const item = document.createElement('div');
                item.className = 'transaction-item';

                // ✅ Use profile picture if available, fallback to icon
                const profilePic = tx.agent_profile 
                    ? `<img src="${tx.agent_profile}" alt="${tx.agent_name}" class="transaction-pic">`
                    : `<div class="transaction-icon"><i class="fas fa-user"></i></div>`;

                item.innerHTML = `
                    ${profilePic}
                    <div class="transaction-info">
                        <p class="transaction-name">${tx.agent_name}</p>
                        <p class="transaction-date-time">${tx.datetime}</p>
                    </div>
                    <div class="transaction-amount ${tx.amount < 0 ? 'negative' : 'positive'}">
                        ₱${Math.abs(tx.amount).toFixed(2)}
                    </div>
                `;
                container.appendChild(item);
            });
        } catch (err) {
            console.error('❌ Error loading transactions:', err);
        }
    }

    /* ================================
       INIT ON PAGE LOAD
    ================================= */
    updateCardBalance();
    loadListingFeeTransactions();
});
</script>
