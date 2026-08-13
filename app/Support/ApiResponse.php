<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Standardized JSON API response builder for CyraAgroLink.
 */
final class ApiResponse
{
    /**
     * Build a successful JSON response.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = Response::HTTP_OK,
        ?array $meta = null
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Build an error JSON response.
     *
     * @param  array<string, mixed>|null  $errors
     */
    public static function error(
        string $message = 'An error occurred.',
        int $status = Response::HTTP_BAD_REQUEST,
        ?array $errors = null,
        ?string $errorCode = null
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'data' => null,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Build a created (201) response.
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully.'
    ): JsonResponse {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Build a no-content style success response.
     */
    public static function noContent(string $message = 'Operation completed successfully.'): JsonResponse
    {
        return self::success(null, $message, Response::HTTP_OK);
    }
}
