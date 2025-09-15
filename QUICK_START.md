# Laravel Chat Soul - Quick Start Guide

## 5-Minute Setup

### 1. Install Package
```bash
composer require omarelnaghy/laravel-chat-soul
```

### 2. Publish & Migrate
```bash
php artisan vendor:publish --tag=chat-soul-config
php artisan vendor:publish --tag=chat-soul-migrations
php artisan migrate
```

### 3. Add Trait to User Model
```php
// app/Models/User.php
use OmarElnaghy\LaravelChatSoul\Traits\HasChatSoul;

class User extends Authenticatable
{
    use HasChatSoul;
    // ...
}
```

### 4. Configure Broadcasting (.env)
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_KEY=your-key
PUSHER_APP_SECRET=your-secret
PUSHER_APP_ID=your-id
PUSHER_APP_CLUSTER=mt1
```

### 5. Test API
```bash
# Health check
curl -H "Authorization: Bearer YOUR_TOKEN" http://your-app.com/api/chat/health

# Create conversation
curl -X POST -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"type":"direct","participant_ids":[2]}' \
     http://your-app.com/api/chat/conversations
```

### 6. Frontend Integration

#### Vue.js
```vue
<template>
    <ChatSoulComponent :conversation-id="1" :user-id="authUser.id" />
</template>

<script>
import ChatSoulComponent from './resources/js/chat-soul/components/Chat.vue';
export default {
    components: { ChatSoulComponent }
}
</script>
```

#### React
```jsx
import ChatSoulComponent from './resources/js/chat-soul/components/Chat.jsx';

function App() {
    return <ChatSoulComponent conversationId={1} userId={authUser.id} />;
}
```

## Essential API Endpoints

```javascript
// Get conversations
GET /api/chat/conversations

// Send message
POST /api/chat/conversations/1/messages
{
    "content": "Hello!",
    "attachment": file // optional
}

// Mark as read
POST /api/chat/conversations/1/messages/123/read

// Set online
POST /api/chat/presence/online

// Send typing
POST /api/chat/typing
{
    "conversation_id": 1,
    "is_typing": true
}
```

## Laravel Echo Setup

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'your-pusher-key',
    cluster: 'mt1',
    forceTLS: true,
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
    },
});

// Listen for messages
Echo.private('chat-soul-conversation.1')
    .listen('ChatSoul.MessageSent', (e) => {
        console.log('New message:', e.message);
    });
```

That's it! Your chat system is ready to use. Check the full INSTALLATION.md for advanced configuration options.