<?php

declare(strict_types=1);

namespace Cinter;

use Cinter\Runtime\Int64;
use Cinter\Runtime\Str;
use Cinter\Runtime\Value;
use Exception;

function c_printf(Str $format, Value ...$args): Value {
    return new Int64(\printf($format->value, ...array_map(fn (Value $value) => $value->getValue(), $args)));
}

function todo(): never {
    throw new Exception('Unimplemented.');
}