# Laravel Chat Soul - Usage Examples

## Basic Usage Examples

### 1. Creating Conversations

```php
// In your controller
use OmarElnaghy\LaravelChatSoul\Services\ChatService;

class ChatController extends Controller
{
    public function createDirectChat(Request $request, ChatService $chatService)
    {
        $user = auth()->user();
        $otherUserId = $request->input('user_id');
        
        // Get or create direct conversation
        $conversation = $user->getOrCreateDirectConversationWith($otherUserId);
        
        return response()->json(['conversation' => $conversation]);
    }
    
    public function createGroupChat(Request $request, ChatService $chatService)
    {
        $user = auth()->user();
        
        $conversation = $chatService->createConversation($user->id, [
            'type' => 'group',
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'participant_ids' => $request->input('participant_ids'),
        ]);
        
        return response()->json(['conversation' => $conversation]);
    }
}
```

### 2. Sending Messages

```php
// Send text message
$message = $chatService->sendMessage([
    'conversation_id' => 1,
    'user_id' => auth()->id(),
    'content' => 'Hello everyone!',
    'type' => 'text'
]);

// Send message with file attachment
$message = $chatService->sendMessage([
    'conversation_id' => 1,
    'user_id' => auth()->id(),
    'content' => 'Check this out!',
], $request->file('attachment'));

// Send reply message
$message = $chatService->sendMessage([
    'conversation_id' => 1,
    'user_id' => auth()->id(),
    'content' => 'Thanks for sharing!',
    'reply_to_id' => 123
]);
```

### 3. Managing Read Receipts

```php
// Mark message as read
$message = Message::find(123);
$messageRead = $message->markAsReadBy(auth()->id());

// Mark all messages in conversation as read
$user = auth()->user();
$user->markAllAsReadInConversation(1);

// Get unread count
$conversation = Conversation::find(1);
$unreadCount = $conversation->getUnreadCountFor(auth()->id());

// Get unread messages for user
$user = auth()->user();
$unreadMessages = $user->getUnreadMessages();
```

## Frontend Integration Examples

### 1. Vue.js Complete Integration

```vue
<template>
    <div class="chat-app">
        <!-- Conversation List -->
        <div class="conversations-sidebar">
            <div 
                v-for="conversation in conversations" 
                :key="conversation.id"
                @click="selectConversation(conversation)"
                class="conversation-item"
                :class="{ active: selectedConversation?.id === conversation.id }"
            >
                <div class="conversation-info">
                    <h4>{{ conversation.name }}</h4>
                    <p>{{ conversation.latest_message?.content }}</p>
                </div>
                <div class="conversation-meta">
                    <span v-if="conversation.unread_count > 0" class="unread-badge">
                        {{ conversation.unread_count }}
                    </span>
                    <small>{{ formatTime(conversation.updated_at) }}</small>
                </div>
            </div>
        </div>
        
        <!-- Chat Component -->
        <div class="chat-main">
            <ChatSoulComponent 
                v-if="selectedConversation"
                :conversation-id="selectedConversation.id" 
                :user-id="authUser.id"
                @message-sent="onMessageSent"
                @conversation-updated="loadConversations"
            />
            <div v-else class="no-conversation">
                Select a conversation to start chatting
            </div>
        </div>
    </div>
</template>

<script>
import ChatSoulComponent from './resources/js/chat-soul/components/Chat.vue';

export default {
    components: {
        ChatSoulComponent
    },
    data() {
        return {
            conversations: [],
            selectedConversation: null,
            authUser: window.authUser
        }
    },
    async mounted() {
        await this.loadConversations();
        this.setupGlobalListeners();
    },
    methods: {
        async loadConversations() {
            try {
                const response = await fetch('/api/chat/conversations', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                this.conversations = data.data;
            } catch (error) {
                console.error('Error loading conversations:', error);
            }
        },
        
        selectConversation(conversation) {
            this.selectedConversation = conversation;
        },
        
        onMessageSent(message) {
            // Update conversation list when new message is sent
            this.loadConversations();
        },
        
        setupGlobalListeners() {
            // Listen for new conversations
            Echo.private(`chat-soul-user.${this.authUser.id}`)
                .listen('ChatSoul.ConversationCreated', (e) => {
                    this.conversations.unshift(e.conversation);
                });
        },
        
        formatTime(timestamp) {
            return new Date(timestamp).toLocaleTimeString([], { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        }
    }
}
</script>

<style scoped>
.chat-app {
    display: flex;
    height: 100vh;
}

.conversations-sidebar {
    width: 300px;
    border-right: 1px solid #e1e5e9;
    overflow-y: auto;
}

.conversation-item {
    display: flex;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
}

.conversation-item:hover,
.conversation-item.active {
    background: #f8f9fa;
}

.chat-main {
    flex: 1;
}

.no-conversation {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6c757d;
}

.unread-badge {
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
</style>
```

