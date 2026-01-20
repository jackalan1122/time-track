<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Tracker - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                        Time Tracker
                    </h1>
                </div>
                <div class="flex gap-2">
                    <button onclick="showView('dashboard')" id="dashboardBtn" 
                        class="px-4 py-2 rounded-lg font-medium transition-all bg-indigo-100 text-indigo-700">
                        Dashboard
                    </button>
                    <button onclick="showView('tracker')" id="trackerBtn" 
                        class="px-4 py-2 rounded-lg font-medium transition-all text-gray-600 hover:bg-gray-100">
                        Tracker
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Dashboard View -->
        <div id="dashboardView">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-3xl font-bold text-gray-800">Analytics Dashboard</h2>
                <select id="periodSelector" onchange="loadDashboard()" 
                    class="bg-white border border-gray-300 rounded-lg px-4 py-2 font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="week">Last 7 Days</option>
                    <option value="month">Last 30 Days</option>
                    <option value="year">Last Year</option>
                </select>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-600 font-medium">Total Time</span>
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div id="totalTime" class="text-3xl font-bold text-gray-800">0:00:00</div>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-600 font-medium">Time Entries</span>
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                    <div id="entryCount" class="text-3xl font-bold text-gray-800">0</div>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-600 font-medium">Active Projects</span>
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div id="activeProjects" class="text-3xl font-bold text-gray-800">0</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Time by Project</h3>
                    <canvas id="projectChart"></canvas>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Daily Activity</h3>
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>

            <!-- Project Breakdown Table -->
            <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800">Project Breakdown</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Project</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time Spent</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Percentage</th>
                            </tr>
                        </thead>
                        <tbody id="projectBreakdownTable" class="divide-y divide-gray-200"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tracker View -->
        <div id="trackerView" class="hidden">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Projects Panel -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                                Projects
                            </h2>
                            <button onclick="showNewProjectForm()" 
                                class="p-2 hover:bg-indigo-50 rounded-lg transition-colors text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </div>

                        <div id="newProjectForm" class="hidden mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <input type="text" id="projectName" placeholder="Project name" 
                                class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <div class="flex gap-2 mb-3" id="colorPicker"></div>
                            <div class="flex gap-2">
                                <button onclick="addProject()" 
                                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Add Project
                                </button>
                                <button onclick="hideNewProjectForm()" 
                                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </div>

                        <div id="projectsList" class="space-y-3"></div>
                    </div>
                </div>

                <!-- Time Entries Panel -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-800">Time Entries</h2>
                            <select id="projectFilter" onchange="loadEntries()" 
                                class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">All Projects</option>
                            </select>
                        </div>

                        <div id="entriesList" class="space-y-3 max-h-[600px] overflow-y-auto"></div>

                        <div id="totalTimeTracker" class="mt-6 pt-4 border-t border-gray-200 hidden">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 font-medium">Total Time:</span>
                                <span id="totalTimeValueTracker" class="text-2xl font-bold text-gray-800"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6'];
        let selectedColor = colors[0];
        let projects = [];
        let entries = [];
        let activeTimer = null;
        let currentTime = 0;
        let timerInterval = null;
        let projectChart = null;
        let dailyChart = null;
        let currentView = 'dashboard';

        // View Management
        function showView(view) {
            currentView = view;
            document.getElementById('dashboardView').classList.toggle('hidden', view !== 'dashboard');
            document.getElementById('trackerView').classList.toggle('hidden', view !== 'tracker');
            
            document.getElementById('dashboardBtn').className = view === 'dashboard'
                ? 'px-4 py-2 rounded-lg font-medium transition-all bg-indigo-100 text-indigo-700'
                : 'px-4 py-2 rounded-lg font-medium transition-all text-gray-600 hover:bg-gray-100';
            
            document.getElementById('trackerBtn').className = view === 'tracker'
                ? 'px-4 py-2 rounded-lg font-medium transition-all bg-indigo-100 text-indigo-700'
                : 'px-4 py-2 rounded-lg font-medium transition-all text-gray-600 hover:bg-gray-100';
            
            if (view === 'dashboard') {
                loadDashboard();
            }
        }

        // Initialize color picker
        const colorPicker = document.getElementById('colorPicker');
        colors.forEach(color => {
            const btn = document.createElement('button');
            btn.className = 'w-8 h-8 rounded-lg transition-all';
            btn.style.backgroundColor = color;
            btn.onclick = () => selectColor(color);
            colorPicker.appendChild(btn);
        });
        selectColor(colors[0]);

        function selectColor(color) {
            selectedColor = color;
            document.querySelectorAll('#colorPicker button').forEach(btn => {
                if (btn.style.backgroundColor === color) {
                    btn.className = 'w-8 h-8 rounded-lg transition-all ring-2 ring-offset-2 ring-indigo-500';
                } else {
                    btn.className = 'w-8 h-8 rounded-lg transition-all opacity-60 hover:opacity-100';
                }
            });
        }

        function formatTime(seconds) {
            const hrs = Math.floor(seconds / 3600);
            const mins = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        function showNewProjectForm() {
            document.getElementById('newProjectForm').classList.remove('hidden');
        }

        function hideNewProjectForm() {
            document.getElementById('newProjectForm').classList.add('hidden');
            document.getElementById('projectName').value = '';
        }

        async function addProject() {
            const name = document.getElementById('projectName').value.trim();
            if (!name) return;

            const response = await fetch('api.php?action=add_project', {
                method: 'POST',
                body: JSON.stringify({ name, color: selectedColor })
            });
            
            if (response.ok) {
                hideNewProjectForm();
                loadProjects();
            }
        }

        async function deleteProject(id) {
            if (!confirm('Delete this project and all its entries?')) return;
            
            if (activeTimer && activeTimer.projectId == id) {
                stopTimer();
            }
            
            await fetch(`api.php?action=delete_project&id=${id}`);
            loadProjects();
            loadEntries();
            if (currentView === 'dashboard') loadDashboard();
        }

        function startTimer(projectId) {
            if (activeTimer) {
                stopTimer();
            }
            activeTimer = { projectId, startTime: Date.now() };
            currentTime = 0;
            
            timerInterval = setInterval(() => {
                currentTime++;
                updateTimerDisplay();
            }, 1000);
            
            renderProjects();
        }

        async function pauseTimer() {
            if (!activeTimer) return;

            clearInterval(timerInterval);
            
            const date = new Date().toISOString().slice(0, 19).replace('T', ' ');
            await fetch('api.php?action=add_entry', {
                method: 'POST',
                body: JSON.stringify({
                    project_id: activeTimer.projectId,
                    duration: currentTime,
                    date: date
                })
            });

            activeTimer = null;
            currentTime = 0;
            
            loadProjects();
            loadEntries();
            if (currentView === 'dashboard') loadDashboard();
        }

        function stopTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            if (activeTimer && currentTime > 0) {
                pauseTimer();
            } else {
                activeTimer = null;
                currentTime = 0;
                renderProjects();
            }
        }

        function updateTimerDisplay() {
            if (activeTimer) {
                const timerEl = document.getElementById(`timer-${activeTimer.projectId}`);
                if (timerEl) {
                    timerEl.textContent = formatTime(currentTime);
                }
            }
        }

        async function deleteEntry(id) {
            await fetch(`api.php?action=delete_entry&id=${id}`);
            loadProjects();
            loadEntries();
            if (currentView === 'dashboard') loadDashboard();
        }

        async function loadProjects() {
            const response = await fetch('api.php?action=get_projects');
            projects = await response.json();
            renderProjects();
            updateProjectFilter();
        }

        function renderProjects() {
            const list = document.getElementById('projectsList');
            
            if (projects.length === 0) {
                list.innerHTML = '<p class="text-gray-400 text-sm text-center py-8">No projects yet. Add one to start tracking!</p>';
                return;
            }

            list.innerHTML = projects.map(project => {
                const isActive = activeTimer && activeTimer.projectId == project.id;
                const displayTime = isActive ? currentTime : parseInt(project.total_time);
                
                return `
                    <div class="p-4 rounded-lg border transition-all ${
                        isActive 
                            ? 'bg-indigo-50 border-indigo-300'
                            : 'bg-gray-50 border-gray-200 hover:bg-gray-100'
                    }">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2 flex-1">
                                <div class="w-3 h-3 rounded-full" style="background-color: ${project.color}"></div>
                                <span class="font-semibold text-gray-800">${project.name}</span>
                            </div>
                            <button onclick="deleteProject(${project.id})" 
                                class="p-1.5 hover:bg-red-50 rounded-lg transition-colors text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                        <div id="timer-${project.id}" class="text-2xl font-bold mb-3 text-gray-800">
                            ${formatTime(displayTime)}
                        </div>
                        ${isActive ? `
                            <div class="flex gap-2">
                                <button onclick="pauseTimer()" 
                                    class="flex-1 bg-amber-500 hover:bg-amber-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Pause
                                </button>
                                <button onclick="stopTimer()" 
                                    class="flex-1 bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Stop
                                </button>
                            </div>
                        ` : `
                            <button onclick="startTimer(${project.id})" 
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                Start Timer
                            </button>
                        `}
                    </div>
                `;
            }).join('');
        }

        function updateProjectFilter() {
            const filter = document.getElementById('projectFilter');
            const currentValue = filter.value;
            filter.innerHTML = '<option value="">All Projects</option>' +
                projects.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
            filter.value = currentValue;
        }

        async function loadEntries() {
            const projectId = document.getElementById('projectFilter').value;
            const url = projectId 
                ? `api.php?action=get_entries&project_id=${projectId}`
                : 'api.php?action=get_entries';
            
            const response = await fetch(url);
            entries = await response.json();
            renderEntries();
        }

        function renderEntries() {
            const list = document.getElementById('entriesList');
            
            if (entries.length === 0) {
                list.innerHTML = '<p class="text-gray-400 text-sm text-center py-12">No time entries yet. Start a timer to begin tracking!</p>';
                document.getElementById('totalTimeTracker').classList.add('hidden');
                return;
            }

            list.innerHTML = entries.map(entry => {
                const project = projects.find(p => p.id == entry.project_id);
                if (!project) return '';
                
                const date = new Date(entry.date);
                return `
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full" style="background-color: ${project.color}"></div>
                            <div>
                                <div class="font-semibold text-gray-800">${project.name}</div>
                                <div class="text-sm text-gray-500">${date.toLocaleString()}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-xl font-bold text-gray-800">
                                ${formatTime(parseInt(entry.duration))}
                            </div>
                            <button onclick="deleteEntry(${entry.id})" 
                                class="p-2 hover:bg-red-50 rounded-lg transition-colors text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            const total = entries.reduce((sum, e) => sum + parseInt(e.duration), 0);
            document.getElementById('totalTimeValueTracker').textContent = formatTime(total);
            document.getElementById('totalTimeTracker').classList.remove('hidden');
        }

        async function loadDashboard() {
            const period = document.getElementById('periodSelector').value;
            const response = await fetch(`api.php?action=get_dashboard_stats&period=${period}`);
            const data = await response.json();

            // Update stats
            document.getElementById('totalTime').textContent = formatTime(parseInt(data.totalTime || 0));
            document.getElementById('entryCount').textContent = data.entryCount || 0;
            document.getElementById('activeProjects').textContent = data.activeProjects || 0;

            // Update project breakdown table
            const tableBody = document.getElementById('projectBreakdownTable');
            const totalTime = parseInt(data.totalTime || 1);
            
            if (data.projectBreakdown && data.projectBreakdown.length > 0) {
                tableBody.innerHTML = data.projectBreakdown.map(project => {
                    const time = parseInt(project.time || 0);
                    const percentage = totalTime > 0 ? ((time / totalTime) * 100).toFixed(1) : 0;
                    return `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full" style="background-color: ${project.color}"></div>
                                    <span class="font-medium text-gray-800">${project.name}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-800">${formatTime(time)}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full" style="width: ${percentage}%; background-color: ${project.color}"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-600">${percentage}%</span>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                tableBody.innerHTML = '<tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">No data available for this period</td></tr>';
            }

            // Update pie chart
            if (projectChart) {
                projectChart.destroy();
            }

            if (data.projectBreakdown && data.projectBreakdown.length > 0) {
                const ctx = document.getElementById('projectChart').getContext('2d');
                projectChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.projectBreakdown.map(p => p.name),
                        datasets: [{
                            data: data.projectBreakdown.map(p => parseInt(p.time) / 3600),
                            backgroundColor: data.projectBreakdown.map(p => p.color),
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    font: { size: 12 },
                                    color: '#374151'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed.toFixed(1) + ' hours';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Update daily chart
            if (dailyChart) {
                dailyChart.destroy();
            }

            if (data.dailyBreakdown && data.dailyBreakdown.length > 0) {
                const ctx = document.getElementById('dailyChart').getContext('2d');
                dailyChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.dailyBreakdown.map(d => {
                            const date = new Date(d.day);
                            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        }),
                        datasets: [{
                            label: 'Hours',
                            data: data.dailyBreakdown.map(d => parseInt(d.time) / 3600),
                            backgroundColor: '#6366f1',
                            borderRadius: 6,
                            barThickness: 30
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#f3f4f6' },
                                ticks: {
                                    callback: function(value) {
                                        return value.toFixed(1) + 'h';
                                    },
                                    color: '#6b7280'
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#6b7280' }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y.toFixed(1) + ' hours';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        // Initialize
        loadProjects();
        loadDashboard();
    </script>
</body>
</html>