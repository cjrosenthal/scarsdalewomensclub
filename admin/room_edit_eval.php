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
$name = trim($_POST['name'] ?? '');
$capacity = trim($_POST['capacity'] ?? '');

if ($id <= 0) {
    header('Location: /admin/rooms.php?err=' . urlencode('Invalid room ID.'));
    exit;
}

// Prepare form data for repopulation on error
$formData = http_build_query([
    'id' => $id,
    'name' => $name,
    'capacity' => $capacity
]);

try {
    $ctx = UserContext::getLoggedInUserContext();
    RoomManagement::update($ctx, $id, [
        'name' => $name,
        'capacity' => (int)$capacity
    ]);
    
    $msg = "You have updated the room '" . $name . "'";
    header('Location: ' . $redirect . '?msg=' . urlencode($msg));
    exit;
} catch (InvalidArgumentException $e) {
    // Validation error - redirect back to form with error
    header('Location: /admin/room_edit.php?' . $formData . '&err=' . urlencode($e->getMessage()));
    exit;
} catch (Exception $e) {
    // Other errors
    header('Location: /admin/room_edit.php?' . $formData . '&err=' . urlencode('Error updating room: ' . $e->getMessage()));
    exit;
}
