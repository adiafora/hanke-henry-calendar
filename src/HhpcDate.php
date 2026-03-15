<?php

declare(strict_types=1);

namespace Adiafora\Hhpc;

use Adiafora\Hhpc\Config\HhpcConfig;
use Adiafora\Hhpc\Contracts\HhpcCalculatorInterface;
use Adiafora\Hhpc\Contracts\HhpcDateInterface;
use Adiafora\Hhpc\Contracts\HhpcMonthInterface;
use Adiafora\Hhpc\Contracts\HhpcQuarterInterface;
use Adiafora\Hhpc\Contracts\HhpcWeekInterface;
use Adiafora\Hhpc\Contracts\HhpcYearInterface;
use Adiafora\Hhpc\Exception\InvalidDateException;
use DateTimeImmutable;
use DateTimeInterface;

final readonly class HhpcDate implements HhpcDateInterface
{
    private HhpcCalculatorInterface $calculator;

    private function __construct(
        private int $year,
        private int $month,
        private int $day,
        private int $hour,
        private int $minute,
        private int $second,
        private int $microsecond,
    ) {
        self::validate(
            year: $year,
            month: $month,
            day: $day,
            hour: $hour,
            minute: $minute,
            second: $second,
            microsecond: $microsecond,
        );

        $this->calculator = HhpcConfig::getCalculator();
    }

    public static function validate(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $minute,
        int $second,
        int $microsecond,
    ): void {
        HhpcMonth::validate($year, $month);

        if (!self::isValid(
            year: $year,
            month: $month,
            day: $day,
            hour: $hour,
            minute: $minute,
            second: $second,
            microsecond: $microsecond,
        )) {
            throw new InvalidDateException('Invalid date');
        }
    }

    public static function isValid(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $minute,
        int $second,
        int $microsecond
    ): bool {
        if (!HhpcMonth::isValid($year, $month)) {
            return false;
        }

        $calculator = HhpcConfig::getCalculator();

        if ($day < 1 || $day > $calculator->getDaysInMonth($month)) {
            return false;
        }

        if ($hour < 0 || $hour > 23) {
            return false;
        }

        if ($minute < 0 || $minute > 59) {
            return false;
        }

        if ($second < 0 || $second > 59) {
            return false;
        }

        if ($microsecond < 0 || $microsecond > 999999) {
            return false;
        }

        return true;
    }

    public static function create(
        int $year,
        int $month,
        int $day,
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
        int $microsecond = 0,
    ): HhpcDateInterface {
        return new self(
            year: $year,
            month: $month,
            day: $day,
            hour: $hour,
            minute: $minute,
            second: $second,
            microsecond: $microsecond,
        );
    }

    public static function now(): HhpcDateInterface
    {
        return self::fromGregorian(HhpcConfig::getGregorianNow());
    }

    public static function today(): HhpcDateInterface
    {
        $gregorianNow = DateTimeImmutable::createFromInterface(HhpcConfig::getGregorianNow());

        return self::fromGregorian(
            $gregorianNow->setTime(0, 0)
        );
    }

    public static function yesterday(): HhpcDateInterface
    {
        return self::today()->subDays();
    }

    public static function tomorrow(): HhpcDateInterface
    {
        return self::today()->addDays();
    }

    public static function createFromFormat(string $format, string $datetime): HhpcDateInterface
    {
        $tokens = [
            'Y' => '(?<year>\d{4})',
            'm' => '(?<month>\d{1,2})',
            'M' => '(?<month_str>[a-zA-Z]+)',
            'd' => '(?<day>\d{1,2})',
            'H' => '(?<hour>\d{1,2})',
            'i' => '(?<minute>\d{1,2})',
            's' => '(?<second>\d{1,2})',
            'u' => '(?<microsecond>\d{1,6})',
        ];

        $pattern = '';
        $escaped = false;

        for ($i = 0; $i < strlen($format); $i++) {
            $char = $format[$i];

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if (!$escaped && isset($tokens[$char])) {
                $pattern .= $tokens[$char];
            } else {
                $pattern .= preg_quote($char, '/');
            }

            $escaped = false;
        }

        if (!preg_match('/^' . $pattern . '$/i', $datetime, $matches)) {
            throw new InvalidDateException();
        }

        $month = 1;
        if (!empty($matches['month'])) {
            $month = (int)$matches['month'];
        } elseif (!empty($matches['month_str'])) {
            $month = (strtolower($matches['month_str']) === 'xtra') ? 13 : 0;

            if ($month === 0) {
                throw new InvalidDateException();
            }
        }

        if (!isset($matches['year'])) {
            throw new InvalidDateException();
        }

        return self::create(
            year: (int)$matches['year'],
            month: $month,
            day: (int)($matches['day'] ?? 1),
            hour: (int)($matches['hour'] ?? 0),
            minute: (int)($matches['minute'] ?? 0),
            second: (int)($matches['second'] ?? 0),
            microsecond: isset($matches['microsecond'])
                ? (int)str_pad($matches['microsecond'], 6, '0')
                : 0
        );
    }

    public static function parse(string $datetime): HhpcDateInterface
    {
        $normalized = strtolower(trim($datetime));

        switch ($normalized) {
            case 'now':
                return self::now();
            case 'today':
                return self::today();
            case 'yesterday':
                return self::yesterday();
            case 'tomorrow':
                return self::tomorrow();
        }

        try {
            return self::createFromFormat('Y-m-d H:i:s', $datetime);
        } catch (InvalidDateException) {
        }

        try {
            return self::createFromFormat('Y-m-d H:i:s.u', $datetime);
        } catch (InvalidDateException) {
        }

        try {
            return self::createFromFormat('Y-m-d', $datetime);
        } catch (InvalidDateException) {
        }

        try {
            return self::createFromFormat('Y-M-d', $datetime);
        } catch (InvalidDateException) {
        }

        throw new InvalidDateException('Unsupported format for Hanke-Henry calendar.');
    }

    public static function fromGregorian(DateTimeInterface $datetime): HhpcDateInterface
    {
        [$year, $month, $day] = HhpcConfig::getCalculator()
            ->fromGregorian($datetime);

        return self::create(
            year: $year,
            month: $month,
            day: $day,
            hour: (int)$datetime->format('H'),
            minute: (int)$datetime->format('i'),
            second: (int)$datetime->format('s'),
            microsecond: (int)$datetime->format('u'),
        );
    }

    public static function fromTimestamp(int $timestamp): HhpcDateInterface
    {
        return self::fromGregorian(new DateTimeImmutable("@$timestamp"));
    }

    public function isToday(): bool
    {
        $today = self::today();

        return $this->year === $today->getYear()
            && $this->month === $today->getMonth()
            && $this->day === $today->getDay();
    }

    public function isSameDay(HhpcDateInterface $date): bool
    {
        return $this->year === $date->getYear()
            && $this->month === $date->getMonth()
            && $this->day === $date->getDay();
    }

    public function setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0): HhpcDateInterface
    {
        return self::create(
            year: $this->year,
            month: $this->month,
            day: $this->day,
            hour: $hour,
            minute: $minute,
            second: $second,
            microsecond: $microsecond,
        );
    }

    public function __toString(): string
    {
        return sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d.%06d',
            $this->year,
            $this->month,
            $this->day,
            $this->hour,
            $this->minute,
            $this->second,
            $this->microsecond,
        );
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getDay(): int
    {
        return $this->day;
    }

    public function getHour(): int
    {
        return $this->hour;
    }

    public function getMinute(): int
    {
        return $this->minute;
    }

    public function getSecond(): int
    {
        return $this->second;
    }

    public function getMicrosecond(): int
    {
        return $this->microsecond;
    }

    public function getMillisecond(): int
    {
        return (int)($this->microsecond / 1000);
    }

    public function getWeek(): int
    {
        return $this->calculator->getWeekOfDate(month: $this->month, day: $this->day);
    }

    public function getDayOfWeek(): int
    {
        return $this->calculator->getDayOfWeek(month: $this->month, day: $this->day);
    }

    public function getDayOfYear(): int
    {
        return $this->calculator->getDayOfYear(month: $this->month, day: $this->day);
    }

    public function getQuarter(): int
    {
        return $this->calculator->getMonthsQuarter($this->month);
    }

    public function getDayOfQuarter(): int
    {
        return $this->calculator->getDayOfQuarter(month: $this->month, day: $this->day);
    }

    public function getDaysRemainingInQuarter(): int
    {
        return $this->calculator->getDaysRemainingInQuarter(year: $this->year, month: $this->month, day: $this->day);
    }

    public function getWeekObject(): HhpcWeekInterface
    {
        $week = $this->calculator->getWeekOfDate(month: $this->month, day: $this->day);

        return HhpcWeek::create(year: $this->year, week: $week);
    }

    public function getQuarterObject(): HhpcQuarterInterface
    {
        $quarter = $this->calculator->getMonthsQuarter($this->month);

        return HhpcQuarter::create(year: $this->year, quarter: $quarter);
    }

    public function getMonthObject(): HhpcMonthInterface
    {
        return HhpcMonth::create(year: $this->year, month: $this->month);
    }

    public function getYearObject(): HhpcYearInterface
    {
        return HhpcYear::create(year: $this->year);
    }

    public function isLeapYear(): bool
    {
        return $this->calculator->isLeapYear($this->year);
    }

    public function isXtra(): bool
    {
        return $this->month === HhpcCalculatorInterface::XTRA_MONTH;
    }

    public function addDays(int $days = 1): HhpcDateInterface
    {
        $dayOfYear = $this->calculator->getDayOfYear(month: $this->month, day: $this->day);
        [$year, $targetDayOfYear] = $this->calculator->addDays(year: $this->year, day: $dayOfYear, addDays: $days);
        [$month, $day] = $this->calculator->getDateFromDayOfYear($targetDayOfYear);

        return self::create(
            year: $year,
            month: $month,
            day: $day,
            hour: $this->hour,
            minute: $this->minute,
            second: $this->second,
            microsecond: $this->microsecond,
        );
    }

    public function subDays(int $days = 1): HhpcDateInterface
    {
        return $this->addDays(-$days);
    }

    public function addMonths(int $months): HhpcDateInterface
    {
        [$year, $month, $day] = $this->calculator->addMonthsToDate(
            year: $this->year,
            month: $this->month,
            day: $this->day,
            months: $months,
        );

        return self::create(
            year: $year,
            month: $month,
            day: $day,
            hour: $this->hour,
            minute: $this->minute,
            second: $this->second,
            microsecond: $this->microsecond,
        );
    }

    public function addMonthsNoOverflow(int $months): HhpcDateInterface
    {
        [$year, $month, $day] = $this->calculator->addMonthsToDate(
            year: $this->year,
            month: $this->month,
            day: $this->day,
            months: $months,
            overflow: false,
        );

        return self::create(
            year: $year,
            month: $month,
            day: $day,
            hour: $this->hour,
            minute: $this->minute,
            second: $this->second,
            microsecond: $this->microsecond,
        );
    }

    public function subMonths(int $months): HhpcDateInterface
    {
        return $this->addMonths(-$months);
    }

    public function subMonthsNoOverflow(int $months): HhpcDateInterface
    {
        return $this->addMonthsNoOverflow(-$months);
    }

    public function addQuarters(int $quarters): HhpcDateInterface
    {
        [$year, $month, $day] = $this->calculator->addQuartersToDate(
            year: $this->year,
            month: $this->month,
            day: $this->day,
            quarters: $quarters,
        );

        return self::create(
            year: $year,
            month: $month,
            day: $day,
            hour: $this->hour,
            minute: $this->minute,
            second: $this->second,
            microsecond: $this->microsecond,
        );
    }

    public function subQuarters(int $quarters): HhpcDateInterface
    {
        return $this->addQuarters(-$quarters);
    }

    public function addQuartersNoOverflow(int $quarters): HhpcDateInterface
    {
        [$year, $month, $day] = $this->calculator->addQuartersToDate(
            year: $this->year,
            month: $this->month,
            day: $this->day,
            quarters: $quarters,
            overflow: false,
        );

        return self::create(
            year: $year,
            month: $month,
            day: $day,
            hour: $this->hour,
            minute: $this->minute,
            second: $this->second,
            microsecond: $this->microsecond,
        );
    }

    public function subQuartersNoOverflow(int $quarters): HhpcDateInterface
    {
        return $this->addQuartersNoOverflow(-$quarters);
    }

    public function addYears(int $years): HhpcDateInterface
    {
        [$year, $month, $day] = $this->calculator->addYearsToDate(
            year: $this->year,
            month: $this->month,
            day: $this->day,
            years: $years,
        );

        return self::create(
            year: $year,
            month: $month,
            day: $day,
            hour: $this->hour,
            minute: $this->minute,
            second: $this->second,
            microsecond: $this->microsecond,
        );
    }

    public function subYears(int $years): HhpcDateInterface
    {
        return $this->addYears(-$years);
    }

    public function addYearsNoOverflow(int $years): HhpcDateInterface
    {
        [$year, $month, $day] = $this->calculator->addYearsToDate(
            year: $this->year,
            month: $this->month,
            day: $this->day,
            years: $years,
            overflow: false,
        );

        return self::create(
            year: $year,
            month: $month,
            day: $day,
            hour: $this->hour,
            minute: $this->minute,
            second: $this->second,
            microsecond: $this->microsecond,
        );
    }

    public function subYearsNoOverflow(int $years): HhpcDateInterface
    {
        return $this->addYearsNoOverflow(-$years);
    }

    public function diffInDays(HhpcDateInterface $date, bool $absolute = true): int
    {
        $thisDayOfYear = $this->calculator->getDayOfYear($this->month, $this->day);
        $otherDayOfYear = $this->calculator->getDayOfYear($date->getMonth(), $date->getDay());

        $diff = $this->calculator->diffDays($this->year, $thisDayOfYear, $date->getYear(), $otherDayOfYear);

        $progressCmp = [$this->hour, $this->minute, $this->second, $this->microsecond]
            <=> [$date->getHour(), $date->getMinute(), $date->getSecond(), $date->getMicrosecond()];

        $diff = $this->calculator->normalizeDiff($diff, $progressCmp);

        return $absolute ? abs($diff) : $diff;
    }

    public function diffInWeeks(HhpcDateInterface $date, bool $absolute = true): int
    {
        $weeksDiff = intdiv($this->diffInDays($date, false), HhpcCalculatorInterface::DAYS_IN_WEEK);

        return $absolute ? abs($weeksDiff) : $weeksDiff;
    }

    public function diffInMonths(HhpcDateInterface $date, bool $absolute = true): int
    {
        $diff = $this->calculator->diffMonths($this->year, $this->month, $date->getYear(), $date->getMonth());

        $progressCmp = [$this->day, $this->hour, $this->minute, $this->second, $this->microsecond]
            <=> [$date->getDay(), $date->getHour(), $date->getMinute(), $date->getSecond(), $date->getMicrosecond()];

        $diff = $this->calculator->normalizeDiff($diff, $progressCmp);

        return $absolute ? abs($diff) : $diff;
    }

    public function diffInYears(HhpcDateInterface $date, bool $absolute = true): int
    {
        $diff = $this->calculator->diffYears($this->year, $date->getYear());

        $progressCmp = [$this->month, $this->day, $this->hour, $this->minute, $this->second, $this->microsecond]
            <=> [$date->getMonth(), $date->getDay(), $date->getHour(), $date->getMinute(), $date->getSecond(), $date->getMicrosecond()];

        $diff = $this->calculator->normalizeDiff($diff, $progressCmp);

        return $absolute ? abs($diff) : $diff;
    }

    public function equals(HhpcDateInterface $other): bool
    {
        return $this->toTimestamp() === $other->toTimestamp()
            && $this->microsecond === $other->getMicrosecond();
    }

    public function isBefore(HhpcDateInterface $other): bool
    {
        if ($this->toTimestamp() === $other->toTimestamp()) {
            return $this->microsecond < $other->getMicrosecond();
        }

        return $this->toTimestamp() < $other->toTimestamp();
    }

    public function isAfter(HhpcDateInterface $other): bool
    {
        if ($this->toTimestamp() === $other->toTimestamp()) {
            return $this->microsecond > $other->getMicrosecond();
        }

        return $this->toTimestamp() > $other->toTimestamp();
    }

    public function startOfDay(): HhpcDateInterface
    {
        return $this->setTime(0, 0);
    }

    public function endOfDay(): HhpcDateInterface
    {
        return $this->setTime(23, 59, 59, 999999);
    }

    public function startOfMonth(): HhpcDateInterface
    {
        return self::create(year: $this->year, month: $this->month, day: 1);
    }

    public function endOfMonth(): HhpcDateInterface
    {
        return self::create(
            year: $this->year,
            month: $this->month,
            day: $this->calculator->getDaysInMonth(month: $this->month),
            hour: 23,
            minute: 59,
            second: 59,
            microsecond: 999999,
        );
    }

    public function startOfYear(): HhpcDateInterface
    {
        return self::create(year: $this->year, month: 1, day: 1);
    }

    public function endOfYear(): HhpcDateInterface
    {
        $lastMonth = $this->calculator->getMonthsInYear(year: $this->year);

        return self::create(
            year: $this->year,
            month: $lastMonth,
            day: $this->calculator->getDaysInMonth(month: $lastMonth),
            hour: 23,
            minute: 59,
            second: 59,
            microsecond: 999999,
        );
    }

    public function toTimestamp(): int
    {
        return $this->calculator->toTimestamp(
            year: $this->year,
            month: $this->month,
            day: $this->day,
            hour: $this->hour,
            minute: $this->minute,
            second: $this->second,
        );
    }

    public function toGregorian(): DateTimeImmutable
    {
        $timestamp = $this->toTimestamp();
        return new DateTimeImmutable("@$timestamp");
    }

    public function format(string $format): string
    {
        $result = '';
        $length = strlen($format);
        $escapeNext = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $format[$i];

            if ($escapeNext) {
                $result .= $char;
                $escapeNext = false;
                continue;
            }

            if ($char === '\\') {
                $escapeNext = true;
                continue;
            }

            $result .= $this->formatCharacter($char);
        }

        return $result;
    }

    private function formatCharacter(string $char): string
    {
        return match ($char) {
            'Y' => sprintf('%04d', $this->year),
            'y' => substr(sprintf('%04d', $this->year), -2),
            'L' => $this->isLeapYear() ? '1' : '0',
            'm' => sprintf('%02d', $this->month),
            'n' => (string) $this->month,
            't' => (string) $this->calculator->getDaysInMonth($this->month),
            'F' => $this->getMonthObject()->getName(),
            'M' => substr($this->getMonthObject()->getName(), 0, 3),
            'd' => sprintf('%02d', $this->day),
            'j' => (string) $this->day,
            'z' => (string) $this->getDayOfYear(),
            'W' => sprintf('%02d', $this->getWeek()),
            'H' => sprintf('%02d', $this->hour),
            'G' => (string) $this->hour,
            'h' => sprintf('%02d', ($this->hour % 12 ?: 12)),
            'g' => (string) ($this->hour % 12 ?: 12),
            'i' => sprintf('%02d', $this->minute),
            's' => sprintf('%02d', $this->second),
            'v' => sprintf('%03d', $this->getMillisecond()),
            'u' => sprintf('%06d', $this->microsecond),
            'a' => $this->hour < 12 ? 'am' : 'pm',
            'A' => $this->hour < 12 ? 'AM' : 'PM',
            'U' => (string)$this->toTimestamp(),

            default => $char,
        };
    }

    /**
     * @return array{
     * datetime: string,
     * timestamp: int,
     * year: int,
     * month: int,
     * day: int,
     * hour: int,
     * minute: int,
     * second: int,
     * microsecond: int,
     * millisecond: int,
     * dayOfWeek: int,
     * dayOfYear: int,
     * week: int,
     * quarter: int,
     * dayOfQuarter: int,
     * daysRemainingInQuarter: int,
     * isLeapYear: bool,
     * isXtra: bool
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'datetime' => $this->__toString(),
            'timestamp' => $this->toTimestamp(),
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'hour' => $this->hour,
            'minute' => $this->minute,
            'second' => $this->second,
            'microsecond' => $this->microsecond,
            'millisecond' => $this->getMillisecond(),
            'dayOfWeek' => $this->getDayOfWeek(),
            'dayOfYear' => $this->getDayOfYear(),
            'week' => $this->getWeek(),
            'quarter' => $this->getQuarter(),
            'dayOfQuarter' => $this->getDayOfQuarter(),
            'daysRemainingInQuarter' => $this->getDaysRemainingInQuarter(),
            'isLeapYear' => $this->isLeapYear(),
            'isXtra' => $this->isXtra(),
        ];
    }
}
