<?php

declare(strict_types=1);

namespace App\Traits;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Convenience helpers for controllers returning standardized API responses.
 */
trait ApiResponds
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        ?array $meta = null
    ): JsonResponse {
        return ApiResponse::success($data, $message, $status, $meta);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    protected function error(
        string $message = 'An error occurred.',
        int $status = 400,
        ?array $errors = null,
        ?string $errorCode = null
    ): JsonResponse {
        return ApiResponse::error($message, $status, $errors, $errorCode);
    }

    protected function created(mixed $data = null, string $message = 'Resource created successfully.'): JsonResponse
    {
        return ApiResponse::created($data, $message);
    }

    protected function noContent(string $message = 'Operation completed successfully.'): JsonResponse
    {
        return ApiResponse::noContent($message);
    }
}
