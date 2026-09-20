<<?php
ob_start();
require_once 'C:/xampp/htdocs/committee-management/backend/config/database.php';
corsHeaders();
ob_clean();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $result = supabaseRequest('reports?select=id,title,report_type,date_from,date_to,created_at&order=created_at.desc');
        echo json_encode(is_array($result) ? $result : []);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = supabaseRequest('reports', 'POST', [
            'title'        => $data['title'],
            'committee_id' => $data['committee_id'] ?? null,
            'report_type'  => $data['report_type'],
            'date_from'    => $data['date_from'] ?? null,
            'date_to'      => $data['date_to'] ?? null
        ]);
        echo json_encode(['success' => true]);
        break;
}
?>