<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Exceptions;

use Throwable;

final class EzpayApiException extends EzpayException
{
    /**
     * @param array<string,mixed> $rawResponse
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $rawResponse = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
