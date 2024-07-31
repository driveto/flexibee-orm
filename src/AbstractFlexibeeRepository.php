<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle;

use Driveto\FlexibeeOrmBundle\Exception\MultipleRecordsFoundException;
use Driveto\FlexibeeOrmBundle\Exception\UnableToDeleteRecordException;
use Driveto\FlexibeeOrmBundle\Exception\UnableToGetRecordException;
use Driveto\FlexibeeOrmBundle\Exception\UnableToPersistRecordException;
use Driveto\FlexibeeOrmBundle\Query\Expression;
use Driveto\FlexibeeOrmBundle\Query\Operator;
use Driveto\FlexibeeOrmBundle\Query\Query;
use Driveto\FlexibeeOrmBundle\Query\QueryBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\TransferStats;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @template T of object
 *
 * @phpstan-import-type CriterionType from QueryBuilder
 */
#[AutoconfigureTag('app.flexibee.repository')]
abstract class AbstractFlexibeeRepository
{
    protected const FETCH_TYPE_RAW = 0;
    protected const FETCH_TYPE_OBJECT = 1;
    protected const FETCH_TYPE_ARRAY = 2;
    protected const ROOT_NODE = 'winstrom';
    protected const ROW_COUNT_NODE = '@rowCount';

    private Client $flexibeeApiClient;
    private Serializer $serializer;
    private FlexibeeMetadataExtractor $flexibeeMetadataExtractor;

    /** @var Query[] */
    private array $queries = [];

