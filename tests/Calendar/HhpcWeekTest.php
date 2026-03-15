<?php

declare(strict_types=1);

namespace Tests\Hhpc\Calendar;

use Adiafora\Hhpc\Config\HhpcConfig;
use Adiafora\Hhpc\Contracts\HhpcDateInterface;
use Adiafora\Hhpc\Exception\InvalidDateException;
use Adiafora\Hhpc\HhpcWeek;
use PHPUnit\Framework\TestCase;

class HhpcWeekTest extends TestCase
{
    public function testIsCurrent(): void
    {
        HhpcConfig::setTestGregorianNow('2026-01-01');
        $week = HhpcWeek::create(2026, 2);
        $this->assertFalse($week->isCurrent());

        HhpcConfig::setTestGregorianNow('2026-01-06');
        $this->assertTrue($week->isCurrent());

        HhpcConfig::setTestGregorianNow();
    }

    public function testCreateAndGetters(): void
    {
        $week = HhpcWeek::create(2025, 10);

        $this->assertSame(2025, $week->getYear());
        $this->assertSame(10, $week->getIndex());
        $this->assertSame('2025-W10', (string)$week);
    }

    public function testCreateInvalidWeekThrowsException(): void
    {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('Invalid week');

        HhpcWeek::create(2025, 53);
    }

    public function testIsXtra(): void
    {
        $regularWeek = HhpcWeek::create(2026, 52);
        $this->assertFalse($regularWeek->isXtra());

        $xtraWeek = HhpcWeek::create(2026, 53);
        $this->assertTrue($xtraWeek->isXtra());

        $shortYearEnd = HhpcWeek::create(2025, 52);
        $this->assertFalse($shortYearEnd->isXtra());
    }

    public function testNavigationNextAndPrevious(): void
    {
        $week = HhpcWeek::create(2025, 10);
        $next = $week->next();
        $this->assertSame(2025, $next->getYear());
        $this->assertSame(11, $next->getIndex());

        $prev = $week->previous();
        $this->assertSame(2025, $prev->getYear());
        $this->assertSame(9, $prev->getIndex());

        $endOfYear = HhpcWeek::create(2025, 52);
        $nextYear = $endOfYear->next();

        $this->assertSame(2026, $nextYear->getYear());
        $this->assertSame(1, $nextYear->getIndex());
    }

    public function testAddAndSubWeeks(): void
    {
        $start = HhpcWeek::create(2025, 1);

        $added = $start->addWeeks(10);
        $this->assertSame(11, $added->getIndex());

        $subbed = $start->subWeeks();
        $this->assertSame(2024, $subbed->getYear());
        $this->assertSame(52, $subbed->getIndex());
    }

    public function testComparisonMethods(): void
    {
        $w1 = HhpcWeek::create(2025, 10);
        $w2 = HhpcWeek::create(2025, 10);
        $w3 = HhpcWeek::create(2025, 11);
        $w4 = HhpcWeek::create(2026, 1);

        $this->assertNotSame($w1, $w2);
        $this->assertTrue($w1->equals($w2));
        $this->assertFalse($w1->equals($w3));

        $this->assertTrue($w1->isBefore($w3));
        $this->assertTrue($w1->isBefore($w4));
        $this->assertFalse($w3->isBefore($w1));

        $this->assertTrue($w3->isAfter($w1));
        $this->assertTrue($w4->isAfter($w1));
        $this->assertFalse($w1->isAfter($w3));
    }

    public function testDiffWeeks(): void
    {
        $w1 = HhpcWeek::create(2025, 52);
        $w2 = HhpcWeek::create(2026, 1);

        $this->assertSame(1, $w1->diffWeeks($w2));
        $this->assertSame(-1, $w2->diffWeeks($w1));
    }

    public function testRelatedObjectsCreation(): void
    {
        $week = HhpcWeek::create(2025, 1);

        $this->assertSame(2025, $week->getYearObject()->getIndex());

        $this->assertSame(1, $week->getQuarterObject()->getIndex());
        $this->assertSame(1, $week->getQuarter());

        $this->assertInstanceOf(HhpcDateInterface::class, $week->getStartDate());
        $this->assertInstanceOf(HhpcDateInterface::class, $week->getEndDate());
    }

    public function testGetDays(): void
    {
        $week = HhpcWeek::create(2025, 1);
        $days = $week->getDays();

        $this->assertCount(7, $days);
        $this->assertContainsOnlyInstancesOf(HhpcDateInterface::class, $days);
    }

    public function testJsonSerialize(): void
    {
        $week = HhpcWeek::create(2026, 53);
        $json = $week->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertSame(2026, $json['year']);
        $this->assertSame(53, $json['week']);
        $this->assertTrue($json['isXtra']);

        $this->assertArrayHasKey('startDate', $json);
        $this->assertArrayHasKey('endDate', $json);
    }
}
