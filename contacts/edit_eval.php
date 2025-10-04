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
$id = (int)($_POST['id'] ?? 0);
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$organization = trim($_POST['organization'] ?? '');
$phoneNumber = trim($_POST['phone_number'] ?? '');

if ($id <= 0) {
    header('Location: /contacts/list.php?err=' . urlencode('Invalid contact ID.'));
    exit;
}

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
    $ok = ContactManagement::update($ctx, $id, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'organization' => $organization,
        'phone_number' => $phoneNumber
    ]);
    
    if ($ok) {
        $msg = "You have updated the contact '" . $firstName . ' ' . $lastName . "'";
        header('Location: ' . $redirect . '?msg=' . urlencode($msg));
    } else {
        header('Location: /contacts/edit.php?id=' . $id . '&' . $formData . '&err=' . urlencode('Failed to update contact.'));
    }
    exit;
} catch (InvalidArgumentException $e) {
    // Validation error - redirect back to form with error
    header('Location: /contacts/edit.php?id=' . $id . '&' . $formData . '&err=' . urlencode($e->getMessage()));
    exit;
} catch (Exception $e) {
    // Other errors
    header('Location: /contacts/edit.php?id=' . $id . '&' . $formData . '&err=' . urlencode('Error updating contact: ' . $e->getMessage()));
    exit;
}
