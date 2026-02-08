<?php

declare(strict_types=1);

namespace Tests\Hhpc\Strategy;

use Hhpc\Strategy\HhpcCalculator;
use PHPUnit\Framework\TestCase;

class HhpcCalculatorTest extends TestCase
{
    private HhpcCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new HhpcCalculator();
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
        $expected2026 = (int)(new \DateTimeImmutable('2025-12-29'))->format('U') / 86400;
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
}
