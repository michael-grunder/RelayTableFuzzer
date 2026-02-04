<?php

declare(strict_types=1);

namespace Relay;

final class Table
{
    public static function get(string $key, string $namespace = ''): mixed
    {
        return null;
    }

    public static function set(string $key, mixed $value, mixed $expire = null, string $namespace = ''): void
    {
    }

    public static function exists(string $key, string $namespace = ''): bool
    {
        return false;
    }

    public static function delete(string $key, string $namespace = ''): bool
    {
        return false;
    }

    public static function ttl(string $key, string $namespace = ''): ?int
    {
        return null;
    }

    public static function count(string $namespace = ''): int
    {
        return 0;
    }

    public static function clear(string $namespace = ''): void
    {
    }

    /**
     * @return list<string>
     */
    public static function namespaces(): array
    {
        return [];
    }

    public static function clearAll(): void
    {
    }
}
