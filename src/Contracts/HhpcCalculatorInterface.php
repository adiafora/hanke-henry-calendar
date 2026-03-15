<?php

declare(strict_types=1);

namespace Adiafora\Hhpc\Contracts;

use DateTimeInterface;

/**
 * Leap year determination logic.
 */
interface HhpcCalculatorInterface
{
    /** Quarters in a year */
    public const QUARTERS_IN_YEAR = 4;

    /** Days in a week */
    public const DAYS_IN_WEEK = 7;

    /** Days in a quarter */
    public const DAYS_IN_QUARTER = 91;

    /** Days in a standard HHPC year (52 weeks) */
    public const DAYS_IN_SHORT_YEAR = 364;

    /** Days in a leap (long) HHPC year (53 weeks) */
    public const DAYS_IN_LONG_YEAR = 371;

    /** Standard number of weeks */
    public const WEEKS_IN_SHORT_YEAR = 52;

    /** Leap year number of weeks */
    public const WEEKS_IN_LONG_YEAR = 53;

    /** Standard number of months */
    public const MONTHS_IN_SHORT_YEAR = 12;

    /** Leap year number of months */
    public const MONTHS_IN_LONG_YEAR = 13;

    public const XTRA_MONTH = 13;

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
     * Returns the number of weeks for date.
     */
    public function getWeekOfDate(int $month, int $day): int;

    /**
     * The number of months in the year (12 or 13).
     */
    public function getMonthsInYear(int $year): int;

    /**
     * The number of quarters in the year (4).
     */
    public function getQuartersInYear(): int;

    /**
     * Returns the year offset relative to Epoch (1970) in days.
     */
    public function getDaysFromEpoch(int $year): int;

    /**
     * Converts a Gregorian date to Hanke-Henry Permanent Calendar coordinates.
     *
     * This method normalizes the input date to UTC and calculates the corresponding
     * HHPC year, month, and day based on the ISO-8601 week numbering system.
     *
     * @param DateTimeInterface $date The Gregorian date to convert (timezone agnostic).
     * @return array{0: int, 1: int, 2: int} Returns [year, month, day] in HHPC format.
     */
    public function fromGregorian(DateTimeInterface $date): array;

    /**
     * Returns the ISO-8601 numeric representation of the day of the week.
     *
     * This method relies on the permanent structure of the HHPC, where every year
     * starts on a Monday. It assumes the provided date coordinates constitute
     * a valid HHPC date.
     *
     * @return int 1 (for Monday) through 7 (for Sunday).
     */
    public function getDayOfWeek(int $month, int $day): int;

    /**
     * Returns the ordinal day number within the year (Day of Year).
     *
     * Calculates the cumulative day count from the beginning of the year
     * based on the fixed 91-day quarter structure (30-30-31 pattern).
     *
     * @param int $month The month number (1-13).
     * @param int $day The day number within the month.
     * @return int The day of the year (1-371).
     */
    public function getDayOfYear(int $month, int $day): int;

    /**
     * Returns the number of days in the specified month.
     *
     * In the Hanke-Henry Permanent Calendar, the length of a specific month index
     * is constant across all years (e.g., the 1st month always has 30 days),
     * regardless of whether the year is a long (Xtra) year or not.
     *
     * @param int $month The month index (1-13).
     * @return int The number of days in the month.
     */
    public function getDaysInMonth(int $month): int;

    /**
     * Returns the quarter number for the specified month.
     *
     * @param int $month The month index.
     * @return int The quarter number (1-4).
     */
    public function getMonthsQuarter(int $month): int;

    /**
     * Returns the range of weeks for the month.
     *
     * @return array{0: int, 1: int} [startWeek, endWeek]
     */
    public function getWeekRangeForMonth(int $month): array;

    /**
     * Adds (or subtracts) a number of quarters to the given year and quarter.
     *
     * Pass a negative value to subtract quarters.
     *
     * @param int $year The base year.
     * @param int $quarter The base quarter index.
     * @param int $addQuarters The number of quarters to add (or subtract if negative).
     * @return array{0: int, 1: int} An array containing the resulting [year, quarter].
     */
    public function addQuarters(int $year, int $quarter, int $addQuarters): array;

