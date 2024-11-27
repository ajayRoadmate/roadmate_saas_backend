<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateExecutiveKey
{
    
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if ($request->hasHeader('executivekey')) {

                $requestExecutiveKey = $request->header('executivekey');
                $executiveKeys = config('app.executive_keys');

                if (isset($executiveKeys['PRIMARY_KEY']) && $requestExecutiveKey === $executiveKeys['PRIMARY_KEY']) {
                   
                    return $next($request);
                }   
                return $this->errorResponse('Invalid executive key.', 401);
            }  
            return $this->errorResponse('Executive key is missing.', 400);
            
        } catch (\Exception $e) {

            return $this->errorResponse('Failed to validate App Key due to a server error.', 500);
        }
    }

    private function errorResponse($message, $statusCode)
    {
        return response()->json([
            'status' => 'failed',
            'error' => true,
            'message' => $message,
        ], $statusCode);
    }

}
