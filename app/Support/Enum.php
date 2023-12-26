<?php

namespace App\Support;

use ReflectionClass;

abstract class Enum
{
    public static function list(): array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }

    public static function has($value): bool
    {
        return in_array($value, static::list());
    }
}
