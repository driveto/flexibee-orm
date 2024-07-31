<?php

declare(strict_types=1);

namespace Driveto\FlexibeeOrmBundle;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FlexibeeNormalizerHelperTest extends TestCase
{
    public function testParsingIdFromRef(): void
    {
        $ref = '/c/driveto_s_r_o_/smlouva/579';
        $id = FlexibeeNormalizerHelper::parseIdFromRef($ref);

        self::assertSame('579', $id);
    }

    #[DataProvider('provideDenormalizeNullableDateData')]
    public function testDenormalizeNullableDate(string $dateString, ?\DateTimeImmutable $expectedDate): void
    {
        $actualDate = FlexibeeNormalizerHelper::denormalizeDate($dateString);

        self::assertEquals($expectedDate, $actualDate);
    }

    public static function provideDenormalizeNullableDateData(): iterable
    {
        yield 'empty date should denormalize as null' => [
            'dateString' => '',
            'expectedDate' => null,
        ];

        yield 'date string should denormalize correctly' => [
            'dateString' => '2023-03-20',
            'expectedDate' => new \DateTimeImmutable('2023-03-20 00:00:00'),
        ];

        yield 'datetime string should denormalize correctly' => [
            'dateString' => '2023-04-21 12:34:55',
            'expectedDate' => new \DateTimeImmutable('2023-04-21 12:34:55'),
        ];

        yield 'date string with utc timezone should denormalize correctly' => [
            'dateString' => '2023-05-18Z',
            'expectedDate' => new \DateTimeImmutable('2023-05-18 00:00:00+0000'),
        ];

        yield 'date string with specific timezone should denormalize correctly' => [
            'dateString' => '2023-07-20+0400',
            'expectedDate' => new \DateTimeImmutable('2023-07-20 00:00:00+0400'),
        ];
    }

    #[DataProvider('provideNormalizeNullableDateData')]
    public function testNormalizeNullableDate(?\DateTimeImmutable $date, string $expectedDateString): void
    {
        $actualDateString = FlexibeeNormalizerHelper::normalizeDate($date);

        self::assertEquals($expectedDateString, $actualDateString);
    }

    public static function provideNormalizeNullableDateData(): iterable
    {
        yield 'empty date should denormalize as null' => [
            'date' => null,
            'expectedDateString' => '',
        ];

        yield 'date string should denormalize correctly' => [
            'date' => new \DateTimeImmutable('2023-03-20 00:00:00'),
            'expectedDateString' => '2023-03-20',
        ];

        yield 'datetime string should denormalize correctly' => [
            'date' => new \DateTimeImmutable('2023-04-21 12:34:55'),
            'expectedDateString' => '2023-04-21',
        ];
    }

    #[DataProvider('provideParseBooleanData')]
    public function testParseBoolean(string|bool $stringBool, ?bool $expectedBool): void
    {
        $actualBool = FlexibeeNormalizerHelper::parseBool($stringBool);

        self::assertEquals($expectedBool, $actualBool);
    }

    public static function provideParseBooleanData(): iterable
    {
        yield 'true in string is parsed as true' => [
            'stringBool' => 'true',
            'expectedBool' => true,
        ];

        yield 'false in string is parsed as true' => [
            'stringBool' => 'false',
            'expectedBool' => false,
        ];

        yield 'empty string is parsed as null' => [
            'stringBool' => '',
            'expectedBool' => null,
        ];

        yield 'unknown value is parsed as null' => [
            'stringBool' => 'asdfas',
            'expectedBool' => null,
        ];

        yield 'zero is parsed as null' => [
            'stringBool' => '0',
            'expectedBool' => null,
        ];

        yield 'true is passed without modification' => [
            'stringBool' => true,
            'expectedBool' => true,
        ];

        yield 'false is passed without modification' => [
            'stringBool' => false,
            'expectedBool' => false,
        ];
    }
}
