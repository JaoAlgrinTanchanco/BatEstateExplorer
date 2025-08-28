<?php
// message.php

session_start();
require_once __DIR__ . '/app/bootstrap.php';

// Logged-in user ID (must be set)
if (!isset($_SESSION['user']['id'])) {
    die("User not logged in.");
}
$user_id = (int) $_SESSION['user']['id'];

// Get agent_id from GET
$agent_id = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : null;
if (!$agent_id) {
    die("Agent not specified.");
}

// Fetch agent name from DB
$stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'agent' LIMIT 1");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
$agent = $result->fetch_assoc();
$stmt->close();

if (!$agent) {
    die("Agent not found.");
}
$agent_name = $agent['name'];

// Fetch all agents for conversations list
$agents_list = [];
$res = $conn->query("SELECT id, name FROM users WHERE role = 'agent'");
while ($row = $res->fetch_assoc()) {
    $agents_list[$row['id']] = $row['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat with <?= htmlspecialchars($agent_name) ?></title>
<link rel="stylesheet" href="/assets/css/user_dashboard.css">
<style>
body { margin:0;font-family:Arial,sans-serif; }
.chat-container { display:flex;height:100vh; }
.conversations-list { width:300px;border-right:1px solid #ddd;overflow-y:auto;padding:10px;background:#f9f9f9; }
.conversations-list h3 { margin-top:0; }
.conversation-item { padding:10px;border-bottom:1px solid #eee; cursor:pointer; }
.conversation-item.unread { background:#eef6ff; font-weight:bold; }
.chat-window { flex:1; display:flex; flex-direction:column; }
.messages { flex:1; padding:20px; overflow-y:auto; background:#fff; }
.message { margin-bottom:15px; }
.message .sender { font-weight:bold; }
.message .text { margin:5px 0; }
.chat-input { display:flex; border-top:1px solid #ddd; padding:10px; background:#f1f1f1; }
.chat-input input[type="text"] { flex:1; padding:10px; border:1px solid #ccc; border-radius:4px; }
.chat-input button { padding:10px 15px; margin-left:10px; border:none; background:#007bff; color:white; border-radius:4px; cursor:pointer; }
.chat-input button:hover { background:#0056b3; }
</style>
</head>
<body>

<div class="chat-container">

    <!-- Conversations List -->
    <div class="conversations-list">
        <h3>Conversations</h3>
        <?php foreach ($agents_list as $id => $name): ?>
            <div class="conversation-item <?= ($id === $agent_id) ? 'unread' : '' ?>" data-agent-id="<?= $id ?>">
                <?= htmlspecialchars($name) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Chat Window -->
    <div class="chat-window">
        <div class="messages" id="messages">
            <div class="message">
                <div class="sender"><?= htmlspecialchars($agent_name) ?>:</div>
                <div class="text">Hello! How can I help you?</div>
            </div>
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

// Send message
sendBtn.addEventListener('click', () => {
    const message = messageInput.value.trim();
    if (!message) return;
    const msgDiv = document.createElement('div');
    msgDiv.classList.add('message');
    msgDiv.innerHTML = `<div class="sender">You:</div><div class="text">${message}</div>`;
    messagesContainer.appendChild(msgDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    messageInput.value = '';
});

// Enter key
messageInput.addEventListener('keypress', e => {
    if (e.key === 'Enter') sendBtn.click();
});

// Switch conversations
document.querySelectorAll('.conversation-item').forEach(item => {
    item.addEventListener('click', () => {
        const agentId = item.dataset.agentId;
        window.location.href = `/BatEstateExplorer/public/message.php?agent_id=${agentId}`;
    });
});
</script>

</body>
</html>
