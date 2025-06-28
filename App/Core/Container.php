<?php

namespace App\Core;

class Container
{
    private static array $instances = [];

    public static function set(string $key, $value): void
    {
        self::$instances[$key] = $value;
    }

    public static function get(string $key)
    {
        if (!array_key_exists($key, self::$instances)) {
            throw new \Exception("Key {$key} not found in container.");
        }
        return self::$instances[$key];
    }
}