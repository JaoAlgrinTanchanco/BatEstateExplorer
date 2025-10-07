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

<<<<<<< HEAD
// 1️⃣ Get user_id from URL (conversation switch)
$user_id = $_GET['user_id'] ?? null;

// 2️⃣ If agent_id provided, resolve to user_id
=======
// Get user_id from URL (conversation switch)
$user_id = $_GET['user_id'] ?? null;

// Resolve agent_id to user_id if provided
>>>>>>> origin/ansel
$agent_id = $_GET['agent_id'] ?? null;
if ($agent_id) {
    $stmt = $conn->prepare("SELECT user_id FROM agents WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $res = $stmt->get_result();
<<<<<<< HEAD
    if ($row = $res->fetch_assoc()) {
        $user_id = (int)$row['user_id'];
    }
    $stmt->close();
}

// 3️⃣ Fetch conversation contact info
=======
    if ($row = $res->fetch_assoc()) $user_id = (int)$row['user_id'];
    $stmt->close();
}

// Fetch conversation contact info
$contact = null;
$contact_name = null;
$chat_header = "Select a conversation";

>>>>>>> origin/ansel
if ($user_id) {
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email 
        FROM users 
<<<<<<< HEAD
        WHERE id = ?
=======
        WHERE id = ? 
>>>>>>> origin/ansel
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $contact = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($contact) {
        $contact_name = trim($contact['first_name'] . ' ' . $contact['last_name']);
        $chat_header = $contact_name;
<<<<<<< HEAD
    } else {
        $contact = null;
        $contact_name = null;
        $chat_header = "No conversation selected";
    }
} else {
    $contact = null;
    $contact_name = null;
    $chat_header = "No conversation selected";
=======
    }
}

// Function to get profile image URL
function getProfileImage($conn, $user_id) {
    $stmt = $conn->prepare("SELECT profile_image_path FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!empty($row['profile_image_path'])) {
        $fileName = basename($row['profile_image_path']);
        $filePath = __DIR__ . '/../storage/uploads/profile_images/' . $fileName;
        if (file_exists($filePath)) {
            return '/BatEstateExplorer/storage/uploads/profile_images/' . $fileName;
        }
    }
    return null;
>>>>>>> origin/ansel
}

// Fetch all contacts for conversation list
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

<<<<<<< HEAD
// Fetch messages
=======
// Map profile images for contacts
$contactsImages = [];
foreach ($contacts_list as $id => $name) {
    $contactsImages[$id] = getProfileImage($conn, $id);
}
// Optionally add current agent profile
$contactsImages[$current_user_id] = getProfileImage($conn, $current_user_id);

// Fetch conversation messages
>>>>>>> origin/ansel
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
<<<<<<< HEAD
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}

$contact_names = $contacts_list;
$contact_names[$current_user_id] = 'You';

$chat_header = $user_id ? $contact_name : "Select a conversation";
?>

=======
    while ($row = $result->fetch_assoc()) $messages[] = $row;
    $stmt->close();
}

// Add current agent name for "You"
$contact_names = $contacts_list;
$contact_names[$current_user_id] = 'You';
?>


>>>>>>> origin/ansel
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat with <?= htmlspecialchars($contact_name ?? 'Client') ?></title>
<link rel="stylesheet" href="../assets/css/agent_message.css">
</head>
<body>

<div class="chat-container">

    <!-- Conversations List -->
    <div class="conversations-list">
        <h3>Conversations</h3>
        <?php foreach ($contacts_list as $id => $name): ?>
            <div class="conversation-item <?= ($id == ($contact['id'] ?? 0)) ? 'active' : '' ?>" data-user-id="<?= $id ?>">
<<<<<<< HEAD
                <?= htmlspecialchars($name) ?>
=======
                <div class="conversation-avatar">
                    <?php if (!empty($contactsImages[$id])): ?>
                        <img src="<?= htmlspecialchars($contactsImages[$id]) ?>" alt="<?= htmlspecialchars($name) ?>">
                    <?php else: ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>
                </div>
                <span class="conversation-name"><?= htmlspecialchars($name) ?></span>
>>>>>>> origin/ansel
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Chat Window -->
    <div class="chat-window" data-user-id="<?= $contact['id'] ?? '' ?>">
        <div class="chat-header">
            <button id="sidebarToggle" class="sidebar-toggle">☰</button>
            <?= htmlspecialchars($chat_header) ?>
        </div>
<<<<<<< HEAD
=======

>>>>>>> origin/ansel
        <div class="messages" id="messages">
            <?php foreach ($messages as $msg):
                $isYou = $msg['sender_id'] === $current_user_id;
                $senderName = htmlspecialchars($contact_names[$msg['sender_id']] ?? 'Client');
                $timestamp = date('M d, Y H:i', strtotime($msg['created_at']));
            ?>
            <div class="message <?= $isYou ? 'you' : 'agent' ?>">
<<<<<<< HEAD
                <div class="sender"><?= $senderName ?> <span class="timestamp"><?= $timestamp ?></span>:</div>
                <div class="text"><?= htmlspecialchars(decryptMessage($msg['message'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
=======
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

>>>>>>> origin/ansel
        <div class="chat-input">
            <input type="text" id="messageInput" placeholder="Type your message..." <?= $contact ? '' : 'disabled' ?>>
            <button id="sendBtn" <?= $contact ? '' : 'disabled' ?>>Send</button>
        </div>
    </div>

</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
<<<<<<< HEAD
document.addEventListener('DOMContentLoaded', () => {
    const sendBtn = document.getElementById('sendBtn');
    const messageInput = document.getElementById('messageInput');
    const messagesContainer = document.getElementById('messages');
    const chatWindow = document.querySelector('.chat-window');
    const receiverId = chatWindow.dataset.userId;

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

                const rawDate = data.message.created_at
                    ? new Date(data.message.created_at)
                    : new Date();

                const options = {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                };
                const timestamp = rawDate.toLocaleString('en-US', options).replace(',', '');

                msgDiv.innerHTML = `
                    <div class="sender">
                        You <span class="timestamp">${timestamp}</span>:
                    </div>
                    <div class="text">${data.message.text}</div>
                `;

                messagesContainer.appendChild(msgDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                messageInput.value = '';
            } else {
                alert(data.error);
            }
        })
        .catch(err => console.error(err));
    };

    sendBtn?.addEventListener('click', sendMessage);
    messageInput?.addEventListener('keypress', e => {
        if (e.key === 'Enter') sendMessage();
    });

    // Sidebar toggle
    const sidebar = document.querySelector('.conversations-list');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');

    toggleBtn?.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    });

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
=======
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
>>>>>>> origin/ansel
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });
<<<<<<< HEAD
    });
});
</script>

=======

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


>>>>>>> origin/ansel
</body>
</html>
