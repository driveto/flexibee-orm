<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Query;

class Query implements \Stringable
{
    public ?float $executionTime = null;
    public ?bool $succeeded = null;

    public function __construct(private readonly string $query)
    {
    }

    public function __toString(): string
    {
        return $this->query;
    }
}
