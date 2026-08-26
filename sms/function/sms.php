<?php

require __DIR__ . '/../../config/env_loader.php';

use AndroidSmsGateway\Client;
use AndroidSmsGateway\Encryptor;
use AndroidSmsGateway\Domain\Message;

$expectedKey = (string) env('SMS_API_KEY', '');
$providedKey = $_POST['api_key'] ?? $_SERVER['HTTP_X_SMS_API_KEY'] ?? '';

if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(401);
    echo 'Unauthorized: invalid or missing API key';
    exit;
}

if (!isset($_POST['message'], $_POST['number'], $_POST['username'], $_POST['password'])) {
    http_response_code(400);
    echo 'Please provide all required fields: message, number, username, and password';
    exit;
}

$login = $_POST['username'];
$password = $_POST['password'];
$message = $_POST['message'];
$numberInput = $_POST['number'];

// Validate the number
$number = '+63' . $numberInput;

// Check if the number starts with +639 and has correct length (Philippines mobile numbers are 10 digits after +63)
if (!preg_match('/^\+639\d{9}$/', $number)) {
    http_response_code(400);
    echo 'Error: Philippine mobile number must start with 9 (format: +639XXXXXXXXX) and be 10 digits long after +63';
    exit;
}

$client = new Client($login, $password);
$message = new Message($message, [$number]);

try {
    $messageState = $client->Send($message);
    echo 'Message sent with ID: ' . $messageState->ID() . PHP_EOL;
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error sending message: ' . $e->getMessage() . PHP_EOL;
    exit;
}

try {
    $messageState = $client->GetState($messageState->ID());
    echo 'Message state: ' . $messageState->State() . PHP_EOL;
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error getting message state: ' . $e->getMessage() . PHP_EOL;
    exit;
}
