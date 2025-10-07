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

// Logged-in user
$current_user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
if (!$current_user_id) die("User not logged in.");

// Get agent_id from URL
$agent_id = $_GET['user_id'] ?? null;

// Resolve agent_id if agent_id parameter exists
if ($agent_id === null && !empty($_GET['agent_id'])) {
    $stmt = $conn->prepare("SELECT user_id FROM agents WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_GET['agent_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) $agent_id = (int)$row['user_id'];
}

// Initialize defaults
$agent = null;
$agent_name = "No conversation selected";
$messages = [];
$receiver_disabled = true;

// Fetch agent info
if ($agent_id) {
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email, profile_image_path
        FROM users
        WHERE id = ? AND user_type IN ('direct_agent','associate_agent')
        LIMIT 1
    ");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $agent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($agent) {
        $agent_name = trim($agent['first_name'] . ' ' . $agent['last_name']);
        $receiver_disabled = false;
    }
}

// Fetch all agents for conversation list
$agents_list = [];
$contactsImages = [];
$sql = "
    SELECT DISTINCT u.id, CONCAT(u.first_name,' ',u.last_name) AS name, u.profile_image_path
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
    $contactsImages[$row['id']] = !empty($row['profile_image_path'])
        ? '/BatEstateExplorer/storage/uploads/profile_images/' . basename($row['profile_image_path'])
        : null;
}
$stmt->close();

// Current user profile image
function getProfileImage($conn, $user_id) {
    $stmt = $conn->prepare("SELECT profile_image_path FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($row['profile_image_path']) && file_exists(__DIR__ . '/../storage/uploads/profile_images/' . basename($row['profile_image_path']))) {
        return '/BatEstateExplorer/storage/uploads/profile_images/' . basename($row['profile_image_path']);
    }
    return null;
}
$contactsImages[$current_user_id] = getProfileImage($conn, $current_user_id);

// Fetch conversation messages
if ($agent) {
    $stmt = $conn->prepare("
        SELECT sender_id, message, created_at
        FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt->bind_param("iiii", $current_user_id, $agent['id'], $agent['id'], $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $messages[] = $row;
    $stmt->close();
}

// Names mapping for display
$contact_names = $agents_list;
$contact_names[$current_user_id] = 'You';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat with <?= htmlspecialchars($agent_name) ?></title>
<link rel="stylesheet" href="../assets/css/agent_message.css">
</head>
<body>

<div class="chat-container">

    <!-- Conversations Sidebar -->
    <div class="conversations-list">
        <h3>Conversations</h3>
        <?php foreach ($agents_list as $id => $name): ?>
            <div class="conversation-item <?= ($id == ($agent['id'] ?? 0)) ? 'active' : '' ?>" data-user-id="<?= $id ?>">
                <div class="conversation-avatar">
                    <?php if (!empty($contactsImages[$id])): ?>
                        <img src="<?= htmlspecialchars($contactsImages[$id]) ?>" alt="<?= htmlspecialchars($name) ?>">
                    <?php else: ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>
                </div>
                <span class="conversation-name"><?= htmlspecialchars($name) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Chat Window -->
    <div class="chat-window" data-user-id="<?= $agent['id'] ?? '' ?>">
        <div class="chat-header">
            <button id="sidebarToggle" class="sidebar-toggle">☰</button>
            <?= htmlspecialchars($agent_name) ?>
        </div>

        <div class="messages" id="messages">
            <?php foreach ($messages as $msg):
                $isYou = $msg['sender_id'] === $current_user_id;
                $senderName = htmlspecialchars($contact_names[$msg['sender_id']] ?? 'Agent');
                $timestamp = date('M d, Y H:i', strtotime($msg['created_at']));
            ?>
            <div class="message <?= $isYou ? 'you' : 'agent' ?>">
                <div class="sender-avatar">
                    <?php if (!empty($contactsImages[$msg['sender_id']])): ?>
                        <img src="<?= htmlspecialchars($contactsImages[$msg['sender_id']]) ?>" alt="<?= htmlspecialchars($senderName) ?>">
                    <?php else: ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="text-container">
                    <div class="sender"><?= $senderName ?> <span class="timestamp"><?= $timestamp ?></span>:</div>
                    <div class="text"><?= htmlspecialchars(decryptMessage($msg['message'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="chat-input">
            <input type="text" id="messageInput" placeholder="Type your message..." <?= $receiver_disabled ? 'disabled' : '' ?>>
            <button id="sendBtn" <?= $receiver_disabled ? 'disabled' : '' ?>>Send</button>
        </div>
    </div>

</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sendBtn = document.getElementById('sendBtn');
        const messageInput = document.getElementById('messageInput');
        const messagesContainer = document.getElementById('messages');
        const chatWindow = document.querySelector('.chat-window');
        const receiverId = chatWindow.dataset.userId;

        const sidebar = document.querySelector('.conversations-list');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');

        const canSend = receiverId && receiverId !== "";

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

                    const rawDate = data.message.created_at ? new Date(data.message.created_at) : new Date();
                    const options = { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false };
                    const timestamp = rawDate.toLocaleString('en-US', options).replace(',', '');

                    const userAvatar = '<?= htmlspecialchars($agentsImages[$current_user_id] ?? $contactsImages[$current_user_id] ?? "") ?>';
                    const avatarHTML = userAvatar ? `<img src="${userAvatar}" alt="You">` : '<i class="fa-solid fa-user"></i>';

                    msgDiv.innerHTML = `
                        <div class="sender-avatar">${avatarHTML}</div>
                        <div class="text-container">
                            <div class="sender">You <span class="timestamp">${timestamp}</span>:</div>
                            <div class="text">${data.message.text}</div>
                        </div>
                    `;

                    messagesContainer.appendChild(msgDiv);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    messageInput.value = '';
                } else alert(data.error);
            })
            .catch(err => console.error(err));
        };

        sendBtn?.addEventListener('click', sendMessage);
        messageInput?.addEventListener('keypress', e => { if(e.key === 'Enter') sendMessage(); });

        // Toggle sidebar manually
        toggleBtn?.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });

        // Collapse sidebar when clicking outside of it
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });

        // Optional: click overlay also closes
        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });

        // Switch conversations
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.userId;
                if (userId) {
                    window.location.href = `/BatEstateExplorer/public/agent_message.php?user_id=${userId}`;
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                }
            });
        });
    });
</script>

</body>
</html>
