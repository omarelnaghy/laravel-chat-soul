<template>
  <div class="chat-container">
    <!-- Chat Header -->
    <div class="chat-header">
      <div class="conversation-info">
        <h3>{{ conversation.name }}</h3>
        <div class="participants">
          <span v-for="participant in conversation.participants" :key="participant.id" class="participant">
            <img :src="participant.avatar" :alt="participant.name" class="avatar-sm">
            <span class="name">{{ participant.name }}</span>
            <span v-if="isUserOnline(participant.id)" class="status online"></span>
            <span v-else class="status offline"></span>
          </span>
        </div>
      </div>
      <div class="chat-actions">
        <button @click="toggleSettings" class="btn-icon">⚙️</button>
      </div>
    </div>

    <!-- Messages Container -->
    <div ref="messagesContainer" class="messages-container" @scroll="handleScroll">
      <div v-if="isLoadingMessages" class="loading">Loading messages...</div>
      
      <div v-for="message in messages" :key="message.id" class="message" :class="getMessageClass(message)">
        <div class="message-avatar">
          <img :src="message.user.avatar" :alt="message.user.name" class="avatar">
        </div>
        <div class="message-content">
          <div class="message-header">
            <span class="sender-name">{{ message.user.name }}</span>
            <span class="timestamp">{{ formatTime(message.created_at) }}</span>
          </div>
          
          <!-- Reply indicator -->
          <div v-if="message.is_reply" class="reply-indicator">
            <span>Replying to:</span>
            <div class="reply-content">{{ message.reply_to.content }}</div>
          </div>
          
          <!-- Message text -->
          <div v-if="message.content" class="message-text">{{ message.content }}</div>
          
          <!-- Attachment -->
          <div v-if="message.attachment" class="message-attachment">
            <div v-if="message.type === 'image'" class="image-attachment">
              <img :src="message.attachment.url" :alt="message.attachment.name" @click="openImage(message.attachment.url)">
            </div>
            <div v-else class="file-attachment">
              <a :href="message.attachment.url" :download="message.attachment.name" class="file-link">
                📎 {{ message.attachment.name }} ({{ message.attachment.formatted_size }})
              </a>
            </div>
          </div>
          
          <!-- Read receipts -->
          <div v-if="message.read_count > 0 && showReadReceipts" class="read-receipts">
            <span class="read-count">✓✓ {{ message.read_count }}</span>
            <div class="read-by-users">
              <span v-for="user in message.read_by" :key="user.id" class="read-by-user">
                {{ user.name }}
              </span>
            </div>
          </div>
        </div>
        
        <!-- Message actions -->
        <div class="message-actions">
          <button @click="replyToMessage(message)" class="btn-icon" title="Reply">↩️</button>
          <button v-if="message.can_edit" @click="editMessage(message)" class="btn-icon" title="Edit">✏️</button>
          <button v-if="message.can_delete" @click="deleteMessage(message)" class="btn-icon" title="Delete">🗑️</button>
        </div>
      </div>
    </div>

    <!-- Typing Indicators -->
    <div v-if="typingUsers.length > 0" class="typing-indicators">
      <div class="typing-indicator">
        <span>{{ getTypingText() }}</span>
        <div class="dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </div>

    <!-- Message Input -->
    <div class="message-input-container">
      <!-- Reply preview -->
      <div v-if="replyingTo" class="reply-preview">
        <div class="reply-content">
          <span>Replying to {{ replyingTo.user.name }}: {{ replyingTo.content }}</span>
        </div>
        <button @click="cancelReply" class="btn-cancel">×</button>
      </div>
      
      <div class="message-input">
        <input
          ref="fileInput"
          type="file"
          @change="handleFileSelect"
          accept="image/*,.pdf,.doc,.docx,.txt"
          style="display: none"
        >
        
        <button @click="$refs.fileInput.click()" class="btn-icon" title="Attach file">📎</button>
        
        <textarea
          v-model="newMessage"
          @keydown="handleKeyDown"
          @input="handleTyping"
          placeholder="Type a message..."
          rows="1"
          class="message-textarea"
        ></textarea>
        
        <button @click="sendMessage" :disabled="!canSendMessage" class="btn-send">Send</button>
      </div>
      
      <!-- File preview -->
      <div v-if="selectedFile" class="file-preview">
        <div class="file-info">
          <span>{{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})</span>
        </div>
        <button @click="removeFile" class="btn-cancel">×</button>
      </div>
    </div>
  </div>
