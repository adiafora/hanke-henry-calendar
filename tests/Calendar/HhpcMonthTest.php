<?php

declare(strict_types=1);

namespace Tests\Hhpc\Calendar;

use Adiafora\Hhpc\Config\HhpcConfig;
use Adiafora\Hhpc\Exception\InvalidDateException;
use Adiafora\Hhpc\HhpcDate;
use Adiafora\Hhpc\HhpcMonth;
use Exception;
use PHPUnit\Framework\TestCase;

class HhpcMonthTest extends TestCase
{
    public function testIsCurrent(): void
    {
        HhpcConfig::setTestGregorianNow('2027-01-02');
        $month = HhpcMonth::create(2027, 10);
        $this->assertFalse($month->isCurrent());

        HhpcConfig::setTestGregorianNow('2027-10-25');
        $this->assertTrue($month->isCurrent());

        HhpcConfig::setTestGregorianNow();
    }

    public function testCreateAndGetters(): void
    {
        $month = HhpcMonth::create(2026, 1);

        $this->assertSame(2026, $month->getYear());
        $this->assertSame(1, $month->getIndex());
        $this->assertSame('January', $month->getName());
        $this->assertSame(1, $month->getQuarter());
        $this->assertSame(30, $month->getDaysInMonth());
        $this->assertFalse($month->isXtra());
        $this->assertSame('2026-01', (string)$month);
    }

    /**
     * @throws Exception
     */
    public function testCurrent(): void
    {
        // It's Saturday Xtra 6 2026
        HhpcConfig::setTestGregorianNow('2027-01-02');

        $month = HhpcMonth::current();

        $this->assertSame(2026, $month->getYear());
        $this->assertSame(13, $month->getIndex());
        $this->assertSame('Xtra', $month->getName());
        $this->assertSame(4, $month->getQuarter());
        $this->assertSame(7, $month->getDaysInMonth());
        $this->assertTrue($month->isXtra());
        $this->assertSame('2026-13', (string)$month);

        HhpcConfig::setTestGregorianNow();
    }

    public function testXtraMonthName(): void
    {
        $month = HhpcMonth::create(2026, 13);

        $this->assertSame('Xtra', $month->getName());
        $this->assertTrue($month->isXtra());
    }

    public function testValidationThrowsExceptionForInvalidMonth(): void
    {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('Invalid month');

        HhpcMonth::create(2025, 13);
    }

    public function testNavigationImmutability(): void
    {
        $december = HhpcMonth::create(2025, 12);
        $january = $december->next();

        $this->assertNotSame($december, $january);
        $this->assertSame(1, $january->getIndex());
        $this->assertSame(2026, $january->getYear());

        $february = $december->addMonths(2);
        $this->assertNotSame($december, $february);
        $this->assertSame(2, $february->getIndex());
        $this->assertSame(2026, $february->getYear());
    }

    public function testPreviousDelegatesToSubMonths(): void
    {
        $january = HhpcMonth::create(2025, 1);
        $december = $january->previous();

        $this->assertNotSame($december, $january);
        $this->assertSame(12, $december->getIndex());
        $this->assertSame(2024, $december->getYear());

        $september = $january->subMonths(4);
        $this->assertNotSame($september, $january);
        $this->assertSame(9, $september->getIndex());
        $this->assertSame(2024, $september->getYear());
    }

    public function testComparisonMethods(): void
    {
        $m1 = HhpcMonth::create(2025, 5);
        $m2 = HhpcMonth::create(2025, 5);
        $m3 = HhpcMonth::create(2025, 6);
        $m4 = HhpcMonth::create(2026, 1);

        // Equals
        $this->assertTrue($m1->equals($m2));
        $this->assertFalse($m1->equals($m3));

        // IsBefore
        $this->assertTrue($m1->isBefore($m3));
        $this->assertTrue($m3->isBefore($m4));
        $this->assertFalse($m3->isBefore($m1));

        // IsAfter
        $this->assertTrue($m3->isAfter($m1));
        $this->assertFalse($m1->isAfter($m3));
    }

    public function testGetDaysReturnsArrayOfObjects(): void
    {
        $month = HhpcMonth::create(2025, 1);
        $days = $month->getDays();

        $this->assertCount(30, $days);
        $this->assertInstanceOf(HhpcDate::class, $days[0]);
    }

    public function testJsonSerializeStructure(): void
    {
        HhpcConfig::setTestGregorianNow('2026-12-02');

        $month = HhpcMonth::create(2025, 1);
        $json = $month->jsonSerialize();

        $expected = [
            'year' => 2025,
            'month' => 1,
            'quarter' => 1,
            'daysCount' => 30,
            'name' => 'January',
            'isCurrent' => false,
            'startDate' => '2025-01-01',
            'endDate' => '2025-01-30',
        ];

        $this->assertSame($expected, $json);
    }
}
