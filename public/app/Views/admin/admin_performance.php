<?php
// Assume $conn is your mysqli connection, session and login checks done above

// Prepare stats arrays with safe defaults
$stats = ['most_selling_property' => 'No data', 'top_agent' => 'No data'];
$agent_performance = [];
$most_selling_properties = [];
$company_performance = [];

// Most selling property
$query = "SELECT p.title, COUNT(*) as sales_count 
          FROM properties p 
          WHERE p.status = 'sold' 
          GROUP BY p.id, p.title 
          ORDER BY sales_count DESC 
          LIMIT 1";
$result = mysqli_query($conn, $query);
if ($result && ($row = mysqli_fetch_assoc($result))) {
    $stats['most_selling_property'] = $row['title'];
}

// Top performing agent
$query = "SELECT u.first_name, u.last_name, COUNT(p.id) as properties_sold
          FROM users u 
          LEFT JOIN agents a ON u.id = a.user_id 
          LEFT JOIN properties p ON a.id = p.agent_id AND p.status = 'sold'
          WHERE u.user_type IN ('direct_agent', 'associate_agent')
          GROUP BY u.id, u.first_name, u.last_name
          ORDER BY properties_sold DESC
          LIMIT 1";
$result = mysqli_query($conn, $query);
if ($result && ($row = mysqli_fetch_assoc($result))) {
    $stats['top_agent'] = $row['first_name'] . ' ' . $row['last_name'];
}

// Properties sold by agents (top 5)
$query = "SELECT u.first_name, u.last_name, COUNT(p.id) as properties_sold
          FROM users u 
          LEFT JOIN agents a ON u.id = a.user_id 
          LEFT JOIN properties p ON a.id = p.agent_id AND p.status = 'sold'
          WHERE u.user_type IN ('direct_agent', 'associate_agent')
          GROUP BY u.id, u.first_name, u.last_name
          ORDER BY properties_sold DESC
          LIMIT 5";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $agent_performance[] = $row;
    }
}

// Most selling properties (top 5)
$query = "SELECT p.title, COUNT(*) as units_sold
          FROM properties p 
          WHERE p.status = 'sold' 
          GROUP BY p.id, p.title 
          ORDER BY units_sold DESC 
          LIMIT 5";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $most_selling_properties[] = $row;
    }
}

// Company properties sold (top 3)
$query = "SELECT c.name, COUNT(p.id) as properties_sold
          FROM companies c 
          LEFT JOIN agents a ON c.id = a.company_id 
          LEFT JOIN properties p ON a.id = p.agent_id AND p.status = 'sold'
          GROUP BY c.id, c.name
          ORDER BY properties_sold DESC
          LIMIT 3";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $company_performance[] = $row;
    }
}
?>

<link rel="stylesheet" href="/BatEstateExplorer/assets/css/admin_performance.css" />

<header class="content-header">
    <h1>Performance</h1>
</header>

<div class="metrics-summary">
  <div class="metric-box">
    <div class="metric-title">Most Selling Property</div>
    <div class="metric-value metric-property"><?php echo htmlspecialchars($stats['most_selling_property']); ?></div>
  </div>
  <div class="metric-box">
    <div class="metric-title">Top Performing Agent</div>
    <div class="metric-value metric-highlight"><?php echo htmlspecialchars($stats['top_agent']); ?></div>
  </div>
</div>

<section class="chart-section">
  <h2>Properties Sold by Agents</h2>
  <?php if (empty($agent_performance)): ?>
    <div class="no-data">No performance data available.</div>
  <?php else: ?>
    <div class="chart-wrapper"><canvas id="performanceChart"></canvas></div>
  <?php endif; ?>
</section>

<section class="chart-section">
  <h2>Most Selling Properties</h2>
  <?php if (empty($most_selling_properties)): ?>
    <div class="no-data">No property sales data available.</div>
  <?php else: ?>
    <div class="chart-wrapper"><canvas id="propertyChart"></canvas></div>
  <?php endif; ?>
</section>

