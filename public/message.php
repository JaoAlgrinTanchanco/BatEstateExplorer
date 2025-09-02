<?php
session_start();
require_once __DIR__ . '/app/bootstrap.php';

define('ENCRYPTION_KEY', '12345678901234567890123456789012'); // same key used to encrypt

function decryptMessage($encrypted_base64) {
    $data = base64_decode($encrypted_base64);
    if (strlen($data) < 16) return $encrypted_base64; // fallback, return as-is
    $iv = substr($data, 0, 16);
    $ciphertext = substr($data, 16);
    return openssl_decrypt($ciphertext, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
}

// ✅ Check for logged-in user
if (!empty($_SESSION['user']['id'])) {
    $current_user_id = (int) $_SESSION['user']['id'];
} elseif (!empty($_SESSION['user_id'])) {
    $current_user_id = (int) $_SESSION['user_id'];
} else {
    die("User not logged in.");
}

// ✅ Get property_id or agent_id from URL
$property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : null;
$agent_id    = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : null;

// 🔹 If coming from a property, fetch agent_id via API
if ($property_id) {
    $apiUrl   = __DIR__ . "/api/get_property_agent.php?property_id=" . $property_id;
    $response = file_get_contents($apiUrl);
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['property']['agent_id'])) {
            $agent_id = (int)$data['property']['agent_id'];
        }
    }
}

// 🔹 Convert agent_id to user_id
$user_id = null;
if ($agent_id) {
    $stmt = $conn->prepare("SELECT user_id FROM agents WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $user_id = (int)$row['user_id'];
    $stmt->close();
}

// 🔹 Fallback if no agent_id: pick first available agent
if (!$user_id) {
    $res = $conn->query("SELECT id AS user_id FROM users WHERE user_type IN ('direct_agent','associate_agent') LIMIT 1");
    if ($row = $res->fetch_assoc()) $user_id = (int)$row['user_id'];
    else die("No agents available.");
}

// ✅ Fetch agent info dynamically based on agent_id from URL
if ($agent_id) {
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email 
        FROM users 
        WHERE id = ? AND user_type IN ('direct_agent','associate_agent') 
        LIMIT 1
    ");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $agent = $result->fetch_assoc();
    $stmt->close();

    if (!$agent) die("Agent not found.");
    $agent_name = trim($agent['first_name'] . " " . $agent['last_name']);
    $user_id = $agent['id']; // also update user_id for fetching messages
}


// ✅ Fetch all agents for conversation list (only those you’ve messaged)
$agents_list = [];
$sql = "
    SELECT DISTINCT u.id, CONCAT(u.first_name,' ',u.last_name) AS name
    FROM messages m
    INNER JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE u.user_type IN ('direct_agent','associate_agent')
      AND (m.sender_id = ? OR m.receiver_id = ?)
      AND u.id != ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $agents_list[$row['id']] = $row['name'];
}
$stmt->close();

