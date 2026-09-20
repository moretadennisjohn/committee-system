<?php
ob_start();
require_once '../../config/database.php';
corsHeaders();
ob_clean();

$method = $_SERVER['REQUEST_METHOD'];
// ... rest of code

switch($method) {
    case 'GET':
        $result = supabaseRequest('members?order=created_at.desc');
        echo json_encode(is_array($result) ? $result : []);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['full_name'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        $skills = isset($data['skills']) && is_array($data['skills']) ? $data['skills'] : [];
        $result = supabaseRequest('members', 'POST', [
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'position' => $data['position'] ?? null,
            'skills' => $skills,
            'availability' => $data['availability'] ?? 'available',
            'workload_score' => 0
        ]);
        echo json_encode(['success' => true, 'data' => $result]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }
        $result = supabaseRequest('members?id=eq.' . $data['id'], 'PATCH', [
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'position' => $data['position'] ?? null,
            'availability' => $data['availability'] ?? 'available'
        ]);
        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }
        supabaseRequest('members?id=eq.' . $data['id'], 'DELETE');
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>