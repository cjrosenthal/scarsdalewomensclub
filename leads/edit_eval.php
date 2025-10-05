<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/Application.php';
require_once __DIR__ . '/../lib/LeadManagement.php';
require_once __DIR__ . '/../lib/ContactManagement.php';
require_once __DIR__ . '/../lib/UserContext.php';

Application::init();
require_login();
require_csrf();

$leadId = (int)($_POST['id'] ?? 0);
$redirect = trim($_POST['redirect'] ?? '/leads/list.php');
$action = trim($_POST['action'] ?? 'update_lead_only');

if ($leadId <= 0) {
    header('Location: /leads/list.php?err=' . urlencode('Invalid lead ID.'));
    exit;
}

try {
    $ctx = UserContext::getLoggedInUserContext();
    $lead = LeadManagement::findById($leadId);
    
    if (!$lead) {
        header('Location: /leads/list.php?err=' . urlencode('Lead not found.'));
        exit;
    }
    
    if ($action === 'replace') {
        // Replace primary contact with a different contact
        $newContactId = (int)($_POST['new_contact_id'] ?? 0);
        if ($newContactId <= 0) {
            header('Location: ' . $redirect . '&err=' . urlencode('Please select a new contact.'));
            exit;
        }
        
        // Update lead with new contact
        $leadData = [
            'main_contact_id' => $newContactId,
            'channel' => $_POST['channel'] ?? '',
            'party_type' => $_POST['party_type'] ?? '',
            'number_of_people' => $_POST['number_of_people'] ?? '',
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        LeadManagement::update($ctx, $leadId, $leadData);
        header('Location: ' . $redirect . '&msg=' . urlencode('Lead updated with new primary contact.'));
        exit;
        
    } elseif ($action === 'update_contact_and_lead') {
        // Update both the contact and the lead
        
        // Update the primary contact
        $contactData = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'organization' => $_POST['organization'] ?? '',
            'phone_number' => $_POST['phone_number'] ?? ''
        ];
        
        ContactManagement::update($ctx, $lead['main_contact_id'], $contactData);
        
        // Update the lead
        $leadData = [
            'main_contact_id' => $lead['main_contact_id'],
            'channel' => $_POST['channel'] ?? '',
            'party_type' => $_POST['party_type'] ?? '',
            'number_of_people' => $_POST['number_of_people'] ?? '',
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        LeadManagement::update($ctx, $leadId, $leadData);
        
        header('Location: ' . $redirect . '&msg=' . urlencode('Contact and lead updated successfully.'));
        exit;
        
    } else {
        // update_lead_only - Only update lead details, not the contact
        $leadData = [
            'main_contact_id' => $lead['main_contact_id'],
            'channel' => $_POST['channel'] ?? '',
            'party_type' => $_POST['party_type'] ?? '',
            'number_of_people' => $_POST['number_of_people'] ?? '',
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        LeadManagement::update($ctx, $leadId, $leadData);
        
        header('Location: ' . $redirect . '&msg=' . urlencode('Lead updated successfully.'));
        exit;
    }
    
} catch (Exception $e) {
    $errMsg = 'Error updating lead: ' . $e->getMessage();
    header('Location: ' . $redirect . '&err=' . urlencode($errMsg));
    exit;
}
