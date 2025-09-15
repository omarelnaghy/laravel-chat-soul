import React, { useState, useEffect, useRef } from 'react';
import chatSoulEcho from '../echo-setup.js';

const ChatSoulComponent = ({ conversationId, userId }) => {
  const [conversation, setConversation] = useState({});
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState('');
  const [selectedFile, setSelectedFile] = useState(null);
  const [replyingTo, setReplyingTo] = useState(null);
  const [typingUsers, setTypingUsers] = useState([]);
  const [onlineUsers, setOnlineUsers] = useState([]);
  const [isLoadingMessages, setIsLoadingMessages] = useState(false);
  const [isTyping, setIsTyping] = useState(false);
  const [isSending, setIsSending] = useState(false);
  const [page, setPage] = useState(1);
  const [hasMoreMessages, setHasMoreMessages] = useState(true);

  const messagesContainerRef = useRef(null);
  const fileInputRef = useRef(null);
  const messageTextareaRef = useRef(null);
  const typingTimeoutRef = useRef(null);

  useEffect(() => {
    initializeChat();
    return () => cleanup();
  }, [conversationId]);

  const initializeChat = async () => {
    await loadConversation();
    await loadMessages();
    setupEcho();
    joinPresence();
  };

  const loadConversation = async () => {
    try {
      const response = await fetch(`/api/chat/conversations/${conversationId}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': 'application/json'
        }
      });
      const data = await response.json();
      setConversation(data.data);
    } catch (error) {
      console.error('Error loading conversation:', error);
    }
  };

  const loadMessages = async () => {
    setIsLoadingMessages(true);
    try {
      const response = await fetch(`/api/chat/conversations/${conversationId}/messages?page=${page}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': 'application/json'
        }
      });
      const data = await response.json();
      
      if (page === 1) {
        setMessages(data.data.reverse());
      } else {
        setMessages(prev => [...data.data.reverse(), ...prev]);
      }
      
      setHasMoreMessages(data.meta.current_page < data.meta.last_page);
      
      if (page === 1) {
        setTimeout(() => scrollToBottom(), 100);
      }
    } catch (error) {
      console.error('Error loading messages:', error);
    } finally {
      setIsLoadingMessages(false);
    }
  };

  const setupEcho = () => {
    chatSoulEcho.joinConversation(conversationId, {
      onMessageSent: (e) => {
        setMessages(prev => [...prev, e.message]);
        setTimeout(() => scrollToBottom(), 100);
      },
      onUserTyping: (e) => {
        if (e.user.id !== userId) {
          setTypingUsers(prev => {
            if (e.is_typing) {
              return prev.find(user => user.id === e.user.id) ? prev : [...prev, e.user];
            } else {
              return prev.filter(user => user.id !== e.user.id);
            }
          });
        }
      },
      onMessageRead: (e) => {
        setMessages(prev => prev.map(msg => {
          if (msg.id === e.message_id) {
            return {
              ...msg,
              read_by: [...(msg.read_by || []), e.user],
              read_count: (msg.read_count || 0) + 1
            };
          }
          return msg;
        }));
      }
    });
  };

  const joinPresence = () => {
    chatSoulEcho.joinPresence({
      onHere: (users) => setOnlineUsers(users),
      onJoining: (user) => setOnlineUsers(prev => [...prev, user]),
      onLeaving: (user) => setOnlineUsers(prev => prev.filter(u => u.id !== user.id))
    });
  };

  const sendMessage = async () => {
    if ((!newMessage.trim() && !selectedFile) || isSending) return;

    const formData = new FormData();
    if (newMessage.trim()) {
      formData.append('content', newMessage.trim());
    }
    
    if (selectedFile) {
      formData.append('attachment', selectedFile);
    }
    
    if (replyingTo) {
      formData.append('reply_to_id', replyingTo.id);
    }

    setIsSending(true);

    try {
      const response = await fetch(`/api/chat/conversations/${conversationId}/messages`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': 'application/json'
        },
        body: formData
      });

      if (response.ok) {
        setNewMessage('');
        setSelectedFile(null);
        setReplyingTo(null);
        stopTyping();
      }
    } catch (error) {
      console.error('Error sending message:', error);
    } finally {
      setIsSending(false);
    }
  };

  const handleKeyDown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      sendMessage();
    }
  };

  const handleTyping = () => {
    if (!isTyping) {
      setIsTyping(true);
      chatSoulEcho.sendTyping(conversationId, true);
    }

    clearTimeout(typingTimeoutRef.current);
    typingTimeoutRef.current = setTimeout(() => {
      stopTyping();
    }, 2000);
  };

  const stopTyping = () => {
    if (isTyping) {
      setIsTyping(false);
      chatSoulEcho.sendTyping(conversationId, false);
    }
    clearTimeout(typingTimeoutRef.current);
  };

  const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
      setSelectedFile(file);
    }
  };

  const removeFile = () => {
    setSelectedFile(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const replyToMessage = (message) => {
    setReplyingTo(message);
    messageTextareaRef.current?.focus();
  };

  const cancelReply = () => {
    setReplyingTo(null);
  };

  const editMessage = async (message) => {
    const newContent = prompt('Edit message:', message.content);
    if (newContent && newContent !== message.content) {
      try {
        const response = await fetch(`/api/chat/conversations/${conversationId}/messages/${message.id}`, {
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
          setMessages(prev => prev.map(msg => msg.id === message.id ? data.data : msg));
        }
      } catch (error) {
        console.error('Error editing message:', error);
      }
    }
  };

  const deleteMessage = async (message) => {
    if (window.confirm('Are you sure you want to delete this message?')) {
      try {
        const response = await fetch(`/api/chat/conversations/${conversationId}/messages/${message.id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          setMessages(prev => prev.filter(msg => msg.id !== message.id));
        }
      } catch (error) {
        console.error('Error deleting message:', error);
      }
    }
  };

  const handleScroll = () => {
    const container = messagesContainerRef.current;
    if (container.scrollTop === 0 && hasMoreMessages && !isLoadingMessages) {
      setPage(prev => prev + 1);
      loadMessages();
    }
  };

  const scrollToBottom = () => {
    const container = messagesContainerRef.current;
    if (container) {
      container.scrollTop = container.scrollHeight;
    }
  };

  const getMessageClass = (message) => {
    const classes = ['message'];
    if (message.user.id === userId) classes.push('own-message');
    else classes.push('other-message');
    if (message.is_system_message) classes.push('system-message');
    if (message.is_reply) classes.push('reply-message');
    return classes.join(' ');
  };

  const getTypingText = () => {
    if (typingUsers.length === 1) {
      return `${typingUsers[0].name} is typing`;
    } else if (typingUsers.length === 2) {
      return `${typingUsers[0].name} and ${typingUsers[1].name} are typing`;
    } else {
      return `${typingUsers.length} people are typing`;
    }
  };

  const isUserOnline = (userId) => {
    return onlineUsers.some(user => user.id === userId);
  };

  const formatTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  };

  const formatFileSize = (bytes) => {
    const sizes = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    while (bytes >= 1024 && i < sizes.length - 1) {
      bytes /= 1024;
      i++;
    }
    return `${bytes.toFixed(1)} ${sizes[i]}`;
  };

  const openImage = (url) => {
    window.open(url, '_blank');
  };

  const cleanup = () => {
    chatSoulEcho.leaveConversation(conversationId);
    chatSoulEcho.leavePresence();
    stopTyping();
  };

  return (
    <div className="chat-container">
      {/* Chat Header */}
      <div className="chat-header">
        <div className="conversation-info">
          <h3>{conversation.name}</h3>
          <div className="participants">
            {conversation.participants?.map(participant => (
              <span key={participant.id} className="participant">
                <img src={participant.avatar} alt={participant.name} className="avatar-sm" />
                <span className="name">{participant.name}</span>
                <span className={`status ${isUserOnline(participant.id) ? 'online' : 'offline'}`}></span>
              </span>
            ))}
          </div>
        </div>
        <div className="chat-actions">
          <button className="btn-icon">⚙️</button>
        </div>
      </div>

      {/* Messages Container */}
      <div ref={messagesContainerRef} className="messages-container" onScroll={handleScroll}>
        {isLoadingMessages && <div className="loading">Loading messages...</div>}
        
        {messages.map(message => (
          <div key={message.id} className={getMessageClass(message)}>
            <div className="message-avatar">
              <img src={message.user.avatar} alt={message.user.name} className="avatar" />
            </div>
            <div className="message-content">
              <div className="message-header">
                <span className="sender-name">{message.user.name}</span>
                <span className="timestamp">{formatTime(message.created_at)}</span>
              </div>
              
              {/* Reply indicator */}
              {message.is_reply && (
                <div className="reply-indicator">
                  <span>Replying to:</span>
                  <div className="reply-content">{message.reply_to?.content}</div>
                </div>
              )}
              
              {/* Message text */}
              {message.content && (
                <div className="message-text">{message.content}</div>
              )}
              
              {/* Attachment */}
              {message.attachment && (
                <div className="message-attachment">
                  {message.type === 'image' ? (
                    <div className="image-attachment">
                      <img 
                        src={message.attachment.url} 
                        alt={message.attachment.name}
                        onClick={() => openImage(message.attachment.url)}
                      />
                    </div>
                  ) : (
                    <div className="file-attachment">
                      <a 
                        href={message.attachment.url} 
                        download={message.attachment.name}
                        className="file-link"
                      >
                        📎 {message.attachment.name} ({message.attachment.formatted_size})
                      </a>
                    </div>
                  )}
                </div>
              )}
              
              {/* Read receipts */}
              {message.read_count > 0 && (
                <div className="read-receipts">
                  <span className="read-count">✓✓ {message.read_count}</span>
                  {message.read_by && (
                    <div className="read-by-users">
                      {message.read_by.map(user => (
                        <span key={user.id} className="read-by-user">{user.name}</span>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>
            
            {/* Message actions */}
            <div className="message-actions">
              <button onClick={() => replyToMessage(message)} className="btn-icon" title="Reply">
                ↩️
              </button>
              {message.can_edit && (
                <button onClick={() => editMessage(message)} className="btn-icon" title="Edit">
                  ✏️
                </button>
              )}
              {message.can_delete && (
                <button onClick={() => deleteMessage(message)} className="btn-icon" title="Delete">
                  🗑️
                </button>
              )}
            </div>
          </div>
        ))}
      </div>

      {/* Typing Indicators */}
      {typingUsers.length > 0 && (
        <div className="typing-indicators">
          <div className="typing-indicator">
            <span>{getTypingText()}</span>
            <div className="dots">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        </div>
      )}

      {/* Message Input */}
      <div className="message-input-container">
        {/* Reply preview */}
        {replyingTo && (
          <div className="reply-preview">
            <div className="reply-content">
              <span>Replying to {replyingTo.user.name}: {replyingTo.content}</span>
            </div>
            <button onClick={cancelReply} className="btn-cancel">×</button>
          </div>
        )}
        
        <div className="message-input">
          <input
            ref={fileInputRef}
            type="file"
            onChange={handleFileSelect}
            accept="image/*,.pdf,.doc,.docx,.txt"
            style={{ display: 'none' }}
          />
          
          <button onClick={() => fileInputRef.current?.click()} className="btn-icon" title="Attach file">
            📎
          </button>
          
          <textarea
            ref={messageTextareaRef}
            value={newMessage}
            onChange={(e) => {
              setNewMessage(e.target.value);
              handleTyping();
            }}
            onKeyDown={handleKeyDown}
            placeholder="Type a message..."
            rows="1"
            className="message-textarea"
          />
          
          <button 
            onClick={sendMessage} 
            disabled={(!newMessage.trim() && !selectedFile) || isSending}
            className="btn-send"
          >
            Send
          </button>
        </div>
        
        {/* File preview */}
        {selectedFile && (
          <div className="file-preview">
            <div className="file-info">
              <span>{selectedFile.name} ({formatFileSize(selectedFile.size)})</span>
            </div>
            <button onClick={removeFile} className="btn-cancel">×</button>
          </div>
        )}
      </div>

      <style jsx>{`
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
      `}</style>
    </div>
  );
};

export default ChatSoulComponent;