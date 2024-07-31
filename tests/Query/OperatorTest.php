<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Query;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OperatorTest extends TestCase
{
    #[DataProvider('provideOperators')]
    public function testShouldCreate(Operator $operatorInstance, string $operator, mixed $value, bool $escape): void
    {
        self::assertSame($operator, $operatorInstance->operator);
        self::assertSame($value, $operatorInstance->value);
        self::assertSame($escape, $operatorInstance->escape);
    }

    public static function provideOperators(): array
    {
        return [
            // $operatorInstance, $operator, $value, $escape
            [Operator::equal('bar'), '=', 'bar', true],
            [Operator::notEqual('foo'), '!=', 'foo', true],
            [Operator::lessThan(0), '<', 0, true],
            [Operator::greaterThan(100), '>', 100, true],
            [Operator::lessThanOrEqual('42'), '<=', '42', true],
            [Operator::greaterThanOrEqual('buzz'), '>=', 'buzz', true],
            [Operator::empty(), 'is', 'empty', false],
            [Operator::notEmpty(), 'is not', 'empty', false],
            [Operator::between(1, 5), 'between', '1 5', false],
            [Operator::like('boofar'), 'like', 'boofar', true],
        ];
    }
}
