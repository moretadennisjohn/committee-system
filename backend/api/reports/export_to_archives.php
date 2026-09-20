<?php
require_once _DIR_ . '/../../config/database.php';
corsHeaders();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $report_id = $data['report_id'] ?? null;

    if (!$report_id) {
        echo json_encode(['success' => false, 'message' => 'report_id required']);
        exit;
    }

    // Get report
    $report = supabaseRequest("reports?id=eq.$report_id&select=*");

    if (empty($report)) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }
    $report = $report[0];

    // Get performance - simple query no joins
    $committee_id = $report['committee_id'] ?? null;
    $perf_url = $committee_id
        ? "performance?committee_id=eq.$committee_id&select=*"
        : "performance?select=*";

    $performance = supabaseRequest($perf_url);

    $compiled = [
        'report'      => $report,
        'performance' => $performance,
        'exported_at' => date('Y-m-d H:i:s'),
        'system'      => 'SK Committee Management System'
    ];

    $ref = 'ARCH-' . date('Ymd') . '-' . str_pad($report_id, 4, '0', STR_PAD_LEFT);

    supabaseRequest('legislative_archives', 'POST', [
        'report_id'         => (int)$report_id,
        'report_title'      => $report['title'] ?? 'Untitled',
        'report_type'       => $report['type'] ?? 'general',
        'committee_id'      => $committee_id,
        'compiled_data'     => $compiled,
        'archive_reference' => $ref,
        'status'            => 'archived'
    ]);

    supabaseRequest('module_integration_log', 'POST', [
        'source_module' => 'reports',
        'target_module' => 'legislative_archives',
        'data_type'     => 'accomplishment_report',
        'record_id'     => (int)$report_id,
        'status'        => 'sent'
    ]);

    echo json_encode([
        'success'           => true,
        'message'           => 'Exported to Legislative Archives!',
        'archive_reference' => $ref
    ]);

} elseif ($method === 'GET') {
    $result = supabaseRequest(
        'legislative_archives?select=*&order=exported_at.desc'
    );
    echo json_encode($result);
}
?>