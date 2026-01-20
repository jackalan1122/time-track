<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

// Validate request method and content type for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') === false) {
        sendJSON(['error' => 'Content-Type must be application/json'], 400);
    }
}

$conn = getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_projects':
            $result = $conn->query("SELECT id, name, color, total_time, created_at FROM projects ORDER BY created_at DESC");
            if (!$result) {
                throw new Exception("Database query failed: " . $conn->error);
            }
            $projects = $result->fetch_all(MYSQLI_ASSOC);
            logAction('get_projects', ['count' => count($projects)]);
            sendJSON($projects);
            break;

        case 'add_project':
            if ($method !== 'POST') {
                sendJSON(['error' => 'POST method required'], 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                sendJSON(['error' => 'Invalid JSON input'], 400);
            }
            
            $name = $data['name'] ?? '';
            $color = $data['color'] ?? '';
            
            // Validation
            if (empty($name) || strlen($name) < 1 || strlen($name) > 100) {
                sendJSON(['error' => 'Project name must be between 1 and 100 characters'], 400);
            }
            if (!isValidColor($color)) {
                sendJSON(['error' => 'Invalid color format'], 400);
            }
            
            $name = sanitizeInput($name);
            
            $stmt = $conn->prepare("INSERT INTO projects (name, color, total_time) VALUES (?, ?, 0)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("ss", $name, $color);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            logAction('add_project', ['name' => $name, 'id' => $conn->insert_id]);
            sendJSON(['id' => $conn->insert_id, 'success' => true], 201);
            break;

        case 'delete_project':
            if ($method !== 'POST' && $method !== 'DELETE') {
                sendJSON(['error' => 'POST or DELETE method required'], 405);
            }
            
            $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id <= 0) {
                sendJSON(['error' => 'Invalid project ID'], 400);
            }
            
            // First delete all entries for this project
            $stmt = $conn->prepare("DELETE FROM time_entries WHERE project_id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Delete entries failed: " . $stmt->error);
            }
            
            // Then delete the project
            $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Delete project failed: " . $stmt->error);
            }
            
            logAction('delete_project', ['id' => $id]);
            sendJSON(['success' => true]);
            break;

        case 'get_entries':
            $project_id = filter_var($_GET['project_id'] ?? null, FILTER_VALIDATE_INT);
            $limit = filter_var($_GET['limit'] ?? 100, FILTER_VALIDATE_INT);
            $offset = filter_var($_GET['offset'] ?? 0, FILTER_VALIDATE_INT);
            
            $limit = min($limit, 500);
            $limit = max($limit, 1);
            
            if ($project_id !== null && $project_id > 0) {
                $stmt = $conn->prepare("SELECT * FROM time_entries WHERE project_id = ? ORDER BY date DESC LIMIT ? OFFSET ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("iii", $project_id, $limit, $offset);
            } else {
                $stmt = $conn->prepare("SELECT * FROM time_entries ORDER BY date DESC LIMIT ? OFFSET ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("ii", $limit, $offset);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $entries = $result->fetch_all(MYSQLI_ASSOC);
            
            logAction('get_entries', ['project_id' => $project_id, 'count' => count($entries)]);
            sendJSON($entries);
            break;

        case 'add_entry':
            if ($method !== 'POST') {
                sendJSON(['error' => 'POST method required'], 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                sendJSON(['error' => 'Invalid JSON input'], 400);
            }
            
            $project_id = filter_var($data['project_id'] ?? 0, FILTER_VALIDATE_INT);
            $duration = filter_var($data['duration'] ?? 0, FILTER_VALIDATE_INT);
            $date = $data['date'] ?? '';
            
            // Validation
            if ($project_id <= 0) {
                sendJSON(['error' => 'Invalid project ID'], 400);
            }
            if ($duration < 0 || $duration > 86400) {
                sendJSON(['error' => 'Duration must be between 0 and 86400 seconds'], 400);
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date)) {
                sendJSON(['error' => 'Invalid date format (YYYY-MM-DD HH:MM:SS)'], 400);
            }
            
            // Verify project exists
            $stmt = $conn->prepare("SELECT id FROM projects WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                sendJSON(['error' => 'Project not found'], 404);
            }
            
            $stmt = $conn->prepare("INSERT INTO time_entries (project_id, duration, date) VALUES (?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("iis", $project_id, $duration, $date);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            // Update project total time
            $stmt = $conn->prepare("UPDATE projects SET total_time = total_time + ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ii", $duration, $project_id);
            if (!$stmt->execute()) {
                throw new Exception("Update failed: " . $stmt->error);
            }
            
            logAction('add_entry', ['project_id' => $project_id, 'duration' => $duration]);
            sendJSON(['id' => $conn->insert_id, 'success' => true], 201);
            break;

        case 'delete_entry':
            if ($method !== 'POST' && $method !== 'DELETE') {
                sendJSON(['error' => 'POST or DELETE method required'], 405);
            }
            
            $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id <= 0) {
                sendJSON(['error' => 'Invalid entry ID'], 400);
            }
            
            // Get entry details
            $stmt = $conn->prepare("SELECT project_id, duration FROM time_entries WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $entry = $result->fetch_assoc();
            
            if (!$entry) {
                sendJSON(['error' => 'Entry not found'], 404);
            }
            
            // Update project total time
            $stmt = $conn->prepare("UPDATE projects SET total_time = GREATEST(0, total_time - ?) WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ii", $entry['duration'], $entry['project_id']);
            if (!$stmt->execute()) {
                throw new Exception("Update failed: " . $stmt->error);
            }
            
            // Delete entry
            $stmt = $conn->prepare("DELETE FROM time_entries WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Delete failed: " . $stmt->error);
            }
            
            logAction('delete_entry', ['id' => $id, 'project_id' => $entry['project_id']]);
            sendJSON(['success' => true]);
            break;

        case 'get_dashboard_stats':
            $period = $_GET['period'] ?? 'week';
            
            // Validate period
            if (!in_array($period, ['day', 'week', 'month', 'year'], true)) {
                sendJSON(['error' => 'Invalid period'], 400);
            }
            
            // Calculate date range
            $endDate = date('Y-m-d 23:59:59');
            switch ($period) {
                case 'day':
                    $startDate = date('Y-m-d 00:00:00');
                    break;
                case 'week':
                    $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
                    break;
                case 'month':
                    $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
                    break;
                case 'year':
                    $startDate = date('Y-m-d 00:00:00', strtotime('-365 days'));
                    break;
            }
            
            // Get total time in period
            $stmt = $conn->prepare("SELECT COALESCE(SUM(duration), 0) as total FROM time_entries WHERE date BETWEEN ? AND ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $totalTime = (int)$stmt->get_result()->fetch_assoc()['total'];
            
            // Get project breakdown
            $stmt = $conn->prepare("
                SELECT p.id, p.name, p.color, COALESCE(SUM(e.duration), 0) as time 
                FROM projects p 
                LEFT JOIN time_entries e ON p.id = e.project_id AND e.date BETWEEN ? AND ?
                GROUP BY p.id, p.name, p.color
                HAVING time > 0
                ORDER BY time DESC
            ");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $projectBreakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get daily breakdown for chart
            $stmt = $conn->prepare("
                SELECT DATE(date) as day, COALESCE(SUM(duration), 0) as time 
                FROM time_entries 
                WHERE date BETWEEN ? AND ?
                GROUP BY DATE(date) 
                ORDER BY day ASC
            ");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $dailyBreakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get entry count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM time_entries WHERE date BETWEEN ? AND ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $entryCount = (int)$stmt->get_result()->fetch_assoc()['count'];
            
            // Get active projects count
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT project_id) as count FROM time_entries WHERE date BETWEEN ? AND ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $activeProjects = (int)$stmt->get_result()->fetch_assoc()['count'];
            
            logAction('get_dashboard_stats', ['period' => $period]);
            sendJSON([
                'totalTime' => $totalTime,
                'projectBreakdown' => $projectBreakdown,
                'dailyBreakdown' => $dailyBreakdown,
                'entryCount' => $entryCount,
                'activeProjects' => $activeProjects
            ]);
            break;

        case 'get_tasks':
            $project_id = filter_var($_GET['project_id'] ?? 0, FILTER_VALIDATE_INT);
            
            if ($project_id <= 0) {
                sendJSON(['error' => 'Invalid project ID'], 400);
            }
            
            $stmt = $conn->prepare("SELECT id, project_id, title, description, is_completed, created_at FROM tasks WHERE project_id = ? ORDER BY created_at DESC");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $project_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            logAction('get_tasks', ['project_id' => $project_id, 'count' => count($tasks)]);
            sendJSON($tasks);
            break;

        case 'add_task':
            if ($method !== 'POST') {
                sendJSON(['error' => 'POST method required'], 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                sendJSON(['error' => 'Invalid JSON input'], 400);
            }
            
            $project_id = filter_var($data['project_id'] ?? 0, FILTER_VALIDATE_INT);
            $title = $data['title'] ?? '';
            $description = $data['description'] ?? '';
            
            // Validation
            if ($project_id <= 0) {
                sendJSON(['error' => 'Invalid project ID'], 400);
            }
            if (empty($title) || strlen($title) < 1 || strlen($title) > 255) {
                sendJSON(['error' => 'Task title must be between 1 and 255 characters'], 400);
            }
            
            // Verify project exists
            $stmt = $conn->prepare("SELECT id FROM projects WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                sendJSON(['error' => 'Project not found'], 404);
            }
            
            $title = sanitizeInput($title);
            $description = sanitizeInput($description);
            
            $stmt = $conn->prepare("INSERT INTO tasks (project_id, title, description) VALUES (?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("iss", $project_id, $title, $description);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            logAction('add_task', ['project_id' => $project_id, 'title' => $title]);
            sendJSON(['id' => $conn->insert_id, 'success' => true], 201);
            break;

        case 'update_task':
            if ($method !== 'POST') {
                sendJSON(['error' => 'POST method required'], 405);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                sendJSON(['error' => 'Invalid JSON input'], 400);
            }
            
            $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
            $title = $data['title'] ?? '';
            $description = $data['description'] ?? '';
            $is_completed = filter_var($data['is_completed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            // Validation
            if ($id <= 0) {
                sendJSON(['error' => 'Invalid task ID'], 400);
            }
            if (empty($title) || strlen($title) > 255) {
                sendJSON(['error' => 'Task title must be between 1 and 255 characters'], 400);
            }
            
            $title = sanitizeInput($title);
            $description = sanitizeInput($description);
            
            $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, is_completed = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ssii", $title, $description, $is_completed, $id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            if ($stmt->affected_rows === 0) {
                sendJSON(['error' => 'Task not found'], 404);
            }
            
            logAction('update_task', ['id' => $id, 'is_completed' => $is_completed]);
            sendJSON(['success' => true]);
            break;

        case 'delete_task':
            if ($method !== 'POST' && $method !== 'DELETE') {
                sendJSON(['error' => 'POST or DELETE method required'], 405);
            }
            
            $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id <= 0) {
                sendJSON(['error' => 'Invalid task ID'], 400);
            }
            
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Delete failed: " . $stmt->error);
            }
            
            if ($stmt->affected_rows === 0) {
                sendJSON(['error' => 'Task not found'], 404);
            }
            
            logAction('delete_task', ['id' => $id]);
            sendJSON(['success' => true]);
            break;

        case 'export_report':
            $period = $_GET['period'] ?? 'week';
            $format = $_GET['format'] ?? 'csv';
            
            // Validate period
            if (!in_array($period, ['day', 'week', 'month', 'year'], true)) {
                sendJSON(['error' => 'Invalid period'], 400);
            }
            
            // Validate format
            if (!in_array($format, ['csv', 'json', 'pdf'], true)) {
                sendJSON(['error' => 'Invalid format'], 400);
            }
            
            // Calculate date range
            $endDate = date('Y-m-d 23:59:59');
            switch ($period) {
                case 'day':
                    $startDate = date('Y-m-d 00:00:00');
                    $periodLabel = 'Today';
                    break;
                case 'week':
                    $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
                    $periodLabel = 'Last 7 Days';
                    break;
                case 'month':
                    $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
                    $periodLabel = 'Last 30 Days';
                    break;
                case 'year':
                    $startDate = date('Y-m-d 00:00:00', strtotime('-365 days'));
                    $periodLabel = 'Last Year';
                    break;
            }
            
            // Get all data for report
            $stmt = $conn->prepare("
                SELECT p.id, p.name, COALESCE(SUM(e.duration), 0) as time 
                FROM projects p 
                LEFT JOIN time_entries e ON p.id = e.project_id AND e.date BETWEEN ? AND ?
                GROUP BY p.id, p.name
                HAVING time > 0
                ORDER BY time DESC
            ");
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $projectBreakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get all time entries
            $stmt = $conn->prepare("
                SELECT e.id, p.name as project_name, e.duration, e.date 
                FROM time_entries e 
                JOIN projects p ON e.project_id = p.id
                WHERE e.date BETWEEN ? AND ?
                ORDER BY e.date DESC
            ");
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get summary stats
            $stmt = $conn->prepare("SELECT COALESCE(SUM(duration), 0) as total FROM time_entries WHERE date BETWEEN ? AND ?");
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $totalTime = (int)$stmt->get_result()->fetch_assoc()['total'];
            
            if ($format === 'csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="time-tracker-report-' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                
                // Summary section
                fputcsv($output, ['Time Tracker Report']);
                fputcsv($output, ['Period', $periodLabel]);
                fputcsv($output, ['Report Date', date('Y-m-d H:i:s')]);
                fputcsv($output, ['Total Time (seconds)', $totalTime]);
                fputcsv($output, ['Total Time (hours)', number_format($totalTime / 3600, 2)]);
                fputcsv($output, []);
                
                // Project breakdown
                fputcsv($output, ['PROJECT BREAKDOWN']);
                fputcsv($output, ['Project Name', 'Time (seconds)', 'Time (hours)']);
                foreach ($projectBreakdown as $project) {
                    fputcsv($output, [
                        $project['name'],
                        $project['time'],
                        number_format($project['time'] / 3600, 2)
                    ]);
                }
                fputcsv($output, []);
                
                // Detailed entries
                fputcsv($output, ['DETAILED TIME ENTRIES']);
                fputcsv($output, ['Date', 'Project', 'Duration (seconds)', 'Duration (HH:MM:SS)']);
                foreach ($entries as $entry) {
                    $hours = floor($entry['duration'] / 3600);
                    $mins = floor(($entry['duration'] % 3600) / 60);
                    $secs = $entry['duration'] % 60;
                    $formatted = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                    fputcsv($output, [
                        $entry['date'],
                        $entry['project_name'],
                        $entry['duration'],
                        $formatted
                    ]);
                }
                
                fclose($output);
                exit;
            } elseif ($format === 'pdf') {
                // PDF format - Generate HTML for printing
                header('Content-Type: text/html; charset=utf-8');
                
                $totalHours = number_format($totalTime / 3600, 2);
                
                $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Time Tracker Report - ' . htmlspecialchars($periodLabel) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        h1 { color: #4f46e5; text-align: center; margin-bottom: 30px; }
        .summary { background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .summary-row { display: flex; justify-content: space-between; margin: 10px 0; font-size: 16px; }
        .summary-label { font-weight: bold; }
        .section-title { font-size: 18px; font-weight: bold; color: #4f46e5; margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #4f46e5; color: white; padding: 12px; text-align: left; font-weight: bold; }
        td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background: #f9fafb; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 12px; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <h1>Time Tracker Report</h1>
    
    <div class="summary">
        <div class="summary-row">
            <span class="summary-label">Period:</span>
            <span>' . htmlspecialchars($periodLabel) . '</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Report Date:</span>
            <span>' . htmlspecialchars(date('Y-m-d H:i:s')) . '</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Time:</span>
            <span><strong>' . htmlspecialchars($totalHours) . ' hours</strong> (' . htmlspecialchars($totalTime) . ' seconds)</span>
        </div>
    </div>
    
    <div class="section-title">Project Breakdown</div>';
                
                if (!empty($projectBreakdown)) {
                    $html .= '<table>
        <thead>
            <tr>
                <th>Project Name</th>
                <th>Time (Hours)</th>
                <th>Time (Seconds)</th>
            </tr>
        </thead>
        <tbody>';
                    foreach ($projectBreakdown as $project) {
                        $projectHours = number_format($project['time'] / 3600, 2);
                        $html .= '<tr>
            <td>' . htmlspecialchars($project['name']) . '</td>
            <td>' . htmlspecialchars($projectHours) . '</td>
            <td>' . htmlspecialchars($project['time']) . '</td>
        </tr>';
                    }
                    $html .= '</tbody>
    </table>';
                } else {
                    $html .= '<p>No project data available for this period.</p>';
                }
                
                $html .= '<div class="section-title">Detailed Time Entries</div>';
                
                if (!empty($entries)) {
                    $html .= '<table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Project</th>
                <th>Duration (HH:MM:SS)</th>
                <th>Duration (Seconds)</th>
            </tr>
        </thead>
        <tbody>';
                    foreach ($entries as $entry) {
                        $hours = floor($entry['duration'] / 3600);
                        $mins = floor(($entry['duration'] % 3600) / 60);
                        $secs = $entry['duration'] % 60;
                        $formatted = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                        $html .= '<tr>
            <td>' . htmlspecialchars($entry['date']) . '</td>
            <td>' . htmlspecialchars($entry['project_name']) . '</td>
            <td>' . htmlspecialchars($formatted) . '</td>
            <td>' . htmlspecialchars($entry['duration']) . '</td>
        </tr>';
                    }
                    $html .= '</tbody>
    </table>';
                } else {
                    $html .= '<p>No time entries available for this period.</p>';
                }
                
                $html .= '<div class="footer">
    <p>Generated by Time Tracker on ' . htmlspecialchars(date('Y-m-d H:i:s')) . '</p>
</div>

</body>
</html>';
                
                logAction('export_report', ['period' => $period, 'format' => 'pdf']);
                echo $html;
                exit;
            } else {
                // JSON format
                logAction('export_report', ['period' => $period, 'format' => $format]);
                sendJSON([
                    'period' => $periodLabel,
                    'exportDate' => date('Y-m-d H:i:s'),
                    'totalTimeSeconds' => $totalTime,
                    'totalTimeHours' => number_format($totalTime / 3600, 2),
                    'projectBreakdown' => $projectBreakdown,
                    'entries' => $entries
                ]);
            }
            break;

        default:
            sendJSON(['error' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    logAction('error', ['message' => $e->getMessage()]);
    $statusCode = APP_ENV === 'development' ? 500 : 500;
    $errorMessage = APP_ENV === 'development' ? $e->getMessage() : 'An error occurred';
    sendJSON(['error' => $errorMessage], $statusCode);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>