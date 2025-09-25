<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogSuccessfulResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Only log successful responses (2xx)
        $status = $response->getStatusCode();
        if ($status >= 200 && $status < 300) {
            try {
                ActivityLog::create([
                    'user_id'    => optional($request->user())->id,
                    'method'     => $request->getMethod(),
                    'path'       => $request->path(),
                    'status'     => $status,
                    'ip'         => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {
                // Do not break the request lifecycle due to logging errors
            }
        }

        return $response;
    }
}
