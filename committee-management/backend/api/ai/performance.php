<?php
require_once __DIR__ . '/../../config/database.php';
corsHeaders();

$data = json_decode(file_get_contents('php://input'), true);
$member_id = $data['member_id'] ?? null;

// Get members
$memberFilter = $member_id ? '?id=eq.' . $member_id : '';
$members = supabaseRequest('members' . $memberFilter);
$tasks = supabaseRequest('tasks');
$performance = supabaseRequest('performance');

$memberScores = [];

foreach ($members ?? [] as $member) {
    $mid = $member['id'];

    // Task stats
    $memberTasks = array_filter($tasks ?? [], fn($t) => $t['member_id'] === $mid);
    $totalTasks = count($memberTasks);
    $completedTasks = count(array_filter($memberTasks, fn($t) => $t['status'] === 'completed'));
    $onTimeTasks = count(array_filter($memberTasks, fn($t) =>
        $t['status'] === 'completed' &&
        $t['due_date'] &&
        strtotime($t['updated_at'] ?? 'now') <= strtotime($t['due_date'])
    ));

    // Performance record
    $perfRecord = array_filter($performance ?? [], fn($p) => $p['member_id'] === $mid);
    $perfRecord = array_values($perfRecord);
    $attendanceRate = $perfRecord[0]['attendance_rate'] ?? 0;

    // Calculate scores
    $taskCompletionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
    $onTimeRate = $completedTasks > 0 ? ($onTimeTasks / $completedTasks) * 100 : 0;

    // Weighted score
    $finalScore = ($taskCompletionRate * 0.50) + ($attendanceRate * 0.20) + ($onTimeRate * 0.30);

    $memberScores[] = [
        'member_id' => $mid,
        'member_name' => $member['full_name'],
        'position' => $member['position'],
        'total_tasks' => $totalTasks,
        'completed_tasks' => $completedTasks,
        'task_completion_rate' => round($taskCompletionRate, 2),
        'attendance_rate' => round($attendanceRate, 2),
        'on_time_rate' => round($onTimeRate, 2),
        'final_score' => round($finalScore, 2),
        'grade' => $finalScore >= 90 ? 'Excellent' : ($finalScore >= 75 ? 'Good' : ($finalScore >= 60 ? 'Average' : 'Needs Improvement'))
    ];
}

// Sort by score
usort($memberScores, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

// Get AI insights
$prompt = "You are a performance analyst for SK Committee System Philippines.

Member Performance Data:
" . implode("\n", array_map(fn($m) =>
    "- {$m['member_name']} | Score:{$m['final_score']}% | Tasks:{$m['completed_tasks']}/{$m['total_tasks']} | Attendance:{$m['attendance_rate']}% | OnTime:{$m['on_time_rate']}% | Grade:{$m['grade']}",
    $memberScores
)) . "

Scoring: Task Completion=50%, Attendance=20%, On-Time=30%

Provide performance insights. Respond ONLY in JSON:
{
  \"top_performer\": \"name\",
  \"needs_improvement\": [\"name1\", \"name2\"],
  \"insights\": [
    \"insight 1\",
    \"insight 2\",
    \"insight 3\"
  ],
  \"recommendations\": [
    \"recommendation 1\",
    \"recommendation 2\"
  ],
  \"overall_team_score\": 0,
  \"team_status\": \"excellent/good/average/poor\"
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
$aiInsights = json_decode(trim($text), true);

if (!$aiInsights) {
    echo json_encode(['success' => false, 'message' => 'Failed to parse AI response as JSON', 'raw' => $text]);
    exit;
}

echo json_encode([
    'success' => true,
    'members' => $memberScores,
    'ai_insights' => $aiInsights
]);
?>