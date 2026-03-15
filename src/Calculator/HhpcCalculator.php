<?php

declare(strict_types=1);

namespace Adiafora\Hhpc\Calculator;

use Adiafora\Hhpc\Contracts\HhpcCalculatorInterface;
use Adiafora\Hhpc\Exception\InvalidDateException;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class HhpcCalculator implements HhpcCalculatorInterface
{
    /**
     * Config of months.
     * Structure: [Days, Quarter, [StartOfWeek, EndOfWeek]]
     *
     * @var array<int, array{days: int, quarter: int, weeks: array{0: int, 1: int}}>
     */
    public const MONTH_CONFIG = [
        // Q1
        1 => ['days' => 30, 'quarter' => 1, 'weeks' => [1, 5]],
        2 => ['days' => 30, 'quarter' => 1, 'weeks' => [5, 9]],
        3 => ['days' => 31, 'quarter' => 1, 'weeks' => [9, 13]],
        // Q2
        4 => ['days' => 30, 'quarter' => 2, 'weeks' => [14, 18]],
        5 => ['days' => 30, 'quarter' => 2, 'weeks' => [18, 22]],
        6 => ['days' => 31, 'quarter' => 2, 'weeks' => [22, 26]],
        // Q3
        7 => ['days' => 30, 'quarter' => 3, 'weeks' => [27, 31]],
        8 => ['days' => 30, 'quarter' => 3, 'weeks' => [31, 35]],
        9 => ['days' => 31, 'quarter' => 3, 'weeks' => [35, 39]],
        // Q4
        10 => ['days' => 30, 'quarter' => 4, 'weeks' => [40, 44]],
        11 => ['days' => 30, 'quarter' => 4, 'weeks' => [44, 48]],
        12 => ['days' => 31, 'quarter' => 4, 'weeks' => [48, 52]],
        // Xtra
        13 => ['days' => 7, 'quarter' => 4, 'weeks' => [53, 53]],
    ];

    public function isLeapYear(int $year): bool
    {
        $prevYear = $year - 1;
        $jan1DayOfWeek = ($year + intdiv($prevYear, 4) - intdiv($prevYear, 100) + intdiv($prevYear, 400)) % self::DAYS_IN_WEEK;

        if ($jan1DayOfWeek === 4) {
            return true;
        }

        if ($jan1DayOfWeek === 3) {
            return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
        }

        return false;
    }

    public function getDaysInYear(int $year): int
    {
        return $this->isLeapYear($year) ? self::DAYS_IN_LONG_YEAR : self::DAYS_IN_SHORT_YEAR;
    }

    public function getWeeksInYear(int $year): int
    {
        return $this->isLeapYear($year) ? self::WEEKS_IN_LONG_YEAR : self::WEEKS_IN_SHORT_YEAR;
    }

    public function getWeekOfDate(int $month, int $day): int
    {
        $dayOfYear = $this->getDayOfYear($month, $day);

        return (int) ceil($dayOfYear / self::DAYS_IN_WEEK);
    }

    public function getMonthsInYear(int $year): int
    {
        return $this->isLeapYear($year) ? self::MONTHS_IN_LONG_YEAR : self::MONTHS_IN_SHORT_YEAR;
    }

    public function getQuartersInYear(): int
    {
        return self::QUARTERS_IN_YEAR;
    }

    public function getDaysFromEpoch(int $year): int
    {
        $dto = (new DateTimeImmutable(timezone: new DateTimeZone('UTC')))
            ->setISODate($year, 1)
            ->setTime(0, 0);

        return (int)($dto->getTimestamp() / self::SECONDS_IN_DAY);
    }

    public function fromGregorian(DateTimeInterface $date): array
    {
        $utcDate = DateTimeImmutable::createFromInterface($date)
            ->setTimezone(new DateTimeZone('UTC'))
            ->setTime(0, 0);

        $hhpcYear = (int)$utcDate->format('o');

        $startOfYear = $utcDate->setISODate($hhpcYear, 1);

        $daysPassed = (int)$utcDate->diff($startOfYear)->days;

        if ($daysPassed >= self::DAYS_IN_SHORT_YEAR) {
            return [$hhpcYear, self::MONTHS_IN_LONG_YEAR, $daysPassed - self::DAYS_IN_SHORT_YEAR + 1];
        }

        $quarterIndex = intdiv($daysPassed, self::DAYS_IN_QUARTER);

        $dayInQuarter = $daysPassed % self::DAYS_IN_QUARTER;

        [$monthOffset, $daysToSubtract] = match (true) {
            $dayInQuarter < 30 => [0, 0],
            $dayInQuarter < 60 => [1, 30],
            default => [2, 60],
        };

        return [
            $hhpcYear,
            ($quarterIndex * 3) + $monthOffset + 1,
            $dayInQuarter - $daysToSubtract + 1,
        ];
    }

    public function getDayOfWeek(int $month, int $day): int
    {
        $monthIndexInQuarter = ($month - 1) % 3;

        $offset = match ($monthIndexInQuarter) {
            0 => 0,
            1 => 2,
            2 => 4,
            default => throw new InvalidDateException('Invalid month: ' . $month),
        };

        return (($day + $offset - 1) % self::DAYS_IN_WEEK) + 1;
    }

    public function getDayOfYear(int $month, int $day): int
    {
        if ($month === self::MONTHS_IN_LONG_YEAR) {
            return self::DAYS_IN_SHORT_YEAR + $day;
        }

        $monthIndexInQuarter = ($month - 1) % 3;

        $daysOffset = match ($monthIndexInQuarter) {
            0 => 0,
            1 => 30,
            2 => 60,
            default => throw new InvalidDateException('Invalid month: ' . $month),
        };

        return (intdiv($month - 1, 3) * self::DAYS_IN_QUARTER) + $daysOffset + $day;
    }

    public function getDaysInMonth(int $month): int
    {
        return self::MONTH_CONFIG[$month]['days']
            ?? throw new InvalidDateException('Invalid month: ' . $month);
    }

    public function getMonthsQuarter(int $month): int
    {
        return self::MONTH_CONFIG[$month]['quarter']
            ?? throw new InvalidDateException('Invalid month: ' . $month);
    }

    public function getWeekRangeForMonth(int $month): array
    {
        return self::MONTH_CONFIG[$month]['weeks']
            ?? throw new InvalidDateException('Invalid month: ' . $month);
    }

    public function addQuarters(int $year, int $quarter, int $addQuarters): array
    {
        return $this->addUnits($year, $quarter, $addQuarters, fn (int $year) => $this->getQuartersInYear());
    }

    public function addMonths(int $year, int $month, int $addMonths): array
    {
        return $this->addUnits($year, $month, $addMonths, fn (int $year) => $this->getMonthsInYear($year));
    }

    public function addWeeks(int $year, int $week, int $addWeeks): array
    {
        return $this->addUnits($year, $week, $addWeeks, fn (int $year) => $this->getWeeksInYear($year));
    }

    public function addDays(int $year, int $day, int $addDays): array
    {
        return $this->addUnits($year, $day, $addDays, fn (int $year) => $this->getDaysInYear($year));
    }

    /**
     * @param callable(int): int $unitsInYearProvider A function that returns the number of units per year.
     * @return array{0: int, 1: int} [targetYear, targetValue]
     */
    private function addUnits(int $year, int $currentValue, int $delta, callable $unitsInYearProvider): array
    {
        $targetValue = $currentValue + $delta;
        $targetYear = $year;

        if ($delta > 0) {
            while (true) {
                $limit = $unitsInYearProvider($targetYear);
                if ($targetValue <= $limit) {
                    break;
                }
                $targetValue -= $limit;
                $targetYear++;
            }
        } else {
            while ($targetValue <= 0) {
                $targetYear--;
                $targetValue += $unitsInYearProvider($targetYear);
            }
        }

        return [$targetYear, $targetValue];
    }

    public function getWeekBoundaries(int $week): array
    {
        $startDayOfYear = ($week - 1) * self::DAYS_IN_WEEK + 1;
        $endDayOfYear = $week * self::DAYS_IN_WEEK;

        [$startMonth, $startDay] = $this->getDateFromDayOfYear($startDayOfYear);
        [$endMonth, $endDay] = $this->getDateFromDayOfYear($endDayOfYear);

        return [
            'start' => ['month' => $startMonth, 'day' => $startDay],
            'end'   => ['month' => $endMonth, 'day' => $endDay],
        ];
    }

    public function getDaysInWeek(int $week): array
    {
        $days = [];

        $startDayOfYear = ($week - 1) * self::DAYS_IN_WEEK + 1;

        for ($i = 0; $i < self::DAYS_IN_WEEK; $i++) {
            $currentDayOfYear = $startDayOfYear + $i;

            [$month, $day] = $this->getDateFromDayOfYear($currentDayOfYear);

            $days[] = ['month' => $month, 'day' => $day];
        }

        return $days;
    }

    public function getDateFromDayOfYear(int $dayOfYear): array
    {
        if ($dayOfYear > self::DAYS_IN_SHORT_YEAR) {
            return [self::MONTHS_IN_LONG_YEAR, $dayOfYear - self::DAYS_IN_SHORT_YEAR];
        }

        $quarterIdx = intdiv($dayOfYear - 1, self::DAYS_IN_QUARTER);

        $dayInQuarter = $dayOfYear - ($quarterIdx * self::DAYS_IN_QUARTER);

        $monthOffset = min(2, intdiv($dayInQuarter - 1, 30));

        return [
            ($quarterIdx * 3) + $monthOffset + 1,
            $dayInQuarter - ($monthOffset * 30)
        ];
    }

    public function getQuarterOfWeek(int $week): int
    {
        return min(4, intdiv($week - 1, 13) + 1);
    }

    public function diffWeeks(int $fromYear, int $fromWeek, int $toYear, int $toWeek): int
    {
        return $this->diffUnits(
            $fromYear,
            $fromWeek,
            $toYear,
            $toWeek,
            fn (int $year) => $this->getWeeksInYear($year)
        );
    }

    public function diffMonths(int $fromYear, int $fromMonth, int $toYear, int $toMonth): int
    {
        return $this->diffUnits(
            $fromYear,
            $fromMonth,
            $toYear,
            $toMonth,
            fn (int $year) => $this->getMonthsInYear($year)
        );
    }

    public function diffDays(int $fromYear, int $fromDayOfYear, int $toYear, int $toDayOfYear): int
    {
        return $this->diffUnits(
            $fromYear,
            $fromDayOfYear,
            $toYear,
            $toDayOfYear,
            fn (int $year) => $this->getDaysInYear($year)
        );
    }

    public function diffQuarters(int $fromYear, int $fromQuarter, int $toYear, int $toQuarter): int
    {
        return $this->diffUnits(
            $fromYear,
            $fromQuarter,
            $toYear,
            $toQuarter,
            fn (int $year) => $this->getQuartersInYear()
        );
    }

    /**
     * A universal method for calculating the difference.
     * @param callable(int): int $unitsInYearProvider A function that returns the number of units in a particular year.
     */
    private function diffUnits(
        int $fromYear,
        int $fromIndex,
        int $toYear,
        int $toIndex,
        callable $unitsInYearProvider
    ): int {
        if ($fromYear === $toYear) {
            return $toIndex - $fromIndex;
        }

        $isNegative = false;

        if ($fromYear > $toYear) {
            [$fromYear, $fromIndex, $toYear, $toIndex] = [$toYear, $toIndex, $fromYear, $fromIndex];
            $isNegative = true;
        }

        $unitsCount = $unitsInYearProvider($fromYear) - $fromIndex;

        for ($year = $fromYear + 1; $year < $toYear; $year++) {
            $unitsCount += $unitsInYearProvider($year);
        }

        $unitsCount += $toIndex;

        return $isNegative ? -$unitsCount : $unitsCount;
    }

    public function diffYears(int $fromYear, int $toYear): int
    {
        return $toYear - $fromYear;
    }

    public function getMonthsInQuarter(int $year, int $quarter): array
    {
        $startMonth = ($quarter - 1) * 3 + 1;
        $months = [$startMonth, $startMonth + 1, $startMonth + 2];

        if ($this->isLongQuarter($year, $quarter)) {
            $months[] = self::XTRA_MONTH;
        }

        return $months;
    }

    public function getDaysInQuarter(int $year, int $quarter): int
    {
        $days = self::DAYS_IN_QUARTER;

        if ($this->isLongQuarter($year, $quarter)) {
            $days += self::DAYS_IN_WEEK;
        }

        return $days;
    }

    public function getQuarterBoundaries(int $year, int $quarter): array
    {
        $startMonth = ($quarter - 1) * 3 + 1;
        $startDay = 1;

        if ($this->isLongQuarter($year, $quarter)) {
            $endMonth = self::XTRA_MONTH;
            $endDay = self::DAYS_IN_WEEK;
        } else {
            $endMonth = $startMonth + 2;
            $endDay = 31;
        }

        return [
            'start' => ['month' => $startMonth, 'day' => $startDay],
            'end' => ['month' => $endMonth, 'day' => $endDay],
        ];
    }

    public function isLongQuarter(int $year, int $quarter): bool
    {
        return $quarter === self::QUARTERS_IN_YEAR && $this->isLeapYear($year);
    }

    public function toTimestamp(int $year, int $month, int $day, int $hour, int $minute, int $second): int
    {
        $daysToYearStart = $this->getDaysFromEpoch($year);
        $daysInYearPassed = $this->getDayOfYear($month, $day) - 1;

        $totalDaysFromEpoch = $daysToYearStart + $daysInYearPassed;

        return ($totalDaysFromEpoch * self::SECONDS_IN_DAY)
            + ($hour * 3600)
            + ($minute * 60)
            + $second;
    }

    public function getDayOfQuarter(int $month, int $day): int
    {
        if ($month === self::XTRA_MONTH) {
            return self::DAYS_IN_QUARTER + $day;
        }

        $monthIndexInQuarter = ($month - 1) % 3;

        $daysOffset = match ($monthIndexInQuarter) {
            0 => 0,
            1 => 30,
            2 => 60,
            default => throw new \LogicException('Impossible condition: the index of the month is out of the acceptable range'),
        };

        return $daysOffset + $day;
    }

    public function getDaysRemainingInQuarter(int $year, int $month, int $day): int
    {
        $quarter = $this->getMonthsQuarter($month);

        $totalDaysInQuarter = $this->getDaysInQuarter($year, $quarter);
        $currentDayOfQuarter = $this->getDayOfQuarter($month, $day);

        return $totalDaysInQuarter - $currentDayOfQuarter;
    }

    public function addMonthsToDate(int $year, int $month, int $day, int $months, bool $overflow = true): array
    {
        [$targetYear, $targetMonth] = $this->addMonths($year, $month, $months);

        $daysInTargetMonth = $this->getDaysInMonth($targetMonth);

        if ($day <= $daysInTargetMonth) {
            return [$targetYear, $targetMonth, $day];
        }

        if (!$overflow) {
            return [$targetYear, $targetMonth, $daysInTargetMonth];
        }

        $dayOfYear = $this->getDayOfYear($targetMonth, $daysInTargetMonth);
        $extraDays = $day - $daysInTargetMonth;

        [$newYear, $newDayOfYear] = $this->addDays($targetYear, $dayOfYear, $extraDays);
        [$newMonth, $newDay] = $this->getDateFromDayOfYear($newDayOfYear);

        return [$newYear, $newMonth, $newDay];
    }

    public function addQuartersToDate(int $year, int $month, int $day, int $quarters, bool $overflow = true): array
    {
        $currentQuarter = $this->getMonthsQuarter($month);
        $dayOfQuarter = $this->getDayOfQuarter($month, $day);

        [$targetYear, $targetQuarter] = $this->addQuarters($year, $currentQuarter, $quarters);

        $daysInTargetQuarter = $this->getDaysInQuarter($targetYear, $targetQuarter);

        if ($dayOfQuarter <= $daysInTargetQuarter) {
            return $this->getDateFromQuarterAndDay($targetYear, $targetQuarter, $dayOfQuarter);
        }

        if ($overflow) {
            $boundaries = $this->getQuarterBoundaries($targetYear, $targetQuarter);
            $quarterEndDayOfYear = $this->getDayOfYear($boundaries['end']['month'], $boundaries['end']['day']);
            $extraDays = $dayOfQuarter - $daysInTargetQuarter;

            [$newYear, $newDayOfYear] = $this->addDays($targetYear, $quarterEndDayOfYear, $extraDays);
            [$newMonth, $newDay] = $this->getDateFromDayOfYear($newDayOfYear);

            return [$newYear, $newMonth, $newDay];
        }

        return $this->getDateFromQuarterAndDay($targetYear, $targetQuarter, $daysInTargetQuarter);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function getDateFromQuarterAndDay(int $year, int $quarter, int $dayOfQuarter): array
    {
        $startMonth = ($quarter - 1) * 3 + 1;

        if ($dayOfQuarter > self::DAYS_IN_QUARTER) {
            return [$year, self::XTRA_MONTH, $dayOfQuarter - self::DAYS_IN_QUARTER];
        }

        $monthOffset = min(2, intdiv($dayOfQuarter - 1, 30));
        $month = $startMonth + $monthOffset;
        $day = $dayOfQuarter - ($monthOffset * 30);

        return [$year, $month, $day];
    }

    public function addYearsToDate(int $year, int $month, int $day, int $years, bool $overflow = true): array
    {
        $targetYear = $year + $years;

        if ($month === self::XTRA_MONTH && !$this->isLeapYear($targetYear)) {
            if ($overflow) {
                [$newYear, $newDayOfYear] = $this->addDays($targetYear, self::DAYS_IN_SHORT_YEAR, $day);
                [$newMonth, $newDay] = $this->getDateFromDayOfYear($newDayOfYear);

                return [$newYear, $newMonth, $newDay];
            }

            return [$targetYear, self::MONTHS_IN_SHORT_YEAR, self::MONTH_CONFIG[self::MONTHS_IN_SHORT_YEAR]['days']];
        }

        return [$targetYear, $month, $day];
    }

    public function normalizeDiff(int $diff, int $progressCmp): int
    {
        if ($diff !== 0 && ($diff <=> 0) === $progressCmp) {
            return $diff - $progressCmp;
        }

        return $diff;
    }
}