### 2. React Complete Integration

```jsx
import React, { useState, useEffect } from 'react';
import ChatSoulComponent from './resources/js/chat-soul/components/Chat.jsx';

function ChatApp() {
    const [conversations, setConversations] = useState([]);
    const [selectedConversation, setSelectedConversation] = useState(null);
    const [authUser] = useState(window.authUser);

    useEffect(() => {
        loadConversations();
        setupGlobalListeners();
    }, []);

    const loadConversations = async () => {
        try {
            const response = await fetch('/api/chat/conversations', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            setConversations(data.data);
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    };

    const setupGlobalListeners = () => {
        window.Echo.private(`chat-soul-user.${authUser.id}`)
            .listen('ChatSoul.ConversationCreated', (e) => {
                setConversations(prev => [e.conversation, ...prev]);
            });
    };

    const handleMessageSent = (message) => {
        loadConversations();
    };

    const formatTime = (timestamp) => {
        return new Date(timestamp).toLocaleTimeString([], { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    };

    return (
        <div className="chat-app">
            <div className="conversations-sidebar">
                {conversations.map(conversation => (
                    <div 
                        key={conversation.id}
                        onClick={() => setSelectedConversation(conversation)}
                        className={`conversation-item ${selectedConversation?.id === conversation.id ? 'active' : ''}`}
                    >
                        <div className="conversation-info">
                            <h4>{conversation.name}</h4>
                            <p>{conversation.latest_message?.content}</p>
                        </div>
                        <div className="conversation-meta">
                            {conversation.unread_count > 0 && (
                                <span className="unread-badge">
                                    {conversation.unread_count}
                                </span>
                            )}
                            <small>{formatTime(conversation.updated_at)}</small>
                        </div>
                    </div>
                ))}
            </div>
            
            <div className="chat-main">
                {selectedConversation ? (
                    <ChatSoulComponent 
                        conversationId={selectedConversation.id} 
                        userId={authUser.id}
                        onMessageSent={handleMessageSent}
                    />
                ) : (
                    <div className="no-conversation">
                        Select a conversation to start chatting
                    </div>
                )}
            </div>
        </div>
    );
}

export default ChatApp;
```

### 3. Custom Event Handling

```javascript
// Custom Echo setup with all events
import { ChatSoulEcho } from './resources/js/chat-soul/echo-setup.js';

class CustomChatHandler {
    constructor(userId) {
        this.userId = userId;
        this.chatEcho = new ChatSoulEcho();
        this.setupGlobalListeners();
    }

    setupGlobalListeners() {
        // Join presence channel
        this.chatEcho.joinPresence({
            onHere: (users) => {
                console.log('Users currently online:', users);
                this.updateOnlineUsers(users);
            },
            onJoining: (user) => {
                console.log('User came online:', user.name);
                this.showNotification(`${user.name} is now online`);
            },
            onLeaving: (user) => {
                console.log('User went offline:', user.name);
                this.updateUserOffline(user.id);
            }
        });
    }

    joinConversation(conversationId) {
        return this.chatEcho.joinConversation(conversationId, {
            onMessageSent: (e) => {
                console.log('New message received:', e.message);
                this.handleNewMessage(e.message);
                
                // Show browser notification if page is not visible
                if (document.hidden && e.message.user.id !== this.userId) {
                    this.showBrowserNotification(
                        `New message from ${e.message.user.name}`,
                        e.message.content
                    );
                }
            },
            onUserTyping: (e) => {
                if (e.user.id !== this.userId) {
                    this.handleTypingIndicator(e.user, e.is_typing);
                }
            },
            onMessageRead: (e) => {
                console.log('Message read by:', e.user.name);
                this.updateMessageReadStatus(e.message_id, e.user);
            }
        });
    }

    handleNewMessage(message) {
        // Custom message handling logic
        this.playNotificationSound();
        this.updateConversationList();
        this.scrollToBottom();
    }

    handleTypingIndicator(user, isTyping) {
        const indicator = document.getElementById('typing-indicator');
        if (isTyping) {
            indicator.textContent = `${user.name} is typing...`;
            indicator.style.display = 'block';
        } else {
            indicator.style.display = 'none';
        }
    }

    showBrowserNotification(title, body) {
        if (Notification.permission === 'granted') {
            new Notification(title, {
                body: body,
                icon: '/path/to/chat-icon.png'
            });
        }
    }

    playNotificationSound() {
        const audio = new Audio('/path/to/notification-sound.mp3');
        audio.play().catch(e => console.log('Could not play sound:', e));
    }
}

// Usage
const chatHandler = new CustomChatHandler(authUser.id);
chatHandler.joinConversation(1);
```

