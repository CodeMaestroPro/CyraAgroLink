<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;

/**
 * Health check endpoint for monitoring and load balancers.
 */
class HealthController extends BaseApiController
{
    /**
     * Return API health status.
     */
    public function __invoke(): JsonResponse
    {
        return $this->success([
            'status' => 'ok',
            'service' => config('cyra.name'),
            'version' => config('cyra.api_version'),
            'timestamp' => now()->toIso8601String(),
        ], 'CyraAgroLink API is healthy.');
    }
}
