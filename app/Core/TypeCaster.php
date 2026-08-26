<?php

declare(strict_types=1);

namespace App\Core;

use BackedEnum;

final class TypeCaster
{
    public static function int(mixed $value, string $key): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int) $value;
        }

        throw new \InvalidArgumentException("Expected integer for {$key}, got " . gettype($value));
    }

    public static function nullableInt(mixed $value, string $key): ?int
    {
        if ($value === null) {
            return null;
        }

        return self::int($value, $key);
    }

    public static function float(mixed $value, string $key): float
    {
        if (is_float($value) || is_int($value) || (is_string($value) && is_numeric($value))) {
            return (float) $value;
        }

        throw new \InvalidArgumentException("Expected float for {$key}, got " . gettype($value));
    }

    public static function nullableFloat(mixed $value, string $key): ?float
    {
        if ($value === null) {
            return null;
        }

        return self::float($value, $key);
    }

    public static function string(mixed $value, string $key): string
    {
        if (is_string($value) || is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw new \InvalidArgumentException("Expected string for {$key}, got " . gettype($value));
    }

    public static function nullableString(mixed $value, string $key): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::string($value, $key);
    }

    /**
     * @param class-string<BackedEnum> $enumClass
     */
    public static function enum(mixed $value, string $key, string $enumClass): BackedEnum
    {
        $case = $enumClass::tryFrom((string) self::string($value, $key));

        if ($case === null) {
            throw new \InvalidArgumentException("Expected valid {$enumClass} value for {$key}");
        }

        return $case;
    }
}
