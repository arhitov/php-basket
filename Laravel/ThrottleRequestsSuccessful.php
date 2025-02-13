<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * app/Http/Kernel.php
 * protected $middlewareAliases = [
 *   // Другие middleware
 *   'throttle.successful' => \App\Http\Middleware\ThrottleSuccessfulRequests::class,
 * ];
 *
 * Route::post('/your-post-route', [YourController::class, 'yourMethod'])
 *   ->middleware('throttle.successful:1,60'); // 1 успешный запрос в час
 */
class ThrottleRequestsSuccessful
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next, $maxAttempts = 1, $decayMinutes = 60): Response
    {
        $key = $this->resolveRequestKey($request);

        // Если лимит превышен, возвращаем ошибку 429
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key);
        }

        // Продолжаем выполнение запроса
        $response = $next($request);

        // Увеличиваем счетчик только для успешных ответов
        if ($response->isSuccessful()) {
            $this->limiter->hit($key, $decayMinutes * 60);
        }

        return $response;
    }

    protected function resolveRequestKey($request): string
    {
        // Используем IP-адрес как ключ
        return $request->ip();
    }

    protected function buildResponse($key): JsonResponse
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'message' => 'Too many attempts. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429);
    }
}