    /**
     * Adds (or subtracts) a number of months to the given year and month.
     *
     * This method automatically handles year transitions, accounting for
     * the variable length of years (12 or 13 months in "Xtra" years).
     * Pass a negative value to subtract months.
     *
     * @param int $year The base year.
     * @param int $month The base month index.
     * @param int $addMonths The number of months to add (or subtract if negative).
     * @return array{0: int, 1: int} An array containing the resulting [year, month].
     */
    public function addMonths(int $year, int $month, int $addMonths): array;

    /**
     * Adds (or subtracts) a number of weeks to the given year and week.
     *
     * This method automatically handles year transitions, accounting for
     * the variable length of years (52 or 53 weeks in "Xtra" years).
     * Pass a negative value to subtract weeks.
     *
     * @param int $year The base year.
     * @param int $week The base week index.
     * @param int $addWeeks The number of weeks to add (or subtract if negative).
     * @return array{0: int, 1: int} An array containing the resulting [year, week].
     */
    public function addWeeks(int $year, int $week, int $addWeeks): array;

    /**
     * Adds (or subtracts) a number of days to the given year and day of year.
     *
     * This method automatically handles year transitions, accounting for
     * the variable length of years (364 or 371 days in "Xtra" years).
     * Pass a negative value to subtract days.
     *
     * @param int $year The base year.
     * @param int $day The base day index.
     * @param int $addDays The number of days to add (or subtract if negative).
     * @return array{0: int, 1: int} An array containing the resulting [year, day].
     */
    public function addDays(int $year, int $day, int $addDays): array;

    /**
     * Calculates the start and end dates for a specific week number.
     *
     * @param int $week The week number (1-53).
     *
     * @return array{
     * start: array{month: int, day: int},
     * end: array{month: int, day: int}
     * } Array containing start and end date components.
     */
    public function getWeekBoundaries(int $week): array;

    /**
     * Retrieves a list of all days in a specific week.
     *
     * Returns an ordered list of 7 days, where each day is represented
     * by its month and day number.
     *
     * @param int $week The week number (1-53).
     *
     * @return list<array{month: int, day: int}> List of days, where each day is an array with 'month' and 'day' keys.
     */
    public function getDaysInWeek(int $week): array;

    /**
     * Converts a day of the year ordinal number into month and day.
     *
     * Reverse operation of getDayOfYear. Calculates the specific month and day
     * based on the fixed 91-day quarter structure.
     *
     * @param int $dayOfYear The day number within the year (1-371).
     * @return array{0: int, 1: int} Array containing [month, day].
     */
    public function getDateFromDayOfYear(int $dayOfYear): array;

    /**
     * Determines the quarter (1-4) to which a specific week belongs.
     *
     * In the Hanke-Henry calendar, each quarter contains exactly 13 weeks.
     * The 53rd week (in leap years) is assigned to the 4th quarter.
     *
     * @param int $week The week number (1-53).
     * @return int The quarter number (1-4).
     */
    public function getQuarterOfWeek(int $week): int;

    /**
     * Calculates the difference in weeks between two dates (specified by year and week).
     *
     * The result is positive if the "to" date is in the future relative to the "from" date,
     * and negative otherwise. The calculation takes into account "Long Years" (53 weeks)
     * and "Short Years" (52 weeks) according to the Hanke-Henry Permanent Calendar rules.
     *
     * @param int $fromYear The start year.
     * @param int $fromWeek The start week number (1-52 or 1-53).
     * @param int $toYear   The target year.
     * @param int $toWeek   The target week number (1-52 or 1-53).
     *
     * @return int The difference in weeks.
     */
    public function diffWeeks(int $fromYear, int $fromWeek, int $toYear, int $toWeek): int;

