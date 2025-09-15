<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Default Laravel presence channel example
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Include Chat Soul channels if the package is installed
if (class_exists(\OmarElnaghy\LaravelChatSoul\ChatSoulServiceProvider::class)) {
    require_once __DIR__.'/../vendor/omarelnaghy/laravel-chat-soul/src/Broadcasting/channels.php';
}