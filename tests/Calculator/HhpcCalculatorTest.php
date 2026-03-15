<?php

declare(strict_types=1);

namespace Tests\Hhpc\Calculator;

use Adiafora\Hhpc\Config\HhpcConfig;
use Adiafora\Hhpc\Contracts\HhpcCalculatorInterface;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HhpcCalculatorTest extends TestCase
{
    private HhpcCalculatorInterface $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = HhpcConfig::getCalculator();
    }

    /**
     * Validates a range of years from 2020 to 3001.
     */
    public function testLeapYearsMatchReferenceData(): void
    {
        /** @var array<int> $leapYears */
        $leapYears = require __DIR__ . '/../Fixtures/hhpc_leap_years.php';
        $leapYearsMap = array_fill_keys($leapYears, true);

        for ($year = 2020; $year <= 3001; $year++) {
            $isExpectedLeap = isset($leapYearsMap[$year]);
            $isLeapYear = $this->calculator->isLeapYear($year);

            $this->assertSame($isExpectedLeap, $isLeapYear);

            if ($isLeapYear) {
                $this->assertSame(53, $this->calculator->getWeeksInYear($year));
                $this->assertSame(371, $this->calculator->getDaysInYear($year));
            } else {
                $this->assertSame(52, $this->calculator->getWeeksInYear($year));
                $this->assertSame(364, $this->calculator->getDaysInYear($year));
            }
        }
    }

    /**
     * Specific test for the "reference point" (Epoch Offset).
     *
     * It is crucial to verify that the year 1970 starts correctly;
     * otherwise, all dates will be miscalculated.
     */
    public function testEpochOffset(): void
    {
        // ISO year 1970 starts on December 29, 1969 (Monday).
        // This is -3 days from January 1, 1970.
        $this->assertSame(-3, $this->calculator->getDaysFromEpoch(1970));

        // The year 2026 starts on December 29, 2025 (Monday).
        // This is -3 days from January 1, 2026.
        // Timestamp 2026-01-01 / 86400 = 20454 days.
        // 20454 - 3 = 20451 days from Epoch.
        // Verify that the calculator provides the correct offset.
        $expected2026 = (int)(new DateTimeImmutable('2025-12-29', new DateTimeZone('UTC')))->format('U') / 86400;
        $this->assertSame($expected2026, $this->calculator->getDaysFromEpoch(2026));
    }

    /**
     * Test boundary years to ensure stability.
     */
    public function testBoundaryYears(): void
    {
        // Past: Ensure we don't crash on pre-epoch years
        $this->assertFalse($this->calculator->isLeapYear(1900));
        $this->assertFalse($this->calculator->isLeapYear(1904));
        $this->assertTrue($this->calculator->isLeapYear(1908));

        // Check The Millennium
        $this->assertFalse($this->calculator->isLeapYear(2000));
    }

    /**
     * @param array<int, int> $expected
     */
    #[DataProvider('dateConversionProvider')]
    public function testFromGregorian(string $inputTime, string $timezone, array $expected): void
    {
        $date = new DateTimeImmutable($inputTime, new DateTimeZone($timezone));

        $result = $this->calculator->fromGregorian($date);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<int, int>}>
     */
    public static function dateConversionProvider(): array
    {
        return [
            'Standard Date' => [
                '2026-01-01 12:00:00',
                'UTC',
                [2026, 1, 4],
            ],

            'Year Boundary Start' => [
                '2024-12-30 10:00:00',
                'UTC',
                [2025, 1, 1],
            ],

            'Tokyo Morning is UTC Yesterday' => [
                '2026-01-01 05:00:00',
                'Asia/Tokyo',
                [2026, 1, 3],
            ],

            'Leap Year Xtra Month' => [
                '2020-12-31 23:59:59',
                'UTC',
                [2020, 13, 4],
            ],

            'Ordinary day' => [
                '2025-05-20 12:00:00',
                'UTC',
                [2025, 05, 21],
            ],

            '2026 Start' => ['2025-12-29 00:00:00', 'UTC', [2026, 1, 1]],
            '2026 Mid'   => ['2026-06-30 00:00:00', 'UTC', [2026, 7, 2]],
            '2026 End Normal' => ['2026-12-27 23:59:59', 'UTC', [2026, 12, 31]],

            '2026 Xtra Start' => ['2026-12-28 00:00:00', 'UTC', [2026, 13, 1]],
            '2026 Xtra Mid'   => ['2026-12-31 23:59:00', 'UTC', [2026, 13, 4]],
            '2026 Xtra End'   => ['2027-01-03 12:00:00', 'UTC', [2026, 13, 7]],

            '2027 Start' => ['2027-01-04 00:00:00', 'UTC', [2027, 1, 1]],

            '2028 Feb 28' => ['2028-02-28 12:00:00', 'UTC', [2028, 2, 27]],
            '2028 Leap Day' => ['2028-02-29 12:00:00', 'UTC', [2028, 2, 28]],
            '2028 March 1' => ['2028-03-01 12:00:00', 'UTC', [2028, 2, 29]],
            '2028 March 4' => ['2028-03-04 12:00:00', 'UTC', [2028, 3, 2]],

            '2032 Early Start' => ['2031-12-31 10:00:00', 'UTC', [2032, 1, 3]],

            '2032 Xtra Month' => ['2032-12-31 23:00:00', 'UTC', [2032, 13, 5]],

            '2052 Start' => ['2052-01-01 00:00:00', 'UTC', [2052, 1, 1]],
            '2052 January 30' => ['2052-01-30 00:00:00', 'UTC', [2052, 1, 30]],
            '2052 January 31' => ['2052-01-31 00:00:00', 'UTC', [2052, 2, 1]],
            '2052 Leap Day' => ['2052-02-29 12:00:00', 'UTC', [2052, 2, 30]],
            '2052 Sync' => ['2052-03-01 00:00:00', 'UTC', [2052, 3, 1]],
        ];
    }

    #[DataProvider('dateForDayOfWeekProvider')]
    public function testGetDayOfWeek(int $month, int $day, int $expectedDayOfWeek): void
    {
        $result = $this->calculator->getDayOfWeek($month, $day);

        $this->assertSame($expectedDayOfWeek, $result);
    }

    /**
     * @return array<int, array{0: int, 1: int, 2: int}>
     */
    public static function dateForDayOfWeekProvider(): array
    {
        return [
            [1, 1, 1],
            [2, 30, 4],
            [3, 23, 6],
            [5, 20, 1],
            [6, 31, 7],
            [8, 15, 3],
            [9, 31, 7],
            [10, 9, 2],
            [11, 3, 5],
            [12, 31, 7],
            [13, 7, 7],
        ];
    }

    #[DataProvider('monthsLenghtProvider')]
    public function testGetMonthLength(int $month, int $expectedDays): void
    {
        $this->assertSame($expectedDays, $this->calculator->getDaysInMonth($month));
    }

    /**
     * @return array<int, list<int>>
     */
    public static function monthsLenghtProvider(): array
    {
        return [
            [1, 30],
            [2, 30],
            [3, 31],
            [4, 30],
            [5, 30],
            [6, 31],
            [7, 30],
            [8, 30],
            [9, 31],
            [10, 30],
            [11, 30],
            [12, 31],
            [13, 7],
        ];
    }

    #[DataProvider('monthsQuartersProvider')]
    public function testGetMonthsQuarter(int $month, int $expectedQuarter): void
    {
        $this->assertSame($expectedQuarter, $this->calculator->getMonthsQuarter($month));
    }

    /**
     * @return array<int, list<int>>
     */
    public static function monthsQuartersProvider(): array
    {
        return [
            [1, 1],
            [2, 1],
            [3, 1],
            [4, 2],
            [5, 2],
            [6, 2],
            [7, 3],
            [8, 3],
            [9, 3],
            [10, 4],
            [11, 4],
            [12, 4],
            [13, 4],
        ];
    }

    /**
     * @param array{int, int} $expectedRange
     */
    #[DataProvider('weekRangeProvider')]
    public function testGetWeekRangeForMonth(int $week, array $expectedRange): void
    {
        $this->assertSame($expectedRange, $this->calculator->getWeekRangeForMonth($week));
    }

    /**
     * @return list<array{
     * 0: int,
     * 1: array{int, int}
     * }>
     */
    public static function weekRangeProvider(): array
    {
        return [
            [1, [1, 5]],
            [2, [5, 9]],
            [3, [9, 13]],
            [4, [14, 18]],
            [5, [18, 22]],
            [6, [22, 26]],
            [7, [27, 31]],
            [8, [31, 35]],
            [9, [35, 39]],
            [10, [40, 44]],
            [11, [44, 48]],
            [12, [48, 52]],
            [13, [53, 53]],
        ];
    }

    #[DataProvider('addMonthsProvider')]
    public function testAddMonths(int $year, int $month, int $addMonths, int $targetYear, int $targetMonth): void
    {
        $this->assertSame([$targetYear, $targetMonth], $this->calculator->addMonths($year, $month, $addMonths));
    }

    /**
     * @return array<int, array{0: int, 1: int, 2: int, 3: int, 4: int}>
     */
    public static function addMonthsProvider(): array
    {
        return [
            [2025, 1, 1, 2025, 2],
            [2025, 1, 5, 2025, 6],
            [2025, 6, 6, 2025, 12],
            [2025, 1, 0, 2025, 1],
            [2026, 10, 2, 2026, 12],
            [2026, 10, 3, 2026, 13],
            [2025, 12, 1, 2026, 1],
            [2025, 11, 2, 2026, 1],
            [2025, 10, 5, 2026, 3],
            [2025, 1, 12, 2026, 1],
            [2025, 6, 12, 2026, 6],
            [2026, 12, 1, 2026, 13],
            [2026, 13, 1, 2027, 1],
            [2026, 1, 12, 2026, 13],
            [2026, 1, 13, 2027, 1],
            [2026, 12, 2, 2027, 1],
            [2025, 1, 25, 2027, 1],
            [2025, 6, 25, 2027, 6],
            [2025, 1, 24, 2026, 13],
            [2025, 1, 13, 2026, 2],
            [2026, 1, 25, 2028, 1],
            [2025, 1, 37, 2028, 1],
            [2025, 1, 85, 2032, 1],
            [2025, 5, -1, 2025, 4],
            [2025, 5, -4, 2025, 1],
            [2025, 12, -6, 2025, 6],
            [2026, 13, -1, 2026, 12],
            [2026, 13, -12, 2026, 1],
            [2026, 1, -1, 2025, 12],
            [2026, 2, -3, 2025, 11],
            [2027, 1, -1, 2026, 13],
            [2027, 1, -2, 2026, 12],
            [2027, 1, -13, 2026, 1],
            [2024, 11, 20, 2026, 7],
            [2025, 10, 20, 2027, 5],
            [2031, 11, 5, 2032, 4],
            [2032, 12, 2, 2033, 1],
            [2032, 10, 5, 2033, 2],
        ];
    }

    #[DataProvider('addWeeksProvider')]
    public function testAddWeeks(int $year, int $week, int $addWeeks, int $targetYear, int $targetWeek): void
    {
        $this->assertSame([$targetYear, $targetWeek], $this->calculator->addWeeks($year, $week, $addWeeks));
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: int, 3: int, 4: int}>
     */
    public static function addWeeksProvider(): array
    {
        return [
            'Within year: Simple jump' => [2025, 1, 10, 2025, 11],
            'Within year: To the end'   => [2025, 1, 51, 2025, 52],
            'Zero delta'                => [2025, 10, 0, 2025, 10],

            'Normal to next: 1 week jump'  => [2025, 52, 1, 2026, 1],
            'Normal to next: Crossing edge' => [2025, 51, 3, 2026, 2],
            'Normal to next: Full year'     => [2025, 1, 52, 2026, 1],

            'Leap: Reach 53rd week'        => [2026, 52, 1, 2026, 53],
            'Leap to next: 1 week jump'    => [2026, 53, 1, 2027, 1],
            'Leap to next: Crossing edge'  => [2026, 52, 2, 2027, 1],
            'Leap to next: Full year jump' => [2026, 1, 53, 2027, 1],

            'Subtract within year'          => [2025, 10, -5, 2025, 5],
            'Back to previous (from normal)' => [2026, 1, -1, 2025, 52],
            'Back to previous (from leap)'   => [2027, 1, -1, 2026, 53],
            'Back to previous: Large jump'   => [2027, 2, -55, 2025, 52],

            'Jump 2 years (Normal 52 + Leap 53)' => [2025, 1, 105, 2027, 1],
            'Jump 3 years (52 + 53 + 52)'        => [2025, 1, 157, 2028, 1],
            'Large jump (Long distance)'         => [2025, 1, 365, 2032, 1],

            'From end of 2025 through 2026' => [2025, 50, 10, 2026, 8],
            'Back from 2027 to 2025'        => [2027, 5, -60, 2025, 50],
        ];
    }

    #[DataProvider('addDaysProvider')]
    public function testAddSays(int $year, int $day, int $addDays, int $targetYear, int $targetDays): void
    {
        $this->assertSame([$targetYear, $targetDays], $this->calculator->addDays($year, $day, $addDays));
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: int, 3: int, 4: int}>
     */
    public static function addDaysProvider(): array
    {
        return [
            'Within year: Simple jump' => [2025, 1, 10, 2025, 11],
            'Within year: To the end'   => [2025, 1, 363, 2025, 364],
            'Zero delta'                => [2025, 10, 0, 2025, 10],

            'Normal to next: 1 day jump'  => [2025, 364, 1, 2026, 1],
            'Normal to next: Crossing edge' => [2025, 363, 3, 2026, 2],
            'Normal to next: Full year'     => [2025, 1, 364, 2026, 1],

            'Leap: Reach Xtra'        => [2026, 364, 1, 2026, 365],
            'Leap to next: 1 day jump'    => [2026, 371, 1, 2027, 1],
            'Leap to next: Crossing edge'  => [2026, 364, 8, 2027, 1],
            'Leap to next: Full year jump' => [2026, 1, 371, 2027, 1],

            'Subtract within year'          => [2025, 10, -5, 2025, 5],
            'Back to previous (from normal)' => [2026, 1, -1, 2025, 364],
            'Back to previous (from leap)'   => [2027, 1, -1, 2026, 371],
            'Back to previous: Large jump'   => [2027, 1, -372, 2025, 364],

            'Jump 2 years (Normal 364 + Leap 371)' => [2025, 1, 364 + 371, 2027, 1],
            'Jump 3 years (364 + 371 + 364)'        => [2025, 1, 364 + 371 + 364, 2028, 1],
            'Large jump (Long distance)'         => [2025, 1, 2555, 2032, 1],

            'From end of 2025 through 2026' => [2025, 350, 22, 2026, 8],
            'Back from 2027 to 2025'        => [2027, 5, -420, 2025, 320],
        ];
    }

    public function testGetQuartersInYear(): void
    {
        $this->assertSame(4, $this->calculator->getQuartersInYear());
    }

    #[DataProvider('daysOfYearProvider')]
    public function testGetDateFromDayOfYear(int $dayOfYear, int $expectedMonth, int $expectedDay): void
    {
        $this->assertSame([$expectedMonth, $expectedDay], $this->calculator->getDateFromDayOfYear($dayOfYear));
    }

    /**
     * @return array<int, array{0: int, 1: int, 2: int}>
     */
    public static function daysOfYearProvider(): array
    {
        return [
            [1, 1, 1],
            [60, 2, 30],
            [182, 6, 31],
            [274, 10, 1],
            [364, 12, 31],
            [365, 13, 1],
            [371, 13, 7],
        ];
    }

    /**
     * @param list<array{month: int, day: int}> $expectedDays
     */
    #[DataProvider('weeksProvider')]
    public function testGetDaysInWeek(int $week, array $expectedDays): void
    {
        $result = $this->calculator->getDaysInWeek($week);

        $this->assertCount(7, $result);
        $this->assertSame($expectedDays, $result);
    }

    /**
     * @return array<string, array{int, list<array{month: int, day: int}>}>
     */
    public static function weeksProvider(): array
    {
        return [
            'Week 1: Start of the year (All in Month 1)' => [
                1, [
                    ['month' => 1, 'day' => 1],
                    ['month' => 1, 'day' => 2],
                    ['month' => 1, 'day' => 3],
                    ['month' => 1, 'day' => 4],
                    ['month' => 1, 'day' => 5],
                    ['month' => 1, 'day' => 6],
                    ['month' => 1, 'day' => 7],
                ]
            ],
            'Week 5: Crossing Month 1 and Month 2' => [
                5, [
                    ['month' => 1, 'day' => 29],
                    ['month' => 1, 'day' => 30],
                    ['month' => 2, 'day' => 1],
                    ['month' => 2, 'day' => 2],
                    ['month' => 2, 'day' => 3],
                    ['month' => 2, 'day' => 4],
                    ['month' => 2, 'day' => 5],
                ]
            ],
            'Week 28: All in Month 7' => [
                28, [
                    ['month' => 7, 'day' => 8],
                    ['month' => 7, 'day' => 9],
                    ['month' => 7, 'day' => 10],
                    ['month' => 7, 'day' => 11],
                    ['month' => 7, 'day' => 12],
                    ['month' => 7, 'day' => 13],
                    ['month' => 7, 'day' => 14],
                ]
            ],
            'Week 53: Xtr Leap Week (Month 13)' => [
                53, [
                    ['month' => 13, 'day' => 1],
                    ['month' => 13, 'day' => 2],
                    ['month' => 13, 'day' => 3],
                    ['month' => 13, 'day' => 4],
                    ['month' => 13, 'day' => 5],
                    ['month' => 13, 'day' => 6],
                    ['month' => 13, 'day' => 7],
                ]
            ],
        ];
    }

    /**
     * @param array{
     *  start: array{month: int, day: int},
     *  end: array{month: int, day: int}
     *  } $expectedDays
     */
    #[DataProvider('weekBoundariesProvider')]
    public function testGetWeekBoundaries(int $week, array $expectedDays): void
    {
        $this->assertSame($expectedDays, $this->calculator->getWeekBoundaries($week));
    }

    /**
     * @return list<array{
     * 0: int,
     * 1: array{
     * start: array{month: int, day: int},
     * end: array{month: int, day: int}
     * }
     * }>
     */
    public static function weekBoundariesProvider(): array
    {
        return [
            [1, [
                'start' => ['month' => 1, 'day' => 1],
                'end' => ['month' => 1, 'day' => 7],
            ]],
            [5, [
                'start' => ['month' => 1, 'day' => 29],
                'end' => ['month' => 2, 'day' => 5],
            ]],
            [28, [
                'start' => ['month' => 7, 'day' => 8],
                'end' => ['month' => 7, 'day' => 14],
            ]],
            [53, [
                'start' => ['month' => 13, 'day' => 1],
                'end' => ['month' => 13, 'day' => 7],
            ]],
        ];
    }

    #[DataProvider('dayOfYearProvider')]
    public function testGetDayOfYear(int $month, int $day, int $expectedDayOfYear): void
    {
        $this->assertSame($expectedDayOfYear, $this->calculator->getDayOfYear($month, $day));
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: int}>
     */
    public static function dayOfYearProvider(): array
    {
        return [
            'First day of year' => [1, 1, 1],
            'Last day of January' => [1, 30, 30],
            'First day of February' => [2, 1, 31],
            'Last day of February' => [2, 30, 60],
            'First day of March' => [3, 1, 61],
            'Last day of March' => [3, 31, 91],
            'First day of April' => [4, 1, 92],
            'Middle of year (Last day of Q2)' => [6, 31, 182],
            'Last day of regular year (Month 12)' => [12, 31, 364],
            'First day of Xtr (Month 13)' => [13, 1, 365],
            'Last day of Leap Year (Month 13)' => [13, 7, 371],
        ];
    }

    #[DataProvider('weekOfDateProvider')]
    public function testGetWeekOfDate(int $month, int $day, int $expectedWeek): void
    {
        $this->assertSame($expectedWeek, $this->calculator->getWeekOfDate($month, $day));
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: int}>
     */
    public static function weekOfDateProvider(): array
    {
        return [
            'First day of the year' => [1, 1, 1],
            'Last day of week 1' => [1, 7, 1],
            'First day of week 2' => [1, 8, 2],
            'End of January (Day 30)' => [1, 30, 5],
            'Start of February (Day 31)' => [2, 1, 5],
            'Middle of February (Day 42)' => [2, 12, 6],
            'Start of Quarter 2 (Day 92)' => [4, 1, 14],
            'Middle of the year' => [6, 31, 26],
            'Start of Q3' => [7, 1, 27],
            'End of regular year (Day 364)' => [12, 31, 52],
            'First day of Xtr (Day 365)' => [13, 1, 53],
            'Last day of Xtr (Day 371)' => [13, 7, 53],
        ];
    }

    #[DataProvider('monthsInYearProvider')]
    public function testGetMonthsInYear(int $year, int $expectedMonth): void
    {
        $this->assertSame($expectedMonth, $this->calculator->getMonthsInYear($year));
    }

    /**
     * @return array<int, list<int>>
     */
    public static function monthsInYearProvider(): array
    {
        return [
            [2025, 12],
            [2026, 13],
            [3001, 13],
            [3002, 12],
        ];
    }
    #[DataProvider('quarterOfWeekProvider')]
    public function testGetQuarterOfWeek(int $week, int $expectedQuarter): void
    {
        $this->assertSame($expectedQuarter, $this->calculator->getQuarterOfWeek($week));
    }

    /**
     * @return array<int, list<int>>
     */
    public static function quarterOfWeekProvider(): array
    {
        return [
            [1, 1],
            [19, 2],
            [26, 2],
            [27, 3],
            [52, 4],
            [53, 4],
        ];
    }

    #[DataProvider('diffWeeksProvider')]
    public function testDiffWeeks(int $fromYear, int $fromWeek, int $toYear, int $toWeek, int $expected): void
    {
        $result = $this->calculator->diffWeeks($fromYear, $fromWeek, $toYear, $toWeek);
        $this->assertSame($expected, $result);

        $reverseResult = $this->calculator->diffWeeks($toYear, $toWeek, $fromYear, $fromWeek);
        $this->assertSame(-$expected, $reverseResult);
    }

    /**
     * @return array<string, list<int>>
     */
    public static function diffWeeksProvider(): array
    {
        return [
            'same_week' => [2025, 1, 2025, 1, 0],
            'same_year_positive' => [2025, 10, 2025, 15, 5],
            'same_year_negative' => [2025, 20, 2025, 10, -10],
            'short_year_boundary' => [2025, 52, 2026, 1, 1],
            'short_year_crossing' => [2025, 50, 2026, 2, 4], // 2 weeks in 2025 + 2 weeks in 2026
            'long_year_boundary_start' => [2026, 53, 2027, 1, 1],
            'long_year_full_crossing'  => [2026, 1, 2027, 1, 53],
            'multi_year_span' => [2025, 1, 2028, 1, 157],
            'twenty_year_span_with_four_leap' => [2020, 1, 2040, 1, 1044],
            'five_year_span_with_one_leap' => [2025, 1, 2030, 1, 261],
        ];
    }

    #[DataProvider('diffMonthsProvider')]
    public function testDiffMonths(int $fromYear, int $fromMonth, int $toYear, int $toMonth, int $expected): void
    {
        $result = $this->calculator->diffMonths($fromYear, $fromMonth, $toYear, $toMonth);
        $this->assertSame($expected, $result);

        $reverseResult = $this->calculator->diffMonths($toYear, $toMonth, $fromYear, $fromMonth);
        $this->assertSame(-$expected, $reverseResult);
    }

    /**
     * @return array<string, list<int>>
     */
    public static function diffMonthsProvider(): array
    {
        return [
            'same_month' => [2025, 1, 2025, 1, 0],
            'diff_in_year' => [2025, 1, 2025, 12, 11],
            'short_year_boundary' => [2025, 12, 2026, 1, 1],
            'long_year_xtra_month' => [2026, 12, 2026, 13, 1],
            'long_year_boundary' => [2026, 13, 2027, 1, 1],
            'full_long_year' => [2026, 1, 2027, 1, 13],
            'full_short_year' => [2025, 1, 2026, 1, 12],
            'two_short_years' => [2023, 1, 2025, 1, 24],
            'span_including_one_leap' => [2025, 1, 2028, 1, 37],
            'large_span_multiple_leaps' => [2025, 1, 2040, 1, 183],
        ];
    }

    #[DataProvider('diffDaysProvider')]
    public function testDiffDays(int $fromYear, int $fromDay, int $toYear, int $toDay, int $expected): void
    {
        $result = $this->calculator->diffDays($fromYear, $fromDay, $toYear, $toDay);
        $this->assertSame($expected, $result);

        $reverseResult = $this->calculator->diffDays($toYear, $toDay, $fromYear, $fromDay);
        $this->assertSame(-$expected, $reverseResult);
    }

    /**
     * @return array<string, list<int>>
     */
    public static function diffDaysProvider(): array
    {
        return [
            'same_day' => [2025, 100, 2025, 100, 0],
            'within_year' => [2025, 1, 2025, 364, 363],
            'short_year_end' => [2025, 364, 2026, 1, 1],
            'long_year_end' => [2026, 371, 2027, 1, 1],
            'crossing_long_year' => [2026, 1, 2027, 1, 371],
            'crossing_short_year' => [2025, 1, 2026, 1, 364],
            'two_short_years' => [2023, 1, 2025, 1, 728],
            'span_including_one_leap' => [2025, 1, 2028, 1, 1099],
            'large_span_multiple_leaps' => [2025, 1, 2040, 1, 5481],
            'mid_year_to_mid_year' => [2025, 182, 2026, 182, 364],
        ];
    }

    #[DataProvider('diffQuartersProvider')]
    public function testDiffQuarters(int $fromYear, int $fromQ, int $toYear, int $toQ, int $expected): void
    {
        $result = $this->calculator->diffQuarters($fromYear, $fromQ, $toYear, $toQ);
        $this->assertSame($expected, $result);

        $reverseResult = $this->calculator->diffQuarters($toYear, $toQ, $fromYear, $fromQ);
        $this->assertSame(-$expected, $reverseResult);
    }

    /**
     * @return array<string, list<int>>
     */
    public static function diffQuartersProvider(): array
    {
        return [
            'same_q' => [2025, 1, 2025, 1, 0],
            'next_q' => [2025, 1, 2025, 2, 1],
            'year_boundary' => [2025, 4, 2026, 1, 1],
            'two_years' => [2025, 1, 2027, 1, 8],
            'three_years_leap_inclusive' => [2025, 1, 2028, 1, 12],
            'large_span_15_years' => [2025, 1, 2040, 1, 60],
            'mid_start_mid_end' => [2025, 3, 2026, 2, 3],
        ];
    }

    #[DataProvider('diffYearsProvider')]
    public function testDiffYears(int $fromYear, int $toYear, int $expected): void
    {
        $this->assertSame($expected, $this->calculator->diffYears($fromYear, $toYear));
        $this->assertSame(-$expected, $this->calculator->diffYears($toYear, $fromYear));
    }

    /**
     * @return array<string, list<int>>
     */
    public static function diffYearsProvider(): array
    {
        return [
            'same_year' => [2025, 2025, 0],
            'next_year' => [2025, 2026, 1],
            'past_year' => [2026, 2025, -1],
            'decade_gap' => [2020, 2030, 10],
            'century_gap' => [2000, 2100, 100],
        ];
    }

    #[DataProvider('daysOfQuarterProvider')]
    public function testGetDayOfQuarter(int $month, int $day, int $expectedDay): void
    {
        $this->assertSame($expectedDay, $this->calculator->getDayOfQuarter($month, $day));
    }

    /**
     * @return array<string, list<int>>
     */
    public static function daysOfQuarterProvider(): array
    {
        return [
            'Q1: 01.01' => [1, 1, 1],
            'Q1: 01.30' => [1, 30, 30],

            'Q1: 02.01' => [2, 1, 31],
            'Q1: 02.30' => [2, 30, 60],

            'Q1: 03.01' => [3, 1, 61],
            'Q1: 03.31 (end of a regular quarter)' => [3, 31, 91],

            'Q2: 04.01' => [4, 1, 1],
            'Q4: end of a regular quarter' => [12, 31, 91],

            'Q4: 1 day of Xtra' => [13, 1, 92],
            'Q4: end day of Xtra' => [13, 7, 98],
        ];
    }

    #[DataProvider('daysRemainingQuarterProvider')]
    public function testGetDaysRemainingInQuarter(int $year, int $month, int $day, int $expectedRemaining): void
    {
        $this->assertSame($expectedRemaining, $this->calculator->getDaysRemainingInQuarter($year, $month, $day));
    }

    /**
     * @return array<string, list<int>>
     */
    public static function daysRemainingQuarterProvider(): array
    {
        $leapYear = 2026;
        $regularYear = 2025;

        return [
            // Standard Quarter (Q1) - Total 91 days
            'Q1: first day of the quarter' => [$regularYear, 1, 1, 90],
            'Q1: last day of the first month' => [$regularYear, 1, 30, 61],
            'Q1: first day of the second month' => [$regularYear, 2, 1, 60],
            'Q1: last day of the quarter' => [$regularYear, 3, 31, 0],

            // Standard Q4 (Regular Year) - Total 91 days
            'Q4 regular: first day of the quarter' => [$regularYear, 10, 1, 90],
            'Q4 regular: last day of the quarter' => [$regularYear, 12, 31, 0],

            // Long Q4 (Leap Year) - Total 98 days
            'Q4 leap: first day of the quarter' => [$leapYear, 10, 1, 97],
            'Q4 leap: last day of the 12th month (7 days remaining in Xtra)' => [$leapYear, 12, 31, 7],
            'Q4 leap: first day of Xtra month' => [$leapYear, 13, 1, 6],
            'Q4 leap: last day of Xtra month (end of the year)' => [$leapYear, 13, 7, 0],
        ];
    }

    /**
     * @param array{0: int, 1: int, 2: int} $expected
     */
    #[DataProvider('monthsToDateProvider')]
    public function testAddMonthsToDate(int $year, int $month, int $day, int $months, bool $overflow, array $expected): void
    {
        $result = $this->calculator->addMonthsToDate($year, $month, $day, $months, $overflow);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<int, array{0: int, 1: int, 2: int, 3: int, 4: bool, 5: array{0: int, 1: int, 2: int}}>
     */
    public static function monthsToDateProvider(): array
    {
        return [
            [2027, 1, 15, 1, true, [2027, 2, 15]],
            [2027, 3, 31, 1, true, [2027, 5, 1]],
            [2027, 3, 31, 1, false, [2027, 4, 30]],
            [2027, 5, 15, -1, true, [2027, 4, 15]],
            [2027, 6, 31, -2, true, [2027, 5, 1]],
            [2027, 6, 31, -2, false, [2027, 4, 30]],
        ];
    }

    /**
     * @param array{0: int, 1: int, 2: int} $expected
     */
    #[DataProvider('quartersToDateProvider')]
    public function testAddQuartersToDate(
        int $year,
        int $month,
        int $day,
        int $quartersToAdd,
        bool $overflow,
        array $expected
    ): void {
        $result = $this->calculator->addQuartersToDate($year, $month, $day, $quartersToAdd, $overflow);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<int, array{0: int, 1: int, 2: int, 3: int, 4: bool, 5: array{0: int, 1: int, 2: int}}>
     */
    public static function quartersToDateProvider(): array
    {
        return [
            [2027, 1, 15, 1, true, [2027, 4, 15]],
            [2026, 13, 5, 1, true, [2027, 4, 5]],
            [2026, 13, 5, 1, false, [2027, 3, 31]],
            [2027, 4, 15, -1, true, [2027, 1, 15]],
            [2027, 1, 15, -1, true, [2026, 10, 15]],
        ];
    }

    /**
     * @param array{0: int, 1: int, 2: int} $expected
     */
    #[DataProvider('yearsToDateProvider')]
    public function testAddYearsToDate(
        int $year,
        int $month,
        int $day,
        int $yearsToAdd,
        bool $overflow,
        array $expected
    ): void {
        $result = $this->calculator->addYearsToDate($year, $month, $day, $yearsToAdd, $overflow);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<int, array{0: int, 1: int, 2: int, 3: int, 4: bool, 5: array{0: int, 1: int, 2: int}}>
     */
    public static function yearsToDateProvider(): array
    {
        return [
            [2027, 5, 15, 1, true, [2028, 5, 15]],
            [2026, 13, 4, 1, true, [2028, 1, 4]],
            [2026, 13, 4, 1, false, [2027, 12, 31]],
            [2028, 5, 15, -1, true, [2027, 5, 15]],
            [2026, 13, 4, -1, true, [2026, 1, 4]],
            [2026, 13, 4, -1, false, [2025, 12, 31]],
        ];
    }

    #[DataProvider('normalizeDiffDataProvider')]
    public function testNormalizeDiff(int $expected, int $diff, int $progressCmp): void
    {
        $result = $this->calculator->normalizeDiff($diff, $progressCmp);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: int}>
     */
    public static function normalizeDiffDataProvider(): iterable
    {
        return [
            'Future: Exact match' => [1, 1, 0],
            'Future: Period not fully completed' => [0, 1, 1],
            'Future: Period completed and passed' => [1, 1, -1],
            'Future: Multiple periods not fully completed' => [4, 5, 1],
            'Past: Exact match' => [-1, -1, 0],
            'Past: Period not fully completed' => [0, -1, -1],
            'Past: Period completed and passed' => [-1, -1 ,1],
            'Past: Multiple periods not fully completed' => [-4, -5, -1],
            'Zero diff: Target progress is less' => [0, 0, 1],
            'Zero diff: Target progress is more' => [0, 0, -1],
        ];
    }
}
