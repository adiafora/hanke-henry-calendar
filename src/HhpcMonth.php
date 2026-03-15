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
use Exception;

final readonly class HhpcMonth implements HhpcMonthInterface
{
    private HhpcCalculatorInterface $calculator;

    private function __construct(
        private int $year,
        private int $month,
    ) {
        self::validate($year, $month);

        $this->calculator = HhpcConfig::getCalculator();
    }

    public static function validate(int $year, int $month): void
    {
        HhpcYear::validate($year);

        if (!self::isValid($year, $month)) {
            throw new InvalidDateException('Invalid month');
        }
    }

    public static function isValid(int $year, int $month): bool
    {
        if (!HhpcYear::isValid($year)) {
            return false;
        }

        if ($month < 1 || $month > HhpcCalculatorInterface::MONTHS_IN_LONG_YEAR) {
            return false;
        }

        return $month <= HhpcConfig::getCalculator()->getMonthsInYear($year);
    }

    public static function create(int $year, int $month): self
    {
        return new self($year, $month);
    }

    /**
     * @throws Exception
     */
    public static function current(): self
    {
        [$year, $month] = HhpcConfig::getCalculator()
            ->fromGregorian(HhpcConfig::getGregorianNow());

        return new self($year, $month);
    }

    public function isCurrent(): bool
    {
        return $this->equals(self::current());
    }

    public function next(): self
    {
        return $this->addMonths();
    }

    public function previous(): self
    {
        return $this->subMonths();
    }

    public function addMonths(int $months = 1): self
    {
        if ($months === 0) {
            return $this;
        }

        [$year, $month] = $this->calculator->addMonths(year: $this->year, month: $this->month, addMonths: $months);

        return self::create($year, $month);
    }

    public function subMonths(int $months = 1): self
    {
        return $this->addMonths(-$months);
    }

    public function equals(HhpcMonthInterface $other): bool
    {
        return $this->year === $other->getYear()
            && $this->month === $other->getIndex();
    }

    public function isBefore(HhpcMonthInterface $other): bool
    {
        if ($this->year !== $other->getYear()) {
            return $this->year < $other->getYear();
        }

        return $this->month < $other->getIndex();
    }

    public function isAfter(HhpcMonthInterface $other): bool
    {
        return !$this->isBefore($other) && !$this->equals($other);
    }

    public function getIndex(): int
    {
        return $this->month;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getQuarter(): int
    {
        return $this->calculator->getMonthsQuarter($this->month);
    }

    public function getYearObject(): HhpcYearInterface
    {
        return HhpcYear::create($this->year);
    }

    public function getQuarterObject(): HhpcQuarterInterface
    {
        return HhpcQuarter::create(year: $this->year, quarter: $this->getQuarter());
    }

    public function getName(): string
    {
        return match ($this->month) {
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
            HhpcCalculatorInterface::MONTHS_IN_LONG_YEAR => 'Xtra',
            default => throw new \InvalidArgumentException('Invalid month index: ' . $this->month),
        };
    }

    public function isXtra(): bool
    {
        return $this->month === HhpcCalculatorInterface::MONTHS_IN_LONG_YEAR;
    }

    public function getDaysInMonth(): int
    {
        return $this->calculator->getDaysInMonth($this->month);
    }

    /** @return array<int, HhpcWeekInterface> */
    public function getWeeks(): array
    {
        [$startWeek, $endWeek] = $this->calculator->getWeekRangeForMonth($this->month);

        $weeks = [];

        for ($week = $startWeek; $week <= $endWeek; $week++) {
            $weeks[] = HhpcWeek::create(year: $this->year, week: $week);
        }

        return $weeks;
    }

    public function getDays(): array
    {
        $length = $this->getDaysInMonth();

        $days = [];

        for ($day = 1; $day <= $length; $day++) {
            $days[] = HhpcDate::create(year: $this->year, month: $this->month, day: $day);
        }

        return $days;
    }

    public function getStartDate(): HhpcDateInterface
    {
        return HhpcDate::create(year: $this->year, month: $this->month, day: 1);
    }

    public function getEndDate(): HhpcDateInterface
    {
        return HhpcDate::create(year:$this->year, month:$this->month, day:$this->getDaysInMonth());
    }

    public function diffMonths(HhpcMonthInterface $other): int
    {
        return $this->calculator->diffMonths(
            fromYear: $this->year,
            fromMonth: $this->month,
            toYear: $other->getYear(),
            toMonth: $other->getIndex()
        );
    }

    public function __toString(): string
    {
        return sprintf('%04s-%02s', $this->year, $this->month);
    }

    /**
     * @return array{
     * year: int,
     * month: int,
     * quarter: int,
     * daysCount: int,
     * name: string,
     * isCurrent: bool,
     * startDate: string,
     * endDate: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'quarter' => $this->getQuarter(),
            'daysCount' => $this->getDaysInMonth(),
            'name' => $this->getName(),
            'isCurrent' => $this->isCurrent(),
            'startDate' => $this->getStartDate()->format('Y-m-d'),
            'endDate' => $this->getEndDate()->format('Y-m-d'),
        ];
    }
}
