<?php

declare(strict_types=1);

namespace Adiafora\Hhpc\Contracts;

use JsonSerializable;
use Stringable;

interface HhpcYearInterface extends JsonSerializable, Stringable
{
    public const MIN_SUPPORTED_YEAR = 1583;
    public const MAX_SUPPORTED_YEAR = 9999;

    public function isCurrent(): bool;

    public function next(): self;

    public function previous(): self;

    public function addYears(int $years = 1): self;

    public function subYears(int $years = 1): self;

    public function equals(HhpcYearInterface $other): bool;

    public function isBefore(HhpcYearInterface $other): bool;

    public function isAfter(HhpcYearInterface $other): bool;

    public function getIndex(): int;

    public function isLeap(): bool;

    /** @return array<int, HhpcQuarterInterface> */
    public function getQuarters(): array;

    /** @return array<int, HhpcMonthInterface> */
    public function getMonths(): array;

    /** @return array<int, HhpcWeekInterface> */
    public function getWeeks(): array;

    public function getMonthsInYear(): int;

    public function getWeeksInYear(): int;

    public function getDaysInYear(): int;

    public function getStartDate(): HhpcDateInterface;

    public function getEndDate(): HhpcDateInterface;

    public function diffYears(HhpcYearInterface $other): int;
}
