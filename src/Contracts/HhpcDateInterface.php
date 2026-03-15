<?php

declare(strict_types=1);

namespace Adiafora\Hhpc\Contracts;

use DateTimeImmutable;
use JsonSerializable;
use Stringable;

interface HhpcDateInterface extends JsonSerializable, Stringable
{
    public function isToday(): bool;

    public function isSameDay(HhpcDateInterface $date): bool;

    public function setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0): self;

    public function getYear(): int;

    public function getMonth(): int;

    public function getDay(): int;

    public function getHour(): int;

    public function getMinute(): int;

    public function getSecond(): int;

    public function getMicrosecond(): int;

    public function getMillisecond(): int;

    public function getWeek(): int;

    public function getDayOfWeek(): int;

    public function getDayOfYear(): int;

    public function getQuarter(): int;

    public function getDayOfQuarter(): int;

    public function getDaysRemainingInQuarter(): int;

    public function getWeekObject(): HhpcWeekInterface;

    public function getQuarterObject(): HhpcQuarterInterface;

    public function getMonthObject(): HhpcMonthInterface;

    public function getYearObject(): HhpcYearInterface;

    public function isLeapYear(): bool;

    public function isXtra(): bool;

    public function addDays(int $days = 1): self;

    public function subDays(int $days = 1): self;

    public function addMonths(int $months): self;

    public function addMonthsNoOverflow(int $months): self;

    public function subMonths(int $months): self;

    public function subMonthsNoOverflow(int $months): self;

    public function addQuarters(int $quarters): self;

    public function subQuarters(int $quarters): self;

    public function addQuartersNoOverflow(int $quarters): self;

    public function subQuartersNoOverflow(int $quarters): self;

    public function addYears(int $years): self;

    public function subYears(int $years): self;

    public function addYearsNoOverflow(int $years): self;

    public function subYearsNoOverflow(int $years): self;

    public function diffInDays(HhpcDateInterface $date, bool $absolute = true): int;

    public function diffInWeeks(HhpcDateInterface $date, bool $absolute = true): int;

    public function diffInMonths(HhpcDateInterface $date, bool $absolute = true): int;

    public function diffInYears(HhpcDateInterface $date, bool $absolute = true): int;

    public function equals(HhpcDateInterface $other): bool;

    public function isBefore(HhpcDateInterface $other): bool;

    public function isAfter(HhpcDateInterface $other): bool;

    public function startOfDay(): self;

    public function endOfDay(): self;

    public function startOfMonth(): self;

    public function endOfMonth(): self;

    public function startOfYear(): self;

    public function endOfYear(): self;

    public function toTimestamp(): int;

    public function toGregorian(): DateTimeImmutable;

    public function format(string $format): string;
}
