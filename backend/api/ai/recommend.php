<?php
require_once '../../config/database.php';
corsHeaders();

$data = json_decode(file_get_contents('php://input'), true);
$committee_id = $data['committee_id'] ?? '';
$required_skills = $data['required_skills'] ?? [];
$max_workload = 5;

if (!$committee_id) {
    echo json_encode(['success' => false, 'message' => 'Committee required']);
    exit;
}

// Get committee info
$committee = supabaseRequest('committees?id=eq.' . $committee_id);
$committee = $committee[0] ?? null;

// Get all available members with task counts
$members = supabaseRequest('members?availability=eq.available');
$tasks = supabaseRequest('tasks?status=eq.pending');

// Count tasks per member
$taskCounts = [];
foreach ($tasks as $task) {
    $taskCounts[$task['member_id']] = ($taskCounts[$task['member_id']] ?? 0) + 1;
}

// Get existing assignments for this committee
$existing = supabaseRequest('committee_members?committee_id=eq.' . $committee_id);
$assignedIds = array_column($existing ?? [], 'member_id');

// Filter out already assigned and overloaded members
$eligibleMembers = array_filter($members ?? [], function($m) use ($taskCounts, $assignedIds, $max_workload) {
    $taskCount = $taskCounts[$m['id']] ?? 0;
    return !in_array($m['id'], $assignedIds) && $taskCount < $max_workload;
});

if (empty($eligibleMembers)) {
    echo json_encode(['success' => false, 'message' => 'No eligible members available']);
    exit;
}

// Build prompt for Gemini
$membersList = array_map(function($m) use ($taskCounts) {
    $skills = is_array($m['skills']) ? implode(', ', $m['skills']) : ($m['skills'] ?? 'None');
    $taskCount = $taskCounts[$m['id']] ?? 0;
    return "- ID:{$m['id']} | Name:{$m['full_name']} | Position:{$m['position']} | Skills:{$skills} | Tasks:{$taskCount}/5 | Availability:{$m['availability']}";
}, $eligibleMembers);

$prompt = "You are an AI for SK (Sangguniang Kabataan) Committee Management System in the Philippines.

Committee: {$committee['name']}
Type: {$committee['type']}
Purpose: {$committee['purpose']}
Required Skills: " . implode(', ', $required_skills) . "
Maximum members needed: 5

Eligible Members:
" . implode("\n", $membersList) . "

Analyze each member and recommend the TOP 5 best members for this committee.
Consider: skills match, current workload (fewer tasks = better), position, and availability.
Score each from 0-100.

Respond ONLY in this exact JSON format:
{
  \"recommendations\": [
    {
      \"member_id\": \"uuid here\",
      \"member_name\": \"name here\",
      \"score\": 95,
      \"reason\": \"brief reason here\",
      \"skills_match\": \"high/medium/low\",
      \"workload_status\": \"light/moderate/heavy\"
    }
  ],
  \"summary\": \"brief overall recommendation summary\"
}";

// Call Gemini API
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

// Clean JSON from response
$text = preg_replace('/```json\n?|\n?```/', '', $text);
$text = trim($text);
$aiData = json_decode($text, true);

if (!$aiData) {
    echo json_encode(['success' => false, 'message' => 'AI response error', 'raw' => $text]);
    exit;
}

echo json_encode(['success' => true, 'data' => $aiData]);
?>