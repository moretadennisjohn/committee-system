<?php
ob_start();
require_once '../../config/database.php';
corsHeaders();
ob_clean();

$method = $_SERVER['REQUEST_METHOD'];
// ... rest of code

switch($method) {
    case 'GET':
        $result = supabaseRequest('committees?order=created_at.desc');
        echo json_encode(is_array($result) ? $result : []);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['name'])) {
            echo json_encode(['success' => false, 'message' => 'Name required']);
            exit;
        }
        $result = supabaseRequest('committees', 'POST', [
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'mandate' => $data['mandate'] ?? null,
            'qualification_requirements' => $data['qualification_requirements'] ?? null,
            'status' => $data['status'] ?? 'active'
        ]);
        echo json_encode(['success' => true, 'data' => $result]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }
        supabaseRequest('committees?id=eq.' . $data['id'], 'PATCH', [
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'mandate' => $data['mandate'] ?? null,
            'qualification_requirements' => $data['qualification_requirements'] ?? null,
            'status' => $data['status'] ?? 'active'
        ]);
        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        supabaseRequest('committees?id=eq.' . $data['id'], 'DELETE');
        echo json_encode(['success' => true]);
        break;
}
?>
