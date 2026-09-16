<?php
ob_start();
require_once '../../config/database.php';
corsHeaders();
ob_clean();

$method = $_SERVER['REQUEST_METHOD'];
// ... rest of code

switch($method) {
    case 'GET':
        $result = supabaseRequest('jurisdictions?order=created_at.desc');
        echo json_encode(is_array($result) ? $result : []);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['committee_id']) || !isset($data['area_name'])) {
            echo json_encode(['success' => false, 'message' => 'Required fields missing']);
            exit;
        }
        $result = supabaseRequest('jurisdictions', 'POST', [
            'committee_id' => $data['committee_id'],
            'area_name' => $data['area_name'],
            'category' => $data['category'] ?? null
        ]);
        echo json_encode(['success' => true, 'data' => $result]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        supabaseRequest('jurisdictions?id=eq.' . $data['id'], 'PATCH', [
            'committee_id' => $data['committee_id'],
            'area_name' => $data['area_name'],
            'category' => $data['category'] ?? null
        ]);
        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        supabaseRequest('jurisdictions?id=eq.' . $data['id'], 'DELETE');
        echo json_encode(['success' => true]);
        break;
}
?>