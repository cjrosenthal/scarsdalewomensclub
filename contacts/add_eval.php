<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/ContactManagement.php';
Application::init();
require_login();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contacts/list.php');
    exit;
}

require_csrf();

$redirect = trim($_POST['redirect'] ?? '/contacts/list.php');
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$organization = trim($_POST['organization'] ?? '');
$phoneNumber = trim($_POST['phone_number'] ?? '');

// Prepare form data for repopulation on error
$formData = http_build_query([
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'organization' => $organization,
    'phone_number' => $phoneNumber
]);

try {
    $ctx = UserContext::getLoggedInUserContext();
    $id = ContactManagement::create($ctx, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'organization' => $organization,
        'phone_number' => $phoneNumber
    ]);
    
    $msg = "You have created the contact '" . $firstName . ' ' . $lastName . "'";
    header('Location: ' . $redirect . '?msg=' . urlencode($msg));
    exit;
} catch (InvalidArgumentException $e) {
    // Validation error - redirect back to form with error
    header('Location: /contacts/add.php?' . $formData . '&err=' . urlencode($e->getMessage()));
    exit;
} catch (Exception $e) {
    // Other errors
    header('Location: /contacts/add.php?' . $formData . '&err=' . urlencode('Error creating contact: ' . $e->getMessage()));
    exit;
}