    /**
     * Calculates the difference in months between two dates.
     *
     * Takes into account that Long Years in HHPC have 13 months (including the 'Xtra' month),
     * while Short Years have 12 months.
     *
     * @param int $fromYear  The start year.
     * @param int $fromMonth The start month index (1-13).
     * @param int $toYear    The target year.
     * @param int $toMonth   The target month index (1-13).
     *
     * @return int The difference in months.
     */
    public function diffMonths(int $fromYear, int $fromMonth, int $toYear, int $toMonth): int;

    /**
     * Calculates the difference in days between two dates (specified by day of year).
     *
     * This method accounts for the varying length of years:
     * - Short Year: 364 days
     * - Long Year: 371 days (adds 'Xtra' week)
     *
     * @param int $fromYear      The start year.
     * @param int $fromDayOfYear The start day index (1-371).
     * @param int $toYear        The target year.
     * @param int $toDayOfYear   The target day index (1-371).
     *
     * @return int The difference in days.
     */
    public function diffDays(int $fromYear, int $fromDayOfYear, int $toYear, int $toDayOfYear): int;

    /**
     * Calculates the difference in quarters between two dates.
     *
     * Since the HHPC structure implies a fixed number of 4 quarters per year
     * (regardless of whether it is a Long or Short year), this calculation is linear.
     *
     * @param int $fromYear    The start year.
     * @param int $fromQuarter The start quarter (1-4).
     * @param int $toYear      The target year.
     * @param int $toQuarter   The target quarter (1-4).
     *
     * @return int The difference in quarters.
     */
    public function diffQuarters(int $fromYear, int $fromQuarter, int $toYear, int $toQuarter): int;

    /**
     * Calculates the difference in years.
     *
     * @return int Positive if toYear is in the future.
     */
    public function diffYears(int $fromYear, int $toYear): int;

    /**
     * Returns the start and end dates (month and day indices) of the quarter.
     *
     * This method calculates the boundaries of the quarter within the HHPC year.
     *
     * @param int $year    The year.
     * @param int $quarter The quarter number (1-4).
     *
     * @return array{
     * start: array{month: int, day: int},
     * end: array{month: int, day: int}
     * } An associative array containing the boundary dates.
     */
    public function getQuarterBoundaries(int $year, int $quarter): array;

    /**
     * Returns the total number of days in the specified quarter.
     *
     * - Standard Quarter: 91 days (30 + 30 + 31).
     * - Long Quarter: 98 days (91 + 7 days of the "Xtra" week).
     *
     * @param int $year    The year.
     * @param int $quarter The quarter number (1-4).
     *
     * @return int The count of days (typically 91 or 98).
     */
    public function getDaysInQuarter(int $year, int $quarter): int;

    /**
     * Returns a list of month indices belonging to the specified quarter.
     *
     * - Standard quarters contain 3 months.
     * - A Long Quarter (Q4 in a Leap Year) contains 4 months (including the 13th "Xtra" month).
     *
     * @param int $year    The year.
     * @param int $quarter The quarter number (1-4).
     *
     * @return list<int> An array of month indices (e.g., [1, 2, 3] or [10, 11, 12, 13]).
     */
    public function getMonthsInQuarter(int $year, int $quarter): array;

    /**
     * Determines if the specified quarter is a "Long Quarter".
     *
     * In the Hanke-Henry Permanent Calendar (HHPC), a "Long Quarter" is one that
     * includes the "Xtra" week (and thus the 13th month). This typically occurs
     * only in the 4th quarter of a Leap (Long) Year.
     *
     * @param int $year    The year to check.
     * @param int $quarter The quarter number (1-4).
     *
     * @return bool True if the quarter is long (contains extra days), false otherwise.
     */
    public function isLongQuarter(int $year, int $quarter): bool;

    /**
     * Converts a specific Hanke-Henry date and time to a Unix timestamp.
     *
     * The Unix timestamp represents the number of seconds that have elapsed
     * since the Unix Epoch (1970-01-01 00:00:00 UTC).
     *
     * @param int $year   The Hanke-Henry year.
     * @param int $month  The Hanke-Henry month (1-13).
     * @param int $day    The day of the month (1-31).
     * @param int $hour   The hour of the day (0-23).
     * @param int $minute The minute of the hour (0-59).
     * @param int $second The second of the minute (0-59).
     *
     * @return int The Unix timestamp in seconds.
     */
    public function toTimestamp(int $year, int $month, int $day, int $hour, int $minute, int $second): int;

