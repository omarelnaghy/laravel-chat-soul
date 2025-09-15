<?php

namespace OmarElnaghy\LaravelChatSoul\Services;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PresenceService
{
    protected string $cachePrefix;
    protected string $driver;
    protected int $presenceTtl;
    protected int $typingTtl;

    public function __construct()
    {
        $this->cachePrefix = config('chat-soul.cache.prefix');
        $this->driver = config('chat-soul.cache.driver');
        $this->presenceTtl = config('chat-soul.cache.presence_ttl');
        $this->typingTtl = config('chat-soul.cache.typing_ttl');
    }

    /**
     * Mark user as online.
     */
    public function setUserOnline(int $userId): void
    {
        $key = $this->getPresenceKey($userId);
        Cache::driver($this->driver)->put($key, now()->toISOString(), $this->presenceTtl);
        
        // Also update last seen
        $this->updateLastSeen($userId);
    }

    /**
     * Mark user as offline.
     */
    public function setUserOffline(int $userId): void
    {
        $key = $this->getPresenceKey($userId);
        Cache::driver($this->driver)->forget($key);
        
        // Update last seen before going offline
        $this->updateLastSeen($userId);
    }

    /**
     * Check if user is online.
     */
    public function isUserOnline(int $userId): bool
    {
        $key = $this->getPresenceKey($userId);
        return Cache::driver($this->driver)->has($key);
    }

    /**
     * Get list of online user IDs.
     */
    public function getOnlineUsers(): array
    {
        // This is a simplified implementation
        // In production, you might want to use Redis sets or other data structures
        $pattern = $this->getPresenceKey('*');
        
        // Note: This requires Redis and is not available with all cache drivers
        if ($this->driver === 'redis') {
            $keys = Cache::getRedis()->keys($pattern);
            $userIds = [];
            
            foreach ($keys as $key) {
                $userId = str_replace($this->getPresenceKey(''), '', $key);
                if (is_numeric($userId)) {
                    $userIds[] = (int) $userId;
                }
            }
            
            return $userIds;
        }
        
        return [];
    }

    /**
     * Set user typing status.
     */
    public function setUserTyping(int $userId, int $conversationId, bool $isTyping): void
    {
        $key = $this->getTypingKey($userId, $conversationId);
        
        if ($isTyping) {
            Cache::driver($this->driver)->put($key, now()->toISOString(), $this->typingTtl);
        } else {
            Cache::driver($this->driver)->forget($key);
        }
    }

    /**
     * Get typing users in a conversation.
     */
    public function getTypingUsers(int $conversationId, ?int $excludeUserId = null): array
    {
        // This is a simplified implementation
        // In production, you might want to use Redis patterns or dedicated storage
        $typingUsers = [];
        
        if ($this->driver === 'redis') {
            $pattern = $this->getTypingKey('*', $conversationId);
            $keys = Cache::getRedis()->keys($pattern);
            
            foreach ($keys as $key) {
                $parts = explode(':', $key);
                $userId = (int) end($parts);
                
                if ($excludeUserId && $userId === $excludeUserId) {
                    continue;
                }
                
                if (Cache::driver($this->driver)->has($key)) {
                    $typingUsers[] = $userId;
                }
            }
        }
        
        return $typingUsers;
    }

    /**
     * Update user's last seen timestamp.
     */
    public function updateLastSeen(int $userId): void
    {
        $key = $this->getLastSeenKey($userId);
        Cache::driver($this->driver)->forever($key, now()->toISOString());
    }

    /**
     * Get user's last seen timestamp.
     */
    public function getLastSeen(int $userId): ?string
    {
        $key = $this->getLastSeenKey($userId);
        return Cache::driver($this->driver)->get($key);
    }

    /**
     * Clean up expired presence data.
     */
    public function cleanup(): void
    {
        // This method can be called via a scheduled command
        // Implementation depends on cache driver
        
        if ($this->driver === 'redis') {
            // Remove expired typing indicators
            $typingPattern = $this->cachePrefix . ':typing:*';
            $typingKeys = Cache::getRedis()->keys($typingPattern);
            
            foreach ($typingKeys as $key) {
                if (!Cache::driver($this->driver)->has($key)) {
                    Cache::driver($this->driver)->forget($key);
                }
            }
        }
    }

    /**
     * Get presence cache key for user.
     */
    protected function getPresenceKey(int|string $userId): string
    {
        return "{$this->cachePrefix}:presence:{$userId}";
    }

    /**
     * Get typing cache key for user and conversation.
     */
    protected function getTypingKey(int|string $userId, int $conversationId): string
    {
        return "{$this->cachePrefix}:typing:{$conversationId}:{$userId}";
    }

    /**
     * Get last seen cache key for user.
     */
    protected function getLastSeenKey(int $userId): string
    {
        return "{$this->cachePrefix}:last_seen:{$userId}";
    }

    /**
     * Get user presence summary.
     */
    public function getUserPresenceSummary(int $userId): array
    {
        return [
            'user_id' => $userId,
            'is_online' => $this->isUserOnline($userId),
            'last_seen' => $this->getLastSeen($userId),
            'last_seen_human' => $this->getLastSeenHuman($userId),
        ];
    }

    /**
     * Get human-readable last seen time.
     */
    protected function getLastSeenHuman(int $userId): ?string
    {
        $lastSeen = $this->getLastSeen($userId);
        
        if (!$lastSeen) {
            return null;
        }
        
        try {
            return Carbon::parse($lastSeen)->diffForHumans();
        } catch (\Exception $e) {
            return null;
        }
    }
}