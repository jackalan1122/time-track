<?php
header('Content-Type: application/json');
require_once 'config.php';

$conn = getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_projects':
        $result = $conn->query("SELECT * FROM projects ORDER BY created_at DESC");
        $projects = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($projects);
        break;

    case 'add_project':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("INSERT INTO projects (name, color) VALUES (?, ?)");
        $stmt->bind_param("ss", $data['name'], $data['color']);
        $stmt->execute();
        echo json_encode(['id' => $conn->insert_id, 'success' => true]);
        break;

    case 'update_project':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("UPDATE projects SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $data['name'], $data['id']);
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;

    case 'delete_project':
        $id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;

    case 'get_entries':
        $project_id = $_GET['project_id'] ?? null;
        if ($project_id) {
            $stmt = $conn->prepare("SELECT * FROM time_entries WHERE project_id = ? ORDER BY date DESC");
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query("SELECT * FROM time_entries ORDER BY date DESC");
        }
        $entries = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($entries);
        break;

    case 'add_entry':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("INSERT INTO time_entries (project_id, duration, date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $data['project_id'], $data['duration'], $data['date']);
        $stmt->execute();
        
        // Update project total time
        $stmt = $conn->prepare("UPDATE projects SET total_time = total_time + ? WHERE id = ?");
        $stmt->bind_param("ii", $data['duration'], $data['project_id']);
        $stmt->execute();
        
        echo json_encode(['id' => $conn->insert_id, 'success' => true]);
        break;

    case 'delete_entry':
        $id = $_GET['id'] ?? 0;
        
        // Get entry details to update project
        $stmt = $conn->prepare("SELECT project_id, duration FROM time_entries WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $entry = $result->fetch_assoc();
        
        if ($entry) {
            // Update project total time
            $stmt = $conn->prepare("UPDATE projects SET total_time = GREATEST(0, total_time - ?) WHERE id = ?");
            $stmt->bind_param("ii", $entry['duration'], $entry['project_id']);
            $stmt->execute();
            
            // Delete entry
            $stmt = $conn->prepare("DELETE FROM time_entries WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        
        echo json_encode(['success' => true]);
        break;

    case 'get_dashboard_stats':
        $period = $_GET['period'] ?? 'week';
        
        // Calculate date range
        $endDate = date('Y-m-d 23:59:59');
        if ($period === 'week') {
            $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
        } elseif ($period === 'month') {
            $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
        } else {
            $startDate = date('Y-m-d 00:00:00', strtotime('-365 days'));
        }
        
        // Get total time in period
        $stmt = $conn->prepare("SELECT SUM(duration) as total FROM time_entries WHERE date BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $totalTime = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        
        // Get project breakdown
        $stmt = $conn->prepare("
            SELECT p.id, p.name, p.color, SUM(e.duration) as time 
            FROM projects p 
            LEFT JOIN time_entries e ON p.id = e.project_id 
            WHERE e.date BETWEEN ? AND ?
            GROUP BY p.id 
            ORDER BY time DESC
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $projectBreakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get daily breakdown for chart
        $stmt = $conn->prepare("
            SELECT DATE(date) as day, SUM(duration) as time 
            FROM time_entries 
            WHERE date BETWEEN ? AND ?
            GROUP BY DATE(date) 
            ORDER BY day
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $dailyBreakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get entry count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM time_entries WHERE date BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $entryCount = $stmt->get_result()->fetch_assoc()['count'];
        
        // Get active projects count
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT project_id) as count FROM time_entries WHERE date BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $activeProjects = $stmt->get_result()->fetch_assoc()['count'];
        
        echo json_encode([
            'totalTime' => $totalTime,
            'projectBreakdown' => $projectBreakdown,
            'dailyBreakdown' => $dailyBreakdown,
            'entryCount' => $entryCount,
            'activeProjects' => $activeProjects
        ]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
?>