// ✅ Fetch conversation messages with this agent
$messages = [];
$stmt = $conn->prepare("
    SELECT sender_id, message, created_at
    FROM messages
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$stmt->bind_param("iiii", $current_user_id, $agent['id'], $agent['id'], $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

$agent_names = $agents_list;
$agent_names[$current_user_id] = 'You';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat with <?= htmlspecialchars($agent_name) ?></title>
<style>
    /* ===== Global ===== */
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #f0f2f5;
    }

    /* ===== Chat Layout ===== */
    .chat-container {
        display: flex;
        height: 100vh;
        overflow: hidden;
    }

    /* ===== Conversations List ===== */
    .conversations-list {
        width: 300px;
        border-right: 1px solid #ddd;
        overflow-y: auto;
        padding: 10px;
        background: #fff;
    }

    .conversations-list h3 {
        margin-top: 0;
        font-size: 1.2rem;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }

    .conversation-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background 0.2s;
    }

    .conversation-item:hover {
        background: #f1f1f1;
    }

    .conversation-item.unread {
        background: #e6f0ff;
        font-weight: bold;
    }

    /* ===== Chat Window ===== */
    .chat-window {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #f9f9f9;
    }

    /* ===== Chat Header ===== */
    .chat-header {
        font-size: 18px;
        background: #f5f5f5;
        border-bottom: 1px solid #ddd;
        padding: 15px;
        font-weight: bold;
        color: #333;
    }

    /* ===== Messages ===== */
    .messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .message {
        max-width: 60%;
        padding: 10px 15px;
        border-radius: 12px;
        word-break: break-word;
        position: relative;
    }

    .message.you {
        background: #007bff;
        color: #fff;
        margin-left: auto;
        border-bottom-right-radius: 0;
    }

    .message.agent {
        background: #e4e6eb;
        color: #000;
        margin-right: auto;
        border-bottom-left-radius: 0;
    }

    .message .sender {
        font-weight: bold;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .message .timestamp {
        font-size: 0.7rem;
        color: #666;
    }

    /* ===== Chat Input ===== */
    .chat-input {
        display: flex;
        border-top: 1px solid #ddd;
        padding: 10px;
        background: #fff;
    }

    .chat-input input[type="text"] {
        flex: 1;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 20px;
        outline: none;
    }

    .chat-input button {
        padding: 10px 20px;
        margin-left: 10px;
        border: none;
        background: #007bff;
        color: #fff;
        border-radius: 20px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .chat-input button:hover {
        background: #0056b3;
    }

    /* ===== Scrollbar Styling ===== */
    .messages::-webkit-scrollbar {
        width: 6px;
    }

    .messages::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.2);
        border-radius: 3px;
    }

    .messages::-webkit-scrollbar-track {
        background: transparent;
    }
</style>
</head>
<body>

<div class="chat-container">

    <!-- Conversations List -->
    <div class="conversations-list">
        <h3>Conversations</h3>
        <?php foreach ($agents_list as $id => $name): ?>
            <div class="conversation-item <?= ($id === $agent['id']) ? 'unread' : '' ?>" data-agent-id="<?= $id ?>">
                <?= htmlspecialchars($name) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Chat Window -->
    <div class="chat-window" data-agent-id="<?= $agent['id'] ?>">
        <!-- Chat Header -->
        <div class="chat-header" style="padding:15px; border-bottom:1px solid #ddd; background:#f5f5f5; font-weight:bold;">
            <?= htmlspecialchars($agent_name) ?>
        </div>

        <div class="messages" id="messages">
            <?php foreach ($messages as $msg): 
                $isYou = $msg['sender_id'] === $current_user_id;
                $senderName = htmlspecialchars($agent_names[$msg['sender_id']] ?? 'Agent');
                $timestamp = date('M d, Y H:i', strtotime($msg['created_at']));
            ?>
            <div class="message <?= $isYou ? 'you' : 'agent' ?>">
                <div class="sender"><?= $senderName ?> <span class="timestamp"><?= $timestamp ?></span>:</div>
                <div class="text"><?= htmlspecialchars(decryptMessage($msg['message'])) ?></div>
            </div>
            <?php endforeach; ?>
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
const chatWindow = document.querySelector('.chat-window');
const receiverId = chatWindow.dataset.agentId;

sendBtn.addEventListener('click', () => {
    const message = messageInput.value.trim();
    if (!message) return;

    fetch('api/send_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `receiver_id=${receiverId}&message=${encodeURIComponent(message)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const msgDiv = document.createElement('div');
            msgDiv.classList.add('message');
            msgDiv.innerHTML = `<div class="sender">You:</div><div class="text">${data.message.text}</div>`;
            messagesContainer.appendChild(msgDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            messageInput.value = '';
        } else {
            alert(data.error);
        }
    })
    .catch(err => console.error(err));
});

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
