<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class HttpApiService
{
    public static function postRequest($url, $body, $file = null, $fileField = 'image')
    {
        try {
            // Initialize HTTP client
             $client = Http::asMultipart();

            // Attach file if provided
            if ($file) {
                $client = $client->attach(
                    $fileField,
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                );
            }

            // Send the request
            $response = $client->post($url, $body);

             // Handle success or failure
             if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                return [
                    'success' => false,
                    'status' => $response->status(),
                    'message' => $response->body(),
                ];
            }
        } catch(\Exception $e) {
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}