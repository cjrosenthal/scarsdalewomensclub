<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/Application.php';
require_once __DIR__ . '/../lib/LeadManagement.php';
require_once __DIR__ . '/../lib/UserContext.php';

Application::init();
require_login();
require_csrf();

$leadId = (int)($_POST['id'] ?? 0);
$redirect = trim($_POST['redirect'] ?? '/leads/list.php');

if ($leadId <= 0) {
    header('Location: /leads/list.php?err=' . urlencode('Invalid lead ID.'));
    exit;
}

try {
    $ctx = UserContext::getLoggedInUserContext();
    
    LeadManagement::delete($ctx, $leadId);
    
    header('Location: ' . $redirect . '?msg=' . urlencode('Lead deleted successfully.'));
    exit;
    
} catch (Exception $e) {
    $errMsg = 'Error deleting lead: ' . $e->getMessage();
    header('Location: ' . $redirect . '?err=' . urlencode($errMsg));
    exit;
}
