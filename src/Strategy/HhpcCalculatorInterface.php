<?php

declare(strict_types=1);

namespace Hhpc\Strategy;

/**
 * Leap year determination logic.
 */
interface HhpcCalculatorInterface
{
    /** Days in a standard HHPC year (52 weeks) */
    public const DAYS_IN_SHORT_YEAR = 364;

    /** Days in a leap (long) HHPC year (53 weeks) */
    public const DAYS_IN_LONG_YEAR = 371;

    /** Standard number of weeks */
    public const WEEKS_IN_SHORT_YEAR = 52;

    /** Leap year number of weeks */
    public const WEEKS_IN_LONG_YEAR = 53;

    /** Seconds in one standard day (24 * 60 * 60) */
    public const SECONDS_IN_DAY = 86400;

    /**
     * Check if the year is a leap year.
     */
    public function isLeapYear(int $year): bool;

    /**
     * Returns the total number of days in the year (364 or 371).
     */
    public function getDaysInYear(int $year): int;

    /**
     * The number of weeks in the year (52 or 53).
     */
    public function getWeeksInYear(int $year): int;

    /**
     * Returns the year offset relative to Epoch (1970) in days.
     */
    public function getDaysFromEpoch(int $year): int;
}
