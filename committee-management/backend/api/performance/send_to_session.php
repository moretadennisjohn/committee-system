<?php
require_once _DIR_ . '/../../config/database.php';
corsHeaders();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $committee_id = $data['committee_id'] ?? null;

    // Simple query - no joins
    $url = $committee_id
        ? "performance?committee_id=eq.$committee_id&select=*"
        : "performance?select=*";

    $scores = supabaseRequest($url);

    if (empty($scores)) {
        echo json_encode([
            'success' => false, 
            'message' => 'No performance data found'
        ]);
        exit;
    }

    $total = count($scores);
    $avgScore = round(
        array_sum(array_column($scores, 'final_score')) / $total, 2
    );

    $analytics = [
        'total_members' => $total,
        'average_score' => $avgScore,
        'top_performer' => 'N/A',
        'generated_at'  => date('Y-m-d H:i:s'),
        'members'       => $scores
    ];

    // Save to session_performance_logs
    foreach ($scores as $score) {
        supabaseRequest('session_performance_logs', 'POST', [
            'committee_id'      => $score['committee_id'] ?? null,
            'member_id'         => $score['member_id'] ?? null,
            'performance_score' => $score['final_score'] ?? 0,
            'attendance_rate'   => $score['attendance_rate'] ?? 0,
            'task_completion'   => $score['task_completion_rate'] ?? 0,
            'analytics_data'    => $analytics,
            'sent_to_session'   => true
        ]);
    }

    // Log integration
    supabaseRequest('module_integration_log', 'POST', [
        'source_module' => 'performance',
        'target_module' => 'session_management',
        'data_type'     => 'performance_logs',
        'record_id'     => $committee_id ?? 0,
        'status'        => 'sent'
    ]);

    echo json_encode([
        'success'           => true,
        'message'           => 'Performance logs sent!',
        'analytics_summary' => $analytics
    ]);

} elseif ($method === 'GET') {
    // Simple test response
    echo json_encode([
        'success' => true,
        'message' => 'send_to_session.php is working!',
        'method'  => 'Use POST to send data'
    ]);
}
?>
