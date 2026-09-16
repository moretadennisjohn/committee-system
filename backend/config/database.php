<?php
ob_start();

define('SUPABASE_URL', 'https://nruuovzxuxagwgrpdnmc.supabase.co');
define('SUPABASE_ANON_KEY', 'sb_publishable_pl8UNT15Ljs3RreIiIQVDA_l_-Erm-D');
define('SUPABASE_API', SUPABASE_URL . '/rest/v1/');

function supabaseRequest($endpoint, $method = 'GET', $data = null) {
    $url = SUPABASE_API . $endpoint;
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Prefer: return=representation'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}

function corsHeaders() {
    ob_clean();
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, apikey');
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
}
?>