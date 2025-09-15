# Laravel Chat Soul

A comprehensive, production-ready real-time chat system package for Laravel 11 applications. Laravel Chat Soul provides modular chat functionality with broadcasting, presence tracking, typing indicators, file attachments, and extensive customization options.

## Features

### Core Functionality
- **One-to-one and Group Conversations** - Support for both direct messages and group chats
- **Message Management** - Send, edit, delete messages with text content and file attachments
- **Read Receipts** - Track which users have read specific messages with timestamps
- **Real-time Typing Indicators** - Show when users are typing in conversations
- **Online Presence System** - Track and display active users
- **Message History** - Paginated message loading with search capabilities
- **File Attachments** - Support for images, documents, and other file types

### Real-time Features
- **Laravel Echo Compatible** - Full integration with Laravel's broadcasting system
- **Multiple Driver Support** - Pusher, Ably, Laravel WebSockets, Redis
- **Custom Events** - MessageSent, UserTyping, UserOnline, UserOffline, MessageRead
- **Automatic Channel Authorization** - Private conversation channels with user verification

### API & Architecture
- **RESTful API** - Clean, consistent API endpoints under `/api/chat/*`
- **Laravel Sanctum Authentication** - Secure API authentication (configurable)
- **JSON API Resources** - Standardized response formatting
- **Rate Limiting** - Built-in protection against abuse
- **Comprehensive Validation** - Request validation for all endpoints

### Customization
- **Extensive Configuration** - Every aspect configurable via `chat-soul.php`
- **Database Flexibility** - Custom table prefixes and connections
- **Feature Flags** - Enable/disable specific functionality
- **Multi-guard Authentication** - Support for different authentication systems

## Installation

### Requirements
- PHP 8.1+
- Laravel 11.0+
- Laravel Sanctum 4.0+

### Install Package

```bash
composer require omarelnaghy/laravel-chat-soul
```

### Publish Configuration and Migrations

```bash
# Publish configuration file
php artisan vendor:publish --tag=chat-soul-config

# Publish migrations
php artisan vendor:publish --tag=chat-soul-migrations

# Publish frontend components (optional)
php artisan vendor:publish --tag=chat-soul-frontend

# Run migrations
php artisan migrate
```

## Configuration

The package includes a comprehensive configuration file at `config/chat-soul.php`:

```php
return [
    // Database configuration
    'database' => [
        'prefix' => 'chat_',
        'connection' => env('CHAT_SOUL_DB_CONNECTION', config('database.default')),
    ],

    // Authentication settings
    'auth' => [
        'guard' => env('CHAT_SOUL_GUARD', 'sanctum'),
        'user_model' => env('CHAT_SOUL_USER_MODEL', 'App\\Models\\User'),
    ],

    // Broadcasting configuration
    'broadcasting' => [
        'driver' => env('CHAT_SOUL_BROADCAST_DRIVER', 'pusher'),
        'channel_prefix' => env('CHAT_SOUL_CHANNEL_PREFIX', 'chat-soul'),
    ],

    // Feature flags
    'features' => [
        'typing_indicators' => env('CHAT_SOUL_TYPING_INDICATORS', true),
        'read_receipts' => env('CHAT_SOUL_READ_RECEIPTS', true),
        'presence' => env('CHAT_SOUL_PRESENCE', true),
        'file_uploads' => env('CHAT_SOUL_FILE_UPLOADS', true),
    ],
    
    // ... more configuration options
];
```

## User Model Setup

Add the `HasChatSoul` trait to your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use OmarElnaghy\LaravelChatSoul\Traits\HasChatSoul;

class User extends Authenticatable
{
    use HasChatSoul;
    
    // ... rest of your User model
}
```

## Broadcasting Setup

Configure Laravel broadcasting in your `.env` file:

```env
BROADCAST_DRIVER=pusher

# Pusher configuration
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1

