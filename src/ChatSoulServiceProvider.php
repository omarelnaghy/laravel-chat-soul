<?php

namespace OmarElnaghy\LaravelChatSoul;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OmarElnaghy\LaravelChatSoul\Broadcasting\ChatChannel;
use OmarElnaghy\LaravelChatSoul\Http\Middleware\ChatAuthMiddleware;
use OmarElnaghy\LaravelChatSoul\Services\ChatService;
use OmarElnaghy\LaravelChatSoul\Services\PresenceService;

class ChatSoulServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/chat-soul.php', 'chat-soul');

        // Register services
        $this->app->singleton(ChatService::class);
        $this->app->singleton(PresenceService::class);

        // Register broadcasting channel
        $this->app->singleton(ChatChannel::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/chat-soul.php' => config_path('chat-soul.php'),
            ], 'chat-soul-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'chat-soul-migrations');

            $this->publishes([
                __DIR__.'/../resources/js' => resource_path('js/chat-soul'),
            ], 'chat-soul-frontend');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerBroadcastingChannels();
    }

    /**
     * Register package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        });
    }

    /**
     * Get route group configuration.
     */
    protected function routeConfiguration(): array
    {
        return [
            'prefix' => 'api/chat',
            'middleware' => ['api', 'auth:'.config('chat-soul.auth.guard')],
        ];
    }

    /**
     * Register middleware.
     */
    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('chat.auth', ChatAuthMiddleware::class);
    }

    /**
     * Register broadcasting channels.
     */
    protected function registerBroadcastingChannels(): void
    {
        if (config('chat-soul.broadcasting.enabled', true)) {
            // Load broadcasting channels
            require __DIR__.'/Broadcasting/channels.php';
        }
    }
}