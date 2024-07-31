<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Tests\Query;

use Driveto\FlexibeeOrmBundle\Query\Expression;
use Driveto\FlexibeeOrmBundle\Query\Operator;
use Driveto\FlexibeeOrmBundle\Query\QueryBuilder;
use Driveto\FlexibeeOrmBundle\Tests\Fixtures\DummyRecord;
use Driveto\FlexibeeOrmBundle\FlexibeeMetadataExtractor;
use PHPUnit\Framework\TestCase;

final class QueryBuilderTest extends TestCase
{
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilder = new QueryBuilder('test-agenda', new FlexibeeMetadataExtractor());
    }

    public function testShouldAssembleQueryAutomaticallyFromGivenRecordClass(): void
    {
        self::assertSame(
            'dummy-record/(0 != 0)?add-row-count=true&start=0&limit=0&detail=custom:privateProperty,attributeName,some,'
            . 'items(foo,foobar),polozka(foo,foobar)&relations=items,relace',
            $this->queryBuilder->from(DummyRecord::class)->where('0 != 0')->getQuery(),
        );
    }

    public function testShouldAssembleQueryMadeFromExpressionsAndOperators(): void
    {
        $query = $this->queryBuilder
            ->where(
                Expression::or(
                    ['foo' => 'bar', 'bar' => 'foo'],
                    ['bar' => Operator::notEqual(null)],
                ),
            )
            ->andWhere(['ufo' => Operator::equal(true), 'life' => Operator::lessThan(42), 'trolling' => false])
            ->andWhere(
                [
                    Expression::or(
                        ['name' => Operator::notEqual('Jane')],
                        ['name' => Operator::notEqual('John')],
                        [Expression::and(['text' => 'spa ce!'], ['text' => Operator::notEqual('???')])],
                    ),
                    'A' => 'B',
                ],
            )
            ->orWhere(
                [
                    'this' => Operator::greaterThan('that'),
                    'that' => Operator::notEmpty(),
                    Expression::and(['lorem' => Operator::empty(), 'ipsum' => Operator::greaterThanOrEqual('')]),
                ],
            )
            ->getQuery()
        ;

        self::assertSame(
            'test-agenda/(foo = "bar" or bar = "foo" or bar != ""'
            . ' and (ufo = true and life < "42" and trolling = false)'
            . ' and ((name != "Jane" or name != "John" or (text = "spa%20ce%21" and text != "%3F%3F%3F")) and A = "B")'
            . ' or (this > "that" and that is not empty and (lorem is empty and ipsum >= ""))'
            . ')?add-row-count=true&start=0&limit=0&detail=full',
            $query,
        );
    }

    public function testShouldAssembleSimpleQueryDefinedAllManually(): void
    {
        $query = $this->queryBuilder
            ->select('*')
            ->from('awesomeness')
            ->join('joy, happiness')
            ->join('kindness')
            ->where('foo > 100')
            ->andWhere('bar != "foo" or foo != "bar"')
            ->orWhere('true is not false')
            ->limit(50)
            ->offset(100)
            ->orderBy('id', Operator::DESC)
            ->getQuery()
        ;

        self::assertSame(
            'awesomeness/(foo > 100 and (bar != "foo" or foo != "bar") or (true is not false))'
            . '?add-row-count=true&start=100&limit=50&detail=full&relations=joy,happiness,kindness&order=id@D',
            $query,
        );
    }

    public function testShouldAssembleBasicCountQuery(): void
    {
        self::assertSame(
            'test-agenda/(id > "0")?add-row-count=true&start=0&limit=1&detail=custom:id',
            $this->queryBuilder->count()->getQuery(),
        );

        self::assertSame(
            'test-agenda/(0 < 1)?add-row-count=true&start=0&limit=1&detail=custom:id',
            $this->queryBuilder->count()->where('0 < 1')->getQuery(),
        );

        self::assertSame(
            'test-agenda/(name = "John Doe")?add-row-count=true&start=0&limit=1&detail=custom:id',
            $this->queryBuilder->where('name = "John Doe"')->count()->getQuery(),
            'Order of method call should not be relevant',
        );
    }

    public function testShouldSelectOnlyDefinedFields(): void
    {
        self::assertSame(
            'test-agenda/(1 = 1)?add-row-count=true&start=0&limit=0&detail=custom:field,column',
            $this->queryBuilder->select(', field, column, ')->where('1 = 1')->getQuery(),
        );

        self::assertSame(
            'test-agenda/(1 = 1)?add-row-count=true&start=0&limit=0&detail=custom:uno,duo,tres',
            $this->queryBuilder->select(['uno', ',duo', 'tres ,'])->where('1 = 1')->getQuery(),
        );

        self::assertSame(
            'test-agenda/(1 = 1)?add-row-count=true&start=0&limit=0&detail=full',
            $this->queryBuilder->select()->where('1 = 1')->getQuery(),
        );
    }
}
