// message.js
document.addEventListener('DOMContentLoaded', () => {
    // Element References
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

    // Image Viewer
    const viewerOverlay = document.getElementById('imageViewerOverlay');
    const viewerImg = document.getElementById('imageViewerImg');
    const thumbnailBar = document.getElementById('thumbnailBar');
    const leftArrow = viewerOverlay?.querySelector('.nav-arrow.left');
    const rightArrow = viewerOverlay?.querySelector('.nav-arrow.right');

    let currentImages = [];
    let currentIndex = 0;

    function openImageViewer() {
        viewerOverlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        renderViewerImage();
        renderThumbnails();
    }

    function closeImageViewer() {
        viewerOverlay.classList.add('hidden');
        document.body.style.overflow = '';
        viewerImg.src = '';
        thumbnailBar.innerHTML = '';
    }

    function renderViewerImage() {
        viewerImg.src = currentImages[currentIndex];
        thumbnailBar.querySelectorAll('img').forEach((thumb, i) => {
            thumb.classList.toggle('active', i === currentIndex);
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
                renderViewerImage();
            });
            thumbnailBar.appendChild(thumb);
        });
    }

    function navigateImageViewer(direction) {
        if (!currentImages.length) return;
        currentIndex = (currentIndex + direction + currentImages.length) % currentImages.length;
        renderViewerImage();
    }

    document.addEventListener('click', e => {
        const clickedImg = e.target.closest('.chat-images img');
        if (clickedImg) {
            const gallery = Array.from(clickedImg.closest('.chat-images').querySelectorAll('img'));
            currentImages = gallery.map(img => img.src);
            currentIndex = gallery.indexOf(clickedImg);
            openImageViewer();
        } else if (e.target === viewerOverlay || e.target === viewerImg) {
            closeImageViewer();
        }
    });

    leftArrow?.addEventListener('click', () => navigateImageViewer(-1));
    rightArrow?.addEventListener('click', () => navigateImageViewer(1));

    document.addEventListener('keydown', e => {
        if (!viewerOverlay || viewerOverlay.classList.contains('hidden')) return;
        if (e.key === 'ArrowLeft') navigateImageViewer(-1);
        if (e.key === 'ArrowRight') navigateImageViewer(1);
        if (e.key === 'Escape') closeImageViewer();
    });

    // Image Preview for Upload
    let selectedFiles = [];

    function renderPreviewContainer() {
        previewContainer.innerHTML = '';
        if (!selectedFiles.length) {
            previewContainer.classList.add('hidden');
            return;
        }

        const scrollWrapper = document.createElement('div');
        scrollWrapper.className = 'image-scroll-wrapper';

        selectedFiles.forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = e => {
                const thumb = document.createElement('div');
                thumb.className = 'preview-thumb';
                thumb.innerHTML = `<img src="${e.target.result}" alt="${file.name}">`;
                thumb.addEventListener('click', () => {
                    selectedFiles.splice(idx, 1);
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

    fileUpload?.addEventListener('change', e => {
        const newFiles = Array.from(e.target.files);
        selectedFiles = [...selectedFiles, ...newFiles];
        renderPreviewContainer();
        fileUpload.value = '';
    });

    // Send Message
    async function sendMessage() {
        const message = messageInput.value.trim();
        if (!message && !selectedFiles.length) return;
        if (!canSend) return alert('No conversation selected.');

        const formData = new FormData();
        formData.append('receiver_id', receiverId);
        formData.append('message', message);
        selectedFiles.forEach(file => formData.append('attachments[]', file));

        try {
            const response = await fetch('api/send_message.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (!data.success) return alert(data.error || 'Failed to send message.');

            appendMessageToDOM(data.message);
            messageInput.value = '';
            selectedFiles = [];
            previewContainer.innerHTML = '';
            previewContainer.classList.add('hidden');
        } catch (err) {
            console.error('Send message error:', err);
            alert('Unexpected error occurred while sending message.');
        }
    }

    function appendMessageToDOM(messageData) {
        // Format timestamp
        const timestamp = new Date(messageData.created_at).toLocaleString('en-US', {
            month: 'short', day: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: false
        }).replace(',', '');

        // Determine if message is from current user
        const isYou = messageData.senderId === messageData.currentUserId; // make sure to include currentUserId in messageData
        const avatarHTML = isYou
            ? `<img src="${CURRENT_USER_AVATAR}" alt="You">`
            : (messageData.avatarUrl ? `<img src="${messageData.avatarUrl}" alt="${messageData.senderName}">` : '<i class="fa-solid fa-user"></i>');

        // Build images HTML if any
        let imagesHTML = '';
        if (messageData.images?.length) {
            const multiple = messageData.images.length > 1;
            imagesHTML = `<div class="chat-images ${multiple ? 'multiple' : 'single'}">
                ${messageData.images.map(img => `<img src="${img}" alt="sent image" loading="lazy">`).join('')}
            </div>`;
        }

        // Create message element
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('message', isYou ? 'you' : 'agent');

        msgDiv.innerHTML = `
            <div class="sender-avatar">${avatarHTML}</div>
            <div class="text-container">
                <div class="sender">${isYou ? 'You' : messageData.senderName} <span class="timestamp">${timestamp}</span>:</div>
                ${messageData.text ? `<div class="text">${messageData.text}</div>` : ''}
                ${imagesHTML}
            </div>
        `;

        // Append to messages container
        messagesContainer.appendChild(msgDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    sendBtn?.addEventListener('click', sendMessage);
    messageInput?.addEventListener('keypress', e => { if (e.key === 'Enter') sendMessage(); });

    // Sidebar Toggle
    toggleBtn?.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    });

    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });


    // 3-dot menu toggle for "You" messages
    document.querySelectorAll('.message.you .options-menu').forEach(icon => {
        icon.addEventListener('click', () => {
            const dropdown = icon.nextElementSibling; // assumes dropdown is next
            dropdown.classList.toggle('active');
        });
    });

    // Close dropdown if clicking outside
    document.addEventListener('click', e => {
        document.querySelectorAll('.message .dropdown.active').forEach(drop => {
            if (!drop.contains(e.target) && !drop.previousElementSibling.contains(e.target)) {
                drop.classList.remove('active');
            }
        });
    });

    // Always scroll to bottom on page load
    if (messagesContainer) {
        // Scroll immediately
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        // Handle slow rendering (images, etc.)
        window.addEventListener('load', () => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });

        // Also scroll again after short delay (for any late DOM paints)
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 300);
    }
});
