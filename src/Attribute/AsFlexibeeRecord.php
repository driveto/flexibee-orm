<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsFlexibeeRecord
{
    public function __construct(public readonly string $agenda)
    {
    }
}
