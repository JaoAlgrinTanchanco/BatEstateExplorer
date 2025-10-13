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

    // --- Determine current user ---
    $current_user_id = $_SESSION['user_id'] ?? null;
    if (!$current_user_id) die("Not logged in.");

    // Determine role safely
    $role = $_SESSION['user_type'] ?? 'user';

    // --- Determine current conversation ---
    $contact_id = $_GET['user_id'] ?? null;

    // --- Fetch contact info ---
    $contact = null;
    $contact_name = "No conversation selected";
    $receiver_disabled = true;

    if ($contact_id) {
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name, email, profile_image_path, user_type
            FROM users
            WHERE id = ?
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

    // --- Fetch contacts list for sidebar ---
    $contacts_list = [];
    $contactsImages = [];

    if ($role === 'user') {
        $userTypeFilter = "AND u.user_type IN ('direct_agent','associate_agent')";
    } else {
        // agent sees users
        $userTypeFilter = "AND u.user_type = 'user'";
    }

    $sql = "
        SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name, u.profile_image_path
        FROM messages m
        INNER JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id)
        WHERE (m.sender_id = ? OR m.receiver_id = ?)
        AND u.id != ?
        $userTypeFilter
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

    // --- Current user profile image ---
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

    // --- Fetch conversation messages ---
    $messages = [];
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
            $row['images'] = !empty($row['image_path']) ? json_decode($row['image_path'], true) : [];
            if (!is_array($row['images'])) $row['images'] = [];
            $messages[] = $row;
        }
        $stmt->close();
    }

    // --- Names mapping ---
    $contact_names = $contacts_list;
    $contact_names[$current_user_id] = 'You';

    // --- Conversation redirect ---
    $conversation_redirect = '/BatEstateExplorer/public/message.php?user_id=';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat with <?= htmlspecialchars($contact_name) ?></title>
<link rel="stylesheet" href="../assets/css/message.css">
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
        
        <!-- Chat Header -->
        <div class="chat-header">
            <button id="sidebarToggle" class="sidebar-toggle">☰</button>
            <span class="chat-contact-name"><?= htmlspecialchars($contact_name ?? 'No conversation selected') ?></span>
        </div>

        <!-- Messages Container -->
        <div class="messages" id="messages">
            <?php foreach ($messages as $msg):
                $isYou = $msg['sender_id'] === $current_user_id;
                $senderName = htmlspecialchars($contact_names[$msg['sender_id']] ?? 'Unknown');
                $timestamp = date('M d, Y H:i', strtotime($msg['created_at']));
                $messageText = htmlspecialchars(decryptMessage($msg['message']));
            ?>
            <div class="message <?= $isYou ? 'you' : 'agent' ?>">
                <!-- Sender Avatar -->
                <div class="sender-avatar">
                    <?php if (!empty($contactsImages[$msg['sender_id']])): ?>
                        <img src="<?= htmlspecialchars($contactsImages[$msg['sender_id']]) ?>" alt="<?= $senderName ?>">
                    <?php else: ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>
                </div>

                <!-- Text Bubble -->
                <div class="text-container">
                    <div class="sender">
                        <?= $senderName ?> 
                        <span class="timestamp"><?= $timestamp ?></span>:
                    </div>

                    <div class="text">
                        <?= $messageText ?>
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

                <!-- 3-dot menu -->
                <?php if ($isYou): ?>
                <div class="message-menu">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                    <div class="dropdown hidden">
                        <button class="delete-message-btn">Delete message</button>
                    </div>
                    <div class="confirm hidden">
                        <span>Are you sure?</span>
                        <button class="confirm-delete">Yes</button>
                        <button class="cancel-delete">Cancel</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Image Preview Before Sending -->
        <div id="imagePreviewContainer" class="image-preview-container hidden"></div>

        <!-- Chat Input -->
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

<script src="/BatEstateExplorer/assets/js/message.js"></script>
<script>
    // Switch Conversations
    const conversationRedirect = '<?= $conversation_redirect ?>';
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', () => {
            const userId = item.dataset.userId;
            if (userId) window.location.href = `${conversationRedirect}${userId}`;
        });
    });
</script>

</body>
</html>