# Chat Soul specific settings
CHAT_SOUL_BROADCAST_DRIVER=pusher
CHAT_SOUL_CHANNEL_PREFIX=chat-soul
```

## API Usage

### Authentication

All API endpoints require authentication. Include the Bearer token in your requests:

```javascript
headers: {
    'Authorization': `Bearer ${authToken}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
}
```

### Core Endpoints

#### Conversations

```javascript
// Get user conversations
GET /api/chat/conversations

// Create new conversation
POST /api/chat/conversations
{
    "type": "group",
    "name": "Project Team",
    "participant_ids": [2, 3, 4]
}

// Get or create direct conversation
POST /api/chat/conversations/direct
{
    "user_id": 2
}
```

#### Messages

```javascript
// Get conversation messages
GET /api/chat/conversations/{id}/messages

// Send message
POST /api/chat/conversations/{id}/messages
{
    "content": "Hello everyone!",
    "reply_to_id": 123 // optional
}

// Send message with attachment
POST /api/chat/conversations/{id}/messages
FormData: {
    content: "Check this out!",
    attachment: file
}

// Mark message as read
POST /api/chat/conversations/{id}/messages/{messageId}/read

// Mark all messages as read
POST /api/chat/conversations/{id}/messages/read-all
```

#### Typing & Presence

```javascript
// Send typing indicator
POST /api/chat/typing
{
    "conversation_id": 1,
    "is_typing": true
}

// Set user online
POST /api/chat/presence/online

// Get online users
GET /api/chat/presence/online-users
```

## Frontend Integration

### Laravel Echo Setup

```javascript
import Echo from 'laravel-echo';
import { ChatSoulEcho } from './resources/js/chat-soul/echo-setup.js';

// Initialize chat
const chatSoul = new ChatSoulEcho();

// Join conversation
chatSoul.joinConversation(conversationId, {
    onMessageSent: (event) => {
        console.log('New message:', event.message);
    },
    onUserTyping: (event) => {
        console.log('User typing:', event.user.name, event.is_typing);
    },
    onMessageRead: (event) => {
        console.log('Message read by:', event.user.name);
    }
});

// Join presence channel
chatSoul.joinPresence({
    onHere: (users) => console.log('Online users:', users),
    onJoining: (user) => console.log('User joined:', user.name),
    onLeaving: (user) => console.log('User left:', user.name)
});
```

### Vue.js Component

Use the provided Vue component:

```vue
<template>
    <ChatSoulComponent 
        :conversation-id="1" 
        :user-id="authUser.id" 
    />
</template>

<script>
import ChatSoulComponent from './resources/js/chat-soul/components/Chat.vue';

export default {
    components: {
        ChatSoulComponent
    },
    // ...
}
</script>
```

### React Component

```jsx
import React from 'react';
import ChatSoulComponent from './resources/js/chat-soul/components/Chat.jsx';

function ChatApp() {
    return (
        <ChatSoulComponent 
            conversationId={1} 
            userId={authUser.id} 
        />
    );
}
```

## Advanced Usage

### Custom Message Types

```php
// In your controller
$message = $chatService->sendMessage([
    'conversation_id' => 1,
    'user_id' => auth()->id(),
    'content' => 'System notification',
    'type' => Message::TYPE_SYSTEM,
    'metadata' => ['system_type' => 'user_joined']
]);
```

### Search Messages

```javascript
// Search across conversations
GET /api/chat/search?query=hello&type=messages

// Search in specific conversation
GET /api/chat/conversations/1/messages?search=hello
```

### File Uploads

The package supports file uploads with configurable restrictions:

```php
// In config/chat-soul.php
'uploads' => [
    'enabled' => true,
    'disk' => 'public',
    'max_file_size' => 10240, // KB
    'allowed_types' => ['jpg', 'png', 'pdf', 'doc', 'txt'],
],
```

### Rate Limiting

Configure rate limits for different operations:

```php
'rate_limits' => [
    'send_message' => '60,1',        // 60 requests per minute
    'create_conversation' => '10,1', // 10 requests per minute
    'typing_events' => '30,1',       // 30 requests per minute
],
```

## Events

The package broadcasts several real-time events:

- `ChatSoul.MessageSent` - When a message is sent
- `ChatSoul.UserTyping` - When user starts/stops typing
- `ChatSoul.UserOnline` - When user comes online
- `ChatSoul.UserOffline` - When user goes offline
- `ChatSoul.MessageRead` - When a message is read

## Database Schema

The package creates these tables:

- `chat_conversations` - Conversation details
- `chat_conversation_user` - User-conversation relationships
- `chat_messages` - Message content and metadata
- `chat_message_reads` - Read receipt tracking

## Security

- **Authentication Required** - All endpoints require valid authentication
- **Conversation Authorization** - Users can only access conversations they participate in
- **File Upload Validation** - Configurable file type and size restrictions
- **Rate Limiting** - Protection against abuse and spam
- **XSS Protection** - Proper input sanitization and output escaping

## Performance

- **Database Indexing** - Optimized indexes for fast queries
- **Pagination** - Efficient message loading
- **Caching** - Redis-based caching for presence and typing indicators
- **Queue Support** - Background processing for heavy operations

## Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## Testing

```bash
# Run package tests
composer test

# Run with coverage
composer test-coverage
```

## License

This package is open-source software licensed under the [MIT license](LICENSE).

## Support

For support, please create an issue on GitHub or contact the maintainer.

## Changelog

### Version 1.0.0
- Initial release
- Complete chat system with real-time features
- Vue.js and React components
- Comprehensive API
- Full documentation

---

Built with ❤️ for the Laravel community.