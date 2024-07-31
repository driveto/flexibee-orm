<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle;

class FlexibeeNormalizerHelper
{
    public static function parseIdFromRef(string $ref): ?string
    {
        $id = \substr($ref, (\strrpos($ref, '/') ?: 0) + 1);

        return $id === '' ? null : $id;
    }

    public static function denormalizeDate(string $dateString): ?\DateTimeImmutable
    {
        if ($dateString === '') {
            return null;
        }

        return new \DateTimeImmutable($dateString);
    }

    public static function normalizeDate(?\DateTimeImmutable $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->format('Y-m-d');
    }

    public static function parseBool(bool|string $value): ?bool
    {
        if (\is_bool($value)) {
            return $value;
        } else {
            return match ($value) {
                'true' => true,
                'false' => false,
                default => null,
            };
        }
    }

    public static function removeCodePrefix(string $value): string
    {
        return \str_replace('code:', '', $value);
    }
}