    /**
     * Calculates the chronological day of the quarter for a given Hanke-Henry date.
     *
     * In the Hanke-Henry Permanent Calendar, standard quarters have exactly 91 days.
     * In leap years, the fourth quarter (Q4) is extended by a 7-day Xtra month,
     * totaling 98 days.
     *
     * @param int $month The Hanke-Henry month (1-13).
     * @param int $day   The day of the month (1-31).
     *
     * @return int The 1-based day index within the current quarter.
     */
    public function getDayOfQuarter(int $month, int $day): int;

    /**
     * Calculates the number of days remaining in the current quarter from a given date.
     *
     * This method computes the difference between the total number of days in the
     * specific quarter and the current day of that quarter. The result is 0 on the
     * very last day of the quarter.
     *
     * @param int $year  The Hanke-Henry year.
     * @param int $month The Hanke-Henry month (1-13).
     * @param int $day   The day of the month (1-31).
     *
     * @return int The number of days left until the end of the quarter.
     */
    public function getDaysRemainingInQuarter(int $year, int $month, int $day): int;

    /**
     * Adds (or subtracts if the value is negative) a given number of months to/from the date.
     *
     * @param int $year The current year.
     * @param int $month The current month.
     * @param int $day The current day of the month.
     * @param int $months The number of months to add.
     * @param bool $overflow If true, days exceeding the target month's capacity will overflow to the next month.
     * If false, the date is clamped to the last day of the target month.
     * @return array{0: int, 1: int, 2: int} An array containing the new year, month, and day respectively.
     */
    public function addMonthsToDate(int $year, int $month, int $day, int $months, bool $overflow = true): array;

    /**
     * Adds (or subtracts if the value is negative) a given number of quarters to/from the date.
     *
     * @param int $year The current year.
     * @param int $month The current month.
     * @param int $day The current day of the month.
     * @param int $quarters The number of quarters to add.
     * @param bool $overflow If true, days exceeding the target quarter's capacity will overflow to the next quarter.
     * If false, the date is clamped to the last day of the target quarter.
     * @return array{0: int, 1: int, 2: int} An array containing the new year, month, and day respectively.
     */
    public function addQuartersToDate(int $year, int $month, int $day, int $quarters, bool $overflow = true): array;

    /**
     * Adds (or subtracts if the value is negative) a given number of years to/from the date.
     *
     * In the Hanke-Henry calendar, date collisions during year transitions mainly occur
     * if the original date is in the 13th (Xtr) month of a leap year and the target year is a common year.
     *
     * @param int $year The current year.
     * @param int $month The current month.
     * @param int $day The current day of the month.
     * @param int $years The number of years to add.
     * @param bool $overflow If true, days from the 13th month will overflow to the beginning of the year following the target year.
     * If false, the date is clamped to the last day of the target common year (month 12, day 31).
     * @return array{0: int, 1: int, 2: int} An array containing the new year, month, and day respectively.
     */
    public function addYearsToDate(int $year, int $month, int $day, int $years, bool $overflow = true): array;

    /**
     * Normalizes the raw calendar difference to calculate the exact number of fully elapsed periods.
     *
     * This method evaluates the direction of the time travel (future or past) against the internal
     * progress of the period (e.g., time, days, or months). If the target date has not yet reached
     * the equivalent internal point in the current period, the difference is adjusted to reflect
     * only fully completed periods.
     *
     * @param int $diff        The raw difference in periods (e.g., calendar days, months, or years).
     * @param int $progressCmp The result of the spaceship operator (<=>) comparing the internal
     * progress of the start date against the target date (-1, 0, or 1).
     * @return int The adjusted difference representing only fully elapsed periods.
     */
    public function normalizeDiff(int $diff, int $progressCmp): int;
}
