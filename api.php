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
            if (!in_array($period, ['week', 'month', 'year'], true)) {
                sendJSON(['error' => 'Invalid period'], 400);
            }
            
            // Calculate date range
            $endDate = date('Y-m-d 23:59:59');
            switch ($period) {
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