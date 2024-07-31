<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class AsFlexibeeRelation
{
    public function __construct(
        public readonly string $relationName,
        /** @var class-string */
        public readonly string $class,
    ) {
    }
}
