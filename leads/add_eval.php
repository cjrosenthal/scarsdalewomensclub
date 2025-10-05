<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/Application.php';
require_once __DIR__ . '/../lib/LeadManagement.php';
require_once __DIR__ . '/../lib/ContactManagement.php';
require_once __DIR__ . '/../lib/UserContext.php';

Application::init();
require_login();
require_csrf();

$redirect = trim($_POST['redirect'] ?? '/leads/list.php');

try {
    $ctx = UserContext::getLoggedInUserContext();
    
    // Determine if we're using an existing contact or creating a new one
    $selectedContactId = (int)($_POST['selected_contact_id'] ?? 0);
    
    if ($selectedContactId > 0) {
        // Using existing contact
        $mainContactId = $selectedContactId;
    } else {
        // Creating new contact
        $contactData = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'organization' => $_POST['organization'] ?? '',
            'phone_number' => $_POST['phone_number'] ?? ''
        ];
        
        $mainContactId = ContactManagement::create($ctx, $contactData);
    }
    
    // Create the lead
    $leadData = [
        'main_contact_id' => $mainContactId,
        'channel' => $_POST['channel'] ?? '',
        'party_type' => $_POST['party_type'] ?? '',
        'number_of_people' => $_POST['number_of_people'] ?? '',
        'description' => $_POST['description'] ?? '',
        'status' => 'active'
    ];
    
    $leadId = LeadManagement::create($ctx, $leadData);
    
    header('Location: ' . $redirect . '?msg=' . urlencode('Lead created successfully.'));
    exit;
    
} catch (Exception $e) {
    $errMsg = 'Error creating lead: ' . $e->getMessage();
    header('Location: /leads/add.php?err=' . urlencode($errMsg));
    exit;
}
