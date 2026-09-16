<?php
ob_start();
require_once '../../config/database.php';
corsHeaders();
ob_clean();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $result = supabaseRequest('performance?order=created_at.desc');
        echo json_encode($result ?: []);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = supabaseRequest('performance', 'POST', [
            'member_id' => $data['member_id'],
            'committee_id' => $data['committee_id'],
            'attendance_rate' => $data['attendance_rate'] ?? 0,
            'task_completion_rate' => $data['task_completion_rate'] ?? 0,
            'performance_score' => $data['performance_score'] ?? 0,
            'period' => $data['period'] ?? null
        ]);
        echo json_encode(['success' => true]);
        break;
}
?>