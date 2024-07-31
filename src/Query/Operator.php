<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Query;

final class Operator
{
    public const ASC = 'A';
    public const DESC = 'D';
    public const OR = 'or';
    public const AND = 'and';

    private function __construct(
        public readonly string|int|float|bool|null $value,
        public readonly string $operator,
        public readonly bool $escape = true,
    ) {
    }

    public static function equal(string|int|float|bool|null $value): self
    {
        return new self($value, operator: '=');
    }

    public static function notEqual(string|int|float|bool|null $value): self
    {
        return new self($value, operator: '!=');
    }

    public static function like(string|int|float|bool|null $value): self
    {
        return new self($value, operator: 'like');
    }

    public static function greaterThan(string|int|float|bool|null $value): self
    {
        return new self($value, operator: '>');
    }

    public static function lessThan(string|int|float|bool|null $value): self
    {
        return new self($value, operator: '<');
    }

    public static function greaterThanOrEqual(string|int|float|bool|null $value): self
    {
        return new self($value, operator: '>=');
    }

    public static function lessThanOrEqual(string|int|float|bool|null $value): self
    {
        return new self($value, operator: '<=');
    }

    public static function between(string|int|float|bool|null $value1, string|int|float|bool|null $value2): self
    {
        return new self(
            \sprintf('%s %s', $value1, $value2),
            operator: 'between',
            escape: false,
        );
    }

    public static function empty(): self
    {
        return new self(value: 'empty', operator: 'is', escape: false);
    }

    public static function notEmpty(): self
    {
        return new self(value: 'empty', operator: 'is not', escape: false);
    }

    /** @param string[]|int[] $values */
    public static function in(array $values): self
    {
        $values = \array_map(static fn (string|int $value): string => \sprintf('"%s"', $value), $values);

        return new self(
            value: \sprintf('(%s)', \implode(',', $values)),
            operator: 'in',
            escape: false,
        );
    }

    /** @param string[]|int[] $values */
    public static function notIn(array $values): self
    {
        $values = \array_map(static fn (string|int $value): string => \sprintf('"%s"', $value), $values);

        return new self(
            value: \sprintf('(%s)', \implode(',', $values)),
            operator: 'not in',
            escape: false,
        );
    }
}
