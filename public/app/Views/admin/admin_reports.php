<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// echo realpath(__DIR__ . '/../../../../config/database.php');



$is_logged_in = is_logged_in();
$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = get_logged_in_user($conn);
    $is_admin = ($current_user && $current_user['user_type'] === 'admin');
}

if (!$is_logged_in || !$is_admin) {
    header('Location: login.php');
    exit;
}

// (You can fetch report data here later per tab as needed)
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_reports.css" />

    <!-- Main Content -->

    <header class="content-header">
        <h1>Admin Reports</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($current_user['email']); ?></span>
        </div>
    </header>

      <div class="sort-row">
        <div class="sort-by">
          <label for="sort">Sort By:</label>
          <select id="sort">
            <option value="name">Name</option>
            <option value="type">Type</option>
            <option value="date">Date</option>
          </select>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tab-bar" role="tablist" aria-label="Report Categories">
        <div class="tab active" role="tab" tabindex="0" aria-selected="true" aria-controls="directAgentReports" id="tab-directAgent">Direct Agents</div>
        <div class="tab" role="tab" tabindex="-1" aria-selected="false" aria-controls="associateAgentReports" id="tab-associateAgent">Associate Agents</div>
        <div class="tab" role="tab" tabindex="-1" aria-selected="false" aria-controls="clientReports" id="tab-clients">Clients</div>
      </div>

      <!-- Report Sections -->
      <section id="directAgentReports" class="report-container active" role="tabpanel" aria-labelledby="tab-directAgent">
        <div class="no-reports">No reports available for Direct Agents.</div>
      </section>

      <section id="associateAgentReports" class="report-container" role="tabpanel" aria-labelledby="tab-associateAgent">
        <div class="no-reports">No reports available for Associate Agents.</div>
      </section>

      <section id="clientReports" class="report-container" role="tabpanel" aria-labelledby="tab-clients">
        <div class="no-reports">No reports available for Clients.</div>
      </section>
    </main>
  </div>

  <script>
    // Tab switching logic
    const tabs = document.querySelectorAll('.tab-bar .tab');
    const reportContainers = document.querySelectorAll('.report-container');
    const sortSelect = document.getElementById('sort');

    function activateTab(tab) {
      tabs.forEach(t => {
        const selected = t === tab;
        t.classList.toggle('active', selected);
        t.setAttribute('aria-selected', selected ? 'true' : 'false');
        t.tabIndex = selected ? 0 : -1;
      });

      reportContainers.forEach(container => {
        container.classList.toggle('active', container.id === tab.getAttribute('aria-controls'));
      });

      // Reset sorting dropdown when switching tabs (optional)
      sortSelect.selectedIndex = 0;
    }

    tabs.forEach(tab => {
      tab.addEventListener('click', () => activateTab(tab));
      tab.addEventListener('keydown', e => {
        if (e.key === 'ArrowRight') {
          const next = tab.nextElementSibling || tabs[0];
          next.focus();
        } else if (e.key === 'ArrowLeft') {
          const prev = tab.previousElementSibling || tabs[tabs.length - 1];
          prev.focus();
        } else if (e.key === 'Enter' || e.key === ' ') {
          activateTab(tab);
        }
      });
    });

    // Sorting placeholder function (to be implemented when reports have content)
    sortSelect.addEventListener('change', () => {
      // Implement sorting logic per visible tab here
      alert(`Sorting reports by: ${sortSelect.value}. (Sorting logic not implemented yet.)`);
    });

    // Sidebar toggle and dropdown logic can be added here if needed (copied from original)

  </script>
