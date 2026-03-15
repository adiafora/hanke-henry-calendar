<?php

declare(strict_types=1);

namespace Adiafora\Hhpc;

use Adiafora\Hhpc\Config\HhpcConfig;
use Adiafora\Hhpc\Contracts\HhpcCalculatorInterface;
use Adiafora\Hhpc\Contracts\HhpcDateInterface;
use Adiafora\Hhpc\Contracts\HhpcQuarterInterface;
use Adiafora\Hhpc\Contracts\HhpcWeekInterface;
use Adiafora\Hhpc\Contracts\HhpcYearInterface;
use Adiafora\Hhpc\Exception\InvalidDateException;
use Exception;

final readonly class HhpcWeek implements HhpcWeekInterface
{
    private HhpcCalculatorInterface $calculator;

    private function __construct(
        private int $year,
        private int $week
    ) {
        self::validate($year, $week);

        $this->calculator = HhpcConfig::getCalculator();
    }

    public static function validate(int $year, int $week): void
    {
        HhpcYear::validate($year);

        if (!self::isValid($year, $week)) {
            throw new InvalidDateException('Invalid week');
        }
    }

    public static function isValid(int $year, int $week): bool
    {
        if (!HhpcYear::isValid($year)) {
            return false;
        }

        return $week <= HhpcConfig::getCalculator()->getWeeksInYear($year);
    }

    public static function create(int $year, int $week): HhpcWeekInterface
    {
        return new self($year, $week);
    }

    /**
     * @throws Exception
     */
    public static function current(): HhpcWeekInterface
    {
        $calculator = HhpcConfig::getCalculator();

        [$year, $month, $day] = $calculator
            ->fromGregorian(HhpcConfig::getGregorianNow());

        $weekNumber = $calculator->getWeekOfDate($month, $day);

        return self::create($year, $weekNumber);
    }

    public function isCurrent(): bool
    {
        return $this->equals(self::current());
    }

    public function next(): HhpcWeekInterface
    {
        return $this->addWeeks();
    }

    public function previous(): HhpcWeekInterface
    {
        return $this->subWeeks();
    }

    public function addWeeks(int $weeks = 1): HhpcWeekInterface
    {
        [$year, $week] = $this->calculator->addWeeks(year: $this->year, week: $this->week, addWeeks: $weeks);

        return self::create($year, $week);
    }

    public function subWeeks(int $weeks = 1): HhpcWeekInterface
    {
        return $this->addWeeks(-$weeks);
    }

    public function equals(HhpcWeekInterface $other): bool
    {
        return $this->year === $other->getYear()
            && $this->week === $other->getIndex();
    }

    public function isBefore(HhpcWeekInterface $other): bool
    {
        if ($this->year !== $other->getYear()) {
            return $this->year < $other->getYear();
        }

        return $this->week < $other->getIndex();
    }

    public function isAfter(HhpcWeekInterface $other): bool
    {
        if ($this->year !== $other->getYear()) {
            return $this->year > $other->getYear();
        }

        return $this->week > $other->getIndex();
    }

    public function getQuarterObject(): HhpcQuarterInterface
    {
        return HhpcQuarter::create($this->year, $this->calculator->getQuarterOfWeek($this->week));
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getQuarter(): int
    {
        return $this->calculator->getQuarterOfWeek($this->week);
    }

    public function getIndex(): int
    {
        return $this->week;
    }

    public function getYearObject(): HhpcYearInterface
    {
        return HhpcYear::create($this->year);
    }

    public function getDays(): array
    {
        $daysData = $this->calculator->getDaysInWeek($this->week);

        return array_map(
            fn (array $date) => HhpcDate::create(year: $this->year, month: $date['month'], day: $date['day']),
            $daysData,
        );
    }

    public function isXtra(): bool
    {
        return $this->week === HhpcCalculatorInterface::WEEKS_IN_LONG_YEAR;
    }

    public function getStartDate(): HhpcDateInterface
    {
        $boundaries = $this->calculator->getWeekBoundaries($this->week);

        return HhpcDate::create(
            year: $this->year,
            month: $boundaries['start']['month'],
            day: $boundaries['start']['day']
        );
    }

    public function getEndDate(): HhpcDateInterface
    {
        $boundaries = $this->calculator->getWeekBoundaries($this->week);

        return HhpcDate::create(
            year: $this->year,
            month: $boundaries['end']['month'],
            day: $boundaries['end']['day']
        );
    }

    public function diffWeeks(HhpcWeekInterface $other): int
    {
        return $this->calculator->diffWeeks(
            fromYear: $this->year,
            fromWeek: $this->week,
            toYear: $other->getYear(),
            toWeek: $other->getIndex()
        );
    }

    public function __toString(): string
    {
        return sprintf('%04d-W%02d', $this->year, $this->week);
    }

    /**
     * @return array{
     * year: int,
     * week: int,
     * isXtra: bool,
     * isCurrent: bool,
     * startDate: string,
     * endDate: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'year' => $this->year,
            'week' => $this->week,
            'isXtra' => $this->isXtra(),
            'isCurrent' => $this->isCurrent(),
            'startDate' => $this->getStartDate()->format('Y-m-d'),
            'endDate' => $this->getEndDate()->format('Y-m-d'),
        ];
    }
}
