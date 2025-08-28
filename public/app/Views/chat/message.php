<?php
// message.php

// Make sure user is logged in
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /auth/login.php");
    exit;
}

$user_id = $_SESSION['user']['id'] ?? null;

// Optional: get agent_id from GET
$agent_id = $_GET['agent_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messaging</title>
    <link rel="stylesheet" href="/assets/css/user_dashboard.css">
    <style>
        body { margin: 0; font-family: Arial, sans-serif; }
        .chat-container { display: flex; height: 100vh; }
        .conversations-list {
            width: 300px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            padding: 10px;
            background: #f9f9f9;
        }
        .conversations-list h3 { margin-top: 0; }
        .conversation-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        .conversation-item.unread { background: #eef6ff; font-weight: bold; }
        .chat-window {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #fff;
        }
        .message { margin-bottom: 15px; }
        .message .sender { font-weight: bold; }
        .message .text { margin: 5px 0; }
        .chat-input {
            display: flex;
            border-top: 1px solid #ddd;
            padding: 10px;
            background: #f1f1f1;
        }
        .chat-input input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .chat-input button {
            padding: 10px 15px;
            margin-left: 10px;
            border: none;
            background: #007bff;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        .chat-input button:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="chat-container">

    <!-- Conversations List -->
    <div class="conversations-list">
        <h3>Conversations</h3>
        <!-- Sample conversation items -->
        <div class="conversation-item unread" data-agent-id="101">
            John Agent
        </div>
        <div class="conversation-item" data-agent-id="102">
            Jane Agent
        </div>
        <!-- Load via AJAX later -->
    </div>

    <!-- Chat Window -->
    <div class="chat-window">
        <div class="messages" id="messages">
            <!-- Sample messages -->
            <div class="message">
                <div class="sender">John Agent:</div>
                <div class="text">Hello! How can I help you?</div>
            </div>
            <div class="message">
                <div class="sender">You:</div>
                <div class="text">Hi! I’m interested in your property listing.</div>
            </div>
            <!-- Messages will be loaded dynamically -->
        </div>
        <div class="chat-input">
            <input type="text" id="messageInput" placeholder="Type your message...">
            <button id="sendBtn">Send</button>
        </div>
    </div>

</div>

<script>
    const sendBtn = document.getElementById('sendBtn');
    const messageInput = document.getElementById('messageInput');
    const messagesContainer = document.getElementById('messages');

    sendBtn.addEventListener('click', function() {
        const message = messageInput.value.trim();
        if (message === '') return;

        // Sample append, replace with AJAX POST to send_message.php
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('message');
        msgDiv.innerHTML = `<div class="sender">You:</div><div class="text">${message}</div>`;
        messagesContainer.appendChild(msgDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        messageInput.value = '';
    });
</script>

</body>
</html>
