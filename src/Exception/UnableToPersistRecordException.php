<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Exception;

class UnableToPersistRecordException extends \Exception implements FlexibeeRecordException
{
    public function __construct(
        string $message,
        public readonly ?string $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}
