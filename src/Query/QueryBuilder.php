<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Query;

use Driveto\FlexibeeOrmBundle\FlexibeeMetadataExtractor;

/**
 * @phpstan-type CriterionType = Operator|scalar|null
 */
class QueryBuilder
{
    private string $agenda;
    private string $select = 'full';
    private string $where = '';
    private int $limit = 0;
    private int $offset = 0;
    private ?string $orderBy = null;
    private array $relations = [];

    /**
     * @param non-empty-string $agenda
     */
    public function __construct(
        string $agenda,
        private readonly FlexibeeMetadataExtractor $flexibeeMetadataExtractor,
    ) {
        $this->from($agenda);
    }

    /**
     * @param non-empty-array<string>|string|null $fields
     */
    public function select(string|array|null $fields = null): self
    {
        $this->select = 'full';
        $this->relations = [];

        if (\is_string($fields) && $fields !== '' && $fields !== '*') {
            $this->select = 'custom:' . $this->removeUnwantedSymbols($fields);
        }

        if (\is_array($fields)) {
            $this->select = 'custom:' . \implode(',', \array_map([$this, 'removeUnwantedSymbols'], $fields));
        }

        return $this;
    }

    /**
     * @param non-empty-string|class-string $agenda
     */
    public function from(string $agenda): self
    {
        if (\class_exists($agenda)) {
            $this->agenda = $this->flexibeeMetadataExtractor->getAgenda($agenda);
            $this->select($this->flexibeeMetadataExtractor->buildCustomDetailParameters($agenda));
            $this->relations = $this->flexibeeMetadataExtractor->getRelations($agenda);

            return $this;
        }

        $this->agenda = $agenda;

        return $this;
    }

    /**
     * @param non-empty-array<string, CriterionType|array<CriterionType>>|non-empty-string|Expression[]|Expression $query
     */
    public function where(string|array|Expression $query): self
    {
        $this->where = '';

        return $this->andWhere($query);
    }

    /**
     * @param non-empty-array<string, CriterionType|array<CriterionType>>|non-empty-string|Expression[]|Expression $query
     */
    public function andWhere(string|array|Expression $query): self
    {
        $this->addWhere($query, Operator::AND);

        return $this;
    }

    /**
     * @param non-empty-array<string, CriterionType|array<CriterionType>>|non-empty-string|Expression|Expression[] $query
     * @return $this
     */
    public function orWhere(string|array|Expression $query): self
    {
        $this->addWhere($query, Operator::OR);

        return $this;
    }

    /**
     * @param non-empty-array<string, CriterionType|array<CriterionType>>|non-empty-string|Expression|Expression[] $query
     * @param Operator::AND|Operator::OR $operator
     */
    private function addWhere(string|array|Expression $query, string $operator): void
    {
        if ($query instanceof Expression) {
            $query = $this->buildCriterionQueryFromExpression($query);
        }

        if (\is_array($query)) {
            $query = $this->buildCriteriaQuery($query);
        }

        if ($this->where === '') {
            $this->where = $query;

            return;
        }

        $this->where .= \sprintf(' %s (%s)', $operator, $query);
    }

    /**
     * @param non-empty-string $relation
     */
    public function join(string $relation): self
    {
        $this->relations[] = $this->removeUnwantedSymbols($relation);

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param non-empty-string $field
     * @param Operator::ASC|Operator::DESC $direction
     */
    public function orderBy(string $field, string $direction): self
    {
        $this->orderBy = \sprintf('%s@%s', $field, $direction);

        return $this;
    }

    public function count(): self
    {
        $this->select('id')->limit(1)->offset(0);

        if ($this->where === '') {
            $this->where(['id' => Operator::greaterThan(0)]);
        }

        return $this;
    }

    /**
     * @throws \LogicException
     */
    public function getQuery(): string
    {
        if ($this->where === '') {
            throw new \LogicException('At least one condition must be given!');
        }

        $query = \sprintf(
            '%s/(%s)?add-row-count=true&start=%d&limit=%d&detail=%s',
            $this->agenda,
            $this->where,
            $this->offset,
            $this->limit,
            $this->select,
        );

        if (\count($this->relations) > 0) {
            $query .= '&relations=' . \implode(',', $this->relations);
        }

        if ($this->orderBy !== null) {
            $query .= \sprintf('&order=%s', $this->orderBy);
        }

        return $query;
    }

    /**
     * @param non-empty-array<string, CriterionType|array<CriterionType>>|Expression[] $criteria
     */
    private function buildCriteriaQuery(array $criteria, string $logicOperator = Operator::AND): string
    {
        $criteriaQuery = [];

        foreach ($criteria as $criterionKey => $criterionValue) {
            if ($criterionValue instanceof Expression) {
                $criteriaQuery[] = $this->buildCriterionQueryFromExpression($criterionValue, nested: true);

                continue;
            }

            if (\is_array($criterionValue)) {
                foreach ($criterionValue as $nestedValue) {
                    $criteriaQuery[] = $this->buildCriterionQuery($criterionKey, $nestedValue);
                }

                continue;
            }

            $criteriaQuery[] = $this->buildCriterionQuery($criterionKey, $criterionValue);
        }

        return \implode(\sprintf(' %s ', $logicOperator), $criteriaQuery);
    }

    private function buildCriterionQuery(string $key, Operator|float|bool|int|string|null $value): string
    {
        if (!$value instanceof Operator) {
            $value = Operator::equal($value);
        }

        $operator = $value->operator;
        $escape = $value->escape;
        $value = $value->value;

        if (\is_bool($value) === true) {
            $value = $value ? 'true' : 'false';
            $escape = false;
        }

        if ($value === null) {
            return \sprintf('%s %s ""', $key, $operator);
        }

        if ($escape === false) {
            return \sprintf('%s %s %s', $key, $operator, \rawurlencode((string) $value));
        }

        return \sprintf('%s %s "%s"', $key, $operator, \rawurlencode((string) $value));
    }

    private function buildCriterionQueryFromExpression(Expression $expression, bool $nested = false): string
    {
        $expressionQuery = [];

        foreach ($expression->criteria as $key => $criterion) {
            if (\is_array($criterion) === true) {
                $expressionQuery[] = $this->buildCriteriaQuery($criterion, $expression->logicOperator);

                continue;
            }

            if ($criterion instanceof Expression) {
                $expressionQuery[] = $this->buildCriterionQueryFromExpression($criterion, nested: true);

                continue;
            }

            $expressionQuery[] = $this->buildCriterionQuery($key, $criterion);
        }

        $query = \implode(\sprintf(' %s ', $expression->logicOperator), $expressionQuery);

        if ($nested === true) {
            return \sprintf('(%s)', $query);
        }

        return $query;
    }

    private function removeUnwantedSymbols(string $value): string
    {
        return \trim(\str_replace([' ', "\n", "\n\r", "\t"], '', $value), ',');
    }
}
