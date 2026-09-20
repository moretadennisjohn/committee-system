<?php
require_once _DIR_ . '/../../config/database.php';
corsHeaders();

$committees  = supabaseRequest('committees?select=*');
$performance = supabaseRequest('performance?select=*');
$tasks       = supabaseRequest('tasks?select=*');

$summary = [];

foreach ($committees as $committee) {
    $cid = $committee['id'];

    $cPerf = array_values(array_filter(
        $performance, fn($p) => $p['committee_id'] == $cid
    ));

    $cTasks = array_values(array_filter(
        $tasks, fn($t) => $t['committee_id'] == $cid
    ));

    $completed = array_filter(
        $cTasks, fn($t) => ($t['status'] ?? '') === 'completed'
    );

    $avgScore = count($cPerf) > 0
        ? round(array_sum(array_column($cPerf, 'final_score')) / count($cPerf), 2)
        : 0;

    $summary[] = [
        'committee_id'    => $cid,
        'committee_name'  => $committee['name'],
        'status'          => $committee['status'],
        'member_count'    => count($cPerf),
        'avg_performance' => $avgScore,
        'total_tasks'     => count($cTasks),
        'completed_tasks' => count($completed)
    ];
}

echo json_encode([
    'success'         => true,
    'accomplishments' => $summary,
    'generated_at'    => date('Y-m-d H:i:s')
]);
?>