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
$stmt = $conn->prepare("
    SELECT sender_id, message, image_path, created_at
    FROM messages
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$stmt->bind_param("iiii", $current_user_id, $agent['id'], $agent['id'], $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // Decode image paths if present
    if (!empty($row['image_path'])) {
        $row['images'] = json_decode($row['image_path'], true);
        if (!is_array($row['images'])) $row['images'] = [];
    } else {
        $row['images'] = [];
    }
    $messages[] = $row;
}
$stmt->close();

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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                    <div class="text">
                        <?= htmlspecialchars(decryptMessage($msg['message'])) ?>
                        <?php if (!empty($msg['images'])): ?>
                            <?php $isMultiple = count($msg['images']) > 1; ?>
                            <div class="chat-images <?= $isMultiple ? 'multiple' : 'single' ?>">
                                <?php foreach ($msg['images'] as $imgPath): ?>
                                    <img src="<?= htmlspecialchars($imgPath) ?>" alt="Message image" loading="lazy">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="imagePreviewContainer" class="image-preview-container hidden"></div>
        <div class="chat-input">
            <div class="file-upload-wrapper">
                <label for="fileUpload" class="file-upload-label">
                <i class="fa-solid fa-paperclip"></i>
                </label>
                <input type="file" id="fileUpload" name="attachments[]" multiple>
            </div>

            <input type="text" id="messageInput" placeholder="Type your message..." />
            <button id="sendBtn">Send</button>
        </div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Image Viewer Overlay -->
<div id="imageViewerOverlay" class="image-viewer-overlay hidden">
  <div class="image-viewer-backdrop"></div>
  <img id="imageViewerImg" src="" alt="Preview" />
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sendBtn = document.getElementById('sendBtn');
        const messageInput = document.getElementById('messageInput');
        const messagesContainer = document.getElementById('messages');
        const chatWindow = document.querySelector('.chat-window');
        const receiverId = chatWindow?.dataset.userId;
        const fileUpload = document.getElementById('fileUpload');
        const previewContainer = document.getElementById('imagePreviewContainer');

        const sidebar = document.querySelector('.conversations-list');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');

        const canSend = Boolean(receiverId);

        // --- Image Viewer Logic ---
        const viewerOverlay = document.getElementById('imageViewerOverlay');
        const viewerImg = document.getElementById('imageViewerImg');

        document.addEventListener('click', (e) => {
        if (e.target.matches('.chat-images img')) {
            viewerImg.src = e.target.src;
            viewerOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // prevent scroll
        } else if (e.target === viewerOverlay || e.target === viewerImg) {
            viewerOverlay.classList.add('hidden');
            viewerImg.src = '';
            document.body.style.overflow = '';
        }
        });


        // --- Image Preview Logic ---
        fileUpload?.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            previewContainer.innerHTML = '';

            if (files.length === 0) {
                previewContainer.classList.add('hidden');
                return;
            }

            files.forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = (event) => {
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.title = file.name;
                    img.addEventListener('click', () => {
                        img.remove();
                        if (previewContainer.children.length === 0) {
                            previewContainer.classList.add('hidden');
                            fileUpload.value = '';
                        }
                    });
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            });

            previewContainer.classList.remove('hidden');
        });

        // --- Send Message Function ---
        const sendMessage = async () => {
            const message = messageInput.value.trim();
            const files = fileUpload.files;

            if (!message && files.length === 0) return;
            if (!canSend) return alert('No conversation selected.');

            const formData = new FormData();
            formData.append('receiver_id', receiverId);
            formData.append('message', message);

            for (let i = 0; i < files.length; i++) {
                formData.append('attachments[]', files[i]);
            }

            try {
                const response = await fetch('api/send_message.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (!data.success) {
                    alert(data.error || 'Failed to send message.');
                    return;
                }

                // Format timestamp
                const rawDate = new Date(data.message.created_at);
                const timestamp = rawDate.toLocaleString('en-US', {
                    month: 'short', day: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', hour12: false
                }).replace(',', '');

                // Avatar (current user)
                const userAvatar = '<?= htmlspecialchars($contactsImages[$current_user_id] ?? "") ?>';
                const avatarHTML = userAvatar 
                    ? `<img src="${userAvatar}" alt="You">`
                    : '<i class="fa-solid fa-user"></i>';

                // Create message bubble
                const msgDiv = document.createElement('div');
                msgDiv.classList.add('message', 'you');

                let imagesHTML = '';
                if (data.message.images && data.message.images.length > 0) {
                    imagesHTML = '<div class="chat-images">' + 
                        data.message.images.map(img => `<img src="${img}" alt="sent image" class="sent-image">`).join('') + 
                        '</div>';
                }

                msgDiv.innerHTML = `
                    <div class="sender-avatar">${avatarHTML}</div>
                    <div class="text-container">
                        <div class="sender">You <span class="timestamp">${timestamp}</span>:</div>
                        ${data.message.text ? `<div class="text">${data.message.text}</div>` : ''}
                        ${imagesHTML}
                    </div>
                `;

                messagesContainer.appendChild(msgDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;

                // Reset inputs
                messageInput.value = '';
                fileUpload.value = '';
                previewContainer.innerHTML = '';
                previewContainer.classList.add('hidden');
            } catch (err) {
                console.error('Send message error:', err);
                alert('Unexpected error occurred while sending message.');
            }
        };

        sendBtn?.addEventListener('click', sendMessage);
        messageInput?.addEventListener('keypress', e => {
            if (e.key === 'Enter') sendMessage();
        });

        // --- Sidebar Toggle ---
        toggleBtn?.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });

        // --- Switch Conversations ---
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.userId;
                if (userId) window.location.href = `/BatEstateExplorer/public/agent_message.php?user_id=${userId}`;
            });
        });
    });
</script>

</body>
</html>
