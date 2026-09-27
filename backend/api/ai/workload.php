<?php
require_once __DIR__ . '/../../config/database.php';
corsHeaders();

$max_tasks = 5;

// Get all members
$members = supabaseRequest('members');
$tasks = supabaseRequest('tasks');
$committees = supabaseRequest('committees');

// Count tasks per member
$taskCounts = [];
$memberTasks = [];
foreach ($tasks ?? [] as $task) {
    if ($task['member_id']) {
        $taskCounts[$task['member_id']] = ($taskCounts[$task['member_id']] ?? 0) + 1;
        $memberTasks[$task['member_id']][] = $task;
    }
}

// Categorize members
$overloaded = [];
$underloaded = [];
$balanced = [];

foreach ($members ?? [] as $m) {
    $count = $taskCounts[$m['id']] ?? 0;
    $m['task_count'] = $count;
    if ($count > $max_tasks) $overloaded[] = $m;
    elseif ($count < 2) $underloaded[] = $m;
    else $balanced[] = $m;
}

// Build prompt
$prompt = "You are an AI workload distribution assistant for SK Committee System.

OVERLOADED MEMBERS (more than 5 tasks):
" . (empty($overloaded) ? "None" : implode("\n", array_map(fn($m) => "- {$m['full_name']}: {$m['task_count']} tasks", $overloaded))) . "

UNDERLOADED MEMBERS (less than 2 tasks):
" . (empty($underloaded) ? "None" : implode("\n", array_map(fn($m) => "- {$m['full_name']}: {$m['task_count']} tasks", $underloaded))) . "

BALANCED MEMBERS (2-5 tasks):
" . (empty($balanced) ? "None" : implode("\n", array_map(fn($m) => "- {$m['full_name']}: {$m['task_count']} tasks", $balanced))) . "

Analyze the workload distribution and provide recommendations.
Respond ONLY in this JSON format:
{
  \"status\": \"balanced/needs_rebalancing\",
  \"overloaded_count\": 0,
  \"underloaded_count\": 0,
  \"recommendations\": [
    {
      \"action\": \"redistribute\",
      \"from_member\": \"name\",
      \"to_member\": \"name\",
      \"reason\": \"brief reason\"
    }
  ],
  \"summary\": \"overall workload analysis summary\",
  \"alert_level\": \"green/yellow/red\"
}";

$geminiKey = defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '' ? GEMINI_API_KEY : (getenv('GEMINI_API_KEY') ?: '');
if (!$geminiKey) {
    echo json_encode(['success' => false, 'message' => 'Gemini API key is not configured in .env. Please set GEMINI_API_KEY in your .env file.']);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $geminiKey);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]]
]));

$response = curl_exec($ch);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode(['success' => false, 'message' => 'cURL error: ' . $curlErr]);
    exit;
}

$result = json_decode($response, true);
if (isset($result['error'])) {
    echo json_encode(['success' => false, 'message' => $result['error']['message'] ?? 'Gemini API error', 'error' => $result['error']]);
    exit;
}

$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
$text = preg_replace('/```json\n?|```/i', '', $text);
$aiData = json_decode(trim($text), true);

if (!$aiData) {
    echo json_encode(['success' => false, 'message' => 'Failed to parse AI response as JSON', 'raw' => $text]);
    exit;
}

echo json_encode([
    'success' => true,
    'stats' => [
        'total_members' => count($members ?? []),
        'overloaded' => count($overloaded),
        'underloaded' => count($underloaded),
        'balanced' => count($balanced),
        'overloaded_members' => $overloaded,
        'underloaded_members' => $underloaded
    ],
    'ai_analysis' => $aiData
]);
?>