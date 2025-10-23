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
                        <span class="card-holder">Administration</span>
                        <span class="card-expiry">05/26</span>
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
    document.addEventListener('DOMContentLoaded', async () => {
        /* ================================
        CONFIG
        ================================ */
        const API_BASE = '/BatEstateExplorer/public/api';
        const TRIM_API = `${API_BASE}/trim_transactions.php`;
        const BALANCE_API = `${API_BASE}/get_listing_fee_total.php`;
        const TX_API = `${API_BASE}/get_listing_fee_transactions.php`;

        /* ================================
        HELPERS
        ================================ */
        function maskNumber(num) {
            return num.replace(/\d/g, "•");
        }

        async function safeFetchJson(url, opts = {}) {
            opts.credentials = opts.credentials || 'same-origin';
            try {
                const res = await fetch(url, opts);
                return await res.json();
            } catch (err) {
                console.error('Fetch error for', url, err);
                return { success: false, error: err.message || 'Fetch error' };
            }
        }

        /* ================================
        CARD NUMBER TOGGLE
        ================================ */
        const cardNumberEl = document.getElementById('cardNumber');
        const toggleBtn2 = document.getElementById('toggleNumber');
        const realNumber = "4012 2312 0552 7892";
        let hidden = true;

        if (cardNumberEl) cardNumberEl.textContent = maskNumber(realNumber);

        if (toggleBtn2) {
            toggleBtn2.addEventListener('click', () => {
                cardNumberEl.textContent = hidden ? realNumber : maskNumber(realNumber);
                hidden = !hidden;
            });
        }

        /* ================================
        TRIM OLD TRANSACTIONS (ADMIN ONLY)
        ================================ */
        async function trimOldTransactions() {
            const data = await safeFetchJson(TRIM_API, { method: 'POST' });
            if (!data.success) {
                console.warn('Trim API warning/error:', data.error || data);
            } else {
                console.log(`Trimmed transactions: ${data.deleted_count ?? 0}`);
            }
            return data;
        }

        /* ================================
        UPDATE CARD BALANCE
        ================================ */
        async function updateCardBalance() {
            const data = await safeFetchJson(BALANCE_API, { method: 'GET' });
            if (!data.success) {
                console.error('Failed to fetch wallet balance:', data.error);
                return;
            }

            const cardBalanceEl = document.querySelector('.card-balance');
            if (!cardBalanceEl) return;

            let formatted = data.wallet_balance_formatted;
            if (!formatted && typeof data.wallet_balance_raw !== 'undefined') {
                formatted = Number(data.wallet_balance_raw)
                    .toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            cardBalanceEl.textContent = `₱${formatted ?? '0.00'}`;
        }

        /* ================================
        LOAD TRANSACTIONS (LISTING FEES)
        ================================ */
        async function loadListingFeeTransactions() {
            const data = await safeFetchJson(TX_API, { method: 'GET' });
            if (!data.success) {
                console.error('Failed to fetch transactions:', data.error);
                return;
            }

            const container = document.getElementById('listingFeeTransactions');
            if (!container) return;

            container.innerHTML = '';

            data.transactions.forEach(tx => {
                const item = document.createElement('div');
                item.className = 'transaction-item';

                const profilePic = tx.agent_profile
                    ? `<img src="${tx.agent_profile}" alt="${tx.agent_name}" class="transaction-pic">`
                    : `<div class="transaction-icon neutral-bg"><i class="fas fa-user neutral-icon"></i></div>`;

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
        }

        /* ================================
        INIT: Trim then refresh UI
        ================================ */
        async function initWalletUI() {
            await trimOldTransactions();  // Admin-only trim
            await updateCardBalance();    // Update balance
            await loadListingFeeTransactions(); // Load latest 10
        }

        await initWalletUI();

        document.addEventListener('visibilitychange', async () => {
            if (document.visibilityState === 'visible') {
                await initWalletUI();
            }
        });
    });
</script>
