<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

$page = 'chat';
$page_title = 'Messages - FleetVision';
$page_css = 'dashboard.css';
$show_sidebar = true;

$current_user_id = $_SESSION['user_id'];

// Fetch all users except the current one
$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id != ? ORDER BY username ASC");
$stmt->execute([$current_user_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<style>
.chat-container {
    display: flex;
    height: calc(100vh - 160px);
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.chat-sidebar {
    width: 300px;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
}

.chat-sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
    background: white;
}

.chat-sidebar-header h3 {
    margin: 0;
    font-size: 16px;
    color: #0f172a;
}

.user-list {
    flex: 1;
    overflow-y: auto;
}

.user-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    cursor: pointer;
    border-bottom: 1px solid #e2e8f0;
    transition: background 0.2s;
}

.user-item:hover {
    background: #f1f5f9;
}

.user-item.active {
    background: #e2e8f0;
    border-right: 3px solid #3b82f6;
}

.chat-user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.chat-user-info {
    flex: 1;
}

.chat-username {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 4px;
}

.chat-user-role {
    font-size: 12px;
    color: #64748b;
}

.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
}

.chat-header {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-header h3 {
    margin: 0;
    font-size: 16px;
    color: #0f172a;
}

.chat-messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: #f8fafc;
}

.message-wrapper {
    display: flex;
    flex-direction: column;
    max-width: 70%;
}

.message-wrapper.sent {
    align-self: flex-end;
    align-items: flex-end;
}

.message-wrapper.received {
    align-self: flex-start;
    align-items: flex-start;
}

.message-bubble {
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 14px;
    line-height: 1.5;
}

.message-wrapper.sent .message-bubble {
    background: #3b82f6;
    color: white;
    border-bottom-right-radius: 4px;
}

.message-wrapper.received .message-bubble {
    background: white;
    color: #0f172a;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
}

.message-time {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
}

.chat-input-area {
    padding: 20px;
    border-top: 1px solid #e2e8f0;
    background: white;
    display: flex;
    gap: 12px;
}

.chat-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 20px;
    font-size: 14px;
    font-family: inherit;
    resize: none;
}

.chat-input:focus {
    outline: none;
    border-color: #3b82f6;
}

.btn-send {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-send:hover {
    background: #2563eb;
}

.no-chat-selected {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 16px;
}

.no-chat-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

@media (max-width: 768px) {
    .chat-container {
        flex-direction: column;
        height: calc(100vh - 100px);
    }
    .chat-sidebar {
        width: 100%;
        height: 200px;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
    }
    .chat-main {
        height: calc(100% - 200px);
    }
}
</style>

<header class="top-header" style="margin-bottom: 20px;">
    <div class="header-left">
        <h1 class="page-title">Messages</h1>
        <p class="page-subtitle">Communicate with your team</p>
    </div>
</header>

<section class="content-section" style="margin-bottom: 0;">
    <div class="chat-container">
        <!-- Sidebar: User List -->
        <div class="chat-sidebar">
            <div class="chat-sidebar-header">
                <h3>Contacts</h3>
            </div>
            <div class="user-list">
                <?php foreach ($users as $user): ?>
                    <div class="user-item" data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['username']); ?>">
                        <div class="chat-user-avatar">
                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                        </div>
                        <div class="chat-user-info">
                            <div class="chat-username"><?php echo htmlspecialchars($user['username']); ?></div>
                            <div class="chat-user-role"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main: Chat Area -->
        <div class="chat-main" id="chat-main" style="display: none;">
            <div class="chat-header">
                <div class="chat-user-avatar" id="active-chat-avatar"></div>
                <h3 id="active-chat-name"></h3>
            </div>
            
            <div class="chat-messages" id="chat-messages">
                <!-- Messages will be loaded here via AJAX -->
            </div>
            
            <div class="chat-input-area">
                <input type="text" id="message-input" class="chat-input" placeholder="Type a message..." autocomplete="off">
                <button id="btn-send" class="btn-send">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Placeholder when no user is selected -->
        <div class="no-chat-selected" id="no-chat-selected">
            <div class="no-chat-icon">💬</div>
            <div>Select a contact to start messaging</div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userItems = document.querySelectorAll('.user-item');
    const chatMain = document.getElementById('chat-main');
    const noChatSelected = document.getElementById('no-chat-selected');
    const activeChatName = document.getElementById('active-chat-name');
    const activeChatAvatar = document.getElementById('active-chat-avatar');
    const chatMessages = document.getElementById('chat-messages');
    const messageInput = document.getElementById('message-input');
    const btnSend = document.getElementById('btn-send');
    
    let activeUserId = null;
    let pollInterval = null;

    // Handle user selection
    userItems.forEach(item => {
        item.addEventListener('click', function() {
            // Update active state
            userItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // Get user data
            activeUserId = this.getAttribute('data-id');
            const username = this.getAttribute('data-name');
            
            // Update UI
            activeChatName.textContent = username;
            activeChatAvatar.textContent = username.substring(0, 2).toUpperCase();
            noChatSelected.style.display = 'none';
            chatMain.style.display = 'flex';
            
            // Initial load of messages
            loadMessages();
            
            // Start polling for new messages every 3 seconds
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(loadMessages, 3000);
            
            // Focus input
            messageInput.focus();
        });
    });

    // Send message on Enter key or button click
    btnSend.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });

    function loadMessages() {
        if (!activeUserId) return;
        
        fetch('api_chat.php?action=fetch&receiver_id=' + activeUserId)
            .then(response => response.json())
            .then(data => {
                const isScrolledToBottom = chatMessages.scrollHeight - chatMessages.clientHeight <= chatMessages.scrollTop + 50;
                
                chatMessages.innerHTML = '';
                if (data.status === 'success' && data.messages) {
                    data.messages.forEach(msg => {
                        const isSent = msg.sender_id == <?php echo $current_user_id; ?>;
                        const wrapperClass = isSent ? 'sent' : 'received';
                        
                        // Format time (e.g. "14:30")
                        const date = new Date(msg.created_at);
                        const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        
                        const msgHtml = `
                            <div class="message-wrapper ${wrapperClass}">
                                <div class="message-bubble">${escapeHtml(msg.message)}</div>
                                <div class="message-time">${timeStr}</div>
                            </div>
                        `;
                        chatMessages.insertAdjacentHTML('beforeend', msgHtml);
                    });
                }
                
                // Auto-scroll if we were already at the bottom
                if (isScrolledToBottom) {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            })
            .catch(err => console.error("Error loading messages:", err));
    }

    function sendMessage() {
        const text = messageInput.value.trim();
        if (!text || !activeUserId) return;
        
        messageInput.value = '';
        messageInput.focus();
        
        const formData = new FormData();
        formData.append('action', 'send');
        formData.append('receiver_id', activeUserId);
        formData.append('message', text);
        
        fetch('api_chat.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                loadMessages();
            }
        })
        .catch(err => console.error("Error sending message:", err));
    }

    // Utility to prevent XSS in chat messages
    function escapeHtml(unsafe) {
        return unsafe.replace(/&/g, "&amp;")
                     .replace(/</g, "&lt;")
                     .replace(/>/g, "&gt;")
                     .replace(/"/g, "&quot;")
                     .replace(/'/g, "&#039;");
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
