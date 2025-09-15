<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database table names and prefixes for chat-soul tables.
    |
    */
    'database' => [
        'prefix' => env('CHAT_SOUL_DB_PREFIX', 'chat_'),
        'connection' => env('CHAT_SOUL_DB_CONNECTION', config('database.default')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configure authentication guard and user model for chat system.
    |
    */
    'auth' => [
        'guard' => env('CHAT_SOUL_GUARD', 'sanctum'),
        'user_model' => env('CHAT_SOUL_USER_MODEL', 'App\\Models\\User'),
        'user_identifier' => 'id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure real-time broadcasting settings.
    |
    */
    'broadcasting' => [
        'driver' => env('CHAT_SOUL_BROADCAST_DRIVER', 'pusher'),
        'channel_prefix' => env('CHAT_SOUL_CHANNEL_PREFIX', 'chat-soul'),
        'event_prefix' => env('CHAT_SOUL_EVENT_PREFIX', 'ChatSoul'),
        'presence_channel' => env('CHAT_SOUL_PRESENCE_CHANNEL', 'chat-presence'),
        'enabled' => env('CHAT_SOUL_BROADCASTING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configure file upload settings for message attachments.
    |
    */
    'uploads' => [
        'enabled' => env('CHAT_SOUL_UPLOADS_ENABLED', true),
        'disk' => env('CHAT_SOUL_UPLOADS_DISK', 'public'),
        'path' => env('CHAT_SOUL_UPLOADS_PATH', 'chat-attachments'),
        'max_file_size' => env('CHAT_SOUL_MAX_FILE_SIZE', 10240), // KB
        'allowed_types' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'csv', 'zip', 'rar', '7z',
            'mp3', 'wav', 'mp4', 'avi', 'mov'
        ],
        'image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'document_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'],
        'archive_types' => ['zip', 'rar', '7z'],
        'media_types' => ['mp3', 'wav', 'mp4', 'avi', 'mov'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for various chat operations.
    |
    */
    'rate_limits' => [
        'send_message' => env('CHAT_SOUL_RATE_LIMIT_MESSAGES', '60,1'),
        'create_conversation' => env('CHAT_SOUL_RATE_LIMIT_CONVERSATIONS', '10,1'),
        'typing_events' => env('CHAT_SOUL_RATE_LIMIT_TYPING', '30,1'),
        'file_upload' => env('CHAT_SOUL_RATE_LIMIT_UPLOADS', '20,1'),
        'search' => env('CHAT_SOUL_RATE_LIMIT_SEARCH', '30,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | Configure pagination settings for message history.
    |
    */
    'pagination' => [
        'messages_per_page' => env('CHAT_SOUL_MESSAGES_PER_PAGE', 50),
        'conversations_per_page' => env('CHAT_SOUL_CONVERSATIONS_PER_PAGE', 20),
        'max_messages_per_page' => env('CHAT_SOUL_MAX_MESSAGES_PER_PAGE', 100),
        'max_conversations_per_page' => env('CHAT_SOUL_MAX_CONVERSATIONS_PER_PAGE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching settings for presence and typing indicators.
    |
    */
    'cache' => [
        'driver' => env('CHAT_SOUL_CACHE_DRIVER', 'redis'),
        'prefix' => env('CHAT_SOUL_CACHE_PREFIX', 'chat_soul'),
        'typing_ttl' => env('CHAT_SOUL_TYPING_TTL', 5), // seconds
        'presence_ttl' => env('CHAT_SOUL_PRESENCE_TTL', 300), // seconds
        'conversation_cache_ttl' => env('CHAT_SOUL_CONVERSATION_CACHE_TTL', 3600), // seconds
        'user_cache_ttl' => env('CHAT_SOUL_USER_CACHE_TTL', 1800), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific chat features.
    |
    */
    'features' => [
        'typing_indicators' => env('CHAT_SOUL_TYPING_INDICATORS', true),
        'read_receipts' => env('CHAT_SOUL_READ_RECEIPTS', true),
        'presence' => env('CHAT_SOUL_PRESENCE', true),
        'file_uploads' => env('CHAT_SOUL_FILE_UPLOADS', true),
        'message_search' => env('CHAT_SOUL_MESSAGE_SEARCH', true),
        'group_chats' => env('CHAT_SOUL_GROUP_CHATS', true),
        'message_editing' => env('CHAT_SOUL_MESSAGE_EDITING', true),
        'message_deletion' => env('CHAT_SOUL_MESSAGE_DELETION', true),
        'conversation_settings' => env('CHAT_SOUL_CONVERSATION_SETTINGS', true),
        'user_blocking' => env('CHAT_SOUL_USER_BLOCKING', false),
        'message_reactions' => env('CHAT_SOUL_MESSAGE_REACTIONS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Configuration
    |--------------------------------------------------------------------------
    |
    | Configure message-related settings.
    |
    */
    'messages' => [
        'max_length' => env('CHAT_SOUL_MAX_MESSAGE_LENGTH', 4000),
        'allow_empty' => env('CHAT_SOUL_ALLOW_EMPTY_MESSAGES', false),
        'soft_delete' => env('CHAT_SOUL_SOFT_DELETE_MESSAGES', true),
        'edit_time_limit' => env('CHAT_SOUL_EDIT_TIME_LIMIT', 300), // seconds (5 minutes)
        'delete_time_limit' => env('CHAT_SOUL_DELETE_TIME_LIMIT', 3600), // seconds (1 hour)
        'auto_delete_after' => env('CHAT_SOUL_AUTO_DELETE_AFTER', null), // days (null = never)
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversation Configuration
    |--------------------------------------------------------------------------
    |
    | Configure conversation-related settings.
    |
    */
    'conversations' => [
        'max_participants' => env('CHAT_SOUL_MAX_PARTICIPANTS', 100),
        'allow_public' => env('CHAT_SOUL_ALLOW_PUBLIC_CONVERSATIONS', false),
        'auto_join_public' => env('CHAT_SOUL_AUTO_JOIN_PUBLIC', false),
        'creator_can_delete' => env('CHAT_SOUL_CREATOR_CAN_DELETE', true),
        'participants_can_leave' => env('CHAT_SOUL_PARTICIPANTS_CAN_LEAVE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security-related settings.
    |
    */
    'security' => [
        'encrypt_messages' => env('CHAT_SOUL_ENCRYPT_MESSAGES', false),
        'log_user_actions' => env('CHAT_SOUL_LOG_USER_ACTIONS', true),
        'require_email_verification' => env('CHAT_SOUL_REQUIRE_EMAIL_VERIFICATION', false),
        'max_failed_attempts' => env('CHAT_SOUL_MAX_FAILED_ATTEMPTS', 5),
        'lockout_duration' => env('CHAT_SOUL_LOCKOUT_DURATION', 900), // seconds (15 minutes)
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configure notification settings.
    |
    */
    'notifications' => [
        'enabled' => env('CHAT_SOUL_NOTIFICATIONS_ENABLED', true),
        'channels' => ['database', 'broadcast'],
        'email_notifications' => env('CHAT_SOUL_EMAIL_NOTIFICATIONS', false),
        'push_notifications' => env('CHAT_SOUL_PUSH_NOTIFICATIONS', false),
        'sound_notifications' => env('CHAT_SOUL_SOUND_NOTIFICATIONS', true),
    ],
];