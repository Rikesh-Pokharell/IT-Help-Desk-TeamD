<?php
require_once 'includes/config.php';

// Simple API - returns JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$db = getDB();

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Simple API Key check
$api_key = $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['api_key'] ?? '';
$valid_key = 'HDTD-API-2026'; // Simple key

if ($api_key !== $valid_key) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid API key. Use: HDTD-API-2026']);
    exit();
}

// ── ROUTES ──
switch ($action) {

    // GET all tickets
    case 'get_tickets':
        $status   = $_GET['status'] ?? '';
        $where = "WHERE 1=1";
        if ($status) $where .= " AND t.status = '" . $db->real_escape_string($status) . "'";

        $result = $db->query("SELECT t.id, t.ticket_number, t.subject, t.category, t.priority, t.status, t.created_at, u.full_name, u.email
                              FROM tickets t JOIN users u ON t.user_id = u.id
                              $where ORDER BY t.created_at DESC");

        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        echo json_encode(['success' => true, 'total' => count($tickets), 'tickets' => $tickets]);
        break;

    // GET single ticket by ID
    case 'get_ticket':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Ticket ID required']);
            break;
        }
        $result = $db->query("SELECT t.*, u.full_name, u.email, u.college_id
                              FROM tickets t JOIN users u ON t.user_id = u.id
                              WHERE t.id = $id");
        $ticket = $result->fetch_assoc();
        if ($ticket) {
            echo json_encode(['success' => true, 'ticket' => $ticket]);
       // } else {
           // echo json_encode(['success' => false, 'message' => 'Ticket not found']);
       // }
       } else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
}
        break;

    // POST - create new ticket
    case 'create_ticket':
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Use POST method']);
            break;
        }
        $data = json_decode(file_get_contents('php://input'), true);

        $user_id     = intval($data['user_id'] ?? 0);
        $category    = $data['category'] ?? '';
        $subject     = $data['subject'] ?? '';
        $description = $data['description'] ?? '';
        $priority    = $data['priority'] ?? 'Medium';

        $valid_cats  = ['Network','Account','Software','Hardware','Other'];
        $valid_prios = ['Low','Medium','High','Critical'];

        if (!$user_id || !$category || !$subject || !$description) {
            echo json_encode(['success' => false, 'message' => 'user_id, category, subject, description are required']);
            break;
        }
        if (!in_array($category, $valid_cats)) {
            echo json_encode(['success' => false, 'message' => 'Invalid category']);
            break;
        }
        if (!in_array($priority, $valid_prios)) {
            echo json_encode(['success' => false, 'message' => 'Invalid priority']);
            break;
        }

        $ticket_number = generateTicketNumber();
        $stmt = $db->prepare("INSERT INTO tickets (ticket_number, user_id, category, subject, description, priority) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("sissss", $ticket_number, $user_id, $category, $subject, $description, $priority);

        if ($stmt->execute()) {
            $new_id = $db->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Ticket created successfully',
                'ticket_id' => $new_id,
                'ticket_number' => $ticket_number
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create ticket']);
        }
        break;

    // PUT - update ticket status
    case 'update_status':
        if ($method !== 'POST' && $method !== 'PUT') {
            echo json_encode(['success' => false, 'message' => 'Use POST or PUT method']);
            break;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $id     = intval($data['id'] ?? $_GET['id'] ?? 0);
        $status = $data['status'] ?? '';

        $valid_statuses = ['Pending','In Progress','Resolved','Closed'];
        if (!$id || !$status) {
            echo json_encode(['success' => false, 'message' => 'id and status are required']);
            break;
        }
        if (!in_array($status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status. Use: Pending, In Progress, Resolved, Closed']);
            break;
        }

        $stmt = $db->prepare("UPDATE tickets SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => "Ticket #$id status updated to '$status'"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ticket not found or status unchanged']);
        }
        break;

    // GET stats summary
    case 'stats':
        $total    = $db->query("SELECT COUNT(*) as c FROM tickets")->fetch_assoc()['c'];
        $pending  = $db->query("SELECT COUNT(*) as c FROM tickets WHERE status='Pending'")->fetch_assoc()['c'];
        $inprog   = $db->query("SELECT COUNT(*) as c FROM tickets WHERE status='In Progress'")->fetch_assoc()['c'];
        $resolved = $db->query("SELECT COUNT(*) as c FROM tickets WHERE status='Resolved'")->fetch_assoc()['c'];
        $closed   = $db->query("SELECT COUNT(*) as c FROM tickets WHERE status='Closed'")->fetch_assoc()['c'];

        echo json_encode([
            'success' => true,
            'stats' => [
                'total'       => $total,
                'pending'     => $pending,
                'in_progress' => $inprog,
                'resolved'    => $resolved,
                'closed'      => $closed,
            ]
        ]);
        break;

    // Default - show available routes
    default:
        echo json_encode([
            'success' => true,
            'message' => 'College IT HelpDesk API v1.0',
            'api_key_required' => 'HDTD-API-2026',
            'routes' => [
                'GET  api.php?action=get_tickets&api_key=HDTD-API-2026'              => 'Get all tickets',
                'GET  api.php?action=get_tickets&status=Pending&api_key=...'         => 'Filter by status',
                'GET  api.php?action=get_ticket&id=1&api_key=...'                    => 'Get single ticket',
                'POST api.php?action=create_ticket&api_key=...'                      => 'Create new ticket',
                'POST api.php?action=update_status&api_key=...'                      => 'Update ticket status',
                'GET  api.php?action=stats&api_key=...'                              => 'Get ticket stats',
            ]
        ]);
        break;
}
?>
