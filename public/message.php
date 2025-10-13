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

    // Determine role
    $role = $_GET['role'] ?? ($_SESSION['user']['role'] ?? 'user'); // 'user' or 'agent'
    $current_user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
    if (!$current_user_id) die("Not logged in.");

    // Determine contact id
    $contact_id = $_GET['user_id'] ?? null;
    if (empty($contact_id) && !empty($_GET['agent_id'])) {
        $stmt = $conn->prepare("SELECT user_id FROM agents WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $_GET['agent_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) $contact_id = (int)$row['user_id'];
        $stmt->close();
    }

    // Initialize
    $contact = null;
    $contact_name = "No conversation selected";
    $messages = [];
    $receiver_disabled = true;

    // Fetch contact info
    if ($contact_id) {
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name, email, profile_image_path
            FROM users
            WHERE id = ? " . ($role === 'user' ? "AND user_type IN ('direct_agent','associate_agent')" : "") . "
            LIMIT 1
        ");
        $stmt->bind_param("i", $contact_id);
        $stmt->execute();
        $contact = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($contact) {
            $contact_name = trim($contact['first_name'] . ' ' . $contact['last_name']);
            $receiver_disabled = false;
        }
    }

    // Fetch contacts list
    $contacts_list = [];
    $contactsImages = [];

    $sql = "
        SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name, u.profile_image_path
        FROM messages m
        INNER JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id)
        WHERE (m.sender_id = ? OR m.receiver_id = ?)
        AND u.id != ? " . ($role === 'user' ? "AND u.user_type IN ('direct_agent','associate_agent')" : "") . "
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $contacts_list[$row['id']] = $row['name'];
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
    if ($contact) {
        $stmt = $conn->prepare("
            SELECT sender_id, message, image_path, created_at
            FROM messages
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC
        ");
        $stmt->bind_param("iiii", $current_user_id, $contact['id'], $contact['id'], $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['image_path'])) {
                $row['images'] = json_decode($row['image_path'], true);
                if (!is_array($row['images'])) $row['images'] = [];
            } else {
                $row['images'] = [];
            }
            $messages[] = $row;
        }
        $stmt->close();
    }

    // Names mapping
    $contact_names = $contacts_list;
    $contact_names[$current_user_id] = 'You';

    // All conversation redirects now point to this single file
    $conversation_redirect = '/BatEstateExplorer/public/message.php?user_id=';
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
        <?php foreach ($contacts_list as $id => $name): ?>
            <div class="conversation-item <?= ($id == ($contact['id'] ?? 0)) ? 'active' : '' ?>" data-user-id="<?= $id ?>">
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
    <div class="chat-window" data-user-id="<?= $contact['id'] ?? '' ?>">
        <div class="chat-header">
            <button id="sidebarToggle" class="sidebar-toggle">☰</button>
            <?= htmlspecialchars($contact_name ?? 'No conversation selected') ?>
        </div>

        <div class="messages" id="messages">
            <?php foreach ($messages as $msg):
                $isYou = $msg['sender_id'] === $current_user_id;
                $senderName = htmlspecialchars($contact_names[$msg['sender_id']] ?? 'Unknown');
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

        <!-- Image preview area (before send) -->
        <div id="imagePreviewContainer" class="image-preview-container hidden"></div>

        <!-- Message input + file upload -->
        <div class="chat-input">
            <div class="file-upload-wrapper">
                <label for="fileUpload" class="file-upload-label">
                    <i class="fa-solid fa-paperclip"></i>
                </label>
                <input type="file" id="fileUpload" name="attachments[]" multiple>
            </div>

            <input type="text" id="messageInput" placeholder="Type your message..." <?= $receiver_disabled ? 'disabled' : '' ?>>
            <button id="sendBtn" <?= $receiver_disabled ? 'disabled' : '' ?>>Send</button>
        </div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Image Viewer Overlay -->
