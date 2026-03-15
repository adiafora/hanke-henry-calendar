<?php

declare(strict_types=1);

namespace Adiafora\Hhpc;

use Adiafora\Hhpc\Config\HhpcConfig;
use Adiafora\Hhpc\Contracts\HhpcCalculatorInterface;
use Adiafora\Hhpc\Contracts\HhpcDateInterface;
use Adiafora\Hhpc\Contracts\HhpcQuarterInterface;
use Adiafora\Hhpc\Contracts\HhpcYearInterface;
use Adiafora\Hhpc\Exception\InvalidDateException;

readonly class HhpcQuarter implements HhpcQuarterInterface
{
    private HhpcCalculatorInterface $calculator;

    private function __construct(
        private int $year,
        private int $quarter
    ) {
        self::validate($year, $quarter);

        $this->calculator = HhpcConfig::getCalculator();
    }

    public static function validate(int $year, int $quarter): void
    {
        HhpcYear::validate($year);

        if (!self::isValid($year, $quarter)) {
            throw new InvalidDateException('Invalid quarter');
        }
    }

    public static function isValid(int $year, int $quarter): bool
    {
        if (!HhpcYear::isValid($year)) {
            return false;
        }

        return $quarter >= 1 && $quarter <= HhpcConfig::getCalculator()->getQuartersInYear();
    }

    public static function create(int $year, int $quarter): self
    {
        return new self($year, $quarter);
    }

    public static function current(): HhpcQuarterInterface
    {
        $calculator = HhpcConfig::getCalculator();

        [$year, $month] = $calculator
            ->fromGregorian(HhpcConfig::getGregorianNow());

        $quarter = $calculator->getMonthsQuarter($month);

        return self::create($year, $quarter);
    }

    public function isCurrent(): bool
    {
        return $this->equals(self::current());
    }

    public function getIndex(): int
    {
        return $this->quarter;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getYearObject(): HhpcYearInterface
    {
        return HhpcYear::create($this->year);
    }

    public function getMonths(): array
    {
        $monthsIndices = $this->calculator->getMonthsInQuarter(year: $this->year, quarter: $this->quarter);

        return array_map(
            fn (int $month) => HhpcMonth::create(year: $this->year, month: $month),
            $monthsIndices
        );
    }

    public function getDaysCount(): int
    {
        return $this->calculator->getDaysInQuarter($this->year, $this->quarter);
    }

    public function getStartDate(): HhpcDateInterface
    {
        $boundaries = $this->calculator->getQuarterBoundaries($this->year, $this->quarter);

        return HhpcDate::create(
            year: $this->year,
            month: $boundaries['start']['month'],
            day: $boundaries['start']['day']
        );
    }

    public function getEndDate(): HhpcDateInterface
    {
        $boundaries = $this->calculator->getQuarterBoundaries($this->year, $this->quarter);

        return HhpcDate::create(
            year: $this->year,
            month: $boundaries['end']['month'],
            day: $boundaries['end']['day']
        );
    }

    public function next(): HhpcQuarterInterface
    {
        return $this->addQuarters();
    }

    public function previous(): HhpcQuarterInterface
    {
        return $this->subQuarters();
    }

    public function addQuarters(int $quarters = 1): HhpcQuarterInterface
    {
        [$year, $quarter] = $this->calculator->addQuarters($this->year, $this->quarter, $quarters);

        return self::create($year, $quarter);
    }

    public function subQuarters(int $quarters = 1): HhpcQuarterInterface
    {
        return $this->addQuarters(-$quarters);
    }

    public function equals(HhpcQuarterInterface $other): bool
    {
        return $this->year === $other->getYear()
            && $this->quarter === $other->getIndex();
    }

    public function isBefore(HhpcQuarterInterface $other): bool
    {
        if ($this->year !== $other->getYear()) {
            return $this->year < $other->getYear();
        }

        return $this->quarter < $other->getIndex();
    }

    public function isAfter(HhpcQuarterInterface $other): bool
    {
        if ($this->year !== $other->getYear()) {
            return $this->year > $other->getYear();
        }

        return $this->quarter > $other->getIndex();
    }

    public function diffQuarters(HhpcQuarterInterface $other): int
    {
        return $this->calculator->diffQuarters(
            $this->year,
            $this->quarter,
            $other->getYear(),
            $other->getIndex()
        );
    }

    public function isLong(): bool
    {
        return $this->calculator->isLongQuarter($this->year, $this->quarter);
    }

    public function __toString(): string
    {
        return sprintf('%04d-Q%d', $this->year, $this->quarter);
    }

    /**
     * @return array{
     * year: int,
     * quarter: int,
     * daysCount: int,
     * isLong: bool,
     * isCurrent: bool,
     * startDate: string,
     * endDate: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'year' => $this->year,
            'quarter' => $this->quarter,
            'daysCount' => $this->getDaysCount(),
            'isLong' => $this->isLong(),
            'isCurrent' => $this->isCurrent(),
            'startDate' => $this->getStartDate()->format('Y-m-d'),
            'endDate' => $this->getEndDate()->format('Y-m-d'),
        ];
    }
}
