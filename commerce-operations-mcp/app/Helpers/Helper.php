<?php

use Illuminate\Http\JsonResponse;

/**
 * Return the common JSON contract used by every public API endpoint.
 *
 * @param  array<string, string>  $headers
 * @param  array<string, mixed>  $extraData
 */
function responseJson(int $status, string|array $message, mixed $data = null, array $headers = [], array $extraData = []): JsonResponse
{
    $response = [
        'success' => $status >= 200 && $status < 300,
        'message' => $message,
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if ($extraData !== []) {
        $response = array_merge($response, $extraData);
    }

    return response()->json($response, $status, $headers);
}
