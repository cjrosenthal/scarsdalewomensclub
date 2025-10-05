<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/Application.php';
require_once __DIR__ . '/../lib/LeadManagement.php';
require_once __DIR__ . '/../lib/UserContext.php';

Application::init();
require_login();
require_csrf();

header('Content-Type: application/json');

$leadId = (int)($_POST['lead_id'] ?? 0);
$contactId = (int)($_POST['contact_id'] ?? 0);

if ($leadId <= 0 || $contactId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid lead or contact ID.']);
    exit;
}

try {
    $ctx = UserContext::getLoggedInUserContext();
    
    LeadManagement::addSecondaryContact($ctx, $leadId, $contactId);
    
    echo json_encode(['success' => true]);
    exit;
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
