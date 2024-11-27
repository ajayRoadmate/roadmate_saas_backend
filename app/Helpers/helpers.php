<?php
if (! function_exists('handleError')) {

    function handleError($errorName)
    {

        $errorCodes = config('app.error_codes');

        if (isset($errorCodes[$errorName])) {

            $responseArr = [
                'status' => 'failed',
                'error' => true,
                'error_code' => $errorCodes[$errorName]['code'],
                'message' => $errorCodes[$errorName]['message']
            ];

            if( $errorName == 'DATA_NOT_FOUND'){
                $responseArr['payload']=[];
            }

            return response()->json($responseArr);
        } else {
            $responseArr = [
                'status' => 'failed',
                'error' => true,
                'error_code' => $errorCodes['UNKNOWN_ERROR']['code'],
                'message' => $errorCodes['UNKNOWN_ERROR']['message']
            ];
            return response()->json($responseArr);
        }
    }
}

if (! function_exists('handleCustomError')) {

    function handleCustomError($errorMessage)
    {
        $errorCodes = config('app.error_codes');

        $responseArr = [
            'status' => 'failed',
            'error' => true,
            'error_code' => $errorCodes['CUSTOM_ERROR']['code'],
            'message' => $errorMessage
        ];
        return response()->json($responseArr);
    }
}

if (! function_exists('handleSuccess')) {

    function handleSuccess($message, $data = null)
    {
        $response = [
            'status' => 'success',
            'error' => false,
            'message' => $message
        ];
        if (!empty($data)) {
            $response['payload'] = $data;
        }
        return response()->json($response);
    }
}

if (! function_exists('handleFetchResponse')) {

    function handlefetchResponse($data)
    {
        if ($data->isNotEmpty()) {

            $message = "Successfully retrieved data from server";
           return handleSuccess($message, $data);
        } else {

            return handleError('DATA_NOT_FOUND');
        }
    }
}
