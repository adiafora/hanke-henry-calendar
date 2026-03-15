# Hanke-Henry Permanent Calendar (HHPC) for PHP

![Build Status](https://img.shields.io/github/actions/workflow/status/adiafora/hanke-henry-calendar/tests.yml?branch=main&style=flat-square)
![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-777bb4.svg?style=flat-square)
[![Stan Level](https://img.shields.io/badge/phpstan-level%209-brightgreen.svg?style=flat-square)](phpstan.neon)

A professional, immutable, and ISO 8601-compliant PHP library for working with the **Hanke-Henry Permanent Calendar**.

This library provides a robust engine to handle the unique structure of the HHPC, including the "Xtra" leap week (Mini-Month), fixed quarterly patterns, and seamless conversion between Gregorian and Hanke-Henry dates.

## ✨ Features

- **Immutable Architecture:** All date objects are immutable, ensuring thread safety and predictable behavior similar to `DateTimeImmutable`.
- **ISO 8601 Compliance:** Fully aligned with the ISO week date system. Every year starts on Monday, and the Xtra week handling respects the 53-week ISO cycle.
- **Xtra Week Support:** First-class support for the intercalary Xtra week (treated programmatically as Month 13).
- **Zero Dependencies:** Requires only native PHP extensions (`intl`).
- **Production Ready:** Fully typed (PHP 8.2+), tested, and analyzed with PHPStan (Level 9).

## 📦 Installation

```bash
composer require adiafora/hanke-henry-calendar
```

## 🚀 Quick Start

```php
use Adiafora\Hhpc\HhpcDate;

// Create a date from Gregorian
$date = HhpcDate::fromGregorian(new \DateTimeImmutable('2026-03-27'));

echo $date->format('Y-m-d'); 
// Output: 2026-03-29

echo $date->addDays(3); 
// 2026-04-01
```
### Handling the "Xtra" Week (Leap Year)

In the Hanke-Henry calendar, leap years (occurring every 5-6 years) include an extra 7-day week at the end of year. This library represents it as Month 13 for ease of calculation.

```php
use Adiafora\Hhpc\HhpcDate;

// 2026 is a Leap Year in HHPC
$endOfYear = HhpcDate::create(2026, 12, 31); // Last day of regular December

$xtraWeek = $endOfYear->addDays();

echo $xtraWeek->format('Y-m-d');
// Output: 2026-13-01 (1st day of the Xtra week)

echo $xtraWeek->isXtra(); 
// Output: true
```

## 🧩 Core Entities

The package provides a rich set of immutable objects representing different components of the Hanke-Henry calendar. All entities are located in the `Adiafora\Hhpc\` namespace.

* **`HhpcDate`** — The primary object for working with precise dates, times, formatting, and comparisons.
* **`HhpcWeek`** — Represents a 7-day week, fully compatible with the ISO week numbering system.
* **`HhpcMonth`** — Represents a calendar month, handling the fixed 30-day/31-day lengths and the special 13th "Xtra" month.
* **`HhpcQuarter`** — Represents a 91-day fixed quarter (consisting of two 30-day months and one 31-day month).
* **`HhpcYear`** — Represents a specific calendar year and provides helpers to check its leap status.


## 📐 Architecture & ISO 8601 Compliance

The Hanke-Henry Permanent Calendar is designed to preserve the 7-day week cycle, making it uniquely compatible with the ISO 8601 week numbering system.

This library strictly adheres to these standards:
- **Week Start:** Every week, month, and year strictly begins on a Monday.

- **Year Definition:** The HHPC year matches the ISO week-numbering year.

- **Synchronization:** The library uses the "Thursday rule" (ISO 8601) to synchronize with the Gregorian calendar, ensuring that the 13th "Xtra" week perfectly aligns with the ISO 53rd week.

This means you can safely use this library in logistics, financial planning, and systems that rely on ISO week numbers.

## 🛠 Development

We use Docker and a set of quality assurance tools to maintain high standards.

```bash
# Build docker container
make build

# Install the dependencies
make composer

# Run CI checks
make ci
```

## 📄 License

The MIT License (MIT). Please see [License File](https://github.com/adiafora/hanke-henry-calendar/blob/main/LICENSE) for more information.
