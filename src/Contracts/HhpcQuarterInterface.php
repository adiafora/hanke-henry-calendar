<?php

declare(strict_types=1);

namespace Adiafora\Hhpc\Contracts;

use JsonSerializable;
use Stringable;

interface HhpcQuarterInterface extends JsonSerializable, Stringable
{
    public function isCurrent(): bool;

    public function getIndex(): int;

    public function getYear(): int;

    public function getYearObject(): HhpcYearInterface;

    /** @return array<int, HhpcMonthInterface> */
    public function getMonths(): array;

    public function getDaysCount(): int; // 91 (or 98 for Q4 in leap year)

    public function getStartDate(): HhpcDateInterface;

    public function getEndDate(): HhpcDateInterface;

    public function next(): self;

    public function previous(): self;

    public function addQuarters(int $quarters = 1): self;

    public function subQuarters(int $quarters = 1): self;

    public function equals(HhpcQuarterInterface $other): bool;

    public function isBefore(HhpcQuarterInterface $other): bool;

    public function isAfter(HhpcQuarterInterface $other): bool;

    public function diffQuarters(HhpcQuarterInterface $other): int;

    public function isLong(): bool;
}
