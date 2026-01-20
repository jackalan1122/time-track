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
                    <option value="day">Today</option>
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

                        <!-- Manual Entry Form -->
                        <div class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                            <h3 class="text-sm font-bold text-indigo-900 mb-3">Add Manual Entry</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <select id="manualProjectId" 
                                    class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select Project</option>
                                </select>
                                <div class="flex gap-2">
                                    <input type="number" id="manualHours" placeholder="Hours" min="0" max="24"
                                        class="flex-1 bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <input type="number" id="manualMinutes" placeholder="Minutes" min="0" max="59"
                                        class="flex-1 bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <input type="number" id="manualSeconds" placeholder="Seconds" min="0" max="59"
                                        class="flex-1 bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <input type="date" id="manualDate" 
                                    class="flex-1 bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <button onclick="addManualEntry()" 
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors">
                                    Add Entry
                                </button>
                            </div>
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

                <!-- Edit Entry Modal -->
                <div id="editEntryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-xl p-6 shadow-lg max-w-sm w-full mx-4">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Time Entry</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hours</label>
                                <input type="number" id="editHours" min="0" max="24"
                                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Minutes</label>
                                <input type="number" id="editMinutes" min="0" max="59"
                                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Seconds</label>
                                <input type="number" id="editSeconds" min="0" max="59"
                                    class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div class="flex gap-2 pt-4">
                                <button onclick="saveEditEntry()" 
                                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                    Save
                                </button>
                                <button onclick="closeEditModal()" 
                                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-medium transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        /**
         * Time Tracker Application
         * Modularized JavaScript with proper error handling and persistence
         */

        // Configuration
        const CONFIG = {
            COLORS: ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6'],
            STORAGE_KEY_TIMER: 'timeTrackerActiveTimer',
            API_BASE: window.location.pathname.replace('index.php', '') + 'api.php'
        };

        // Application State
        const AppState = {
            selectedColor: CONFIG.COLORS[0],
            projects: [],
            entries: [],
            activeTimer: null,
            currentTime: 0,
            timerInterval: null,
            projectChart: null,
            dailyChart: null,
            currentView: 'dashboard',
            isLoading: false,

            loadFromStorage() {
                const stored = localStorage.getItem(CONFIG.STORAGE_KEY_TIMER);
                if (stored) {
                    try {
                        const data = JSON.parse(stored);
                        if (data.projectId && data.startTime) {
                            this.activeTimer = data;
                            this.currentTime = Math.floor((Date.now() - data.startTime) / 1000);
                            this.startTimerLoop();
                        }
                    } catch (e) {
                        console.error('Failed to restore timer from storage:', e);
                        localStorage.removeItem(CONFIG.STORAGE_KEY_TIMER);
                    }
                }
            },

            saveToStorage() {
                if (this.activeTimer) {
                    localStorage.setItem(CONFIG.STORAGE_KEY_TIMER, JSON.stringify(this.activeTimer));
                } else {
                    localStorage.removeItem(CONFIG.STORAGE_KEY_TIMER);
                }
            }
        };

        // API Service
        const APIService = {
            async call(action, method = 'GET', body = null, params = {}) {
                try {
                    let url = CONFIG.API_BASE + '?action=' + encodeURIComponent(action);
                    Object.entries(params).forEach(([key, value]) => {
                        if (value !== null) {
                            url += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(value);
                        }
                    });

                    const options = {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    };

                    if (body) {
                        options.body = JSON.stringify(body);
                    }

                    const response = await fetch(url, options);
                    
                    // Check if response is valid JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('API returned invalid response (expected JSON, got ' + contentType + ')');
                    }
                    
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || `HTTP ${response.status}`);
                    }

                    return data;
                } catch (error) {
                    console.error(`API Error (${action}):`, error);
                    console.error('URL was:', CONFIG.API_BASE + '?action=' + action);
                    UIHelpers.showError(error.message);
                    throw error;
                }
            },

            getProjects: () => APIService.call('get_projects'),
            addProject: (name, color) => APIService.call('add_project', 'POST', { name, color }),
            deleteProject: (id) => APIService.call('delete_project', 'POST', null, { id }),
            getEntries: (projectId = null) => APIService.call('get_entries', 'GET', null, { project_id: projectId }),
            addEntry: (projectId, duration, date) => APIService.call('add_entry', 'POST', { project_id: projectId, duration, date }),
            deleteEntry: (id) => APIService.call('delete_entry', 'POST', null, { id }),
            getDashboardStats: (period) => APIService.call('get_dashboard_stats', 'GET', null, { period })
        };

        // UI Helpers
        const UIHelpers = {
            formatTime(seconds) {
                const hrs = Math.floor(seconds / 3600);
                const mins = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;
                return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            },

            showError(message) {
                console.error(message);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg max-w-sm z-50';
                errorDiv.textContent = message;
                document.body.appendChild(errorDiv);
                setTimeout(() => errorDiv.remove(), 5000);
            },

            showSuccess(message) {
                const successDiv = document.createElement('div');
                successDiv.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-lg max-w-sm z-50';
                successDiv.textContent = message;
                document.body.appendChild(successDiv);
                setTimeout(() => successDiv.remove(), 3000);
            },

            setLoading(isLoading) {
                AppState.isLoading = isLoading;
                document.querySelectorAll('button').forEach(btn => {
                    btn.disabled = isLoading;
                });
            }
        };

        // View Management
        function showView(view) {
            AppState.currentView = view;
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

        // Color Picker Initialization
        function initializeColorPicker() {
            const colorPicker = document.getElementById('colorPicker');
            CONFIG.COLORS.forEach(color => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-8 h-8 rounded-lg transition-all';
                btn.style.backgroundColor = color;
                btn.setAttribute('aria-label', `Select color ${color}`);
                btn.onclick = (e) => {
                    e.preventDefault();
                    selectColor(color);
                };
                colorPicker.appendChild(btn);
            });
            selectColor(CONFIG.COLORS[0]);
        }

        function selectColor(color) {
            AppState.selectedColor = color;
            document.querySelectorAll('#colorPicker button').forEach(btn => {
                if (btn.style.backgroundColor === color) {
                    btn.className = 'w-8 h-8 rounded-lg transition-all ring-2 ring-offset-2 ring-indigo-500';
                } else {
                    btn.className = 'w-8 h-8 rounded-lg transition-all opacity-60 hover:opacity-100';
                }
            });
        }

        // Project Form Functions
        function showNewProjectForm() {
            document.getElementById('newProjectForm').classList.remove('hidden');
            document.getElementById('projectName').focus();
        }

        function hideNewProjectForm() {
            document.getElementById('newProjectForm').classList.add('hidden');
            document.getElementById('projectName').value = '';
        }

        async function addProject() {
            const name = document.getElementById('projectName').value.trim();
            if (!name) {
                UIHelpers.showError('Project name is required');
                return;
            }

            UIHelpers.setLoading(true);
            try {
                await APIService.addProject(name, AppState.selectedColor);
                hideNewProjectForm();
                await loadProjects();
                UIHelpers.showSuccess('Project created successfully');
            } finally {
                UIHelpers.setLoading(false);
            }
        }

        async function deleteProject(id) {
            if (!confirm('Delete this project and all its entries? This cannot be undone.')) return;
            
            if (AppState.activeTimer && AppState.activeTimer.projectId === id) {
                stopTimer();
            }
            
            UIHelpers.setLoading(true);
            try {
                await APIService.deleteProject(id);
                await loadProjects();
                await loadEntries();
                if (AppState.currentView === 'dashboard') await loadDashboard();
                UIHelpers.showSuccess('Project deleted');
            } finally {
                UIHelpers.setLoading(false);
            }
        }

        // Timer Functions
        function startTimer(projectId) {
            if (AppState.activeTimer) {
                stopTimer();
            }
            AppState.activeTimer = { projectId, startTime: Date.now() };
            AppState.currentTime = 0;
            AppState.saveToStorage();
            AppState.startTimerLoop();
            renderProjects();
        }

        AppState.startTimerLoop = function() {
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.timerInterval = setInterval(() => {
                this.currentTime++;
                updateTimerDisplay();
            }, 1000);
        };

        async function pauseTimer() {
            if (!AppState.activeTimer) return;

            clearInterval(AppState.timerInterval);
            AppState.timerInterval = null;
            
            const date = new Date().toISOString().slice(0, 19).replace('T', ' ');
            
            UIHelpers.setLoading(true);
            try {
                await APIService.addEntry(AppState.activeTimer.projectId, AppState.currentTime, date);
                AppState.activeTimer = null;
                AppState.currentTime = 0;
                AppState.saveToStorage();
                
                await loadProjects();
                await loadEntries();
                if (AppState.currentView === 'dashboard') await loadDashboard();
                UIHelpers.showSuccess('Time entry saved');
            } finally {
                UIHelpers.setLoading(false);
            }
        }

        function stopTimer() {
            if (AppState.timerInterval) {
                clearInterval(AppState.timerInterval);
                AppState.timerInterval = null;
            }
            if (AppState.activeTimer && AppState.currentTime > 0) {
                pauseTimer();
            } else {
                AppState.activeTimer = null;
                AppState.currentTime = 0;
                AppState.saveToStorage();
                renderProjects();
            }
        }

        function updateTimerDisplay() {
            if (AppState.activeTimer) {
                const timerEl = document.getElementById(`timer-${AppState.activeTimer.projectId}`);
                if (timerEl) {
                    timerEl.textContent = UIHelpers.formatTime(AppState.currentTime);
                }
            }
        }

        // Entry Functions
        async function deleteEntry(id) {
            if (!confirm('Delete this time entry?')) return;
            
            UIHelpers.setLoading(true);
            try {
                await APIService.deleteEntry(id);
                await loadProjects();
                await loadEntries();
                if (AppState.currentView === 'dashboard') await loadDashboard();
                UIHelpers.showSuccess('Entry deleted');
            } finally {
                UIHelpers.setLoading(false);
            }
        }

        async function loadProjects() {
            try {
                AppState.projects = await APIService.getProjects();
                renderProjects();
                updateProjectFilter();
                updateManualProjectFilter();
            } catch (error) {
                console.error('Failed to load projects:', error);
            }
        }

        function renderProjects() {
            const list = document.getElementById('projectsList');
            
            if (AppState.projects.length === 0) {
                list.innerHTML = '<p class="text-gray-400 text-sm text-center py-8">No projects yet. Add one to start tracking!</p>';
                return;
            }

            list.innerHTML = AppState.projects.map(project => {
                const isActive = AppState.activeTimer && AppState.activeTimer.projectId === project.id;
                const displayTime = isActive ? AppState.currentTime : parseInt(project.total_time);
                
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
                                aria-label="Delete project"
                                class="p-1.5 hover:bg-red-50 rounded-lg transition-colors text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                        <div id="timer-${project.id}" class="text-2xl font-bold mb-3 text-gray-800">
                            ${UIHelpers.formatTime(displayTime)}
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
                AppState.projects.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
            filter.value = currentValue;
        }

        async function loadEntries() {
            try {
                const projectId = document.getElementById('projectFilter').value;
                AppState.entries = await APIService.getEntries(projectId || null);
                renderEntries();
            } catch (error) {
                console.error('Failed to load entries:', error);
            }
        }

        function renderEntries() {
            const list = document.getElementById('entriesList');
            
            if (AppState.entries.length === 0) {
                list.innerHTML = '<p class="text-gray-400 text-sm text-center py-12">No time entries yet. Start a timer to begin tracking!</p>';
                document.getElementById('totalTimeTracker').classList.add('hidden');
                return;
            }

            list.innerHTML = AppState.entries.map(entry => {
                const project = AppState.projects.find(p => p.id == entry.project_id);
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
                        <div class="flex items-center gap-3">
                            <div class="text-xl font-bold text-gray-800">
                                ${UIHelpers.formatTime(parseInt(entry.duration))}
                            </div>
                            <button onclick="openEditModal(${entry.id}, ${entry.duration})" 
                                aria-label="Edit entry"
                                class="p-2 hover:bg-blue-50 rounded-lg transition-colors text-blue-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button onclick="deleteEntry(${entry.id})" 
                                aria-label="Delete entry"
                                class="p-2 hover:bg-red-50 rounded-lg transition-colors text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            const total = AppState.entries.reduce((sum, e) => sum + parseInt(e.duration), 0);
            document.getElementById('totalTimeValueTracker').textContent = UIHelpers.formatTime(total);
            document.getElementById('totalTimeTracker').classList.remove('hidden');
        }

        // Manual Entry Functions
        let editingEntryId = null;

        async function addManualEntry() {
            const projectId = document.getElementById('manualProjectId').value;
            const hours = parseInt(document.getElementById('manualHours').value) || 0;
            const minutes = parseInt(document.getElementById('manualMinutes').value) || 0;
            const seconds = parseInt(document.getElementById('manualSeconds').value) || 0;
            const date = document.getElementById('manualDate').value;

            if (!projectId) {
                UIHelpers.showError('Please select a project');
                return;
            }

            if (hours === 0 && minutes === 0 && seconds === 0) {
                UIHelpers.showError('Please enter a time duration');
                return;
            }

            const duration = hours * 3600 + minutes * 60 + seconds;
            const entryDate = date ? new Date(date).toISOString().slice(0, 19).replace('T', ' ') : new Date().toISOString().slice(0, 19).replace('T', ' ');

            UIHelpers.setLoading(true);
            try {
                await APIService.addEntry(parseInt(projectId), duration, entryDate);
                
                // Clear form
                document.getElementById('manualProjectId').value = '';
                document.getElementById('manualHours').value = '';
                document.getElementById('manualMinutes').value = '';
                document.getElementById('manualSeconds').value = '';
                document.getElementById('manualDate').value = new Date().toISOString().split('T')[0];

                await loadProjects();
                await loadEntries();
                if (AppState.currentView === 'dashboard') await loadDashboard();
                UIHelpers.showSuccess('Time entry added successfully');
            } finally {
                UIHelpers.setLoading(false);
            }
        }

        function updateManualProjectFilter() {
            const select = document.getElementById('manualProjectId');
            const currentValue = select.value;
            select.innerHTML = '<option value="">Select Project</option>' +
                AppState.projects.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
            select.value = currentValue;
        }

        function openEditModal(entryId, duration) {
            editingEntryId = entryId;
            
            const hours = Math.floor(duration / 3600);
            const minutes = Math.floor((duration % 3600) / 60);
            const seconds = duration % 60;

            document.getElementById('editHours').value = hours;
            document.getElementById('editMinutes').value = minutes;
            document.getElementById('editSeconds').value = seconds;
            document.getElementById('editEntryModal').classList.remove('hidden');
        }

        function closeEditModal() {
            editingEntryId = null;
            document.getElementById('editEntryModal').classList.add('hidden');
        }

        async function saveEditEntry() {
            if (editingEntryId === null) return;

            const hours = parseInt(document.getElementById('editHours').value) || 0;
            const minutes = parseInt(document.getElementById('editMinutes').value) || 0;
            const seconds = parseInt(document.getElementById('editSeconds').value) || 0;
            const newDuration = hours * 3600 + minutes * 60 + seconds;

            if (newDuration === 0) {
                UIHelpers.showError('Duration cannot be 0');
                return;
            }

            UIHelpers.setLoading(true);
            try {
                // Delete old entry and add new one with updated duration
                const entry = AppState.entries.find(e => e.id == editingEntryId);
                if (!entry) {
                    UIHelpers.showError('Entry not found');
                    return;
                }

                await APIService.deleteEntry(editingEntryId);
                await APIService.addEntry(entry.project_id, newDuration, entry.date);

                await loadProjects();
                await loadEntries();
                if (AppState.currentView === 'dashboard') await loadDashboard();
                closeEditModal();
                UIHelpers.showSuccess('Entry updated successfully');
            } finally {
                UIHelpers.setLoading(false);
            }
        }

        // Dashboard Functions
        async function loadDashboard() {
            const period = document.getElementById('periodSelector').value;
            
            UIHelpers.setLoading(true);
            try {
                const data = await APIService.getDashboardStats(period);
                
                document.getElementById('totalTime').textContent = UIHelpers.formatTime(parseInt(data.totalTime || 0));
                document.getElementById('entryCount').textContent = data.entryCount || 0;
                document.getElementById('activeProjects').textContent = data.activeProjects || 0;

                renderProjectBreakdownTable(data);
                renderProjectChart(data);
                renderDailyChart(data);
            } catch (error) {
                console.error('Failed to load dashboard:', error);
            } finally {
                UIHelpers.setLoading(false);
            }
        }

        function renderProjectBreakdownTable(data) {
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
                            <td class="px-6 py-4 font-semibold text-gray-800">${UIHelpers.formatTime(time)}</td>
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
        }

        function renderProjectChart(data) {
            if (AppState.projectChart) {
                AppState.projectChart.destroy();
            }

            if (data.projectBreakdown && data.projectBreakdown.length > 0) {
                const ctx = document.getElementById('projectChart').getContext('2d');
                AppState.projectChart = new Chart(ctx, {
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
        }

        function renderDailyChart(data) {
            if (AppState.dailyChart) {
                AppState.dailyChart.destroy();
            }

            if (data.dailyBreakdown && data.dailyBreakdown.length > 0) {
                const ctx = document.getElementById('dailyChart').getContext('2d');
                AppState.dailyChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.dailyBreakdown.map(d => {
                            const date = new Date(d.day);
                            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        }),
                        datasets: [{
                            label: 'Hours',
                            data: data.dailyBreakdown.map(d => (parseInt(d.time) / 3600).toFixed(1)),
                            backgroundColor: '#6366f1',
                            borderRadius: 6,
                            barThickness: 30
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        layout: {
                            padding: {
                                top: 30
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#f3f4f6' },
                                ticks: {
                                    callback: function(value) {
                                        return parseFloat(value).toFixed(1) + 'h';
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
                                        return parseFloat(context.parsed.y).toFixed(1) + ' hours';
                                    }
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'end'
                            }
                        }
                    },
                    plugins: [{
                        id: 'chartAreaPlugin',
                        afterDatasetsDraw(chart) {
                            const { ctx, scales: { x, y } } = chart;
                            chart.data.datasets.forEach((dataset, i) => {
                                const meta = chart.getDatasetMeta(i);
                                meta.data.forEach((bar, index) => {
                                    const value = dataset.data[index];
                                    if (value !== null && value !== undefined) {
                                        const text = parseFloat(value).toFixed(1) + 'h';
                                        ctx.fillStyle = '#1f2937';
                                        ctx.font = 'bold 11px Arial';
                                        ctx.textAlign = 'center';
                                        ctx.textBaseline = 'bottom';
                                        ctx.fillText(text, bar.x, bar.y - 8);
                                    }
                                });
                            });
                        }
                    }]
                });
            }
        }

        // Application Initialization
        document.addEventListener('DOMContentLoaded', () => {
            initializeColorPicker();
            AppState.loadFromStorage();
            
            // Set today's date as default for manual entry
            document.getElementById('manualDate').valueAsDate = new Date();
            
            loadProjects();
            loadDashboard();
            loadEntries();

            // Restore timer on page visibility change
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && AppState.activeTimer) {
                    AppState.loadFromStorage();
                }
            });

            // Save timer state before unload
            window.addEventListener('beforeunload', () => {
                AppState.saveToStorage();
            });

            // Close modal on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeEditModal();
                }
            });
        });
    </script>
</body>
</html>