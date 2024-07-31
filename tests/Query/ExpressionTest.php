<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Tests\Query;

use Driveto\FlexibeeOrmBundle\Query\Expression;
use Driveto\FlexibeeOrmBundle\Query\Operator;
use Driveto\FlexibeeOrmBundle\Query\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class ExpressionTest extends TestCase
{
    public function testShouldCreateOrExpression(): void
    {
        $criteria = [['foo' => 'bar'], ['bar' => Operator::notEqual('foo')]];
        $expression = Expression::or(...$criteria);

        self::assertSame(Operator::OR, $expression->logicOperator);
        self::assertSame($criteria, $expression->criteria);
    }

    public function testShouldCreateAndExpression(): void
    {
        $criteria = [['foo' => Operator::notEqual('bar')], ['bar' => 'foo']];
        $expression = Expression::and(...$criteria);

        self::assertSame(Operator::AND, $expression->logicOperator);
        self::assertSame($criteria, $expression->criteria);
    }
}
