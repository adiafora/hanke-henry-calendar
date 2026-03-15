<?php

declare(strict_types=1);

namespace Tests\Hhpc\Calendar;

use Adiafora\Hhpc\Config\HhpcConfig;
use Adiafora\Hhpc\Contracts\HhpcMonthInterface;
use Adiafora\Hhpc\Exception\InvalidDateException;
use Adiafora\Hhpc\HhpcYear;
use Exception;
use PHPUnit\Framework\TestCase;

class HhpcYearTest extends TestCase
{
    public function testIsCurrent(): void
    {
        HhpcConfig::setTestGregorianNow('2027-02-02');
        $year = HhpcYear::create(2026);
        $this->assertFalse($year->isCurrent());

        HhpcConfig::setTestGregorianNow('2026-02-28');
        $this->assertTrue($year->isCurrent());

        HhpcConfig::setTestGregorianNow();
    }

    public function testCreateValidYear(): void
    {
        $year = HhpcYear::create(2025);

        $this->assertSame(2025, $year->getIndex());
        $this->assertEquals('2025', (string)$year);
    }

    /**
     * @throws Exception
     */
    public function testCurrent(): void
    {
        // It's Saturday Xtra 6 2026
        HhpcConfig::setTestGregorianNow('2027-01-02');

        $year = HhpcYear::current();

        $this->assertSame(2026, $year->getIndex());
        $this->assertTrue($year->isLeap());
        $this->assertEquals('2026', (string)$year);

        HhpcConfig::setTestGregorianNow();
    }

    public function testCreateThrowsExceptionOnInvalidLowerBound(): void
    {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('The year must be between 1583 and 9999');

        HhpcYear::create(1582);
    }

    public function testCreateThrowsExceptionOnInvalidUpperBound(): void
    {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('The year must be between 1583 and 9999');

        HhpcYear::create(10000);
    }

    public function testNavigationMethodsReturnNewInstances(): void
    {
        $year2024 = HhpcYear::create(2024);

        $year2025 = $year2024->next();
        $this->assertNotSame($year2024, $year2025);
        $this->assertSame(2025, $year2025->getIndex());

        $year2023 = $year2024->previous();
        $this->assertNotSame($year2024, $year2023);
        $this->assertSame(2023, $year2023->getIndex());

        $year2026 = $year2024->addYears(2);
        $this->assertNotSame($year2024, $year2026);
        $this->assertSame(2026, $year2026->getIndex());

        $year2020 = $year2024->subYears(4);
        $this->assertNotSame($year2024, $year2020);
        $this->assertSame(2020, $year2020->getIndex());
    }

    public function testComparisonMethods(): void
    {
        $year2024 = HhpcYear::create(2024);
        $year2025 = HhpcYear::create(2025);
        $year2024Copy = HhpcYear::create(2024);

        $this->assertTrue($year2024->equals($year2024Copy));
        $this->assertFalse($year2024->equals($year2025));

        $this->assertTrue($year2024->isBefore($year2025));
        $this->assertFalse($year2025->isBefore($year2024));

        $this->assertTrue($year2025->isAfter($year2024));
        $this->assertFalse($year2024->isAfter($year2025));
    }

    public function testDelegatedMethodsWorkCorrectly(): void
    {
        $yearVal = 2026;
        $year = HhpcYear::create($yearVal);

        $this->assertTrue($year->isLeap());

        $this->assertSame(371, $year->getDaysInYear());

        $this->assertSame(53, $year->getWeeksInYear());
    }

    public function testGetMonthsReturnsArrayOfObjects(): void
    {
        $yearVal = 2024;
        $year = HhpcYear::create($yearVal);

        $months = $year->getMonths();

        $this->assertCount(12, $months);
        $this->assertContainsOnlyInstancesOf(HhpcMonthInterface::class, $months);

        $this->assertSame(1, $months[0]->getIndex());
        $this->assertSame(2, $months[1]->getIndex());
        $this->assertSame(3, $months[2]->getIndex());
        $this->assertSame(12, $months[11]->getIndex());
    }

    public function testJsonSerializeStructure(): void
    {
        $yearVal = 2024;
        $year = HhpcYear::create($yearVal);

        $json = $year->jsonSerialize();

        $expectedKeys = [
            'year',
            'daysCount',
            'weeksCount',
            'monthsCount',
            'startDate',
            'endDate',
            'isLeap',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $json);
        }

        $this->assertSame(2024, $json['year']);
        $this->assertSame(364, $json['daysCount']);
        $this->assertSame(52, $json['weeksCount']);
        $this->assertSame(12, $json['monthsCount']);
        $this->assertSame(false, $json['isLeap']);
        $this->assertSame('2024-01-01', $json['startDate']);
        $this->assertSame('2024-12-31', $json['endDate']);
        $this->assertIsString($json['startDate']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $json['startDate']);
        $this->assertIsString($json['endDate']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $json['endDate']);
    }
}
