<?php

declare(strict_types=1);

namespace Adiafora\Hhpc\Contracts;

use JsonSerializable;
use Stringable;

interface HhpcMonthInterface extends JsonSerializable, Stringable
{
    public function isCurrent(): bool;

    public function next(): self;

    public function previous(): self;

    public function addMonths(int $months = 1): self;

    public function subMonths(int $months = 1): self;

    public function equals(HhpcMonthInterface $other): bool;

    public function isBefore(HhpcMonthInterface $other): bool;

    public function isAfter(HhpcMonthInterface $other): bool;

    public function getIndex(): int;

    public function getYear(): int;

    public function getQuarter(): int;

    public function getYearObject(): HhpcYearInterface;

    public function getQuarterObject(): HhpcQuarterInterface;

    public function getName(): string;

    public function isXtra(): bool;

    public function getDaysInMonth(): int;

    /** @return array<int, HhpcWeekInterface> */
    public function getWeeks(): array;

    /** @return array<int, HhpcDateInterface> */
    public function getDays(): array;

    public function getStartDate(): HhpcDateInterface;

    public function getEndDate(): HhpcDateInterface;

    public function diffMonths(HhpcMonthInterface $other): int;
}
