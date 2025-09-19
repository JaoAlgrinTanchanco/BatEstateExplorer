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
                    <div class="card-balance">$7,983.82</div>
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
            <div class="transactions-list">
                <div class="transaction-item">
                    <div class="transaction-icon"><i class="fas fa-utensils"></i></div>
                    <div class="transaction-info">
                        <p class="transaction-name">Restaurant food</p>
                        <p class="transaction-date-time">2/02 • 6:11 PM</p>
                    </div>
                    <div class="transaction-amount negative">-$32.01</div>
                </div>

                <div class="transaction-item">
                    <div class="transaction-icon"><i class="fas fa-user"></i></div>
                    <div class="transaction-info">
                        <p class="transaction-name">From Joe</p>
                        <p class="transaction-date-time">1/02 • 1:21 PM</p>
                    </div>
                    <div class="transaction-amount positive">+$100.00</div>
                </div>

                <div class="transaction-item">
                    <div class="transaction-icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="transaction-info">
                        <p class="transaction-name">Shopping cashback</p>
                        <p class="transaction-date-time">1/02 • 11:21 AM</p>
                    </div>
                    <div class="transaction-amount positive">+$4.11</div>
                </div>

                <div class="transaction-item">
                    <div class="transaction-icon"><i class="fas fa-gift"></i></div>
                    <div class="transaction-info">
                        <p class="transaction-name">For Tom's gift</p>
                        <p class="transaction-date-time">20/01 • 8:32 PM</p>
                    </div>
                    <div class="transaction-amount negative">-$58.00</div>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
    const cardNumberEl = document.getElementById('cardNumber');
    const toggleBtn2 = document.getElementById('toggleNumber');

    let hidden = true; // hidden by default
    const realNumber = "4012 2312 0552 7892";

    toggleBtn2.addEventListener('click', () => {
        if (hidden) {
            cardNumberEl.textContent = realNumber;
            hidden = false;
        } else {
            cardNumberEl.textContent = realNumber.replace(/\d/g, "•");
            hidden = true;
        }
    });

    // Initialize hidden state
    cardNumberEl.textContent = realNumber.replace(/\d/g, "•");
</script>