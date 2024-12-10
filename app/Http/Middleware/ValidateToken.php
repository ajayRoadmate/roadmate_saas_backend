<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ValidateToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {

            $token = $request->bearerToken();
            $secretKey = config('app.app_secret');

            if (!$token) {
                return $this->errorResponse('Token is required to access the endpoint', Response::HTTP_UNAUTHORIZED);
            }

            $executiveId = $this->isTokenValid($token, $secretKey);

            if (!$executiveId) {
                return $this->errorResponse('Invalid token.', Response::HTTP_UNAUTHORIZED);
            }

            if (!$this->isExecutiveActive($executiveId)) {
                return $this->errorResponse('Unauthorized access. Please contact the administrator.', Response::HTTP_FORBIDDEN);
            }

            return $next($request);
        } catch (\Exception $e) {

            return $this->errorResponse('Could not access the server due to an error in the server.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function isTokenValid($token, $secretKey)
    {
        try {
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            return $decoded->sub ?? null;
        } catch (\Exception $e) {

            return null;
        }
    }

    private function isExecutiveActive($executiveId)
    {
        return DB::table('executives')
            ->where('id', $executiveId)
            ->where('executive_status', 1)
            ->exists();
    }

    private function errorResponse(string $message, int $statusCode): Response
    {
        return response()->json([
            'status' => 'failed',
            'error' => true,
            'message' => $message,
        ], $statusCode);
    }
}
