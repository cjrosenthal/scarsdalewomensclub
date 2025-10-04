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

if ($id <= 0) {
    header('Location: /contacts/list.php?err=' . urlencode('Invalid contact ID.'));
    exit;
}

try {
    // Get contact name before deletion for message
    $contact = ContactManagement::findById($id);
    $contactName = $contact ? ($contact['first_name'] . ' ' . $contact['last_name']) : 'the contact';
    
    $ctx = UserContext::getLoggedInUserContext();
    $ok = ContactManagement::delete($ctx, $id);
    
    if ($ok) {
        $msg = "You have deleted the contact '" . $contactName . "'";
        header('Location: ' . $redirect . '?msg=' . urlencode($msg));
    } else {
        header('Location: ' . $redirect . '?err=' . urlencode('Failed to delete contact.'));
    }
    exit;
} catch (Exception $e) {
    header('Location: ' . $redirect . '?err=' . urlencode('Error deleting contact: ' . $e->getMessage()));
    exit;
}
