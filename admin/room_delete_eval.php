<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/RoomManagement.php';
Application::init();
require_admin();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/rooms.php');
    exit;
}

require_csrf();

$id = (int)($_POST['id'] ?? 0);
$redirect = trim($_POST['redirect'] ?? '/admin/rooms.php');

if ($id <= 0) {
    header('Location: /admin/rooms.php?err=' . urlencode('Invalid room ID.'));
    exit;
}

try {
    // Get room name before deletion for success message
    $room = RoomManagement::findById($id);
    $roomName = $room ? $room['name'] : 'Unknown';
    
    $ctx = UserContext::getLoggedInUserContext();
    RoomManagement::delete($ctx, $id);
    
    $msg = "You have deleted the room '" . $roomName . "'";
    header('Location: ' . $redirect . '?msg=' . urlencode($msg));
    exit;
} catch (Exception $e) {
    header('Location: ' . $redirect . '?err=' . urlencode('Error deleting room: ' . $e->getMessage()));
    exit;
}
