# Laravel Chat Soul - Installation & Setup Guide

## Requirements

- PHP 8.1 or higher
- Laravel 11.0 or higher
- Laravel Sanctum 4.0 or higher
- Redis (recommended for presence and caching)
- A broadcasting service (Pusher, Ably, or Laravel WebSockets)

## Step 1: Install the Package

```bash
composer require omarelnaghy/laravel-chat-soul
```

The package will be automatically discovered by Laravel.

## Step 2: Publish Configuration and Migrations

```bash
# Publish the configuration file
php artisan vendor:publish --tag=chat-soul-config

# Publish database migrations
php artisan vendor:publish --tag=chat-soul-migrations

# Publish frontend components (optional)
php artisan vendor:publish --tag=chat-soul-frontend
```

## Step 3: Configure Environment Variables

Add these variables to your `.env` file:

```env
# Broadcasting Configuration
BROADCAST_DRIVER=pusher

# Pusher Configuration (if using Pusher)
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1

# Chat Soul Specific Settings
CHAT_SOUL_BROADCAST_DRIVER=pusher
CHAT_SOUL_CHANNEL_PREFIX=chat-soul
CHAT_SOUL_GUARD=sanctum

# Cache Configuration (Redis recommended)
CACHE_DRIVER=redis
CHAT_SOUL_CACHE_DRIVER=redis

# File Upload Settings
CHAT_SOUL_UPLOADS_ENABLED=true
CHAT_SOUL_MAX_FILE_SIZE=10240
```

## Step 4: Configure Broadcasting

### Option A: Using Pusher

1. Install Pusher PHP SDK:
```bash
composer require pusher/pusher-php-server
```

2. Configure `config/broadcasting.php`:
```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'useTLS' => true,
    ],
],
```

### Option B: Using Laravel WebSockets

1. Install Laravel WebSockets:
```bash
composer require beyondcode/laravel-websockets
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan migrate
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

2. Update your `.env`:
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1
```

3. Configure `config/websockets.php`:
```php
'apps' => [
    [
        'id' => env('PUSHER_APP_ID'),
        'name' => env('APP_NAME'),
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'path' => env('PUSHER_APP_PATH'),
        'capacity' => null,
        'enable_client_messages' => false,
        'enable_statistics' => true,
    ],
],
```

## Step 5: Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `chat_conversations`
- `chat_conversation_user`
- `chat_messages`
- `chat_message_reads`

## Step 6: Setup User Model

Add the `HasChatSoul` trait to your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use OmarElnaghy\LaravelChatSoul\Traits\HasChatSoul;

class User extends Authenticatable
{
    use HasApiTokens, HasChatSoul;
    
    // ... rest of your User model
}
```

## Step 7: Configure Laravel Sanctum (if not already done)

1. Publish Sanctum configuration:
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

2. Add Sanctum middleware to `app/Http/Kernel.php`:
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

3. Configure CORS in `config/cors.php`:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],
'supports_credentials' => true,
```

## Step 8: Setup Queue Worker (Recommended)

For better performance with broadcasting:

```bash
# Configure queue driver in .env
QUEUE_CONNECTION=redis

# Run queue worker
php artisan queue:work
```

## Step 8: Setup Broadcasting Channels

The package automatically registers broadcasting channels. If you need to customize them:

1. The channels are defined in `vendor/omarelnaghy/laravel-chat-soul/src/Broadcasting/channels.php`
2. Your main `routes/channels.php` should include them automatically
3. If you need custom authorization logic, you can override the channels in your `routes/channels.php`:

```php
// Custom conversation channel authorization
Broadcast::channel('chat-soul-conversation.{conversationId}', function ($user, $conversationId) {
    // Your custom logic here
    $conversation = \OmarElnaghy\LaravelChatSoul\Models\Conversation::find($conversationId);
    return $conversation && $conversation->hasParticipant($user->id);
});
```

## Step 9: Frontend Setup

### Option A: Using Vue.js

1. Install dependencies:
```bash
npm install laravel-echo pusher-js
```

2. Configure Laravel Echo in your `resources/js/bootstrap.js`:
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true,
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
    },
});
```

3. Use the Vue component:
```vue
<template>
    <div>
        <ChatSoulComponent 
            :conversation-id="1" 
            :user-id="authUser.id" 
        />
    </div>
</template>

<script>
import ChatSoulComponent from './path/to/published/components/Chat.vue';

export default {
    components: {
        ChatSoulComponent
    },
    data() {
        return {
            authUser: window.authUser // Your authenticated user
        }
    }
}
</script>
```

### Option B: Using React

1. Install dependencies:
```bash
npm install laravel-echo pusher-js
```

2. Configure Laravel Echo:
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true,
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
    },
});
```

3. Use the React component:
```jsx
import React from 'react';
import ChatSoulComponent from './path/to/published/components/Chat.jsx';

function ChatApp() {
    const authUser = window.authUser; // Your authenticated user
    
    return (
        <div>
            <ChatSoulComponent 
                conversationId={1} 
                userId={authUser.id} 
            />
        </div>
    );
}

export default ChatApp;
```

## Step 10: Authentication Setup

Create API tokens for users:

```php
// In your authentication controller
$user = auth()->user();
$token = $user->createToken('chat-token')->plainTextToken;

// Return token to frontend
return response()->json([
    'user' => $user,
    'token' => $token
]);
```

Store the token in localStorage:
```javascript
localStorage.setItem('auth_token', token);
```

## Step 11: Start Broadcasting Services

### If using Laravel WebSockets:
```bash
php artisan websockets:serve
```

