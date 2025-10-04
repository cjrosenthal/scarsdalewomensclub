<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/RoomManagement.php';
Application::init();
require_admin();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /rooms/list.php');
    exit;
}

require_csrf();

$redirect = trim($_POST['redirect'] ?? '/rooms/list.php');
$name = trim($_POST['name'] ?? '');
$capacity = trim($_POST['capacity'] ?? '');

// Prepare form data for repopulation on error
$formData = http_build_query([
    'name' => $name,
    'capacity' => $capacity
]);

try {
    $ctx = UserContext::getLoggedInUserContext();
    $id = RoomManagement::create($ctx, [
        'name' => $name,
        'capacity' => (int)$capacity
    ]);
    
    $msg = "You have created the room '" . $name . "'";
    header('Location: ' . $redirect . '?msg=' . urlencode($msg));
    exit;
} catch (InvalidArgumentException $e) {
    // Validation error - redirect back to form with error
    header('Location: /rooms/add.php?' . $formData . '&err=' . urlencode($e->getMessage()));
    exit;
} catch (Exception $e) {
    // Other errors
    header('Location: /rooms/add.php?' . $formData . '&err=' . urlencode('Error creating room: ' . $e->getMessage()));
    exit;
}
