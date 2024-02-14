<?php

// Set your Telegram bot token here
define('BOT_TOKEN', '6631716013:AAEeR_ntT3dRGLurMhJw8jFqGj528Jmf9a4');

// Temporary email API URL
define('EMAIL_API_URL', 'https://www.1secmail.com/api/v1/');

// Function to generate a random email address
function generateEmail() {
    $domain = "1secmail.com";
    $username = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyz', ceil(10/strlen($x)) )),1,10);
    return $username . '@' . $domain;
}

// Command handler for /start
function start($chat_id) {
    $help_message = "Welcome to Temporary Email Bot!\n\n"
                  . "This bot allows you to generate temporary email addresses and fetch emails received in those addresses.\n\n"
                  . "Commands:\n"
                  . "/generate - Generate a temporary email address.\n"
                  . "/fetchmail - Fetch emails received in the temporary email inbox.\n"
                  . "/help - Display this help message.";
    sendMessage($chat_id, $help_message);
}

// Command handler for /generate
function generate($chat_id) {
    $email = generateEmail();
    $message = "Your temporary email address is: " . $email;
    sendMessage($chat_id, $message);
    // Store generated email in database or session
    // For simplicity, I'm not demonstrating storage in this example
}

// Command handler for /fetchmail
function fetchMail($chat_id, $user_data) {
    $email = $user_data['email'] ?? null;
    if ($email) {
        $response = file_get_contents(EMAIL_API_URL . '?action=getMessages&login=' . explode('@', $email)[0] . '&domain=' . explode('@', $email)[1]);
        if ($response) {
            $messages = json_decode($response, true);
            if ($messages) {
                foreach ($messages as $message) {
                    $subject = $message['subject'] ?? 'No Subject';
                    sendMessage($chat_id, "Subject: " . $subject);
                }
            } else {
                sendMessage($chat_id, "No messages found.");
            }
        } else {
            sendMessage($chat_id, "Failed to fetch messages.");
        }
    } else {
        sendMessage($chat_id, "Please generate a temporary email first using /generate.");
    }
}

// Function to send message to Telegram
function sendMessage($chat_id, $message) {
    $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage';
    $data = [
        'chat_id' => $chat_id,
        'text' => $message
    ];
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return $result;
}

// Get updates from Telegram API
$update = json_decode(file_get_contents('php://input'), true);
if (isset($update)) {
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'];
    $user_data = []; // Placeholder for storing user-specific data

    switch ($text) {
        case '/start':
            start($chat_id);
            break;
        case '/generate':
            generate($chat_id);
            break;
        case '/fetchmail':
            fetchMail($chat_id, $user_data);
            break;
        case '/help':
            start($chat_id);
            break;
        default:
            sendMessage($chat_id, "Invalid command. Type /help to see available commands.");
            break;
    }
}
