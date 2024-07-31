<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\DataCollector;

use Driveto\FlexibeeOrmBundle\AbstractFlexibeeRepository;
use Driveto\FlexibeeOrmBundle\Query\Query;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FlexibeeDataCollector extends AbstractDataCollector
{
    /**
     * @param AbstractFlexibeeRepository[] $flexibeeRepositories
     */
    public function __construct(private readonly iterable $flexibeeRepositories)
    {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        foreach ($this->flexibeeRepositories as $flexibeeRepository) {
            $this->data[$flexibeeRepository::class] = $flexibeeRepository->getQueries();
        }
    }

    public static function getTemplate(): ?string
    {
        return 'profiler/flexibee-orm.html.twig';
    }

    /**
     * @return array<class-string, Query[]>
     */
    public function getQueriesByRepository(): array
    {
        return $this->data; // @phpstan-ignore-line
    }

    public function getQueriesCount(): int
    {
        $count = 0;

        foreach ($this->getQueriesByRepository() as $queries) {
            $count += \count($queries);
        }

        return $count;
    }

    public function getFailedQueriesCount(): int
    {
        $count = 0;

        foreach ($this->getQueriesByRepository() as $queries) {
            foreach ($queries as $query) {
                if ($query->succeeded === false) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    public function getQueriesExecutionTime(): float
    {
        $executionTime = 0.0;

        foreach ($this->getQueriesByRepository() as $queries) {
            foreach ($queries as $query) {
                $executionTime += (float) $query->executionTime;
            }
        }

        return $executionTime;
    }
}
