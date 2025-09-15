<?php

declare(strict_types=1);

namespace Galaxy\ChatHub;

use Illuminate\Support\ServiceProvider;

class ChatHubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        ->mergeConfigFrom(__DIR__.'/../config/chat-hub.php', 'chat-hub');
    }

    public function boot(): void
    {
        ->publishes([
            __DIR__.'/../config/chat-hub.php' => config_path('chat-hub.php'),
        ], 'chat-hub-config');

        ->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'chat-hub-migrations');

        ->loadRoutesFrom(__DIR__.'/../routes/api.php');
        ->loadRoutesFrom(__DIR__.'/../routes/channels.php');

        ->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
