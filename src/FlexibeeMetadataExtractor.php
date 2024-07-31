<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle;

use Driveto\FlexibeeOrmBundle\Attribute\AsFlexibeeRecord;
use Driveto\FlexibeeOrmBundle\Attribute\AsFlexibeeRelation;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

/** @TODO results can be cached for better performance */
class FlexibeeMetadataExtractor
{
    /**
     * @throws \ReflectionException
     * @param class-string $recordClass
     */
    public function buildCustomDetailParameters(string $recordClass): string
    {
        $parameters = [];

        foreach ((new \ReflectionClass($recordClass))->getProperties() as $property) {
            $isIgnored = $property->getAttributes(Ignore::class)[0] ?? false;

            if ($isIgnored !== false) {
                continue;
            }

            $nameAttribute = ($property->getAttributes(SerializedName::class)[0] ?? null)?->newInstance();

            $propertyName = $nameAttribute instanceof SerializedName
                ? $nameAttribute->getSerializedName()
                : $property->getName();

            [$propertyName] = \explode('@', $propertyName);

            $relationAttribute = ($property->getAttributes(AsFlexibeeRelation::class)[0] ?? null)?->newInstance();

            if ($relationAttribute instanceof AsFlexibeeRelation) {
                $nestedParameters = \sprintf(
                    '%s(%s)',
                    $propertyName,
                    $this->buildCustomDetailParameters($relationAttribute->class),
                );

                $parameters[$nestedParameters] = true;
            } else {
                $parameters[$propertyName] = true;
            }
        }

        return \implode(',', \array_keys($parameters));
    }

    /**
     * @throws \ReflectionException
     * @param class-string $recordClass
     * @return string[]
     */
    public function getRelations(string $recordClass): array
    {
        $relationNames = [];

        foreach ((new \ReflectionClass($recordClass))->getProperties() as $property) {
            $relationAttribute = ($property->getAttributes(AsFlexibeeRelation::class)[0] ?? null)?->newInstance();

            if ($relationAttribute instanceof AsFlexibeeRelation) {
                $relationNames[$relationAttribute->relationName] = true;
            }
        }

        return \array_keys($relationNames);
    }

    /**
     * @throws \LogicException|\ReflectionException
     * @param class-string $recordClass
     */
    public function getAgenda(string $recordClass): string
    {
        $reflection = new \ReflectionClass($recordClass);
        $recordAttribute = ($reflection->getAttributes(AsFlexibeeRecord::class)[0] ?? null)?->newInstance();

        if ($recordAttribute instanceof AsFlexibeeRecord) {
            return $recordAttribute->agenda;
        }

        throw new \LogicException(
            \sprintf('"%s" attribute must be defined!', AsFlexibeeRecord::class),
        );
    }
}
