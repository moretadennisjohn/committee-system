<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: pages/login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SK Committee System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <div class="bg-blue-900 text-white w-64 flex-shrink-0 flex flex-col">
        <div class="p-5 border-b border-blue-800">
            <h1 class="text-lg font-bold">SK Committee</h1>
            <p class="text-blue-300 text-xs mt-1">Management System</p>
        </div>
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <a href="dashboard.html" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-blue-700 text-white text-sm font-medium">📊 Dashboard</a>
            <a href="members.html" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-800 text-blue-200 text-sm">👥 Members</a>
            <a href="committees.html" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-800 text-blue-200 text-sm">🏛️ Committees</a>
            <a href="assignments.html" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-800 text-blue-200 text-sm">📋 Assignments</a>
            <a href="jurisdiction.html" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-800 text-blue-200 text-sm">🗺️ Jurisdiction</a>
            <a href="workload.html" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-800 text-blue-200 text-sm">⚖️ Workload</a>
            <a href="performance.html" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-800 text-blue-200 text-sm">📈 Performance</a>
            <a href="reports.html" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-800 text-blue-200 text-sm">📄 Reports</a>
        </nav>
        <div class="p-4 border-t border-blue-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="bg-blue-600 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold">A</div>
                <div>
                    <p class="text-sm font-medium">Admin</p>
                    <p class="text-xs text-blue-300" id="sidebarEmail">admin@sk.gov</p>
                </div>
            </div>
            <button onclick="logout()" class="w-full text-left px-3 py-2 text-blue-300 hover:text-white text-sm rounded hover:bg-blue-800">🚪 Logout</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm px-6 py-4 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Dashboard</h2>
                <p class="text-gray-500 text-sm">Welcome back! Here's your system overview.</p>
            </div>
            <div class="text-sm text-gray-500" id="currentDate"></div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">Total Committees</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-1" id="totalCommittees">0</h3>
                        </div>
                        <span class="text-3xl">🏛️</span>
                    </div>
                    <p class="text-green-500 text-xs mt-2">Active committees</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">Total Members</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-1" id="totalMembers">0</h3>
                        </div>
                        <span class="text-3xl">👥</span>
                    </div>
                    <p class="text-green-500 text-xs mt-2">Registered members</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-yellow-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">Pending Tasks</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-1" id="pendingTasks">0</h3>
                        </div>
                        <span class="text-3xl">📋</span>
                    </div>
                    <p class="text-yellow-500 text-xs mt-2">Needs attention</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">Completed Tasks</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-1" id="completedTasks">0</h3>
                        </div>
                        <span class="text-3xl">✅</span>
                    </div>
                    <p class="text-purple-500 text-xs mt-2">Tasks done</p>
                </div>
            </div>

            <!-- Recent Data -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <!-- Recent Committees -->
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800">Recent Committees</h3>
                        <a href="committees.html" class="text-blue-600 text-sm hover:underline">View all</a>
                    </div>
                    <div id="recentCommittees">
                        <p class="text-gray-400 text-sm text-center py-4">No committees yet</p>
                    </div>
                </div>

                <!-- Recent Members -->
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800">Recent Members</h3>
                        <a href="members.html" class="text-blue-600 text-sm hover:underline">View all</a>
                    </div>
                    <div id="recentMembers">
                        <p class="text-gray-400 text-sm text-center py-4">No members yet</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h3 class="font-bold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <a href="members.html" class="flex flex-col items-center gap-2 p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                        <span class="text-2xl">👤</span>
                        <span class="text-sm text-blue-700 font-medium">Add Member</span>
                    </a>
                    <a href="committees.html" class="flex flex-col items-center gap-2 p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                        <span class="text-2xl">🏛️</span>
                        <span class="text-sm text-green-700 font-medium">New Committee</span>
                    </a>
                    <a href="assignments.html" class="flex flex-col items-center gap-2 p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                        <span class="text-2xl">🤖</span>
                        <span class="text-sm text-yellow-700 font-medium">AI Recommend</span>
                    </a>
                    <a href="reports.html" class="flex flex-col items-center gap-2 p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                        <span class="text-2xl">📄</span>
                        <span class="text-sm text-purple-700 font-medium">Generate Report</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-PH', {weekday:'long', year:'numeric', month:'long', day:'numeric'});

function logout() {
    localStorage.removeItem('user');
    window.location.href = 'login.html';
}

// Load stats
async function loadStats() {
    try {
        const BACKEND = 'backend/api';
        const [c, m, t] = await Promise.all([
            fetch(BACKEND + '/committees/index.php').then(r => r.json()),
            fetch(BACKEND + '/members/index.php').then(r => r.json()),
            fetch(BACKEND + '/tasks/index.php').then(r => r.json())
        ]);

        document.getElementById('totalCommittees').textContent = Array.isArray(c) ? c.length : 0;
        document.getElementById('totalMembers').textContent = Array.isArray(m) ? m.length : 0;
        document.getElementById('pendingTasks').textContent = Array.isArray(t) ? t.filter(x => x.status === 'pending').length : 0;
        document.getElementById('completedTasks').textContent = Array.isArray(t) ? t.filter(x => x.status === 'completed').length : 0;

        if (Array.isArray(c) && c.length > 0) {
            document.getElementById('recentCommittees').innerHTML = c.slice(0,5).map(x => `
                <div class="flex items-center justify-between py-2 border-b last:border-0">
                    <span class="text-sm font-medium text-gray-700">${x.name}</span>
                    <span class="text-xs px-2 py-1 rounded-full ${x.status === 'active' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-500'}">${x.status}</span>
                </div>`).join('');
        }

        if (Array.isArray(m) && m.length > 0) {
            document.getElementById('recentMembers').innerHTML = m.slice(0,5).map(x => `
                <div class="flex items-center justify-between py-2 border-b last:border-0">
                    <div class="flex items-center gap-2">
                        <div class="bg-blue-100 text-blue-600 rounded-full w-7 h-7 flex items-center justify-center text-xs font-bold">${x.full_name.charAt(0)}</div>
                        <span class="text-sm font-medium text-gray-700">${x.full_name}</span>
                    </div>
                    <span class="text-xs text-gray-500">${x.position || 'Member'}</span>
                </div>`).join('');
        }
    } catch(e) {
        console.log('Loading stats...');
    }
}
loadStats();
</script>
</body>
</html>