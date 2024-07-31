<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle\Tests\Fixtures;

use Driveto\FlexibeeOrmBundle\Attribute\AsFlexibeeRecord;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[AsFlexibeeRecord('dummy-record-item')]
class DummyRecordItem
{
    public string $foo;

    #[SerializedName('foobar')]
    public string $bar;

    #[Ignore]
    public int $ignoreMe;
}
