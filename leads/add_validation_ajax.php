<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/Application.php';
require_once __DIR__ . '/../lib/ContactManagement.php';

Application::init();
require_login();

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$phoneNumber = trim($_POST['phone_number'] ?? '');

try {
    $matches = ContactManagement::findPotentialDuplicates($email, $phoneNumber);
    
    $hasEmailMatch = false;
    $hasPhoneMatch = false;
    
    foreach ($matches as $match) {
        if (strpos($match['match_type'], 'email') !== false) {
            $hasEmailMatch = true;
        }
        if (strpos($match['match_type'], 'phone') !== false) {
            $hasPhoneMatch = true;
        }
    }
    
    echo json_encode([
        'success' => true,
        'matches' => $matches,
        'has_email_match' => $hasEmailMatch,
        'has_phone_match' => $hasPhoneMatch
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