</template>

<script>
import chatSoulEcho from '../echo-setup.js';

export default {
  name: 'ChatSoulComponent',
  props: {
    conversationId: {
      type: Number,
      required: true
    },
    userId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      conversation: {},
      messages: [],
      newMessage: '',
      selectedFile: null,
      replyingTo: null,
      typingUsers: [],
      onlineUsers: [],
      isLoadingMessages: false,
      isTyping: false,
      typingTimeout: null,
      showReadReceipts: true,
      page: 1,
      hasMoreMessages: true
    };
  },
  computed: {
    canSendMessage() {
      return (this.newMessage.trim() || this.selectedFile) && !this.isSending;
    }
  },
  mounted() {
    this.initializeChat();
  },
  beforeUnmount() {
    this.cleanup();
  },
  methods: {
    async initializeChat() {
      await this.loadConversation();
      await this.loadMessages();
      this.setupEcho();
      this.joinPresence();
    },

    async loadConversation() {
      try {
        const response = await fetch(`/api/chat/conversations/${this.conversationId}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
          }
        });
        const data = await response.json();
        this.conversation = data.data;
      } catch (error) {
        console.error('Error loading conversation:', error);
      }
    },

    async loadMessages() {
      this.isLoadingMessages = true;
      try {
        const response = await fetch(`/api/chat/conversations/${this.conversationId}/messages?page=${this.page}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
          }
        });
        const data = await response.json();
        
        if (this.page === 1) {
          this.messages = data.data.reverse();
        } else {
          this.messages = [...data.data.reverse(), ...this.messages];
        }
        
        this.hasMoreMessages = data.meta.current_page < data.meta.last_page;
        
        if (this.page === 1) {
          this.$nextTick(() => {
            this.scrollToBottom();
          });
        }
      } catch (error) {
        console.error('Error loading messages:', error);
      } finally {
        this.isLoadingMessages = false;
      }
    },

    setupEcho() {
      chatSoulEcho.joinConversation(this.conversationId, {
        onMessageSent: (e) => {
          this.messages.push(e.message);
          this.$nextTick(() => {
            this.scrollToBottom();
          });
        },
        onUserTyping: (e) => {
          if (e.user.id !== this.userId) {
            if (e.is_typing) {
              if (!this.typingUsers.find(user => user.id === e.user.id)) {
                this.typingUsers.push(e.user);
              }
            } else {
              this.typingUsers = this.typingUsers.filter(user => user.id !== e.user.id);
            }
          }
        },
        onMessageRead: (e) => {
          const message = this.messages.find(msg => msg.id === e.message_id);
          if (message && message.read_by) {
            message.read_by.push(e.user);
            message.read_count++;
          }
        }
      });
    },

    joinPresence() {
      chatSoulEcho.joinPresence({
        onHere: (users) => {
          this.onlineUsers = users;
        },
        onJoining: (user) => {
          this.onlineUsers.push(user);
        },
        onLeaving: (user) => {
          this.onlineUsers = this.onlineUsers.filter(u => u.id !== user.id);
        }
      });
    },

    async sendMessage() {
      if (!this.canSendMessage) return;

      const formData = new FormData();
      formData.append('content', this.newMessage.trim());
      
      if (this.selectedFile) {
        formData.append('attachment', this.selectedFile);
      }
      
      if (this.replyingTo) {
        formData.append('reply_to_id', this.replyingTo.id);
      }

      this.isSending = true;

      try {
        const response = await fetch(`/api/chat/conversations/${this.conversationId}/messages`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
          },
          body: formData
        });

        if (response.ok) {
          this.newMessage = '';
          this.selectedFile = null;
          this.replyingTo = null;
          this.stopTyping();
        }
      } catch (error) {
        console.error('Error sending message:', error);
      } finally {
        this.isSending = false;
      }
    },

    handleKeyDown(event) {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        this.sendMessage();
      }
    },

    handleTyping() {
      if (!this.isTyping) {
        this.isTyping = true;
        chatSoulEcho.sendTyping(this.conversationId, true);
      }

      clearTimeout(this.typingTimeout);
      this.typingTimeout = setTimeout(() => {
        this.stopTyping();
      }, 2000);
    },

    stopTyping() {
      if (this.isTyping) {
        this.isTyping = false;
        chatSoulEcho.sendTyping(this.conversationId, false);
      }
      clearTimeout(this.typingTimeout);
    },

    handleFileSelect(event) {
      const file = event.target.files[0];
      if (file) {
        this.selectedFile = file;
      }
    },

    removeFile() {
      this.selectedFile = null;
      this.$refs.fileInput.value = '';
    },

    replyToMessage(message) {
      this.replyingTo = message;
      this.$refs.messageTextarea?.focus();
    },

    cancelReply() {
      this.replyingTo = null;
    },

    async editMessage(message) {
      // Implementation for editing messages
      const newContent = prompt('Edit message:', message.content);
      if (newContent && newContent !== message.content) {
        try {
          const response = await fetch(`/api/chat/conversations/${this.conversationId}/messages/${message.id}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
              'Accept': 'application/json'
            },
            body: JSON.stringify({ content: newContent })
          });

          if (response.ok) {
            const data = await response.json();
            const index = this.messages.findIndex(msg => msg.id === message.id);
            if (index !== -1) {
              this.messages[index] = data.data;
            }
          }
        } catch (error) {
          console.error('Error editing message:', error);
        }
      }
    },

    async deleteMessage(message) {
      if (confirm('Are you sure you want to delete this message?')) {
        try {
          const response = await fetch(`/api/chat/conversations/${this.conversationId}/messages/${message.id}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
              'Accept': 'application/json'
            }
          });

          if (response.ok) {
            this.messages = this.messages.filter(msg => msg.id !== message.id);
          }
        } catch (error) {
          console.error('Error deleting message:', error);
        }
      }
    },

    handleScroll() {
      const container = this.$refs.messagesContainer;
      if (container.scrollTop === 0 && this.hasMoreMessages && !this.isLoadingMessages) {
        this.page++;
        this.loadMessages();
      }
    },

    scrollToBottom() {
      const container = this.$refs.messagesContainer;
      container.scrollTop = container.scrollHeight;
    },

    getMessageClass(message) {
      return {
        'own-message': message.user.id === this.userId,
        'other-message': message.user.id !== this.userId,
        'system-message': message.is_system_message,
        'reply-message': message.is_reply
      };
    },

    getTypingText() {
      if (this.typingUsers.length === 1) {
        return `${this.typingUsers[0].name} is typing`;
      } else if (this.typingUsers.length === 2) {
        return `${this.typingUsers[0].name} and ${this.typingUsers[1].name} are typing`;
      } else {
        return `${this.typingUsers.length} people are typing`;
      }
    },

    isUserOnline(userId) {
      return this.onlineUsers.some(user => user.id === userId);
    },

    formatTime(timestamp) {
      return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },

    formatFileSize(bytes) {
      const sizes = ['B', 'KB', 'MB', 'GB'];
      let i = 0;
      while (bytes >= 1024 && i < sizes.length - 1) {
        bytes /= 1024;
        i++;
      }
      return `${bytes.toFixed(1)} ${sizes[i]}`;
    },

    openImage(url) {
      window.open(url, '_blank');
    },

    toggleSettings() {
      // Implementation for conversation settings
    },

    cleanup() {
      chatSoulEcho.leaveConversation(this.conversationId);
      chatSoulEcho.leavePresence();
      this.stopTyping();
    }
  }
};
</script>

