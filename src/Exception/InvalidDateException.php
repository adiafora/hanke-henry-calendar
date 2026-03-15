<?php

declare(strict_types=1);

namespace Adiafora\Hhpc\Exception;

use Adiafora\Hhpc\Contracts\HhpcExceptionInterface;

class InvalidDateException extends \InvalidArgumentException implements HhpcExceptionInterface
{
}
