<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Exception thrown when a business rule is violated.
 */
class BusinessLogicException extends DomainException
{
    public function __construct(
        string $message = 'Business rule violation.',
        ?string $errorCode = 'BUSINESS_LOGIC_ERROR',
        int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY
    ) {
        parent::__construct($message, $statusCode, $errorCode);
    }
}
