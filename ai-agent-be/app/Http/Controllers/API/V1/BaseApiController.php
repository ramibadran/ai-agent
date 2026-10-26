<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

abstract class BaseApiController extends Controller
{
    /**
     * Execute an endpoint with the shared API error contract.
     *
     * Validation and authentication middleware return this contract directly.
     */
    protected function executeWithErrorHandling(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            report($exception);

            Log::error('Unhandled API exception', [
                'exception' => $exception::class,
                'endpoint' => request()->method().' '.request()->path(),
            ]);

            return $this->apiResponse('An unexpected error occurred.', statusCode: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param  array<string, mixed>  $extraData
     */
    protected function apiResponse(string|array $message, mixed $data = null, int $statusCode = Response::HTTP_OK, array $extraData = []): JsonResponse
    {
        return responseJson($statusCode, $message, $data, extraData: $extraData);
    }
}
