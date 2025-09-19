<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_wallet.css" />

<div class="dashboard-container">
    <main class="wallet-content">
        <header class="dashboard-header">
            <div class="welcome-message">
                <h1>Hey there, Amy!</h1>
                <p>Have a great day! Good luck on your financial journey.</p>
            </div>
        </header>

        <section class="cards-section">
            <h2>Your cards</h2>
            <div class="cards-grid">
                <div class="card visa-standard">
                    <div class="card-type">VISA Standard</div>
                    <div class="card-balance">$7,983.82</div>
                    <div class="card-number">4012 2312 0552 7892</div>
                    <div class="card-details">
                        <span class="card-holder">MR. JOHN DOE</span>
                        <span class="card-expiry">05/23</span>
                    </div>
                </div>
                <div class="card visa-premium">
                    <div class="card-type">VISA Premium</div>
                    <div class="card-balance">$8,172.72</div>
                    <div class="card-number">3761 0200 0003 7882</div>
                    <div class="card-details">
                        <span class="card-holder">MS. JANE DOE</span>
                        <span class="card-expiry">04/22</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="transactions-section">
            <h2>Recent transactions</h2>
            <div class="transactions-table">
                <div class="table-header">
                    <span>Transaction name</span>
                    <span>Amount</span>
                    <span>Date</span>
                    <span>Time</span>
                    <span>Actions</span>
                </div>
                <div class="transaction-item">
                    <span class="icon"><i class="fas fa-utensils"></i></span>
                    <span>Restaurant food</span>
                    <span class="negative">-$32.01</span>
                    <span>2/02</span>
                    <span>6:11 PM</span>
                    <button class="btn-details">Details</button>
                </div>
                <div class="transaction-item">
                    <span class="icon"><i class="fas fa-user"></i></span>
                    <span>From Joe</span>
                    <span class="positive">+$100.00</span>
                    <span>1/02</span>
                    <span>1:21 PM</span>
                    <button class="btn-details">Details</button>
                </div>
                <div class="transaction-item">
                    <span class="icon"><i class="fas fa-shopping-bag"></i></span>
                    <span>Shopping cashback</span>
                    <span class="positive">+$4.11</span>
                    <span>1/02</span>
                    <span>11:21 AM</span>
                    <button class="btn-details">Details</button>
                </div>
                <div class="transaction-item">
                    <span class="icon"><i class="fas fa-gift"></i></span>
                    <span>For Tom's gift</span>
                    <span class="negative">-$58.00</span>
                    <span>20/01</span>
                    <span>8:32 PM</span>
                    <button class="btn-details">Details</button>
                </div>
            </div>
            <div class="transactions-footer">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search transaction history...">
                </div>
                <button class="btn-secondary">See all history</button>
                <button class="btn-primary">New transfer</button>
            </div>
        </section>
    </main>

    <aside class="profile-sidebar">
        <div class="profile-card">
            <div class="profile-avatar">
                <img src="https://via.placeholder.com/60" alt="Amy Smith"> </div>
            <div class="profile-info">
                <h3>Amy Smith</h3>
                <p>Total balance: <span>$19,842.12</span></p>
                <p>Acc. status: <span class="status-activated">Activated</span></p>
            </div>
            <nav class="profile-actions">
                <ul>
                    <li><i class="fas fa-cog"></i> Account Settings</li>
                    <li><i class="fas fa-history"></i> Transfer history</li>
                    <li><i class="fas fa-exchange-alt"></i> Transfer settings</li>
                    <li><i class="fas fa-building"></i> Open company account</li>
                </ul>
            </nav>
        </div>
        <div class="promo-card">
            <i class="fas fa-hand-holding-heart"></i>
            <h4>Enjoying freelye?</h4>
            <p>Support us by inviting friends to join freelye</p>
        </div>
    </aside>
</div>