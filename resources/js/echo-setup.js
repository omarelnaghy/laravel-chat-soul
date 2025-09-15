import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Configure Laravel Echo based on your broadcasting driver
const echoConfig = {
    broadcaster: 'pusher', // or 'socket.io', 'null'
    key: process.env.MIX_PUSHER_APP_KEY || 'your-pusher-key',
    cluster: process.env.MIX_PUSHER_APP_CLUSTER || 'mt1',
    forceTLS: true,
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
    },
};

// Initialize Echo
window.Echo = new Echo(echoConfig);

// Chat Soul Echo Helper
export class ChatSoulEcho {
    constructor() {
        this.channelPrefix = 'chat-soul';
        this.eventPrefix = 'ChatSoul';
        this.subscriptions = new Map();
    }

    /**
     * Join conversation channel
     */
    joinConversation(conversationId, callbacks = {}) {
        const channelName = `${this.channelPrefix}-conversation.${conversationId}`;
        
        if (this.subscriptions.has(channelName)) {
            this.leaveConversation(conversationId);
        }

        const channel = window.Echo.private(channelName);

        // Listen for message events
        if (callbacks.onMessageSent) {
            channel.listen(`${this.eventPrefix}.MessageSent`, callbacks.onMessageSent);
        }

        if (callbacks.onUserTyping) {
            channel.listen(`${this.eventPrefix}.UserTyping`, callbacks.onUserTyping);
        }

        if (callbacks.onMessageRead) {
            channel.listen(`${this.eventPrefix}.MessageRead`, callbacks.onMessageRead);
        }

        this.subscriptions.set(channelName, channel);
        return channel;
    }

    /**
     * Leave conversation channel
     */
    leaveConversation(conversationId) {
        const channelName = `${this.channelPrefix}-conversation.${conversationId}`;
        const channel = this.subscriptions.get(channelName);

        if (channel) {
            window.Echo.leave(channelName);
            this.subscriptions.delete(channelName);
        }
    }

    /**
     * Join presence channel
     */
    joinPresence(callbacks = {}) {
        const channelName = 'chat-presence';
        
        if (this.subscriptions.has(channelName)) {
            this.leavePresence();
        }

        const channel = window.Echo.join(channelName)
            .here((users) => {
                if (callbacks.onHere) {
                    callbacks.onHere(users);
                }
            })
            .joining((user) => {
                if (callbacks.onJoining) {
                    callbacks.onJoining(user);
                }
            })
            .leaving((user) => {
                if (callbacks.onLeaving) {
                    callbacks.onLeaving(user);
                }
            });

        // Listen for presence events
        if (callbacks.onUserOnline) {
            channel.listen(`${this.eventPrefix}.UserOnline`, callbacks.onUserOnline);
        }

        if (callbacks.onUserOffline) {
            channel.listen(`${this.eventPrefix}.UserOffline`, callbacks.onUserOffline);
        }

        this.subscriptions.set(channelName, channel);
        return channel;
    }

    /**
     * Leave presence channel
     */
    leavePresence() {
        const channelName = 'chat-presence';
        const channel = this.subscriptions.get(channelName);

        if (channel) {
            window.Echo.leave(channelName);
            this.subscriptions.delete(channelName);
        }
    }

    /**
     * Send typing event
     */
    sendTyping(conversationId, isTyping = true) {
        return fetch('/api/chat/typing', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                conversation_id: conversationId,
                is_typing: isTyping,
            }),
        });
    }

    /**
     * Update presence status
     */
    setOnline() {
        return fetch('/api/chat/presence/online', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Accept': 'application/json',
            },
        });
    }

    setOffline() {
        return fetch('/api/chat/presence/offline', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Accept': 'application/json',
            },
        });
    }

    /**
     * Cleanup all subscriptions
     */
    cleanup() {
        this.subscriptions.forEach((channel, channelName) => {
            window.Echo.leave(channelName);
        });
        this.subscriptions.clear();
    }
}

// Export singleton instance
export default new ChatSoulEcho();