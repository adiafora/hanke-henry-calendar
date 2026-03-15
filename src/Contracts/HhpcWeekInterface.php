<?php

declare(strict_types=1);

namespace Adiafora\Hhpc\Contracts;

use JsonSerializable;
use Stringable;

interface HhpcWeekInterface extends JsonSerializable, Stringable
{
    public function isCurrent(): bool;

    public function getIndex(): int;

    public function next(): self;

    public function previous(): self;

    public function addWeeks(int $weeks = 1): self;

    public function subWeeks(int $weeks = 1): self;

    public function equals(HhpcWeekInterface $other): bool;

    public function isBefore(HhpcWeekInterface $other): bool;

    public function isAfter(HhpcWeekInterface $other): bool;

    public function getYear(): int;

    public function getQuarter(): int;

    public function getYearObject(): HhpcYearInterface;

    public function getQuarterObject(): HhpcQuarterInterface;

    /** @return array<int, HhpcDateInterface> */
    public function getDays(): array;

    public function isXtra(): bool;

    public function getStartDate(): HhpcDateInterface;

    public function getEndDate(): HhpcDateInterface;

    public function diffWeeks(HhpcWeekInterface $other): int;
}
