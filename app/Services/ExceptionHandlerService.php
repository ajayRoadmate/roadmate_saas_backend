<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Database\QueryException;
class ExceptionHandlerService
{

    public static function handleExceptions(Exceptions $exceptions)
    {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
         
            if ($request->is('api/*')) {
                
                $responseArr = [
                    'status' => 'failed',
                    'error' => true,
                    'error_code' => 404,
                    'message' => 'API resource not found',
                ];
                return response()->json($responseArr,404);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
        
            $errorCodes = config('app.error_codes');
            if ($request->is('api/*')) {

                $responseArr = [
                    'status' => 'failed',
                    'error' => true,
                    'error_code' => $errorCodes['VALIDATION_FAILED']['code'],
                    'message' => $errorCodes['VALIDATION_FAILED']['message'],
                    'details' => $e->getMessage()
                ];
                return response()->json($responseArr,400);
            }
        });

        $exceptions->render(function (QueryException  $e, Request $request) {
         
            $errorCodes = config('app.error_codes');
            if ($request->is('api/*')) {

                $responseArr = [
                    'status' => 'failed',
                    'error' => true,
                    'error_code' => $errorCodes['INTERNAL_SERVER_ERROR']['code'],
                    'message' => $errorCodes['INTERNAL_SERVER_ERROR']['message'],
                    'details' => $e->getMessage()
                ];
                return response()->json($responseArr,500);
            }
        });

    }
}
