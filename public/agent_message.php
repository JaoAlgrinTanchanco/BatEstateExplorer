<?php
session_start();
require_once __DIR__ . '/app/bootstrap.php';

define('ENCRYPTION_KEY', '12345678901234567890123456789012');

function decryptMessage($encrypted_base64) {
    $data = base64_decode($encrypted_base64);
    if (strlen($data) < 16) return $encrypted_base64;
    $iv = substr($data, 0, 16);
    $ciphertext = substr($data, 16);
    return openssl_decrypt($ciphertext, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
}

// Logged-in agent
$current_user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
if (!$current_user_id) die("Agent not logged in.");

// 1️⃣ Get user_id from URL (conversation switch)
$user_id = $_GET['user_id'] ?? null;

// 2️⃣ If agent_id is provided (from modal button), resolve to user_id
$agent_id = $_GET['agent_id'] ?? null;
if ($agent_id) {
    $stmt = $conn->prepare("SELECT user_id FROM agents WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user_id = (int)$row['user_id'];
    }
    $stmt->close();
}

// 3️⃣ Prepare conversation header and contact info
if ($user_id) {
    // Fetch user info for the conversation
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email 
        FROM users 
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $contact = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($contact) {
        $contact_name = trim($contact['first_name'] . ' ' . $contact['last_name']);
        $chat_header = $contact_name;
    } else {
        $contact = null;
        $contact_name = null;
        $chat_header = "No conversation selected";
    }
} else {
    $contact = null;
    $contact_name = null;
    $chat_header = "No conversation selected";
}

// Fetch all users for conversation list
$contacts_list = [];
$sql = "
    SELECT DISTINCT u.id, CONCAT(u.first_name,' ',u.last_name) AS name
    FROM messages m
    INNER JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?)
      AND u.id != ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $contacts_list[$row['id']] = $row['name'];
}
$stmt->close();

// Fetch messages only if a conversation is selected
$messages = [];
if ($contact) {
    $stmt = $conn->prepare("
        SELECT sender_id, message, created_at
        FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt->bind_param("iiii", $current_user_id, $contact['id'], $contact['id'], $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}

// Prepare names for messages
$contact_names = $contacts_list;
$contact_names[$current_user_id] = 'You';

$chat_header = $user_id ? $contact_name : "Select a conversation";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat with <?= htmlspecialchars($contact_name) ?></title>
<style>
/* Your CSS remains the same */
body{margin:0;font-family:Arial,sans-serif;background:#f0f2f5}
.chat-container{display:flex;height:100vh;overflow:hidden}
.conversations-list{width:300px;border-right:1px solid #ddd;overflow-y:auto;padding:10px;background:#fff}
.conversations-list h3{margin-top:0;font-size:1.2rem;color:#333;border-bottom:1px solid #eee;padding-bottom:5px}
.conversation-item{padding:10px;border-bottom:1px solid #eee;cursor:pointer;transition:background .2s}
.conversation-item:hover{background:#f1f1f1}
.conversation-item.unread{background:#e6f0ff;font-weight:bold}
.chat-window{flex:1;display:flex;flex-direction:column;background:#f9f9f9}
.chat-header{font-size:18px;background:#f5f5f5;border-bottom:1px solid #ddd;padding:15px;font-weight:bold;color:#333}
.messages{flex:1;padding:20px;overflow-y:auto;display:flex;flex-direction:column;gap:10px}
.message{max-width:60%;padding:10px 15px;border-radius:12px;word-break:break-word;position:relative}
.message.you{background:#007bff;color:#fff;margin-left:auto;border-bottom-right-radius:0}
.message.agent{background:#e4e6eb;color:#000;margin-right:auto;border-bottom-left-radius:0}
.message .sender{font-weight:bold;font-size:.85rem;display:flex;align-items:center;gap:5px}
.message .timestamp{font-size:.7rem;color:#666}
.chat-input{display:flex;border-top:1px solid #ddd;padding:10px;background:#fff}
.chat-input input[type="text"]{flex:1;padding:10px;border:1px solid #ccc;border-radius:20px;outline:none}
.chat-input button{padding:10px 20px;margin-left:10px;border:none;background:#007bff;color:#fff;border-radius:20px;cursor:pointer;transition:background .2s}
.chat-input button:hover{background:#0056b3}
.messages::-webkit-scrollbar{width:6px}
.messages::-webkit-scrollbar-thumb{background:rgba(0,0,0,.2);border-radius:3px}
.messages::-webkit-scrollbar-track{background:transparent}
</style>
</head>
<body>

<div class="chat-container">

    <!-- Conversations List -->
<div class="conversations-list">
    <h3>Conversations</h3>
        <?php foreach ($contacts_list as $id => $name): ?>
            <div class="conversation-item <?= ($id === $contact['id']) ? 'unread' : '' ?>" data-user-id="<?= $id ?>">
                <?= htmlspecialchars($name) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Chat Window -->
    <div class="chat-window" data-user-id="<?= $contact['id'] ?>">
        <div class="chat-header"><?= htmlspecialchars($chat_header) ?></div>
        <div class="messages" id="messages">
            <?php foreach ($messages as $msg):
                $isYou = $msg['sender_id'] === $current_user_id;
                $senderName = htmlspecialchars($contact_names[$msg['sender_id']] ?? 'Client');
                $timestamp = date('M d, Y H:i', strtotime($msg['created_at']));
            ?>
            <div class="message <?= $isYou ? 'you' : 'agent' ?>">
                <div class="sender"><?= $senderName ?> <span class="timestamp"><?= $timestamp ?></span>:</div>
                <div class="text"><?= htmlspecialchars(decryptMessage($msg['message'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="chat-input">
            <input type="text" id="messageInput" placeholder="Type your message..." <?= $contact ? '' : 'disabled' ?>>
            <button id="sendBtn" <?= $contact ? '' : 'disabled' ?>>Send</button>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sendBtn = document.getElementById('sendBtn');
    const messageInput = document.getElementById('messageInput');
    const messagesContainer = document.getElementById('messages');
    const chatWindow = document.querySelector('.chat-window');
    const receiverId = chatWindow.dataset.userId;

    // Disable input if no conversation selected
    const canSend = receiverId && receiverId !== "";
    if (!canSend) {
        sendBtn.disabled = true;
        messageInput.disabled = true;
    }

    // Send message function
    const sendMessage = () => {
        const message = messageInput.value.trim();
        if (!message || !canSend) return;

        fetch('api/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `receiver_id=${receiverId}&message=${encodeURIComponent(message)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const msgDiv = document.createElement('div');
                msgDiv.classList.add('message', 'you');
                msgDiv.innerHTML = `<div class="sender">You:</div><div class="text">${data.message.text}</div>`;
                messagesContainer.appendChild(msgDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                messageInput.value = '';
            } else {
                alert(data.error);
            }
        })
        .catch(err => console.error(err));
    };

    // Event listeners
    sendBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', e => {
        if (e.key === 'Enter') sendMessage();
    });

    // Switch conversations
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', () => {
            const userId = item.dataset.userId;
            if (userId) {
                window.location.href = `/BatEstateExplorer/public/agent_message.php?user_id=${userId}`;
            }
        });
    });
});
</script>


</body>
</html>