<style scoped>
.chat-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  max-width: 800px;
  margin: 0 auto;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  overflow: hidden;
}

.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f8f9fa;
  border-bottom: 1px solid #e1e5e9;
}

.conversation-info h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
}

.participants {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.participant {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8rem;
  color: #6c757d;
}

.avatar-sm {
  width: 20px;
  height: 20px;
  border-radius: 50%;
}

.status {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status.online {
  background: #28a745;
}

.status.offline {
  background: #6c757d;
}

.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  background: #ffffff;
}

.message {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.message.own-message {
  flex-direction: row-reverse;
}

.message.own-message .message-content {
  background: #007bff;
  color: white;
  margin-left: auto;
}

.message-avatar .avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
}

.message-content {
  max-width: 70%;
  background: #f8f9fa;
  border-radius: 1rem;
  padding: 0.75rem 1rem;
}

.message-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.sender-name {
  font-weight: 600;
  font-size: 0.9rem;
}

.timestamp {
  font-size: 0.75rem;
  opacity: 0.7;
}

.reply-indicator {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 0.5rem;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
  border-left: 3px solid #007bff;
}

.message-text {
  line-height: 1.4;
}

.message-attachment {
  margin-top: 0.5rem;
}

.image-attachment img {
  max-width: 100%;
  border-radius: 0.5rem;
  cursor: pointer;
}

.file-attachment {
  padding: 0.5rem;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 0.5rem;
}

.file-link {
  text-decoration: none;
  color: inherit;
}

.read-receipts {
  margin-top: 0.5rem;
  font-size: 0.75rem;
  opacity: 0.7;
}

.message-actions {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  opacity: 0;
  transition: opacity 0.2s;
}

.message:hover .message-actions {
  opacity: 1;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 50%;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-icon:hover {
  background: rgba(0, 0, 0, 0.1);
}

.typing-indicators {
  padding: 0.5rem 1rem;
  background: #f8f9fa;
  border-top: 1px solid #e1e5e9;
}

.typing-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #6c757d;
}

.dots {
  display: flex;
  gap: 0.2rem;
}

.dots span {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #6c757d;
  animation: typing 1.5s infinite;
}

.dots span:nth-child(2) {
  animation-delay: 0.3s;
}

.dots span:nth-child(3) {
  animation-delay: 0.6s;
}

@keyframes typing {
  0%, 60%, 100% {
    opacity: 0.3;
  }
  30% {
    opacity: 1;
  }
}

.message-input-container {
  border-top: 1px solid #e1e5e9;
  background: #ffffff;
}

.reply-preview {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 1rem;
  background: #f8f9fa;
  font-size: 0.9rem;
  border-left: 3px solid #007bff;
}

.message-input {
  display: flex;
  align-items: flex-end;
  gap: 0.5rem;
  padding: 1rem;
}

.message-textarea {
  flex: 1;
  min-height: 2.5rem;
  max-height: 6rem;
  border: 1px solid #e1e5e9;
  border-radius: 1.25rem;
  padding: 0.75rem 1rem;
  resize: none;
  font-family: inherit;
}

.message-textarea:focus {
  outline: none;
  border-color: #007bff;
}

.btn-send {
  background: #007bff;
  color: white;
  border: none;
  border-radius: 1.25rem;
  padding: 0.75rem 1.5rem;
  cursor: pointer;
  font-weight: 600;
}

.btn-send:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-cancel {
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 50%;
  width: 1.5rem;
  height: 1.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.file-preview {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 1rem;
  background: #f8f9fa;
  font-size: 0.9rem;
}

.loading {
  text-align: center;
  padding: 1rem;
  color: #6c757d;
}

/* Responsive design */
@media (max-width: 768px) {
  .chat-container {
    height: 100vh;
    border: none;
    border-radius: 0;
  }
  
  .message-content {
    max-width: 85%;
  }
  
  .participants {
    font-size: 0.7rem;
  }
}
</style>