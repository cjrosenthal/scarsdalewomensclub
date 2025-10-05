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
$commentText = trim($_POST['comment_text'] ?? '');

if ($leadId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid lead ID.']);
    exit;
}

if ($commentText === '') {
    echo json_encode(['success' => false, 'error' => 'Comment text is required.']);
    exit;
}

try {
    $ctx = UserContext::getLoggedInUserContext();
    
    $commentId = LeadManagement::addComment($ctx, $leadId, $commentText);
    
    echo json_encode(['success' => true, 'comment_id' => $commentId]);
    exit;
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
