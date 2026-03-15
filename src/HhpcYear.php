<?php

declare(strict_types=1);

namespace Adiafora\Hhpc;

use Adiafora\Hhpc\Config\HhpcConfig;
use Adiafora\Hhpc\Contracts\HhpcCalculatorInterface;
use Adiafora\Hhpc\Contracts\HhpcDateInterface;
use Adiafora\Hhpc\Contracts\HhpcYearInterface;
use Adiafora\Hhpc\Exception\InvalidDateException;
use Exception;

final readonly class HhpcYear implements HhpcYearInterface
{
    private HhpcCalculatorInterface $calculator;

    private function __construct(
        private int $year,
    ) {
        self::validate($year);

        $this->calculator = HhpcConfig::getCalculator();
    }

    public static function create(int $year): self
    {
        return new self($year);
    }

    /**
     * @throws Exception
     */
    public static function current(): self
    {
        [$year] = HhpcConfig::getCalculator()
            ->fromGregorian(HhpcConfig::getGregorianNow());

        return new self($year);
    }

    public static function validate(int $year): void
    {
        if (!self::isValid($year)) {
            throw new InvalidDateException(sprintf(
                'The year must be between %d and %d',
                self::MIN_SUPPORTED_YEAR,
                self::MAX_SUPPORTED_YEAR,
            ));
        }
    }

    public static function isValid(int $year): bool
    {
        return $year >= self::MIN_SUPPORTED_YEAR
            && $year <= self::MAX_SUPPORTED_YEAR;
    }

    public function isCurrent(): bool
    {
        return $this->equals(self::current());
    }

    public function next(): HhpcYearInterface
    {
        return $this->addYears();
    }

    public function previous(): HhpcYearInterface
    {
        return $this->subYears();
    }

    public function addYears(int $years = 1): HhpcYearInterface
    {
        return self::create($this->year + $years);
    }

    public function subYears(int $years = 1): HhpcYearInterface
    {
        return self::create($this->year - $years);
    }

    public function equals(HhpcYearInterface $other): bool
    {
        return $this->year === $other->getIndex();
    }

    public function isBefore(HhpcYearInterface $other): bool
    {
        return $this->year < $other->getIndex();
    }

    public function isAfter(HhpcYearInterface $other): bool
    {
        return $this->year > $other->getIndex();
    }

    public function __toString(): string
    {
        return sprintf('%04s', $this->year);
    }

    public function getIndex(): int
    {
        return $this->year;
    }

    public function isLeap(): bool
    {
        return $this->calculator->isLeapYear($this->year);
    }

    public function getQuarters(): array
    {
        $quarters = [];

        for ($quarter = 1; $quarter <= $this->calculator->getQuartersInYear(); $quarter++) {
            $quarters[] = HhpcQuarter::create(year: $this->year, quarter: $quarter);
        }

        return $quarters;
    }

    public function getMonths(): array
    {
        $months = [];

        for ($month = 1; $month <= $this->calculator->getMonthsInYear($this->year); $month++) {
            $months[] = HhpcMonth::create(year: $this->year, month: $month);
        }

        return $months;
    }

    public function getMonthsInYear(): int
    {
        return $this->calculator->getMonthsInYear($this->year);
    }

    public function getWeeks(): array
    {
        $weeks = [];

        for ($week = 1; $week <= $this->calculator->getWeeksInYear($this->year); $week++) {
            $weeks[] = HhpcWeek::create(year: $this->year, week: $week);
        }

        return $weeks;
    }

    public function getWeeksInYear(): int
    {
        return $this->calculator->getWeeksInYear($this->year);
    }

    public function getDaysInYear(): int
    {
        return $this->calculator->getDaysInYear($this->year);
    }

    public function getStartDate(): HhpcDateInterface
    {
        return HhpcDate::create(year: $this->year, month: 1, day: 1);
    }

    public function getEndDate(): HhpcDateInterface
    {
        $endMonth = $this->calculator->getMonthsInYear($this->year);

        return HhpcDate::create(
            year: $this->year,
            month: $endMonth,
            day: $this->calculator->getDaysInMonth($endMonth),
        );
    }

    public function diffYears(HhpcYearInterface $other): int
    {
        return $this->calculator->diffYears(fromYear: $this->year, toYear: $other->getIndex());
    }

    /**
     * @return array{
     * year: int,
     * daysCount: int,
     * weeksCount: int,
     * monthsCount: int,
     * startDate: string,
     * endDate: string,
     * isCurrent: bool,
     * isLeap: bool
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'year' => $this->year,
            'daysCount' => $this->getDaysInYear(),
            'weeksCount' => $this->getWeeksInYear(),
            'monthsCount' => $this->getMonthsInYear(),
            'startDate' => $this->getStartDate()->format('Y-m-d'),
            'endDate' => $this->getEndDate()->format('Y-m-d'),
            'isLeap' => $this->isLeap(),
            'isCurrent' => $this->isCurrent(),
        ];
    }
}