<div id="imageViewerOverlay" class="image-viewer-overlay hidden">
  <div class="image-viewer-backdrop"></div>
  <button class="nav-arrow left"><i class="fa-solid fa-chevron-left"></i></button>
  <img id="imageViewerImg" src="" alt="Preview" />
  <button class="nav-arrow right"><i class="fa-solid fa-chevron-right"></i></button>

  <div class="thumbnail-bar" id="thumbnailBar"></div>
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

        // --- Enhanced Image Viewer Logic ---
        const viewerOverlay = document.getElementById('imageViewerOverlay');
        const viewerImg = document.getElementById('imageViewerImg');
        const thumbnailBar = document.getElementById('thumbnailBar');
        const leftArrow = viewerOverlay.querySelector('.nav-arrow.left');
        const rightArrow = viewerOverlay.querySelector('.nav-arrow.right');

        let currentIndex = 0;
        let currentImages = [];

        document.addEventListener('click', (e) => {
            const clickedImg = e.target.closest('.chat-images img');
            if (clickedImg) {
                const parentGallery = Array.from(clickedImg.closest('.chat-images').querySelectorAll('img'));
                currentImages = parentGallery.map(img => img.src);
                currentIndex = parentGallery.indexOf(clickedImg);
                openImageViewer();
            } else if (e.target === viewerOverlay || e.target === viewerImg) {
                closeImageViewer();
            }
        });

        function openImageViewer() {
            viewerOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            renderImage();
            renderThumbnails();
        }

        function closeImageViewer() {
            viewerOverlay.classList.add('hidden');
            document.body.style.overflow = '';
            viewerImg.src = '';
            thumbnailBar.innerHTML = '';
        }

        function renderImage() {
            viewerImg.src = currentImages[currentIndex];
            document.querySelectorAll('.thumbnail-bar img').forEach((thumb, idx) => {
                thumb.classList.toggle('active', idx === currentIndex);
            });
        }

        function renderThumbnails() {
            thumbnailBar.innerHTML = '';
            currentImages.forEach((src, idx) => {
                const thumb = document.createElement('img');
                thumb.src = src;
                if (idx === currentIndex) thumb.classList.add('active');
                thumb.addEventListener('click', () => {
                    currentIndex = idx;
                    renderImage();
                });
                thumbnailBar.appendChild(thumb);
            });
        }

        leftArrow.addEventListener('click', () => {
            if (!currentImages.length) return;
            currentIndex = (currentIndex - 1 + currentImages.length) % currentImages.length;
            renderImage();
        });

        rightArrow.addEventListener('click', () => {
            if (!currentImages.length) return;
            currentIndex = (currentIndex + 1) % currentImages.length;
            renderImage();
        });

        document.addEventListener('keydown', (e) => {
            if (viewerOverlay.classList.contains('hidden')) return;
            if (e.key === 'ArrowLeft') leftArrow.click();
            if (e.key === 'ArrowRight') rightArrow.click();
            if (e.key === 'Escape') closeImageViewer();
        });

        // --- Image Preview Logic ---
        let selectedFiles = [];

        function renderPreviewContainer() {
            previewContainer.innerHTML = '';
            if (selectedFiles.length === 0) {
                previewContainer.classList.add('hidden');
                return;
            }

            const scrollWrapper = document.createElement('div');
            scrollWrapper.className = 'image-scroll-wrapper';

            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const thumb = document.createElement('div');
                    thumb.className = 'preview-thumb';
                    thumb.innerHTML = `<img src="${e.target.result}" alt="${file.name}">`;
                    thumb.addEventListener('click', () => {
                        selectedFiles.splice(index, 1);
                        renderPreviewContainer();
                    });
                    scrollWrapper.appendChild(thumb);
                };
                reader.readAsDataURL(file);
            });

            previewContainer.appendChild(scrollWrapper);

            const addDiv = document.createElement('div');
            addDiv.className = 'preview-add';
            addDiv.innerHTML = '+';
            addDiv.addEventListener('click', () => fileUpload.click());
            previewContainer.appendChild(addDiv);

            previewContainer.classList.remove('hidden');
        }

        fileUpload?.addEventListener('change', (e) => {
            const newFiles = Array.from(e.target.files);
            selectedFiles = [...selectedFiles, ...newFiles];
            renderPreviewContainer();
            fileUpload.value = '';
        });

        // --- Send Message Function ---
        const sendMessage = async () => {
            const message = messageInput.value.trim();
            if (!message && selectedFiles.length === 0) return;
            if (!canSend) return alert('No conversation selected.');

            const formData = new FormData();
            formData.append('receiver_id', receiverId);
            formData.append('message', message);

            selectedFiles.forEach(file => formData.append('attachments[]', file));

            try {
                const response = await fetch('api/send_message.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (!data.success) {
                    alert(data.error || 'Failed to send message.');
                    return;
                }

                const rawDate = new Date(data.message.created_at);
                const timestamp = rawDate.toLocaleString('en-US', {
                    month: 'short', day: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', hour12: false
                }).replace(',', '');

                const userAvatar = '<?= htmlspecialchars($contactsImages[$current_user_id] ?? "") ?>';
                const avatarHTML = userAvatar ? `<img src="${userAvatar}" alt="You">` : '<i class="fa-solid fa-user"></i>';

                const msgDiv = document.createElement('div');
                msgDiv.classList.add('message', 'you');

                let imagesHTML = '';
                if (data.message.images && data.message.images.length > 0) {
                    const isMultiple = data.message.images.length > 1;
                    imagesHTML = `<div class="chat-images ${isMultiple ? 'multiple' : 'single'}">
                                    ${data.message.images.map(img => `<img src="${img}" alt="sent image" loading="lazy">`).join('')}
                                </div>`;
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

                messageInput.value = '';
                fileUpload.value = '';
                selectedFiles = [];
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
        const conversationRedirect = '<?= $conversation_redirect ?>'; // dynamically set in PHP
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.userId;
                if (userId) window.location.href = `${conversationRedirect}${userId}`;
            });
        });
    });
</script>

</body>
</html>
