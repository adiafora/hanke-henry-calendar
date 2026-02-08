<?php

declare(strict_types=1);

namespace Hhpc\Strategy;

use DateTimeImmutable;
use DateTimeZone;

class HhpcCalculator implements HhpcCalculatorInterface
{
    /**
     * @inheritDoc
     */
    public function isLeapYear(int $year): bool
    {
        $date = new DateTimeImmutable($year . '-12-28', new DateTimeZone('UTC'));

        return (int)$date->format('W') === self::WEEKS_IN_LONG_YEAR;
    }

    /**
     * @inheritDoc
     */
    public function getDaysInYear(int $year): int
    {
        return $this->isLeapYear($year) ? self::DAYS_IN_LONG_YEAR : self::DAYS_IN_SHORT_YEAR;
    }

    /**
     * @inheritDoc
     */
    public function getWeeksInYear(int $year): int
    {
        return $this->isLeapYear($year) ? self::WEEKS_IN_LONG_YEAR : self::WEEKS_IN_SHORT_YEAR;
    }

    /**
     * @inheritDoc
     */
    public function getDaysFromEpoch(int $year): int
    {
        $dto = new DateTimeImmutable(sprintf('%04dW011', $year), new DateTimeZone('UTC'));

        return (int)($dto->getTimestamp() / self::SECONDS_IN_DAY);
    }
}
