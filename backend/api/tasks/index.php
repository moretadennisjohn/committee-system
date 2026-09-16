<?php
ob_start();
require_once '../../config/database.php';
corsHeaders();
ob_clean();

$method = $_SERVER['REQUEST_METHOD'];
// ... rest of code

switch($method) {
    case 'GET':
        $result = supabaseRequest('tasks?order=created_at.desc');
        echo json_encode(is_array($result) ? $result : []);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['title'])) {
            echo json_encode(['success' => false, 'message' => 'Title required']);
            exit;
        }
        $result = supabaseRequest('tasks', 'POST', [
            'committee_id' => $data['committee_id'] ?? null,
            'member_id' => $data['member_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'due_date' => $data['due_date'] ?? null
        ]);
        echo json_encode(['success' => true, 'data' => $result]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }
        supabaseRequest('tasks?id=eq.' . $data['id'], 'PATCH', [
            'status' => $data['status'] ?? 'pending',
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null
        ]);
        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }
        supabaseRequest('tasks?id=eq.' . $data['id'], 'DELETE');
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>