<?php
require_once '../../config/database.php';
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

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=AQ.Ab8RN6LxwEHD8ygzbNkyJ4rUVl29qTF4JtKbq3YmLhCPIKhKkA');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]]
]));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
$text = preg_replace('/```json\n?|\n?```/', '', $text);
$aiData = json_decode(trim($text), true);

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