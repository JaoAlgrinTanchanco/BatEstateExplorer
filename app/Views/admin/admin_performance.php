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

<!-- Main Content HTML -->
<header class="content-header">
    <h1>Performance</h1>
    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($current_user['email']); ?></span>
    </div>
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
    <canvas id="performanceChart" height="260"></canvas>
  <?php endif; ?>
</section>

<section class="chart-section">
  <h2>Most Selling Properties</h2>
  <?php if (empty($most_selling_properties)): ?>
    <div class="no-data">No property sales data available.</div>
  <?php else: ?>
    <canvas id="propertyChart" height="260"></canvas>
  <?php endif; ?>
</section>

<section class="chart-section">
  <h2>Company Properties Sold</h2>
  <?php if (empty($company_performance)): ?>
    <div class="no-data">No company performance data available.</div>
  <?php else: ?>
    <canvas id="companyChart" height="260"></canvas>
  <?php endif; ?>
</section>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
<?php if (!empty($agent_performance)): ?>
const performanceCtx = document.getElementById('performanceChart').getContext('2d');
new Chart(performanceCtx, {
  type: 'bar',
  data: {
    labels: [<?php echo implode(',', array_map(fn($a) => '"' . addslashes($a['first_name'] . ' ' . $a['last_name']) . '"', $agent_performance)); ?>],
    datasets: [{
      label: 'Properties Sold',
      data: [<?php echo implode(',', array_map(fn($a) => (int)$a['properties_sold'], $agent_performance)); ?>],
      backgroundColor: '#1a7f1a',
      borderRadius: 8,
    }]
  },
  options: {
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => `Properties Sold: ${ctx.parsed.y}`
        }
      }
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { color: '#333', font: { size: 14 } },
        title: {
          display: true,
          text: 'Agents',
          color: '#333',
          font: { size: 16, weight: 'bold' },
          padding: { top: 12 }
        }
      },
      y: {
        beginAtZero: true,
        grid: { color: '#eee' },
        ticks: { color: '#888', font: { size: 13 } },
        title: {
          display: true,
          text: 'Properties Sold',
          color: '#333',
          font: { size: 16, weight: 'bold' },
          padding: { bottom: 12 }
        }
      }
    }
  }
});
<?php endif; ?>

<?php if (!empty($most_selling_properties)): ?>
const propertyCtx = document.getElementById('propertyChart').getContext('2d');
new Chart(propertyCtx, {
  type: 'bar',
  data: {
    labels: [<?php echo implode(',', array_map(fn($p) => '"' . addslashes($p['title']) . '"', $most_selling_properties)); ?>],
    datasets: [{
      label: 'Units Sold',
      data: [<?php echo implode(',', array_map(fn($p) => (int)$p['units_sold'], $most_selling_properties)); ?>],
      backgroundColor: '#0074d9',
      borderRadius: 8,
    }]
  },
  options: {
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => `Units Sold: ${ctx.parsed.y}`
        }
      }
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { color: '#333', font: { size: 14 } },
        title: {
          display: true,
          text: 'Properties',
          color: '#333',
          font: { size: 16, weight: 'bold' },
          padding: { top: 12 }
        }
      },
      y: {
        beginAtZero: true,
        grid: { color: '#eee' },
        ticks: { color: '#888', font: { size: 13 } },
        title: {
          display: true,
          text: 'Units Sold',
          color: '#333',
          font: { size: 16, weight: 'bold' },
          padding: { bottom: 12 }
        }
      }
    }
  }
});
<?php endif; ?>

<?php if (!empty($company_performance)): ?>
const companyCtx = document.getElementById('companyChart').getContext('2d');
new Chart(companyCtx, {
  type: 'bar',
  data: {
    labels: [<?php echo implode(',', array_map(fn($c) => '"' . addslashes($c['name']) . '"', $company_performance)); ?>],
    datasets: [{
      label: 'Properties Sold',
      data: [<?php echo implode(',', array_map(fn($c) => (int)$c['properties_sold'], $company_performance)); ?>],
      backgroundColor: ['#0074d9', '#1a7f1a', '#bbb'].slice(0, <?php echo count($company_performance); ?>),
      borderRadius: 8,
    }]
  },
  options: {
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => `Properties Sold: ${ctx.parsed.y}`
        }
      }
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { color: '#333', font: { size: 14 } },
        title: {
          display: true,
          text: 'Companies',
          color: '#333',
          font: { size: 16, weight: 'bold' },
          padding: { top: 12 }
        }
      },
      y: {
        beginAtZero: true,
        grid: { color: '#eee' },
        ticks: { color: '#888', font: { size: 13 } },
        title: {
          display: true,
          text: 'Properties Sold',
          color: '#333',
          font: { size: 16, weight: 'bold' },
          padding: { bottom: 12 }
        }
      }
    }
  }
});
<?php endif; ?>
</script>
