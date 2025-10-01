<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Helper to count properties for an agent
function countAgentProperties($conn, $agentId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM properties WHERE agent_id = ?");
    $stmt->bind_param("i", $agentId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)$result['cnt'];
}

// Fetch all agents
$agentsResult = $conn->query("SELECT * FROM users WHERE user_type IN ('direct_agent','associate_agent')");
$direct_agents = [];
$associate_agents = [];

foreach ($agentsResult as $agent) {
    $propertyCount = countAgentProperties($conn, $agent['id']);
    $agentData = [
        'id' => $agent['id'],
        'email' => $agent['email'],
        'active' => $propertyCount > 0,
        'experience_years' => (int)$agent['experience_years'],
        'created_at' => $agent['created_at']
    ];

    if ($agent['user_type'] === 'direct_agent') {
        $direct_agents[] = $agentData;
    } else {
        $associate_agents[] = $agentData;
    }
}

// Fetch clients
$clientsResult = $conn->query("SELECT * FROM users WHERE user_type='user'");
$clients = [];
foreach ($clientsResult as $client) {
    $privileges = json_decode($client['privileges'], true) ?: [];
    $clients[] = [
        'id' => $client['id'],
        'email' => $client['email'],
        'active' => count($privileges) > 0,
        'created_at' => $client['created_at']
    ];
}

// Function to summarize metrics
function summarize($list) {
    $total = count($list);
    $active = count(array_filter($list, fn($x) => $x['active']));
    $inactive = $total - $active;

    // Monthly signups
    $monthly = [];
    foreach ($list as $item) {
        $month = substr($item['created_at'], 0, 7);
        if (!isset($monthly[$month])) $monthly[$month] = 0;
        $monthly[$month]++;
    }
    $monthly_signups = [];
    foreach ($monthly as $month => $count) {
        $monthly_signups[] = ['month' => $month, 'count' => $count];
    }

    // Avg experience (only for agents)
    $avg_exp = 0;
    if (isset($list[0]['experience_years'])) {
        $totalExp = array_sum(array_map(fn($x)=>$x['experience_years'],$list));
        $avg_exp = $total > 0 ? round($totalExp/$total,1) : 0;
    }

    return [
        'total' => $total,
        'active' => $active,
        'inactive' => $inactive,
        'avg_experience_years' => $avg_exp,
        'monthly_signups' => $monthly_signups
    ];
}

$response = [
    'direct_agents' => summarize($direct_agents),
    'associate_agents' => summarize($associate_agents),
    'clients' => summarize($clients)
];

echo json_encode($response, JSON_PRETTY_PRINT);
