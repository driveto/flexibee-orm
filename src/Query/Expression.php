<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Query;

/**
 * @phpstan-import-type CriterionType from QueryBuilder
 */
class Expression
{
    /**
     * @param array<array<string, CriterionType|non-empty-array<CriterionType>>|Expression[]> $criteria
     */
    private function __construct(public readonly string $logicOperator, public readonly array $criteria)
    {
    }

    /**
     * @param array<string, CriterionType|non-empty-array<CriterionType>>|Expression[] $criteria
     */
    public static function or(array ...$criteria): self
    {
        return new self(Operator::OR, $criteria);
    }

    /**
     * @param array<string, CriterionType|non-empty-array<CriterionType>>|Expression[] $criteria
     */
    public static function and(array ...$criteria): self
    {
        return new self(Operator::AND, $criteria);
    }
}
