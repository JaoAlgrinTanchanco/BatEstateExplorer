<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Helper to count agent properties
function countAgentProperties($conn, $agentId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM properties WHERE agent_id = ?");
    $stmt->bind_param("i", $agentId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)$result['cnt'];
}

// --- Fetch Agents ---
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

// --- Fetch Agent Reports ---
$agentReports = [];
$sqlAgent = "
    SELECT ar.*, 
           reporter.first_name AS reporter_fname, reporter.last_name AS reporter_lname, reporter.email AS reporter_email,
           reported.first_name AS reported_fname, reported.last_name AS reported_lname, reported.email AS reported_email
    FROM agent_reports ar
    LEFT JOIN users reporter ON ar.reporter_id = reporter.id
    LEFT JOIN users reported ON ar.agent_id = reported.id
    ORDER BY ar.created_at DESC";
$resAgent = $conn->query($sqlAgent);

if ($resAgent) {
    while ($r = $resAgent->fetch_assoc()) {
        $agentReports[] = [
            'id' => $r['id'],
            'report_type' => 'agent',
            'reporter_name' => trim($r['reporter_fname'] . ' ' . $r['reporter_lname']),
            'reporter_email' => $r['reporter_email'],
            'reported_user_name' => trim($r['reported_fname'] . ' ' . $r['reported_lname']),
            'reported_user_email' => $r['reported_email'],
            'reason' => $r['reason'],
            'other_reason' => $r['other_reason'],
            'details' => $r['details'],
            'status' => $r['status'],
            'blockage_date' => $r['blockage_date'],
            'evidence_path' => $r['evidence_path'],
            'created_at' => $r['created_at']
        ];
    }
}

// --- Fetch User Reports ---
$userReports = [];
$sqlUser = "
    SELECT ur.*, 
           reporter.first_name AS reporter_fname, reporter.last_name AS reporter_lname, reporter.email AS reporter_email,
           reported.first_name AS reported_fname, reported.last_name AS reported_lname, reported.email AS reported_email
    FROM user_reports ur
    LEFT JOIN users reporter ON ur.reporter_id = reporter.id
    LEFT JOIN users reported ON ur.reported_user_id = reported.id
    ORDER BY ur.created_at DESC";
$resUser = $conn->query($sqlUser);

if ($resUser) {
    while ($r = $resUser->fetch_assoc()) {
        $userReports[] = [
            'id' => $r['id'],
            'report_type' => 'user',
            'reporter_name' => trim($r['reporter_fname'] . ' ' . $r['reporter_lname']),
            'reporter_email' => $r['reporter_email'],
            'reported_user_name' => trim($r['reported_fname'] . ' ' . $r['reported_lname']),
            'reported_user_email' => $r['reported_email'],
            'reason' => $r['reason'],
            'other_reason' => $r['other_reason'],
            'details' => $r['details'],
            'status' => $r['status'],
            'blockage_date' => $r['blockage_date'],
            'evidence_path' => $r['evidence_path'],
            'created_at' => $r['created_at']
        ];
    }
}

// Merge both types of reports
$allReports = array_merge($agentReports, $userReports);

// --- Function to summarize metrics ---
function summarize($list) {
    $total = count($list);
    $active = count(array_filter($list, fn($x) => $x['active']));
    $inactive = $total - $active;

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

    $avg_exp = 0;
    if (isset($list[0]['experience_years'])) {
        $totalExp = array_sum(array_map(fn($x) => $x['experience_years'], $list));
        $avg_exp = $total > 0 ? round($totalExp / $total, 1) : 0;
    }

    return [
        'total' => $total,
        'active' => $active,
        'inactive' => $inactive,
        'avg_experience_years' => $avg_exp,
        'monthly_signups' => $monthly_signups
    ];
}

// --- Final response ---
$response = [
    'direct_agents' => summarize($direct_agents),
    'associate_agents' => summarize($associate_agents),
    'user_reports' => $allReports,
    'debug' => [
        'agent_reports_count' => count($agentReports),
        'user_reports_count' => count($userReports),
        'total_reports_count' => count($allReports)
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
