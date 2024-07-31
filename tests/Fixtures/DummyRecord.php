<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Tests\Fixtures;

use Driveto\FlexibeeOrmBundle\Attribute\AsFlexibeeRecord;
use Driveto\FlexibeeOrmBundle\Attribute\AsFlexibeeRelation;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[AsFlexibeeRecord('dummy-record')]
class DummyRecord
{
    public string $privateProperty;

    #[SerializedName('attributeName')]
    public string $privatePropertyWithAttribute;

    #[SerializedName('some@ref')]
    public string $someRef;

    #[AsFlexibeeRelation('items', DummyRecordItem::class)]
    public array $items = [];

    #[SerializedName('polozka')]
    #[AsFlexibeeRelation('relace', DummyRecordItem::class)]
    public array $anotherItems = [];

    #[Ignore]
    private readonly string $publicProperty; // @phpstan-ignore-line
}
