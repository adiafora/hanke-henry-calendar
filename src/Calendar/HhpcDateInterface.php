<?php

declare(strict_types=1);

namespace Hhpc\Calendar;

use DateTimeImmutable;
use JsonSerializable;
use Stringable;

interface HhpcDateInterface extends JsonSerializable, Stringable
{
    public const QUARTER_MONTHS = [30, 30, 31];
    public const XTRA_MONTH_INDEX = 13;
    public const XTRA_DAYS = 7;

    public static function now(): self;

    public static function today(): self;

    public static function yesterday(): self;

    public static function tomorrow(): self;

    public static function createFromFormat(string $format, string $datetime): self|false;

    public static function parse(?string $time = null): self;

    public static function create(
        int $year,
        int $month,
        int $day,
        int $hour = 0,
        int $minute = 0,
        int $second = 0
    ): self;

    // Getters
    public function getYear(): int;

    public function getMonth(): int; // 1-13

    public function getDay(): int;

    public function getDayOfWeek(): int; // Всегда 1 (Пн) - 7 (Вс)

    public function getDayOfYear(): int; // 1-364 (или 371)

    public function getQuarter(): int;

    public function addQuarters(int $quarters): self;

    public function subQuarters(int $quarters): self;

    public function getDayOfQuarter(): int;

    // Inspection
    public function isValid(int $year, int $month, int $day): bool;

    public function isLeapYear(): bool;

    public function isXtraWeek(): bool;

    // Arithmetic (returns a new instance)
    public function addDays(int $days): self;

    public function addMonths(int $months): self;

    public function addMonthNoOverflow(int $months): self;

    public function addYears(int $years): self;

    public function subDays(int $days): self;

    public function subMonths(int $months): self;

    public function subMonthsNoOverflow(int $months): self;

    public function subYears(int $years): self;

    // Calculations
    public function diffInDays(HhpcDateInterface $date): int;

    public function diffInWeeks(HhpcDateInterface $date): int;

    // Comparisons
    public function equals(HhpcDateInterface $date): bool;

    public function isBefore(HhpcDateInterface $date): bool;

    public function isAfter(HhpcDateInterface $date): bool;

    // Boundaries
    public function startOfDay(): self;

    public function endOfDay(): self;

    public function startOfMonth(): self;

    public function endOfMonth(): self;

    public function startOfYear(): self;

    public function endOfYear(): self;

    // Interoperability
    public function toTimestamp(): int;

    public function toGregorian(): DateTimeImmutable;

    public static function fromGregorian(DateTimeImmutable $datetime): self;

    public static function fromTimestamp(int $timestamp): self;

    // Formatting
    public function format(string $format): string; // Свой аналог date()
}
