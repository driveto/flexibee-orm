<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Tests;

use Driveto\FlexibeeOrmBundle\Attribute\AsFlexibeeRecord;
use Driveto\FlexibeeOrmBundle\FlexibeeMetadataExtractor;
use Driveto\FlexibeeOrmBundle\Tests\Fixtures\DummyRecord;
use Driveto\FlexibeeOrmBundle\Tests\Fixtures\DummyRecordItem;
use PHPUnit\Framework\TestCase;

final class FlexibeeMetadataExtractorTest extends TestCase
{
    private FlexibeeMetadataExtractor $flexibeeMetadataExtractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->flexibeeMetadataExtractor = new FlexibeeMetadataExtractor();
    }

    public function testShouldBuildCustomDetailParametersExcludingIgnoredOnes(): void
    {
        self::assertSame(
            'privateProperty,attributeName,some,items(foo,foobar),polozka(foo,foobar)',
            $this->flexibeeMetadataExtractor->buildCustomDetailParameters(DummyRecord::class),
        );
    }

    public function testShouldReturnEmptyArrayIfNoRelationsArePresent(): void
    {
        self::assertEmpty($this->flexibeeMetadataExtractor->getRelations(DummyRecordItem::class));
    }

    public function testShouldExtractRelations(): void
    {
        self::assertSame(['items', 'relace'], $this->flexibeeMetadataExtractor->getRelations(DummyRecord::class));
    }

    public function testShouldExtractAgendaName(): void
    {
        self::assertSame('dummy-record', $this->flexibeeMetadataExtractor->getAgenda(DummyRecord::class));
        self::assertSame('dummy-record-item', $this->flexibeeMetadataExtractor->getAgenda(DummyRecordItem::class));
    }

    public function testShouldThrowExceptionIfTryingToExtractAgendaFromClassWithoutDefinedAttribute(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(\sprintf('"%s" attribute must be defined!', AsFlexibeeRecord::class));

        $this->flexibeeMetadataExtractor->getAgenda(\stdClass::class);
    }
}