## API Usage Examples

### 1. Advanced Message Operations

```javascript
// Send message with metadata
const sendAdvancedMessage = async (conversationId, content, metadata = {}) => {
    const formData = new FormData();
    formData.append('content', content);
    formData.append('metadata', JSON.stringify(metadata));
    
    const response = await fetch(`/api/chat/conversations/${conversationId}/messages`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
        },
        body: formData
    });
    
    return response.json();
};

// Search messages
const searchMessages = async (query, type = 'all') => {
    const response = await fetch(`/api/chat/search?query=${encodeURIComponent(query)}&type=${type}`, {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
        }
    });
    
    return response.json();
};

// Get conversation statistics
const getConversationStats = async (conversationId) => {
    const response = await fetch(`/api/chat/conversations/${conversationId}/stats`, {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
        }
    });
    
    return response.json();
};
```

### 2. Presence Management

```javascript
// Advanced presence handling
class PresenceManager {
    constructor() {
        this.onlineUsers = new Set();
        this.setupPresenceTracking();
    }

    setupPresenceTracking() {
        // Set user online when page loads
        this.setOnline();
        
        // Set offline when page unloads
        window.addEventListener('beforeunload', () => {
            this.setOffline();
        });
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.setOffline();
            } else {
                this.setOnline();
            }
        });
        
        // Periodic heartbeat
        setInterval(() => {
            if (!document.hidden) {
                this.updateLastSeen();
            }
        }, 30000); // Every 30 seconds
    }

    async setOnline() {
        try {
            await fetch('/api/chat/presence/online', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Accept': 'application/json'
                }
            });
        } catch (error) {
            console.error('Error setting online status:', error);
        }
    }

    async setOffline() {
        try {
            await fetch('/api/chat/presence/offline', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Accept': 'application/json'
                }
            });
        } catch (error) {
            console.error('Error setting offline status:', error);
        }
    }

    async updateLastSeen() {
        try {
            await fetch('/api/chat/presence/update-last-seen', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Accept': 'application/json'
                }
            });
        } catch (error) {
            console.error('Error updating last seen:', error);
        }
    }

    async getOnlineUsers() {
        try {
            const response = await fetch('/api/chat/presence/online-users', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            return data.online_users;
        } catch (error) {
            console.error('Error getting online users:', error);
            return [];
        }
    }
}

// Initialize presence manager
const presenceManager = new PresenceManager();
```

### 3. File Upload with Progress

```javascript
// File upload with progress tracking
const uploadFileMessage = async (conversationId, file, content = '', onProgress = null) => {
    const formData = new FormData();
    formData.append('attachment', file);
    if (content) {
        formData.append('content', content);
    }

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        
        // Track upload progress
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable && onProgress) {
                const percentComplete = (e.loaded / e.total) * 100;
                onProgress(percentComplete);
            }
        });
        
        xhr.addEventListener('load', () => {
            if (xhr.status === 200 || xhr.status === 201) {
                resolve(JSON.parse(xhr.responseText));
            } else {
                reject(new Error(`Upload failed with status ${xhr.status}`));
            }
        });
        
        xhr.addEventListener('error', () => {
            reject(new Error('Upload failed'));
        });
        
        xhr.open('POST', `/api/chat/conversations/${conversationId}/messages`);
        xhr.setRequestHeader('Authorization', `Bearer ${localStorage.getItem('auth_token')}`);
        xhr.setRequestHeader('Accept', 'application/json');
        
        xhr.send(formData);
    });
};

// Usage example
const handleFileUpload = async (file) => {
    try {
        const result = await uploadFileMessage(
            conversationId, 
            file, 
            'Check out this file!',
            (progress) => {
                console.log(`Upload progress: ${progress.toFixed(2)}%`);
                // Update progress bar in UI
                updateProgressBar(progress);
            }
        );
        
        console.log('File uploaded successfully:', result);
    } catch (error) {
        console.error('File upload failed:', error);
    }
};
```

These examples demonstrate the full capabilities of the Laravel Chat Soul package and show how to integrate it into real-world applications with advanced features like presence management, file uploads, and custom event handling.