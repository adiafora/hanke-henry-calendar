<?php

declare(strict_types=1);

namespace Tests\Hhpc\Calendar;

use Adiafora\Hhpc\Config\HhpcConfig;
use Adiafora\Hhpc\Exception\InvalidDateException;
use Adiafora\Hhpc\HhpcDate;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HhpcDateTest extends TestCase
{
    public function testCreate(): void
    {
        $date = HhpcDate::create(2026, 13, 5, 12, 30, 45, 123456);

        $this->assertEquals(2026, $date->getYear());
        $this->assertEquals(13, $date->getMonth());
        $this->assertEquals(5, $date->getDay());
        $this->assertEquals(12, $date->getHour());
        $this->assertEquals(30, $date->getMinute());
        $this->assertEquals(45, $date->getSecond());
        $this->assertEquals(123456, $date->getMicrosecond());
    }

    /**
     * @param array<string, array{0: string, 1: string, 2: array<string, int>}> $expected
     */
    #[DataProvider('validFormatsProvider')]
    public function testCreateFromFormat(
        string $format,
        string $datetime,
        array $expected
    ): void {
        $date = HhpcDate::createFromFormat($format, $datetime);

        $this->assertEquals($expected['year'], $date->getYear());
        $this->assertEquals($expected['month'], $date->getMonth());
        $this->assertEquals($expected['day'], $date->getDay());

        $this->assertEquals($expected['hour'] ?? 0, $date->getHour());
        $this->assertEquals($expected['minute'] ?? 0, $date->getMinute());
        $this->assertEquals($expected['second'] ?? 0, $date->getSecond());
        $this->assertEquals($expected['microsecond'] ?? 0, $date->getMicrosecond());
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, int>}>
     */
    public static function validFormatsProvider(): array
    {
        return [
            'Standard ISO' => [
                'Y-m-d H:i:s',
                '2026-02-15 14:30:05',
                ['year' => 2026, 'month' => 2, 'day' => 15, 'hour' => 14, 'minute' => 30, 'second' => 5]
            ],
            'Date Only' => [
                'Y-m-d',
                '2026-01-30',
                ['year' => 2026, 'month' => 1, 'day' => 30]
            ],
            'Xtra Month (Case Insensitive)' => [
                'Y-M-d',
                '2026-Xtra-07',
                ['year' => 2026, 'month' => 13, 'day' => 7]
            ],
            'Xtra Month (Lower Case)' => [
                'Y-M-d',
                '2026-xtra-01',
                ['year' => 2026, 'month' => 13, 'day' => 1]
            ],
            'With Microseconds (3 digits)' => [
                'Y-m-d H:i:s.u',
                '2026-13-06 14:30:05.123',
                ['year' => 2026, 'month' => 13, 'day' => 6, 'hour' => 14, 'minute' => 30, 'second' => 5, 'microsecond' => 123000]
            ],
            'With Microseconds (6 digits)' => [
                'Y-m-d H:i:s.u',
                '2026-02-15 14:30:05.123456',
                ['year' => 2026, 'month' => 2, 'day' => 15, 'hour' => 14, 'minute' => 30, 'second' => 5, 'microsecond' => 123456]
            ],
            'Dots separator' => [
                'd.m.Y',
                '30.01.2025',
                ['year' => 2025, 'month' => 1, 'day' => 30]
            ],
            'Only Year (defaults check)' => [
                'Y',
                '2030',
                ['year' => 2030, 'month' => 1, 'day' => 1]
            ],
        ];
    }

    #[DataProvider('invalidFormatsProvider')]
    public function testCreateFromFormatException(
        string $format,
        string $datetime
    ): void {
        $this->expectException(InvalidDateException::class);
        HhpcDate::createFromFormat($format, $datetime);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidFormatsProvider(): array
    {
        return [
            'Format mismatch' => ['Y-m-d', '2026/01/01'],
            'Missing Year' => ['m-d', '01-30'],
            'Unknown Text Month' => ['Y-M-d', '2026-Feb-01'],
            'Garbage string' => ['Y-m-d', 'hello-world'],
            'Empty string' => ['Y-m-d', ''],
        ];
    }

    public function testParseStandardFormats(): void
    {
        $date = HhpcDate::parse('2026-05-15 10:00:00');
        $this->assertEquals(2026, $date->getYear());
        $this->assertEquals(5, $date->getMonth());
        $this->assertEquals(15, $date->getDay());

        $date2 = HhpcDate::parse('2026-05-15');
        $this->assertEquals(2026, $date2->getYear());
        $this->assertEquals(5, $date2->getMonth());
        $this->assertEquals(15, $date2->getDay());

        $date3 = HhpcDate::parse('2026-Xtra-02');
        $this->assertEquals(2026, $date3->getYear());
        $this->assertEquals(13, $date3->getMonth());
        $this->assertEquals(2, $date3->getDay());
    }

    public function testParseKeywords(): void
    {
        $this->assertInstanceOf(HhpcDate::class, HhpcDate::parse('now'));
        $this->assertInstanceOf(HhpcDate::class, HhpcDate::parse('today'));

        $this->assertInstanceOf(HhpcDate::class, HhpcDate::parse('yesterday'));
        $this->assertInstanceOf(HhpcDate::class, HhpcDate::parse('tomorrow'));
        $this->assertInstanceOf(HhpcDate::class, HhpcDate::parse('NOW'));
    }

    public function testParseException(): void
    {
        $this->expectException(InvalidDateException::class);
        HhpcDate::parse('bla-bla-bla');
    }

    public function testParseHandlesEscaping(): void
    {
        $datetime = '2026-01-01 mode: fast';

        $format = 'Y-m-d \m\o\d\e: f\a\s\t';

        $date = HhpcDate::createFromFormat($format, $datetime);

        $this->assertEquals(2026, $date->getYear());
        $this->assertEquals(1, $date->getMonth());
        $this->assertEquals(1, $date->getDay());

        $this->assertEquals(0, $date->getHour());
        $this->assertEquals(0, $date->getMinute());
        $this->assertEquals(0, $date->getSecond());
    }

    /**
     * @param array<string, int> $expected
     */
    #[DataProvider('fromTimestampsProvider')]
    public function testFromTimestamp(int $timestamp, array $expected): void
    {
        $date = HhpcDate::fromTimestamp($timestamp);

        $this->assertEquals($expected['year'], $date->getYear());
        $this->assertEquals($expected['month'], $date->getMonth());
        $this->assertEquals($expected['day'], $date->getDay());

        $this->assertEquals($expected['hour'], $date->getHour());
        $this->assertEquals($expected['minute'], $date->getMinute());
        $this->assertEquals($expected['second'], $date->getSecond());
    }

    /**
     * @return array<int, array{0: int, 1: array<string, int>}>
     */
    public static function fromTimestampsProvider(): array
    {
        return [
            [
                1770983970,
                ['year' => 2026, 'month' => 2, 'day' => 17, 'hour' => 11, 'minute' => 59, 'second' => 30]
            ],
            [
                1798977600,
                ['year' => 2026, 'month' => 13, 'day' => 7, 'hour' => 12, 'minute' => 00, 'second' => 00]
            ],
            [
                1799574359,
                ['year' => 2027, 'month' => 1, 'day' => 7, 'hour' => 9, 'minute' => 45, 'second' => 59]
            ],
        ];
    }

    #[DataProvider('validDateProvider')]
    public function testValidateDate(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $minute,
        int $second,
        int $microsecond,
    ): void {
        HhpcDate::create($year, $month, $day, $hour, $minute, $second, $microsecond);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function validDateProvider(): array
    {
        return [
            'Standard Date' => [
                2026, 1, 15, 12, 0, 0, 0,
            ],
            'End of Short Month (Feb)' => [
                2026, 2, 30, 23, 59, 59, 999999,
            ],
            'End of Long Month (Mar)' => [
                2026, 3, 31, 10, 30, 0, 500,
            ],
            'Leap Year Xtra Week (Day 1)' => [
                2026, 13, 1, 0, 0, 0, 0,
            ],
            'Leap Year Xtra Week (Day 7)' => [
                2026, 13, 7, 12, 30, 15, 123456,
            ],
            'Max Microseconds' => [
                2025, 5, 15, 10, 10, 10, 999999,
            ],
        ];
    }

    #[DataProvider('invalidDateProvider')]
    public function testInvalidDate(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $minute,
        int $second,
        int $microsecond,
        string $exceptionMessage,
    ): void {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage($exceptionMessage);

        HhpcDate::create($year, $month, $day, $hour, $minute, $second, $microsecond);
    }

    /**
     * @return array<string, array{int, int|string}>
     */
    public static function invalidDateProvider(): array
    {
        return [
            'Day Zero' => [
                2026, 1, 0, 0, 0, 0, 0,
                'Invalid date',
            ],
            'Day Negative' => [
                2026, 1, -5, 0, 0, 0, 0,
                'Invalid date',
            ],
            'Max Days (Standard)' => [
                2026, 1, 32, 0, 0, 0, 0,
                'Invalid date',
            ],
            'Max Days (Xtra Leap)' => [
                2026, 13, 8, 0, 0, 0, 0,
                'Invalid date',
            ],
            'Xtra Month in Non-Leap Year' => [
                2027, 13, 1, 0, 0, 0, 0,
                'Invalid month',
            ],
            'Hour Too High' => [
                2026, 1, 1, 24, 0, 0, 0,
                'Invalid date',
            ],
            'Hour Negative' => [
                2026, 1, 1, -1, 0, 0, 0,
                'Invalid date',
            ],
            'Minute Too High' => [
                2026, 1, 1, 12, 60, 0, 0,
                'Invalid date',
            ],
            'Second Too High' => [
                2026, 1, 1, 12, 0, 60, 0,
                'Invalid date',
            ],
            'Microsecond Too High' => [
                2026, 1, 1, 12, 0, 0, 1000000,
                'Invalid date',
            ],
        ];
    }

    public function testIsToday(): void
    {
        HhpcConfig::setTestGregorianNow('2026-02-16 09:45:00');

        $hhpcDateToday = HhpcDate::fromGregorian(new DateTimeImmutable('2026-02-16 18:11:09'));
        $this->assertTrue($hhpcDateToday->isToday());

        $hhpcDateYesterday = HhpcDate::fromGregorian(new DateTimeImmutable('2026-02-15 09:45:00'));
        $this->assertFalse($hhpcDateYesterday->isToday());

        HhpcConfig::setTestGregorianNow();
    }

    public function testIsSameDay(): void
    {
        $hhpcDate = HhpcDate::parse('2026-02-30 05:15:00');

        $sameDay = HhpcDate::parse('2026-02-30 22:00:00');
        $this->assertTrue($hhpcDate->isSameDay($sameDay));

        $otherDay = HhpcDate::parse('2026-03-01 01:00:00');
        $this->assertFalse($hhpcDate->isSameDay($otherDay));
    }

    #[DataProvider('weekDateProvider')]
    public function testGetWeek(string $date, int $week): void
    {
        $hhpcDate = HhpcDate::parse($date);

        $this->assertSame($week, $hhpcDate->getWeek());
    }

    /**
     * @return array<int, array{string, int}>
     */
    public static function weekDateProvider(): array
    {
        return [
            [
                '2026-02-16 09:45:00',
                7,
            ],
            [
                '2026-07-30 23:59:59',
                31,
            ],
            [
                '2026-08-01 00:00:00',
                31,
            ],
            [
                '2026-12-31 23:55:00',
                52,
            ],
            [
                '2026-13-07 23:55:00',
                53,
            ],
        ];
    }

    #[DataProvider('dateForDayOfWeekProvider')]
    public function testGetDayOfWeek(string $date, int $expectedDayOfWeek): void
    {
        $hhpcDate = HhpcDate::parse($date);

        $this->assertSame($expectedDayOfWeek, $hhpcDate->getDayOfWeek());
    }

    /**
     * @return array<int, array{string, int}>
     */
    public static function dateForDayOfWeekProvider(): array
    {
        return [
            [
                '2026-01-01 09:45:00',
                1,
            ],
            [
                '2026-12-31 23:55:00',
                7,
            ],
            [
                '2026-13-07 23:55:00',
                7,
            ],
        ];
    }

    #[DataProvider('dayOfYearProvider')]
    public function testGetDayOfYear(string $date, int $expectedDayOfYear): void
    {
        $hhpcDate = HhpcDate::parse($date);

        $this->assertSame($expectedDayOfYear, $hhpcDate->getDayOfYear());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function dayOfYearProvider(): array
    {
        return [
            'Last day of February' => ['2027-02-30', 60],
            'Middle of year (Last day of Q2)' => ['2027-06-31', 182],
            'Last day of regular year (Month 12)' => ['2030-12-31', 364],
            'First day of Xtr (Month 13)' => ['2043-13-01', 365],
        ];
    }

    public function testGetWeekObject(): void
    {
        $hhpcDate = HhpcDate::parse('2032-02-30 09:45:00');

        $week = $hhpcDate->getWeekObject();

        $this->assertEquals(9, $week->getIndex());
    }

    public function testGetMonthObject(): void
    {
        $hhpcDate = HhpcDate::parse('2032-02-30 09:45:00');

        $month = $hhpcDate->getMonthObject();

        $this->assertEquals(2, $month->getIndex());
    }

    public function testGetYearObject(): void
    {
        $hhpcDate = HhpcDate::parse('2032-02-30 09:45:00');

        $year = $hhpcDate->getYearObject();

        $this->assertEquals(2032, $year->getIndex());
    }

    public function testIsLeapYear(): void
    {
        $leapYearDate = HhpcDate::parse('2032-02-30 09:45:00');
        $this->assertTrue($leapYearDate->isLeapYear());

        $regularYearDate = HhpcDate::parse('2047-02-30 09:45:00');
        $this->assertFalse($regularYearDate->isLeapYear());
    }

    public function testIsXtra(): void
    {
        $xtraDate = HhpcDate::parse('2032-13-01 09:45:00');
        $this->assertTrue($xtraDate->isXtra());

        $regularDate = HhpcDate::parse('2033-02-30 09:45:00');
        $this->assertFalse($regularDate->isXtra());
    }

    public function testAddDays(): void
    {
        $date = HhpcDate::parse('2032-13-01 09:45:00');

        $added = $date->addDays();
        $this->assertSame(2032, $added->getYear());
        $this->assertSame(13, $added->getMonth());
        $this->assertSame(2, $added->getDay());

        $date = HhpcDate::parse('2032-01-01 09:45:00');
        $added = $date->addDays(371);
        $this->assertSame(2033, $added->getYear());
        $this->assertSame(1, $added->getMonth());
        $this->assertSame(1, $added->getDay());
    }

    public function testSubDays(): void
    {
        $date = HhpcDate::parse('2032-13-01 09:45:00');

        $subed = $date->subDays();
        $this->assertSame(2032, $subed->getYear());
        $this->assertSame(12, $subed->getMonth());
        $this->assertSame(31, $subed->getDay());

        $date = HhpcDate::parse('2034-01-01 09:45:00');

        $subed = $date->subDays(364);
        $this->assertSame(2033, $subed->getYear());
        $this->assertSame(1, $subed->getMonth());
        $this->assertSame(1, $subed->getDay());
    }

    public function testEquals(): void
    {
        $hhpcDate = HhpcDate::parse('2035-02-30 05:15:00');

        $other = HhpcDate::parse('2035-02-30 05:15:00');
        $this->assertTrue($hhpcDate->equals($other));

        $other = HhpcDate::parse('2036-03-01 01:00:00');
        $this->assertFalse($hhpcDate->equals($other));
    }

    public function testIsBefore(): void
    {
        $hhpcDate = HhpcDate::parse('2035-02-30 05:15:00');

        $other = HhpcDate::parse('2035-02-30 06:15:00');
        $this->assertTrue($hhpcDate->isBefore($other));

        $other = HhpcDate::parse('2035-02-01 01:00:00');
        $this->assertFalse($hhpcDate->isBefore($other));
    }

    public function testIsAfter(): void
    {
        $hhpcDate = HhpcDate::parse('2035-02-30 05:15:00');

        $other = HhpcDate::parse('2035-02-01 04:15:00');
        $this->assertTrue($hhpcDate->isAfter($other));

        $other = HhpcDate::parse('2035-03-01 01:00:00');
        $this->assertFalse($hhpcDate->isAfter($other));
    }

    public function testSetTime(): void
    {
        $hhpcDate = HhpcDate::parse('2035-02-30 05:15:35');

        $newTime = $hhpcDate->setTime(6, 48);

        $this->assertTrue($hhpcDate->isSameDay($newTime));
        $this->assertSame(6, $newTime->getHour());
        $this->assertSame(48, $newTime->getMinute());
        $this->assertSame(0, $newTime->getSecond());

        $newTime = $hhpcDate->setTime(23, 31, 59, 9654);

        $this->assertTrue($hhpcDate->isSameDay($newTime));
        $this->assertSame(23, $newTime->getHour());
        $this->assertSame(31, $newTime->getMinute());
        $this->assertSame(59, $newTime->getSecond());
        $this->assertSame(9654, $newTime->getMicrosecond());
    }

    public function testStartOfDay(): void
    {
        $hhpcDate = HhpcDate::parse('2035-02-30 05:15:35');

        $startOfDay = $hhpcDate->startOfDay();

        $this->assertTrue($hhpcDate->isSameDay($startOfDay));
        $this->assertSame(0, $startOfDay->getHour());
        $this->assertSame(0, $startOfDay->getMinute());
        $this->assertSame(0, $startOfDay->getSecond());
        $this->assertSame(0, $startOfDay->getMicrosecond());
    }

    public function testEndOfDay(): void
    {
        $hhpcDate = HhpcDate::parse('2035-02-30 05:15:35');

        $endOfDay = $hhpcDate->endOfDay();

        $this->assertTrue($hhpcDate->isSameDay($endOfDay));
        $this->assertSame(23, $endOfDay->getHour());
        $this->assertSame(59, $endOfDay->getMinute());
        $this->assertSame(59, $endOfDay->getSecond());
        $this->assertSame(999999, $endOfDay->getMicrosecond());
    }

    public function testStartOfMonth(): void
    {
        $hhpcDate = HhpcDate::parse('2035-02-30 05:15:35');

        $startOfMonth = $hhpcDate->startOfMonth();

        $this->assertSame(2035, $startOfMonth->getYear());
        $this->assertSame(2, $startOfMonth->getMonth());
        $this->assertSame(1, $startOfMonth->getDay());
        $this->assertSame(0, $startOfMonth->getHour());
        $this->assertSame(0, $startOfMonth->getMinute());
        $this->assertSame(0, $startOfMonth->getSecond());
        $this->assertSame(0, $startOfMonth->getMicrosecond());
    }

    public function testEndOfMonth(): void
    {
        $hhpcDate = HhpcDate::parse('2035-02-15 05:15:35');

        $endOfMonth = $hhpcDate->endOfMonth();

        $this->assertSame(2035, $endOfMonth->getYear());
        $this->assertSame(2, $endOfMonth->getMonth());
        $this->assertSame(30, $endOfMonth->getDay());
        $this->assertSame(23, $endOfMonth->getHour());
        $this->assertSame(59, $endOfMonth->getMinute());
        $this->assertSame(59, $endOfMonth->getSecond());
        $this->assertSame(999999, $endOfMonth->getMicrosecond());
    }

    public function testStartOfYear(): void
    {
        $hhpcDate = HhpcDate::parse('2035-02-30 05:15:35');

        $startOfYear = $hhpcDate->startOfYear();

        $this->assertSame(2035, $startOfYear->getYear());
        $this->assertSame(1, $startOfYear->getMonth());
        $this->assertSame(1, $startOfYear->getDay());
        $this->assertSame(0, $startOfYear->getHour());
        $this->assertSame(0, $startOfYear->getMinute());
        $this->assertSame(0, $startOfYear->getSecond());
        $this->assertSame(0, $startOfYear->getMicrosecond());
    }

    public function testEndOfYear(): void
    {
        $hhpcDate = HhpcDate::parse('2035-02-15 05:15:35');

        $endOfYear = $hhpcDate->endOfYear();

        $this->assertSame(2035, $endOfYear->getYear());
        $this->assertSame(12, $endOfYear->getMonth());
        $this->assertSame(31, $endOfYear->getDay());
        $this->assertSame(23, $endOfYear->getHour());
        $this->assertSame(59, $endOfYear->getMinute());
        $this->assertSame(59, $endOfYear->getSecond());
        $this->assertSame(999999, $endOfYear->getMicrosecond());
    }

    public function testToTimestamp(): void
    {
        $timestamp = 1771331681;

        $date = HhpcDate::fromGregorian(new DateTimeImmutable("@$timestamp"));
        $this->assertSame($timestamp, $date->toTimestamp());
    }

    public function testToGregorian(): void
    {
        $gregorianDate = new DateTimeImmutable('2035-02-15 05:15:35');

        $date = HhpcDate::fromGregorian($gregorianDate);
        $this->assertSame($gregorianDate->getTimestamp(), $date->toGregorian()->getTimestamp());
    }

    #[DataProvider('dateQuartersProvider')]
    public function testGetQuarter(string $datetime, int $expectedQuarter): void
    {
        $date = HhpcDate::parse($datetime);
        $this->assertSame($expectedQuarter, $date->getQuarter());

        $quarter = $date->getQuarterObject();
        $this->assertSame($date->getYear(), $quarter->getYear());
        $this->assertSame($expectedQuarter, $quarter->getIndex());
    }

    /**
     * @return array<int, array<int, int|string>>
     */
    public static function dateQuartersProvider(): array
    {
        return [
            ['2060-01-01', 1],
            ['2060-12-31', 4],
            ['2060-13-3', 4],
        ];
    }

    public function testgetDayOfQuarter(): void
    {
        $date = HhpcDate::parse('2035-04-15 05:15:35');
        $this->assertSame(15, $date->getDayOfQuarter());
    }

    public function testGetDaysRemainingInQuarter(): void
    {
        $date = HhpcDate::parse('2035-03-29 05:15:35');
        $this->assertSame(2, $date->getDaysRemainingInQuarter());
    }

    public function testaddMonths(): void
    {
        $date = HhpcDate::parse('2032-03-31 09:45:00');
        $added = $date->addMonths(1);
        $this->assertSame(2032, $added->getYear());
        $this->assertSame(5, $added->getMonth());
        $this->assertSame(1, $added->getDay());

        $date = HhpcDate::parse('2032-01-01 09:45:00');
        $added = $date->addMonths(12);
        $this->assertSame(2032, $added->getYear());
        $this->assertSame(13, $added->getMonth());
        $this->assertSame(1, $added->getDay());

        $date = HhpcDate::parse('2033-01-01 09:45:00');
        $added = $date->addMonths(12);
        $this->assertSame(2034, $added->getYear());
        $this->assertSame(1, $added->getMonth());
        $this->assertSame(1, $added->getDay());
    }

    public function testaddMonthsNoOverflow(): void
    {
        $date = HhpcDate::parse('2032-03-31 09:45:00');
        $added = $date->addMonthsNoOverflow(1);
        $this->assertSame(2032, $added->getYear());
        $this->assertSame(4, $added->getMonth());
        $this->assertSame(30, $added->getDay());

        $date = HhpcDate::parse('2033-12-31 09:45:00');
        $added = $date->addMonthsNoOverflow(1);
        $this->assertSame(2034, $added->getYear());
        $this->assertSame(1, $added->getMonth());
        $this->assertSame(30, $added->getDay());

        $date = HhpcDate::parse('2032-12-31 09:45:00');
        $added = $date->addMonthsNoOverflow(1);
        $this->assertSame(2032, $added->getYear());
        $this->assertSame(13, $added->getMonth());
        $this->assertSame(7, $added->getDay());
    }

    public function testsubMonths(): void
    {
        $date = HhpcDate::parse('2032-03-31 09:45:00');
        $subed = $date->subMonths(1);
        $this->assertSame(2032, $subed->getYear());
        $this->assertSame(3, $subed->getMonth());
        $this->assertSame(1, $subed->getDay());

        $date = HhpcDate::parse('2033-01-30 09:45:00');
        $subed = $date->subMonths(1);
        $this->assertSame(2033, $subed->getYear());
        $this->assertSame(1, $subed->getMonth());
        $this->assertSame(23, $subed->getDay());
    }

    public function testsubMonthsNoOverflow(): void
    {
        $date = HhpcDate::parse('2032-03-31 09:45:00');
        $subed = $date->subMonthsNoOverflow(1);
        $this->assertSame(2032, $subed->getYear());
        $this->assertSame(2, $subed->getMonth());
        $this->assertSame(30, $subed->getDay());

        $date = HhpcDate::parse('2033-01-30 09:45:00');
        $subed = $date->subMonthsNoOverflow(1);
        $this->assertSame(2032, $subed->getYear());
        $this->assertSame(13, $subed->getMonth());
        $this->assertSame(7, $subed->getDay());
    }

    public function testaddQuarters(): void
    {
        $date = HhpcDate::parse('2032-12-31 09:45:00');
        $added = $date->addQuarters(1);
        $this->assertSame(2033, $added->getYear());
        $this->assertSame(1, $added->getQuarter());
        $this->assertSame(3, $added->getMonth());
        $this->assertSame(31, $added->getDay());

        $date = HhpcDate::parse('2032-13-07 09:45:00');
        $added = $date->addQuarters(1);
        $this->assertSame(2033, $added->getYear());
        $this->assertSame(2, $added->getQuarter());
        $this->assertSame(4, $added->getMonth());
        $this->assertSame(7, $added->getDay());
    }

    public function testaddQuartersNoOverflow(): void
    {
        $date = HhpcDate::parse('2032-12-31 09:45:00');
        $added = $date->addQuartersNoOverflow(1);
        $this->assertSame(2033, $added->getYear());
        $this->assertSame(1, $added->getQuarter());
        $this->assertSame(3, $added->getMonth());
        $this->assertSame(31, $added->getDay());

        $date = HhpcDate::parse('2032-13-07 09:45:00');
        $added = $date->addQuartersNoOverflow(1);
        $this->assertSame(2033, $added->getYear());
        $this->assertSame(1, $added->getQuarter());
        $this->assertSame(3, $added->getMonth());
        $this->assertSame(31, $added->getDay());
    }

    public function testsubQuarters(): void
    {
        $date = HhpcDate::parse('2032-13-07 09:45:00');
        $added = $date->subQuarters(1);
        $this->assertSame(2032, $added->getYear());
        $this->assertSame(4, $added->getQuarter());
        $this->assertSame(10, $added->getMonth());
        $this->assertSame(7, $added->getDay());
    }

    public function testsubQuartersNoOverflow(): void
    {
        $date = HhpcDate::parse('2032-13-07 09:45:00');
        $added = $date->subQuartersNoOverflow(1);
        $this->assertSame(2032, $added->getYear());
        $this->assertSame(3, $added->getQuarter());
        $this->assertSame(9, $added->getMonth());
        $this->assertSame(31, $added->getDay());
    }

    public function testaddYears(): void
    {
        $date = HhpcDate::parse('2032-13-05 09:45:00');
        $added = $date->addYears(1);
        $this->assertSame(2034, $added->getYear());
        $this->assertSame(1, $added->getMonth());
        $this->assertSame(5, $added->getDay());
    }

    public function testsubYears(): void
    {
        $date = HhpcDate::parse('2032-13-05 09:45:00');
        $added = $date->subYears(1);
        $this->assertSame(2032, $added->getYear());
        $this->assertSame(1, $added->getMonth());
        $this->assertSame(5, $added->getDay());
    }

    public function testaddYearsNoOverflow(): void
    {
        $date = HhpcDate::parse('2032-13-05 09:45:00');
        $added = $date->addYearsNoOverflow(1);
        $this->assertSame(2033, $added->getYear());
        $this->assertSame(12, $added->getMonth());
        $this->assertSame(31, $added->getDay());
    }

    public function testsubYearsNoOverflow(): void
    {
        $date = HhpcDate::parse('2032-13-05 09:45:00');
        $added = $date->subYearsNoOverflow(1);
        $this->assertSame(2031, $added->getYear());
        $this->assertSame(12, $added->getMonth());
        $this->assertSame(31, $added->getDay());
    }

    #[DataProvider('provideDiffInDaysCases')]
    public function testDiffInDays(
        string $startStr,
        string $endStr,
        bool $absolute,
        int $expectedDiff,
    ): void {
        $dateStart = HhpcDate::parse($startStr);
        $dateEnd = HhpcDate::parse($endStr);

        $actualDiff = $dateStart->diffInDays($dateEnd, $absolute);

        $this->assertSame($expectedDiff, $actualDiff);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: bool, 3: int}>
     */
    public static function provideDiffInDaysCases(): array
    {
        return [
            [
                '2026-01-01 10:00:00',
                '2026-01-01 18:00:00',
                true,
                0,
            ],
            [
                '2026-01-01 15:00:00',
                '2026-01-02 10:00:00',
                true,
                0,
            ],
            [
                '2026-01-01 15:00:00',
                '2026-01-02 15:00:00',
                true,
                1,
            ],
            [
                '2026-02-30 12:00:00',
                '2026-03-01 12:00:00',
                true,
                1,
            ],
            [
                '2032-13-07 09:45:00',
                '2033-01-01 09:45:00',
                true,
                1,
            ],
            [
                '2026-01-01 12:00:00.000500',
                '2026-01-02 12:00:00.000499',
                true,
                0,
            ],
            [
                '2026-01-01 12:00:00',
                '2026-01-05 12:00:00',
                false,
                4,
            ],
            [
                '2026-01-05 12:00:00',
                '2026-01-01 12:00:00',
                false,
                -4,
            ],
            [
                '2026-01-02 12:00:00',
                '2026-01-01 14:00:00',
                false,
                0,
            ],
            [
                '2026-01-02 12:00:00',
                '2026-01-01 10:00:00',
                false,
                -1,
            ],
        ];
    }

    #[DataProvider('provideDiffInWeeksCases')]
    public function testDiffInWeeks(
        string $startStr,
        string $endStr,
        bool $absolute,
        int $expectedDiff,
    ): void {
        $dateStart = HhpcDate::parse($startStr);
        $dateEnd = HhpcDate::parse($endStr);

        $actualDiff = $dateStart->diffInWeeks($dateEnd, $absolute);

        $this->assertSame($expectedDiff, $actualDiff);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: bool, 3: int}>
     */
    public static function provideDiffInWeeksCases(): array
    {
        return [
            [
                '2026-01-01 10:00:00',
                '2026-01-07 10:00:00',
                true,
                0,
            ],
            [
                '2026-01-01 10:00:00',
                '2026-01-08 10:00:00',
                true,
                1,
            ],
            [
                '2026-01-01 10:00:00',
                '2026-01-08 09:00:00',
                true,
                0,
            ],
            [
                '2026-01-28 12:00:00',
                '2026-02-05 12:00:00',
                true,
                1,
            ],
            [
                '2032-13-01 09:45:00',
                '2033-01-01 09:45:00',
                true,
                1,
            ],
            [
                '2026-01-01 12:00:00.000500',
                '2026-01-08 12:00:00.000499',
                true,
                0,
            ],
            [
                '2026-01-01 12:00:00',
                '2026-01-15 12:00:00',
                false,
                2,
            ],
            [
                '2026-01-15 12:00:00',
                '2026-01-01 12:00:00',
                false,
                -2,
            ],
            [
                '2026-01-10 12:00:00',
                '2026-01-01 12:00:00',
                false,
                -1,
            ],
            [
                '2026-01-07 12:00:00',
                '2026-01-01 12:00:00',
                false,
                0,
            ],
        ];
    }

    #[DataProvider('provideDiffInMonthsCases')]
    public function testDiffInMonths(
        string $startStr,
        string $endStr,
        bool $absolute,
        int $expectedDiff,
    ): void {
        $dateStart = HhpcDate::parse($startStr);
        $dateEnd = HhpcDate::parse($endStr);

        $actualDiff = $dateStart->diffInMonths($dateEnd, $absolute);

        $this->assertSame($expectedDiff, $actualDiff);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: bool, 3: int}>
     */
    public static function provideDiffInMonthsCases(): array
    {
        return [
            [
                '2026-01-01 10:00:00',
                '2026-01-28 10:00:00',
                true,
                0,
            ],
            [
                '2026-01-05 10:00:00',
                '2026-02-05 10:00:00',
                true,
                1,
            ],
            [
                '2026-01-05 10:00:00',
                '2026-02-05 09:59:59',
                true,
                0,
            ],
            [
                '2027-12-15 12:00:00',
                '2028-01-15 12:00:00',
                true,
                1,
            ],
            [
                '2032-12-05 00:00:00',
                '2032-13-05 00:00:00',
                true,
                1,
            ],
            [
                '2026-03-31 12:00:00',
                '2026-04-30 12:00:00',
                true,
                0,
            ],
            [
                '2026-01-01 12:00:00.000500',
                '2026-02-01 12:00:00.000499',
                true,
                0,
            ],
            [
                '2027-01-01 12:00:00',
                '2028-01-01 12:00:00',
                true,
                12,
            ],
            [
                '2026-01-01 12:00:00',
                '2026-02-01 12:00:00',
                false,
                1,
            ],
            [
                '2026-02-01 12:00:00',
                '2026-01-01 12:00:00',
                false,
                -1,
            ],
            [
                '2026-03-02 12:00:00',
                '2026-02-01 12:00:00',
                false,
                -1,
            ],
            [
                '2026-03-01 12:00:00',
                '2026-02-02 12:00:00',
                false,
                0,
            ],
        ];
    }

    #[DataProvider('provideDiffInYearsCases')]
    public function testDiffInYears(
        string $startStr,
        string $endStr,
        bool $absolute,
        int $expectedDiff,
    ): void {
        $dateStart = HhpcDate::parse($startStr);
        $dateEnd = HhpcDate::parse($endStr);

        $actualDiff = $dateStart->diffInYears($dateEnd, $absolute);

        $this->assertSame($expectedDiff, $actualDiff);
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: bool, 3: int}>
     */
    public static function provideDiffInYearsCases(): array
    {
        return [
            [
                '2026-01-01 10:00:00',
                '2026-12-30 10:00:00',
                true,
                0,
            ],
            [
                '2026-05-15 12:00:00',
                '2027-05-15 12:00:00',
                true,
                1,
            ],
            [
                '2026-05-15 12:00:00',
                '2027-05-15 11:59:59',
                true,
                0,
            ],
            [
                '2026-01-01 00:00:00',
                '2031-01-01 00:00:00',
                true,
                5,
            ],
            [
                '2031-12-01 00:00:00',
                '2032-13-01 00:00:00',
                true,
                1,
            ],
            [
                '2032-13-07 12:00:00',
                '2033-12-15 12:00:00',
                true,
                0,
            ],
            [
                '2026-01-01 12:00:00.000500',
                '2027-01-01 12:00:00.000499',
                true,
                0,
            ],
            [
                '2026-01-01 12:00:00',
                '2027-01-01 12:00:00',
                false,
                1,
            ],
            [
                '2027-01-01 12:00:00',
                '2026-01-01 12:00:00',
                false,
                -1,
            ],
            [
                '2027-03-01 12:00:00',
                '2026-01-01 12:00:00',
                false,
                -1,
            ],
            [
                '2027-01-01 12:00:00',
                '2026-01-02 12:00:00',
                false,
                0,
            ],
        ];
    }

    #[DataProvider('provideFormatCases')]
    public function testFormat(string $dateStr, string $format, string $expected): void
    {
        $date = HhpcDate::parse($dateStr);

        $this->assertSame($expected, $date->format($format));
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public static function provideFormatCases(): array
    {
        return [
            ['2026-01-05 10:00:00', 'Y-m-d', '2026-01-05'],
            ['2026-02-08 10:00:00', 'd.m.Y', '08.02.2026'],
            ['2026-02-08 10:00:00', 'd/m/y', '08/02/26'],
            ['2026-02-05 10:00:00', 'd m Y', '05 02 2026'],
            ['2026-02-05 10:00:00', 'd m y', '05 02 26'],
            ['2026-02-05 10:00:00', 'j n Y', '5 2 2026'],
            ['2026-01-05 09:05:07', 'H:i:s', '09:05:07'],
            ['2026-01-05 09:05:07', 'G:i:s', '9:05:07'],
            ['2026-01-05 14:30:00', 'H:i:s', '14:30:00'],
            ['2026-01-05 09:05:07', 'h:i:s A', '09:05:07 AM'],
            ['2026-01-05 09:05:07', 'g:i:s a', '9:05:07 am'],
            ['2026-01-05 14:30:00', 'h:i A', '02:30 PM'],
            ['2026-01-05 14:30:00', 'g:i a', '2:30 pm'],
            ['2026-01-05 00:15:00', 'h:i A', '12:15 AM'],
            ['2026-01-05 12:15:00', 'h:i A', '12:15 PM'],
            ['2026-01-05 10:00:00.123456', 'H:i:s.v u', '10:00:00.123 123456'],
            ['2026-01-05 10:00:00.005000', 'v u', '005 005000'],
            ['2026-03-30 15:45:12', 'Y-m-d H:i:s', '2026-03-30 15:45:12'],
            ['2026-03-30 15:45:12', 'YmdHis', '20260330154512'],
            ['2026-01-05 10:00:00', '\Y-\m-\d', 'Y-m-d'],
            ['2026-01-05 10:00:00', '\H\o\u\r: H', 'Hour: 10'],
            ['2026-01-05 10:00:00', 'Y \y\e\a\r', '2026 year'],
            ['2026-01-05 10:00:00', '\\\\Y', '\2026'],
            ['2026-01-05 10:00:00', 'Y-m-d (\t\i\me: H:i)', '2026-01-05 (time: 10:00)'],
            ['2026-01-05 10:00:00', '[Y] * m / d', '[2026] * 01 / 05'],
            ['2026-01-05 10:00:00', 'U', '1767348000'],
            ['2026-01-05 10:00:00', 'Y-m-d L', '2026-01-05 1'],
            ['2026-01-05 10:00:00', 't', '30'],
            ['2026-01-05 10:00:00', 'j F Y', '5 January 2026'],
            ['2026-01-05 10:00:00', 'j M. y', '5 Jan. 26'],
            ['2026-01-05 10:00:00', 'z', '5'],
            ['2026-01-05 10:00:00', 'W', '01'],
        ];
    }

    public function testJsonSerialize(): void
    {
        $date = HhpcDate::parse('2032-13-06 14:30:45.123456');

        $serialized = $date->jsonSerialize();

        $this->assertIsArray($serialized);

        $this->assertSame('2032-13-06 14:30:45.123456', $serialized['datetime']);
        $this->assertSame(2032, $serialized['year']);
        $this->assertSame(13, $serialized['month']);
        $this->assertSame(6, $serialized['day']);
        $this->assertSame(14, $serialized['hour']);
        $this->assertSame(30, $serialized['minute']);
        $this->assertSame(45, $serialized['second']);
        $this->assertSame(123456, $serialized['microsecond']);
        $this->assertSame(123, $serialized['millisecond']);
        $this->assertTrue($serialized['isXtra']);
        $this->assertTrue($serialized['isLeapYear']);
        $this->assertSame(1988202645, $serialized['timestamp']);
        $this->assertSame(6, $serialized['dayOfWeek']);
        $this->assertSame(370, $serialized['dayOfYear']);
        $this->assertSame(53, $serialized['week']);
        $this->assertSame(4, $serialized['quarter']);
        $this->assertSame(97, $serialized['dayOfQuarter']);
        $this->assertSame(1, $serialized['daysRemainingInQuarter']);

        $jsonString = json_encode($date);

        $this->assertIsString($jsonString);
        $this->assertStringContainsString('"year":2032', $jsonString);
        $this->assertStringContainsString('"month":13', $jsonString);
        $this->assertStringContainsString('"isXtra":true', $jsonString);
    }
}
