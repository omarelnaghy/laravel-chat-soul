<?php

namespace OmarElnaghy\LaravelChatSoul\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChatAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verify the user is authenticated with the configured guard
        $guard = config('chat-soul.auth.guard');
        
        if (!auth($guard)->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Add user to request for easy access
        $request->merge(['chat_user' => auth($guard)->user()]);

        return $next($request);
    }
}