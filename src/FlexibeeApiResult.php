<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class FlexibeeApiResult
{
    public const RESULT_SUCCESS = true;
    public const RESULT_FAILED = false;

    public function __construct(private bool $result, private int $responseCode, private string $errorMessage)
    {
    }

    public function isResultSuccessful(): bool
    {
        return $this->result;
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public static function createFromResponse(?ResponseInterface $response): self
    {
        if ($response === null || $response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            return new self(
                self::RESULT_FAILED,
                (int) $response?->getStatusCode(),
                $response?->getReasonPhrase() ?? 'NULL response',
            );
        }

        return new self(
            self::RESULT_SUCCESS,
            $response->getStatusCode(),
            $response->getReasonPhrase(),
        );
    }
}