### If using Pusher:
No additional setup needed - Pusher handles the infrastructure.

## Step 12: Test the Installation

1. Create a simple test route in `routes/web.php`:
```php
Route::get('/chat-test', function () {
    $user = auth()->user();
    
    // Create a test conversation
    $conversation = $user->getOrCreateDirectConversationWith(2); // User ID 2
    
    return response()->json([
        'conversation' => $conversation,
        'health' => app('Illuminate\Http\Client\Factory')->get(url('/api/chat/health'))->json()
    ]);
})->middleware('auth');
```

2. Visit `/chat-test` to verify the package is working.

## Configuration Options

The package can be extensively customized through `config/chat-soul.php`:

### Database Configuration
```php
'database' => [
    'prefix' => 'chat_',
    'connection' => env('CHAT_SOUL_DB_CONNECTION', config('database.default')),
],
```

### Feature Flags
```php
'features' => [
    'typing_indicators' => env('CHAT_SOUL_TYPING_INDICATORS', true),
    'read_receipts' => env('CHAT_SOUL_READ_RECEIPTS', true),
    'presence' => env('CHAT_SOUL_PRESENCE', true),
    'file_uploads' => env('CHAT_SOUL_FILE_UPLOADS', true),
    'message_search' => env('CHAT_SOUL_MESSAGE_SEARCH', true),
    'group_chats' => env('CHAT_SOUL_GROUP_CHATS', true),
],
```

### File Upload Settings
```php
'uploads' => [
    'enabled' => env('CHAT_SOUL_UPLOADS_ENABLED', true),
    'disk' => env('CHAT_SOUL_UPLOADS_DISK', 'public'),
    'path' => env('CHAT_SOUL_UPLOADS_PATH', 'chat-attachments'),
    'max_file_size' => env('CHAT_SOUL_MAX_FILE_SIZE', 10240), // KB
    'allowed_types' => [
        'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'
    ],
],
```

### Rate Limiting
```php
'rate_limits' => [
    'send_message' => env('CHAT_SOUL_RATE_LIMIT_MESSAGES', '60,1'),
    'create_conversation' => env('CHAT_SOUL_RATE_LIMIT_CONVERSATIONS', '10,1'),
    'typing_events' => env('CHAT_SOUL_RATE_LIMIT_TYPING', '30,1'),
],
```

## API Endpoints

Once installed, the following endpoints will be available:

### Conversations
- `GET /api/chat/conversations` - Get user conversations
- `POST /api/chat/conversations` - Create new conversation
- `GET /api/chat/conversations/{id}` - Get specific conversation
- `PUT /api/chat/conversations/{id}` - Update conversation
- `DELETE /api/chat/conversations/{id}` - Delete conversation
- `POST /api/chat/conversations/direct` - Get or create direct conversation

### Messages
- `GET /api/chat/conversations/{id}/messages` - Get messages
- `POST /api/chat/conversations/{id}/messages` - Send message
- `PUT /api/chat/conversations/{id}/messages/{messageId}` - Edit message
- `DELETE /api/chat/conversations/{id}/messages/{messageId}` - Delete message
- `POST /api/chat/conversations/{id}/messages/{messageId}/read` - Mark as read
- `POST /api/chat/conversations/{id}/messages/read-all` - Mark all as read

### Presence & Typing
- `POST /api/chat/presence/online` - Set user online
- `POST /api/chat/presence/offline` - Set user offline
- `GET /api/chat/presence/online-users` - Get online users
- `POST /api/chat/typing` - Send typing indicator

### Utility
- `GET /api/chat/health` - Health check
- `GET /api/chat/search` - Search messages
- `GET /api/chat/stats` - Get user stats

## Troubleshooting

### Common Issues

1. **Broadcasting not working:**
   - Check your `.env` broadcasting configuration
   - Ensure queue worker is running
   - Verify WebSocket connection (if using Laravel WebSockets)

2. **Authentication errors:**
   - Ensure Sanctum is properly configured
   - Check that auth token is being sent in headers
   - Verify the auth guard configuration

3. **File uploads not working:**
   - Check file permissions on storage directory
   - Verify `CHAT_SOUL_UPLOADS_ENABLED=true`
   - Check file size and type restrictions

4. **Database errors:**
   - Ensure migrations have been run
   - Check database connection configuration
   - Verify table prefixes match configuration

### Debug Mode

Enable debug mode in your `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

Check logs in `storage/logs/laravel.log` for detailed error information.

## Performance Optimization

1. **Use Redis for caching:**
```env
CACHE_DRIVER=redis
CHAT_SOUL_CACHE_DRIVER=redis
```

2. **Enable queue processing:**
```env
QUEUE_CONNECTION=redis
```

3. **Optimize database queries:**
   - The package includes proper indexes
   - Consider database query optimization for large datasets

4. **Use CDN for file uploads:**
   - Configure a different disk for file storage
   - Use cloud storage services like AWS S3

## Security Considerations

1. **Rate Limiting:** The package includes built-in rate limiting
2. **File Upload Security:** File types and sizes are restricted
3. **Authorization:** All endpoints require authentication
4. **XSS Protection:** Content is properly escaped
5. **CSRF Protection:** Sanctum provides CSRF protection

## Support

For issues and questions:
1. Check the troubleshooting section above
2. Review the configuration options
3. Check Laravel logs for errors
4. Create an issue on the package repository

## Next Steps

After installation, you can:
1. Customize the configuration to match your needs
2. Extend the models and controllers for additional functionality
3. Customize the frontend components
4. Add additional event listeners for custom behavior
5. Implement push notifications for mobile apps