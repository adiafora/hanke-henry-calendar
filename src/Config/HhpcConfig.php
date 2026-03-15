<?php

declare(strict_types=1);

namespace Adiafora\Hhpc\Config;

use Adiafora\Hhpc\Calculator\HhpcCalculator;
use Adiafora\Hhpc\Contracts\HhpcCalculatorInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;

class HhpcConfig
{
    private static ?HhpcCalculatorInterface $calculator = null;
    private static ?DateTimeInterface $clock = null;

    public static function getCalculator(): HhpcCalculatorInterface
    {
        return self::$calculator ??= new HhpcCalculator();
    }

    /**
     * @throws Exception
     */
    public static function getGregorianNow(): DateTimeInterface
    {
        return self::$clock ??= new DateTimeImmutable(timezone: new DateTimeZone('UTC'));
    }

    /**
     * Sets a mock for the current server time.
     *
     * This method is intended for TESTING PURPOSES ONLY.
     * Do not use this in a production environment.
     *
     * @param string|DateTimeInterface|null $testNow
     * @throws Exception
     * @internal
     */
    public static function setTestGregorianNow(null|string|DateTimeInterface $testNow = null): void
    {
        self::$clock = is_string($testNow)
            ? new DateTimeImmutable($testNow)
            : $testNow;
    }
}
