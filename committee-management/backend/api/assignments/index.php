<?php
ob_start();
require_once '../../config/database.php';
corsHeaders();
ob_clean();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $result = supabaseRequest('committee_members?order=assigned_at.desc');
        if (!is_array($result)) $result = [];
        echo json_encode($result);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['committee_id']) || !isset($data['member_id'])) {
            echo json_encode(['success' => false, 'message' => 'Committee and member required']);
            exit;
        }

        // Check duplicate
        $existing = supabaseRequest(
            'committee_members?committee_id=eq.' . $data['committee_id'] .
            '&member_id=eq.' . $data['member_id']
        );
        if (is_array($existing) && count($existing) > 0) {
            echo json_encode(['success' => false, 'message' => 'Member already assigned to this committee']);
            exit;
        }

        // Check max 5
        $current = supabaseRequest('committee_members?committee_id=eq.' . $data['committee_id']);
        if (is_array($current) && count($current) >= 5) {
            echo json_encode(['success' => false, 'message' => 'Committee already has maximum 5 members']);
            exit;
        }

        $result = supabaseRequest('committee_members', 'POST', [
            'committee_id' => $data['committee_id'],
            'member_id' => $data['member_id'],
            'role' => $data['role'] ?? 'Member'
        ]);
        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }
        supabaseRequest('committee_members?id=eq.' . $data['id'], 'DELETE');
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>