    /**
     * @param class-string<T> $recordClass
     */
    public function __construct(
        private readonly string $recordClass,
    ) {
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->recordClass, $this->flexibeeMetadataExtractor);
    }

    /**
     * @throws UnableToGetRecordException
     * @param int-mask-of<self::FETCH_TYPE_*> $fetchType
     * @return ($fetchType is int<self::FETCH_TYPE_OBJECT, self::FETCH_TYPE_OBJECT> ? T[] : array)
     */
    protected function runCustomQuery(string $query, int $fetchType = self::FETCH_TYPE_OBJECT): array
    {
        $response = $this->runQuery($query);

        return $fetchType === self::FETCH_TYPE_OBJECT
            ? $this->mapResponse($response)
            : $this->decodeResponse($response, $fetchType);
    }

    /**
     * @throws UnableToGetRecordException
     * @return T|null
     */
    protected function find(string|int $id): ?object
    {
        $query = $this->createQueryBuilder()
            ->where(['id' => Operator::equal($id)])
            ->limit(1)
            ->getQuery()
        ;

        $record = $this->runCustomQuery($query);

        return $record === [] ? null : \reset($record);
    }

    /**
     * @throws UnableToGetRecordException
     * @throws \LogicException
     * @param non-empty-array<string, CriterionType|array<CriterionType>|Expression> $criteria
     * @param array<non-empty-string, Operator::ASC|Operator::DESC> $orderBy
     * @param int-mask-of<self::FETCH_TYPE_*> $fetchType
     * @return ($fetchType is int<self::FETCH_TYPE_OBJECT, self::FETCH_TYPE_OBJECT> ? T[] : array)
     */
    protected function findBy(
        array $criteria,
        array $orderBy = [],
        int $offset = 0,
        int $limit = 0,
        int $fetchType = self::FETCH_TYPE_OBJECT,
    ): array {
        if (\count($orderBy) > 1) {
            throw new \LogicException('Only single field is supported in the order by clause!');
        }

        $queryBuilder = $this->createQueryBuilder()
            ->where($criteria)
            ->offset($offset)
            ->limit($limit)
        ;

        if (\count($orderBy) === 1) {
            $queryBuilder->orderBy(field: \key($orderBy), direction: \reset($orderBy));
        }

        return $this->runCustomQuery($queryBuilder->getQuery(), $fetchType);
    }

    /**
     * @throws MultipleRecordsFoundException
     * @throws UnableToGetRecordException
     * @param non-empty-array<string, CriterionType|array<CriterionType>|Expression> $criteria
     * @param int-mask-of<self::FETCH_TYPE_*> $fetchType
     * @return ($fetchType is int<self::FETCH_TYPE_OBJECT, self::FETCH_TYPE_OBJECT> ? T : array)|null
     */
    protected function findOneBy(array $criteria, int $fetchType = self::FETCH_TYPE_OBJECT): object|array|null
    {
        $records = $this->findBy($criteria, fetchType: $fetchType);

        if (\count($records) === 0) {
            return null;
        }

        if (\count($records) > 1) {
            throw new MultipleRecordsFoundException();
        }

        return \reset($records);
    }

    /**
     * @throws UnableToPersistRecordException
     * @param T $record
     * @return string Record's identifier
     */
    public function persist($record): string
    {
        try {
            $normalizedData = $this->serializer->normalize(
                $record,
                'array',
                [AbstractObjectNormalizer::SKIP_NULL_VALUES => true],
            );
        } catch (ExceptionInterface $e) {
            throw new UnableToPersistRecordException('Unable to normalize given data!', previous: $e);
        }

        $requestData = [
            'json' => [
                self::ROOT_NODE => [
                    '@version' => '1.0',
                    $this->flexibeeMetadataExtractor->getAgenda($this->recordClass) => $normalizedData,
                ],
            ],
        ];

        try {
            $response = $this->flexibeeApiClient->put(
                $this->flexibeeMetadataExtractor->getAgenda($this->recordClass),
                $requestData,
            );
        } catch (ClientExceptionInterface $e) {
            $data = $this->serializer->encode($normalizedData, JsonEncoder::FORMAT);
            $responseBody = null;

            if ($e instanceof RequestException) {
                $responseBody = $e->getResponse()?->getBody()->getContents();
            }

            throw new UnableToPersistRecordException(
                \sprintf(
                    'Unable to persist data "%s" to agenda "%s"!',
                    $data,
                    $this->flexibeeMetadataExtractor->getAgenda($this->recordClass),
                ),
                $responseBody,
                previous: $e,
            );
        }

        return (string) $this->decodeResponse($response, self::FETCH_TYPE_RAW)['results'][0]['id'];
    }

    /**
     * @throws UnableToDeleteRecordException
     * @throws \LogicException
     * @param T $record
     */
    protected function remove($record): void
    {
        if (isset($record->id) === false) {
            throw new \LogicException('Property "id" is expected in record object.');
        }

        try {
            $this->flexibeeApiClient->delete(\sprintf(
                '%s/%s',
                $this->flexibeeMetadataExtractor->getAgenda($this->recordClass),
                $record->id,
            ));
        } catch (ClientExceptionInterface $e) {
            throw new UnableToDeleteRecordException('Unable to delete record ID "%s"!', previous: $e);
        }
    }

    /**
     * @throws UnableToGetRecordException
     * @param array<string, CriterionType|array<CriterionType>> $criteria
     */
    protected function count(array $criteria): int
    {
        $queryBuilder = $this->createQueryBuilder()->count();

        if (\count($criteria) !== 0) {
            $queryBuilder->where($criteria);
        }

        return (int) $this->runCustomQuery($queryBuilder->getQuery(), self::FETCH_TYPE_RAW)[self::ROW_COUNT_NODE];
    }

    /**
     * @throws UnableToGetRecordException
     */
    private function runQuery(string $query): ResponseInterface
    {
        $query = new Query($query);
        $this->queries[] = $query;

        try {
            $response = $this->flexibeeApiClient->get(
                $query->__toString(),
                ['on_stats' => static function (TransferStats $stats) use ($query): void {
                    if ($stats->getTransferTime() === null) {
                        return;
                    }

                    $query->executionTime = \round($stats->getTransferTime() * 1000, 2);
                }],
            );
        } catch (ClientExceptionInterface $e) {
            $query->succeeded = false;

            throw new UnableToGetRecordException(
                \sprintf('An error occurred for query: "%s"', $query),
                previous: $e,
            );
        }

        $query->succeeded = true;

        return $response;
    }

    /**
     * @return T[]
     */
    private function mapResponse(ResponseInterface $response): array
    {
        $records = $this->decodeResponse($response);

        if (\count($records) === 0) {
            return [];
        }

        return $this->serializer->denormalize($records, $this->recordClass . '[]');
    }

    /**
     * @param int-mask-of<self::FETCH_TYPE_*> $fetchType
     */
    private function decodeResponse(ResponseInterface $response, int $fetchType = self::FETCH_TYPE_ARRAY): array
    {
        /** @var array $decodedJson */
        $decodedJson = $this->serializer->decode(
            $response->getBody()->getContents(),
            JsonEncoder::FORMAT,
            [JsonDecode::ASSOCIATIVE => true],
        );

        if ($fetchType === self::FETCH_TYPE_ARRAY) {
            return $decodedJson[self::ROOT_NODE][$this->flexibeeMetadataExtractor->getAgenda($this->recordClass)];
        }

        return $decodedJson[self::ROOT_NODE];
    }

    #[Required]
    public function setClient(Client $flexibeeApiClient): void
    {
        $this->flexibeeApiClient = $flexibeeApiClient;
    }

    #[Required]
    public function setSerializer(Serializer $flexibeeSerializer): void
    {
        $this->serializer = $flexibeeSerializer;
    }

    #[Required]
    public function setFlexibeeMetadataExtractor(FlexibeeMetadataExtractor $flexibeeMetadataExtractor): void
    {
        $this->flexibeeMetadataExtractor = $flexibeeMetadataExtractor;
    }

    /**
     * @return Query[]
     */
    public function getQueries(): array
    {
        return $this->queries;
    }
}