<section class="chart-section">
  <h2>Company Properties Sold</h2>
  <?php if (empty($company_performance)): ?>
    <div class="no-data">No company performance data available.</div>
  <?php else: ?>
    <div class="chart-wrapper"><canvas id="companyChart"></canvas></div>
  <?php endif; ?>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function getBarThickness() {
    let width = window.innerWidth;
    return Math.min(Math.max(Math.floor(width / 15), 20), 60); 
    // min 20px, max 40px
}

// ===== Agent Performance =====
<?php if (!empty($agent_performance)): ?>
const performanceCtx = document.getElementById('performanceChart').getContext('2d');
const performanceGradient = performanceCtx.createLinearGradient(0, 0, 0, document.querySelector('#performanceChart').parentElement.clientHeight);
performanceGradient.addColorStop(0, 'rgba(26,127,26,0.8)');
performanceGradient.addColorStop(1, 'rgba(26,127,26,0.1)');

new Chart(performanceCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(fn($a)=>'"'.addslashes($a['first_name'].' '.$a['last_name']).'"', $agent_performance)); ?>],
        datasets: [{
            label: 'Properties Sold',
            data: [<?php echo implode(',', array_map(fn($a)=> (int)$a['properties_sold'], $agent_performance)); ?>],
            backgroundColor: performanceGradient,
            borderRadius: 8,
            maxBarThickness: getBarThickness()
        }]
    },
    options: { responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            x: { grid: { display: false }, ticks: { color: '#333', font: { size: 14 } } },
            y: { beginAtZero: true, grid: { color: '#eee' }, ticks: { color: '#888', font: { size: 13 } } }
        }
    }
});
<?php endif; ?>

// ===== Most Selling Properties =====
<?php if (!empty($most_selling_properties)): ?>
const propertyCtx = document.getElementById('propertyChart').getContext('2d');
const propertyGradient = propertyCtx.createLinearGradient(0, 0, 0, document.querySelector('#propertyChart').parentElement.clientHeight);
propertyGradient.addColorStop(0, 'rgba(0,116,217,0.8)');
propertyGradient.addColorStop(1, 'rgba(0,116,217,0.1)');

new Chart(propertyCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(fn($p)=>'"'.addslashes($p['title']).'"', $most_selling_properties)); ?>],
        datasets: [{
            label: 'Units Sold',
            data: [<?php echo implode(',', array_map(fn($p)=> (int)$p['units_sold'], $most_selling_properties)); ?>],
            backgroundColor: propertyGradient,
            borderRadius: 8,
            maxBarThickness: getBarThickness()
        }]
    },
    options: { responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            x: { grid: { display: false }, ticks: { color: '#333', font: { size: 14 } } },
            y: { beginAtZero: true, grid: { color: '#eee' }, ticks: { color: '#888', font: { size: 13 } } }
        }
    }
});
<?php endif; ?>

// ===== Company Performance =====
<?php if (!empty($company_performance)): ?>
const companyCtx = document.getElementById('companyChart').getContext('2d');
const pastelColors = [
    'rgba(0,116,217,0.8)',
    'rgba(26,127,26,0.8)',
    'rgba(187,187,187,0.8)'
];
const companyGradients = [];
const companyHeight = document.querySelector('#companyChart').parentElement.clientHeight;

for (let i=0; i<<?php echo count($company_performance); ?>; i++){
    let grad = companyCtx.createLinearGradient(0,0,0,companyHeight);
    grad.addColorStop(0, pastelColors[i]);
    grad.addColorStop(1, pastelColors[i].replace('0.8','0.1'));
    companyGradients.push(grad);
}

new Chart(companyCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(fn($c)=>'"'.addslashes($c['name']).'"', $company_performance)); ?>],
        datasets: [{
            label: 'Properties Sold',
            data: [<?php echo implode(',', array_map(fn($c)=> (int)$c['properties_sold'], $company_performance)); ?>],
            backgroundColor: companyGradients,
            borderRadius: 8,
            maxBarThickness: getBarThickness()
        }]
    },
    options: { responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            x: { grid: { display: false }, ticks: { color: '#333', font: { size: 14 } } },
            y: { beginAtZero: true, grid: { color: '#eee' }, ticks: { color: '#888', font: { size: 13 } } }
        }
    }
});
<?php endif; ?>

// Re-render on resize
window.addEventListener('resize', () => location.reload());
</script>
